<?php
/**
 * ShiftController - Manajemen Tutup Shift & Rekapitulasi Kasir
 * Veloce POS - PT Taman Wisata Borobudur
 */

require_once __DIR__ . '/../config/database.php';

class ShiftController {
    private $conn;

    public function __construct($dbConn = null) {
        if ($dbConn) {
            $this->conn = $dbConn;
        } else {
            global $conn;
            $this->conn = $conn;
        }
    }

    /**
     * Mengambil statistik shift berjalan saat ini untuk petugas kasir
     */
    public function getCurrentShiftStats($kasirNama, $posAktif = '', $outletId = 1) {
        $kasirNamaSafe = $this->conn->real_escape_string($kasirNama);
        $posAktifSafe  = $this->conn->real_escape_string($posAktif);

        // Cari waktu penutupan shift terakhir hari ini untuk kasir ini
        $lastClosing = null;
        $qLast = $this->conn->query("
            SELECT closing_time 
            FROM `cashier_shifts` 
            WHERE `kasir_nama` = '$kasirNamaSafe' AND DATE(`closing_time`) = CURDATE()
            ORDER BY `id` DESC LIMIT 1
        ");
        if ($qLast && $qLast->num_rows > 0) {
            $lastClosing = $qLast->fetch_assoc()['closing_time'];
        }

        // Query transaksi sejak buka shift / sejak closing terakhir
        $whereClause = "WHERE `petugas` = '$kasirNamaSafe' AND `tanggal` = CURDATE()";
        if ($lastClosing) {
            $whereClause .= " AND `created_at` > '$lastClosing'";
        }
        if (!empty($posAktifSafe)) {
            $whereClause .= " AND `pos_aktif` = '$posAktifSafe'";
        }

        $sql = "
            SELECT 
                COUNT(*) as nota_count,
                COALESCE(SUM(total_harga), 0) as grand_total,
                COALESCE(SUM(CASE WHEN LOWER(metode) LIKE '%cash%' OR LOWER(metode) LIKE '%tunai%' THEN total_harga ELSE 0 END), 0) as cash_total,
                COALESCE(SUM(CASE WHEN LOWER(metode) LIKE '%qris%' THEN total_harga ELSE 0 END), 0) as qris_total,
                MIN(created_at) as first_tx_time,
                MAX(created_at) as last_tx_time
            FROM `transaksi`
            $whereClause
        ";

        $res = $this->conn->query($sql);
        $stats = $res ? $res->fetch_assoc() : [
            'nota_count' => 0,
            'grand_total' => 0,
            'cash_total' => 0,
            'qris_total' => 0,
            'first_tx_time' => null,
            'last_tx_time' => null
        ];

        // Format opening time (jika belum ada transaksi, gunakan waktu closing terakhir atau awal hari)
        $openingTime = $stats['first_tx_time'] ?: ($lastClosing ?: date('Y-m-d 08:00:00'));

        return [
            'kasir_nama'     => $kasirNama,
            'pos_aktif'      => $posAktif ?: 'Kasir Utama',
            'opening_time'   => $openingTime,
            'current_time'   => date('Y-m-d H:i:s'),
            'nota_count'     => intval($stats['nota_count']),
            'cash_sales'     => intval($stats['cash_total']),
            'qris_sales'     => intval($stats['qris_total']),
            'total_sales'    => intval($stats['grand_total']),
            'default_float'  => 100000 // Modal awal standar TWB
        ];
    }

    /**
     * Memproses penutupan shift kasir
     */
    public function closeShift($data) {
        $kasirNama    = trim($data['kasir_nama'] ?? '');
        $posAktif     = trim($data['pos_aktif'] ?? 'Kasir Utama');
        $outletId     = intval($data['outlet_id'] ?? 1);
        $openingTime  = trim($data['opening_time'] ?? date('Y-m-d 08:00:00'));
        $closingTime  = date('Y-m-d H:i:s');
        $openingCash  = intval($data['opening_cash'] ?? 0);
        $actualCash   = intval($data['actual_cash'] ?? 0);
        $notes        = trim($data['notes'] ?? '');

        if (empty($kasirNama)) {
            return ['status' => 'error', 'message' => 'Nama petugas kasir tidak boleh kosong.'];
        }

        // Ambil data transaksi riil dari database untuk validasi anti-manipulasi
        $stats = $this->getCurrentShiftStats($kasirNama, $posAktif, $outletId);
        $cashSales   = $stats['cash_sales'];
        $qrisSales   = $stats['qris_sales'];
        $totalSales  = $stats['total_sales'];
        $txCount     = $stats['nota_count'];

        // Perhitungan selisih kas
        $expectedCash = $openingCash + $cashSales;
        $difference   = $actualCash - $expectedCash;

        // Buat nomor referensi shift unik
        $shiftNumber = 'SFT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Simpan ke database
        $stmt = $this->conn->prepare("
            INSERT INTO `cashier_shifts` 
            (`shift_number`, `kasir_nama`, `outlet_id`, `pos_aktif`, `opening_time`, `closing_time`, `opening_cash`, `cash_sales`, `qris_sales`, `total_sales`, `transaction_count`, `actual_cash`, `difference`, `notes`, `status`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'closed')
        ");
        
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Query error: ' . $this->conn->error];
        }

        $stmt->bind_param(
            "ssisssiiiiiiis",
            $shiftNumber,
            $kasirNama,
            $outletId,
            $posAktif,
            $openingTime,
            $closingTime,
            $openingCash,
            $cashSales,
            $qrisSales,
            $totalSales,
            $txCount,
            $actualCash,
            $difference,
            $notes
        );

        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Gagal menyimpan penutupan shift: ' . $stmt->error];
        }

        $insertedId = $stmt->insert_id;
        $stmt->close();

        // Rekam notifikasi otomatis ke admin
        $this->notifyAdminShiftClosed($shiftNumber, $kasirNama, $totalSales, $difference);

        return [
            'status'        => 'success',
            'message'       => 'Shift berhasil ditutup dan direkapitulasi secara resmi.',
            'shift_id'      => $insertedId,
            'shift_number'  => $shiftNumber,
            'kasir_nama'    => $kasirNama,
            'pos_aktif'     => $posAktif,
            'opening_time'  => $openingTime,
            'closing_time'  => $closingTime,
            'opening_cash'  => $openingCash,
            'cash_sales'    => $cashSales,
            'qris_sales'    => $qrisSales,
            'total_sales'   => $totalSales,
            'tx_count'      => $txCount,
            'expected_cash' => $expectedCash,
            'actual_cash'   => $actualCash,
            'difference'    => $difference,
            'notes'         => $notes
        ];
    }

