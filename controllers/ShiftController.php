<?php
/**
 * ShiftController - Manajemen Tutup Shift & Rekapitulasi Kasir
 * Veloce POS - PT Taman Wisata Borobudur
 * Dilengkapi Shift Lifecycle State Machine & Anti-Double Entry Prevention
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
     * Mengambil statistik shift berjalan & mendeteksi status siklus shift
     */
    public function getCurrentShiftStats($kasirNama, $posAktif = '', $outletId = 1) {
        $kasirNamaSafe = $this->conn->real_escape_string($kasirNama);
        $posAktifSafe  = $this->conn->real_escape_string($posAktif);

        // 1. Cari penutupan shift terakhir hari ini untuk kasir ini
        $lastClosedShift = null;
        $qLast = $this->conn->query("
            SELECT * 
            FROM `cashier_shifts` 
            WHERE `kasir_nama` = '$kasirNamaSafe' AND DATE(`closing_time`) = CURDATE()
            ORDER BY `id` DESC LIMIT 1
        ");
        if ($qLast && $qLast->num_rows > 0) {
            $lastClosedShift = $qLast->fetch_assoc();
        }

        // 2. Cari transaksi berjalan hari ini yang BELUM TERIKAT ke shift manapun (shift_id IS NULL atau 0)
        $wherePending = "WHERE `petugas` = '$kasirNamaSafe' AND `tanggal` = CURDATE() AND (`shift_id` IS NULL OR `shift_id` = 0)";
        if (!empty($posAktifSafe)) {
            $wherePending .= " AND `pos_aktif` = '$posAktifSafe'";
        }

        $sqlPending = "
            SELECT 
                COUNT(*) as nota_count,
                COALESCE(SUM(total_harga), 0) as grand_total,
                COALESCE(SUM(CASE WHEN LOWER(metode) LIKE '%cash%' OR LOWER(metode) LIKE '%tunai%' THEN total_harga ELSE 0 END), 0) as cash_total,
                COALESCE(SUM(CASE WHEN LOWER(metode) LIKE '%qris%' THEN total_harga ELSE 0 END), 0) as qris_total,
                MIN(created_at) as first_tx_time,
                MAX(created_at) as last_tx_time
            FROM `transaksi`
            $wherePending
        ";

        $res = $this->conn->query($sqlPending);
        $pendingStats = $res ? $res->fetch_assoc() : [
            'nota_count' => 0, 'grand_total' => 0, 'cash_total' => 0, 'qris_total' => 0,
            'first_tx_time' => null, 'last_tx_time' => null
        ];

        $pendingNotaCount = intval($pendingStats['nota_count']);

        // 3. Evaluasi State Machine Siklus Shift
        if ($pendingNotaCount === 0 && $lastClosedShift !== null) {
            // KONDISI: Shift sebelumnya sudah ditutup & belum ada transaksi baru
            // Berikan data shift terakhir agar kasir bisa melihat/mengoreksi atau cetak ulang
            return [
                'shift_state'       => 'ALREADY_CLOSED',
                'is_already_closed' => true,
                'can_update'        => true,
                'shift_id'          => intval($lastClosedShift['id']),
                'shift_number'      => $lastClosedShift['shift_number'],
                'kasir_nama'        => $kasirNama,
                'pos_aktif'         => $lastClosedShift['pos_aktif'],
                'opening_time'      => $lastClosedShift['opening_time'],
                'closing_time'      => $lastClosedShift['closing_time'],
                'current_time'      => date('Y-m-d H:i:s'),
                'nota_count'        => intval($lastClosedShift['transaction_count']),
                'cash_sales'        => intval($lastClosedShift['cash_sales']),
                'qris_sales'        => intval($lastClosedShift['qris_sales']),
                'total_sales'       => intval($lastClosedShift['total_sales']),
                'opening_cash'      => intval($lastClosedShift['opening_cash']),
                'actual_cash'       => intval($lastClosedShift['actual_cash']),
                'expected_cash'     => intval($lastClosedShift['opening_cash']) + intval($lastClosedShift['cash_sales']),
                'difference'        => intval($lastClosedShift['difference']),
                'notes'             => $lastClosedShift['notes'] ?? '',
                'default_float'     => intval($lastClosedShift['opening_cash'])
            ];
        }

        // KONDISI: Ada transaksi aktif yang siap ditutup (atau shift baru tanpa closing sebelumnya)
        $openingTime = $pendingStats['first_tx_time'] ?: ($lastClosedShift ? $lastClosedShift['closing_time'] : date('Y-m-d 08:00:00'));

        return [
            'shift_state'       => ($pendingNotaCount > 0) ? 'ACTIVE_PENDING' : 'NEW_EMPTY_SHIFT',
            'is_already_closed' => false,
            'can_update'        => false,
            'shift_id'          => null,
            'shift_number'      => null,
            'kasir_nama'        => $kasirNama,
            'pos_aktif'         => $posAktif ?: 'Kasir Utama',
            'opening_time'      => $openingTime,
            'current_time'      => date('Y-m-d H:i:s'),
            'nota_count'        => $pendingNotaCount,
            'cash_sales'        => intval($pendingStats['cash_total']),
            'qris_sales'        => intval($pendingStats['qris_total']),
            'total_sales'       => intval($pendingStats['grand_total']),
            'opening_cash'      => 100000,
            'actual_cash'       => 100000 + intval($pendingStats['cash_total']),
            'expected_cash'     => 100000 + intval($pendingStats['cash_total']),
            'difference'        => 0,
            'notes'             => '',
            'default_float'     => 100000
        ];
    }

    /**
     * Memproses penutupan shift kasir baru (Dengan proteksi anti-duplikasi & pengikatan shift_id)
     */
    public function closeShift($data) {
        $kasirNama    = trim($data['kasir_nama'] ?? '');
        $posAktif     = trim($data['pos_aktif'] ?? 'Kasir Utama');
        $outletId     = intval($data['outlet_id'] ?? 1);
        $openingCash  = intval($data['opening_cash'] ?? 100000);
        $actualCash   = intval($data['actual_cash'] ?? 100000);
        $notes        = trim($data['notes'] ?? '');

        if (empty($kasirNama)) {
            return ['status' => 'error', 'message' => 'Nama petugas kasir tidak boleh kosong.'];
        }

        // Cek apakah ada shift_id yang disertakan untuk update, atau jika shift sudah ditutup
        $currentStats = $this->getCurrentShiftStats($kasirNama, $posAktif, $outletId);
        if ($currentStats['shift_state'] === 'ALREADY_CLOSED') {
            // Pengguna menekan simpan saat shift sudah ditutup: alihkan ke UPDATE untuk mencegah data double
            $targetShiftId = $currentStats['shift_id'];
            return $this->updateClosedShift([
                'shift_id'     => $targetShiftId,
                'opening_cash' => $openingCash,
                'actual_cash'  => $actualCash,
                'notes'        => $notes
            ]);
        }

        // Ambil data transaksi pending
        $kasirNamaSafe = $this->conn->real_escape_string($kasirNama);
        $posAktifSafe  = $this->conn->real_escape_string($posAktif);
        $openingTime   = $currentStats['opening_time'];
        $closingTime   = date('Y-m-d H:i:s');
        $cashSales     = $currentStats['cash_sales'];
        $qrisSales     = $currentStats['qris_sales'];
        $totalSales    = $currentStats['total_sales'];
        $txCount       = $currentStats['nota_count'];

        // Perhitungan selisih kas
        $expectedCash  = $openingCash + $cashSales;
        $difference    = $actualCash - $expectedCash;

        // Nomor referensi shift unik
        $shiftNumber = 'SFT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Mulai Database Transaction untuk integritas audit
        $this->conn->begin_transaction();

        try {
            // 1. Simpan baris baru ke cashier_shifts
            $stmt = $this->conn->prepare("
                INSERT INTO `cashier_shifts` 
                (`shift_number`, `kasir_nama`, `outlet_id`, `pos_aktif`, `opening_time`, `closing_time`, `opening_cash`, `cash_sales`, `qris_sales`, `total_sales`, `transaction_count`, `actual_cash`, `difference`, `notes`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'closed')
            ");

            if (!$stmt) {
                throw new Exception('Query prepare error: ' . $this->conn->error);
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
                throw new Exception('Gagal menyimpan shift: ' . $stmt->error);
            }

            $newShiftId = $stmt->insert_id;
            $stmt->close();

            // 2. Ikat seluruh transaksi kasir yang belum terikat ke shift_id baru ini
            $updateTxSql = "
                UPDATE `transaksi`
                SET `shift_id` = $newShiftId
                WHERE `petugas` = '$kasirNamaSafe' 
                  AND `tanggal` = CURDATE() 
                  AND (`shift_id` IS NULL OR `shift_id` = 0)
            ";
            if (!empty($posAktifSafe)) {
                $updateTxSql .= " AND `pos_aktif` = '$posAktifSafe'";
            }
            $this->conn->query($updateTxSql);

            // Komit transaksi
            $this->conn->commit();

            // Notifikasi ke admin
            $this->notifyAdminShiftClosed($shiftNumber, $kasirNama, $totalSales, $difference);

            return [
                'status'        => 'success',
                'action_type'   => 'created',
                'message'       => 'Shift berhasil ditutup dan transaksi resmi dikunci.',
                'shift_id'      => $newShiftId,
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

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Memperbarui / Mengoreksi Data Shift Terakhir yang Sudah Ditutup (Anti-Duplicate UPDATE)
     */
    public function updateClosedShift($data) {
        $shiftId     = intval($data['shift_id'] ?? 0);
        $actualCash  = intval($data['actual_cash'] ?? 0);
        $openingCash = isset($data['opening_cash']) ? intval($data['opening_cash']) : null;
        $notes       = trim($data['notes'] ?? '');

        if ($shiftId <= 0) {
            return ['status' => 'error', 'message' => 'ID shift tidak valid untuk pembaruan.'];
        }

        // Ambil data shift yang tersimpan
        $q = $this->conn->query("SELECT * FROM `cashier_shifts` WHERE `id` = $shiftId LIMIT 1");
        if (!$q || $q->num_rows === 0) {
            return ['status' => 'error', 'message' => 'Data shift tidak ditemukan di sistem.'];
        }

        $shift = $q->fetch_assoc();
        $finalOpeningCash = ($openingCash !== null) ? $openingCash : intval($shift['opening_cash']);
        $cashSales        = intval($shift['cash_sales']);
        
        // Hitung ulang selisih kas fisik
        $expectedCash = $finalOpeningCash + $cashSales;
        $difference   = $actualCash - $expectedCash;

        // Eksekusi UPDATE (Bukan INSERT sehingga tidak ada baris ganda)
        $stmt = $this->conn->prepare("
            UPDATE `cashier_shifts` 
            SET `opening_cash` = ?, `actual_cash` = ?, `difference` = ?, `notes` = ? 
            WHERE `id` = ?
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Query error: ' . $this->conn->error];
        }

        $stmt->bind_param("iiisi", $finalOpeningCash, $actualCash, $difference, $notes, $shiftId);
        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Gagal memperbarui shift: ' . $stmt->error];
        }
        $stmt->close();

        // Kirim notifikasi pembaruan ke admin
        $this->notifyAdminShiftUpdated($shift['shift_number'], $shift['kasir_nama'], $difference);

        return [
            'status'        => 'success',
            'action_type'   => 'updated',
            'message'       => 'Data penutupan shift berhasil diperbarui (tidak ada duplikasi data).',
            'shift_id'      => $shiftId,
            'shift_number'  => $shift['shift_number'],
            'kasir_nama'    => $shift['kasir_nama'],
            'pos_aktif'     => $shift['pos_aktif'],
            'opening_time'  => $shift['opening_time'],
            'closing_time'  => $shift['closing_time'],
            'opening_cash'  => $finalOpeningCash,
            'cash_sales'    => $cashSales,
            'qris_sales'    => intval($shift['qris_sales']),
            'total_sales'   => intval($shift['total_sales']),
            'tx_count'      => intval($shift['transaction_count']),
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

    /**
     * Helper notifikasi pembaruan / koreksi shift
     */
    private function notifyAdminShiftUpdated($shiftNumber, $kasirNama, $difference) {
        $diffText = ($difference === 0) 
            ? "Kas Pas (Rp 0)" 
            : (($difference > 0) ? "Lebih Rp " . number_format($difference, 0, ',', '.') : "Kurang Rp " . number_format(abs($difference), 0, ',', '.'));

        $title = "Koreksi Shift Kasir: $kasirNama ($shiftNumber)";
        $message = "Kasir $kasirNama memperbarui rekonsiliasi fisik shift. Status selisih kas terbaru: $diffText.";

        $stmt = $this->conn->prepare("
            INSERT INTO `admin_notifications` (`category`, `type`, `title`, `message`, `link_url`, `reference_id`, `is_read`, `created_at`)
            VALUES ('transaksi_baru', 'info', ?, ?, 'dashboard.php?page=kasir', ?, 0, NOW())
        ");
        if ($stmt) {
            $stmt->bind_param("sss", $title, $message, $shiftNumber);
            $stmt->execute();
            $stmt->close();
        }
    }
}