    /**
     * Ambil riwayat penutupan shift untuk dashboard admin
     */
    public function getShiftHistory($limit = 50, $filterDate = null, $filterKasir = null) {
        $where = [];
        if (!empty($filterDate)) {
            $safeDate = $this->conn->real_escape_string($filterDate);
            $where[] = "DATE(cs.closing_time) = '$safeDate'";
        }
        if (!empty($filterKasir)) {
            $safeKasir = $this->conn->real_escape_string($filterKasir);
            $where[] = "cs.kasir_nama = '$safeKasir'";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $limit = intval($limit);

        $sql = "
            SELECT cs.*, l.name as outlet_name 
            FROM `cashier_shifts` cs
            LEFT JOIN `locations` l ON cs.outlet_id = l.id
            $whereClause
            ORDER BY cs.id DESC
            LIMIT $limit
        ";

        $res = $this->conn->query($sql);
        $list = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $list[] = $row;
            }
        }
        return $list;
    }

    /**
     * Helper notifikasi otomatis saat closing shift
     */
    private function notifyAdminShiftClosed($shiftNumber, $kasirNama, $totalSales, $difference) {
        $diffText = ($difference === 0) 
            ? "Kas Pas / Seimbang (Rp 0)" 
            : (($difference > 0) ? "Lebih Rp " . number_format($difference, 0, ',', '.') : "Kurang Rp " . number_format(abs($difference), 0, ',', '.'));

        $type = ($difference === 0) ? 'success' : 'warning';
        $title = "Closing Shift Kasir: $kasirNama ($shiftNumber)";
        $message = "Shift kasir $kasirNama resmi ditutup. Omzet: Rp " . number_format($totalSales, 0, ',', '.') . " ($diffText).";

        $stmt = $this->conn->prepare("
            INSERT INTO `admin_notifications` (`category`, `type`, `title`, `message`, `link_url`, `reference_id`, `is_read`, `created_at`)
            VALUES ('transaksi_baru', ?, ?, ?, 'dashboard.php?page=kasir', ?, 0, NOW())
        ");
        if ($stmt) {
            $stmt->bind_param("ssss", $type, $title, $message, $shiftNumber);
            $stmt->execute();
            $stmt->close();
        }
    }
}
