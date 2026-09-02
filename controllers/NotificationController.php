<?php
/**
 * NotificationController - Pusat Pengelolaan Notifikasi & Sistem Peringatan Real-time
 * Veloce POS - PT Taman Wisata Borobudur
 */

class NotificationController {
    private $conn;

    public function __construct($dbConnection = null) {
        if ($dbConnection) {
            $this->conn = $dbConnection;
        } else {
            global $conn;
            $this->conn = $conn;
        }
    }

    /**
     * Ambil daftar notifikasi dengan filter kategori dan limit
     */
    public function getNotifications($limit = 30, $category = null, $unreadOnly = false) {
        $conditions = ["1=1"];
        if (!empty($category) && $category !== 'all') {
            $cat = $this->conn->real_escape_string($category);
            $conditions[] = "`category` = '$cat'";
        }
        if ($unreadOnly) {
            $conditions[] = "`is_read` = 0";
        }
        $whereSql = implode(" AND ", $conditions);
        $limitVal = intval($limit);

        $sql = "
            SELECT * FROM `admin_notifications`
            WHERE $whereSql
            ORDER BY `is_read` ASC, `created_at` DESC, `id` DESC
            LIMIT $limitVal
        ";

        $res = $this->conn->query($sql);
        $notifs = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['time_ago'] = $this->timeAgo($row['created_at']);
                $notifs[] = $row;
            }
        }
        return $notifs;
    }

    /**
     * Ambil total notifikasi yang belum dibaca (unread count)
     */
    public function getUnreadCount() {
        $sql = "SELECT COUNT(*) as unread FROM `admin_notifications` WHERE `is_read` = 0";
        $res = $this->conn->query($sql);
        if ($res) {
            $row = $res->fetch_assoc();
            return intval($row['unread'] ?? 0);
        }
        return 0;
    }

    /**
     * Tandai satu notifikasi telah dibaca
     */
    public function markAsRead($id) {
        $id = intval($id);
        if ($id <= 0) return false;
        $stmt = $this->conn->prepare("UPDATE `admin_notifications` SET `is_read` = 1 WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Tandai semua notifikasi telah dibaca
     */
    public function markAllAsRead() {
        return $this->conn->query("UPDATE `admin_notifications` SET `is_read` = 1 WHERE `is_read` = 0");
    }

    /**
     * Tambah notifikasi baru dengan pencegahan duplikasi dalam 6 jam
     */
    public function createNotification($category, $type, $title, $message, $link_url = null, $reference_id = null) {
        if (!empty($reference_id)) {
            $refEsc = $this->conn->real_escape_string($reference_id);
            // Cek apakah ada notifikasi identik yang belum dibaca atau dibuat dalam 6 jam terakhir
            $cek = $this->conn->query("
                SELECT `id` FROM `admin_notifications` 
                WHERE `reference_id` = '$refEsc' 
                  AND (`is_read` = 0 OR `created_at` >= NOW() - INTERVAL 6 HOUR)
                LIMIT 1
            ");
            if ($cek && $cek->num_rows > 0) {
                return false; // Skip duplicate notification
            }
        }

        $stmt = $this->conn->prepare("
            INSERT INTO `admin_notifications` (`category`, `type`, `title`, `message`, `link_url`, `reference_id`, `is_read`, `created_at`) 
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->bind_param("ssssss", $category, $type, $title, $message, $link_url, $reference_id);
        return $stmt->execute();
    }

    /**
     * Pindai dan sinkronkan peringatan operasional otomatis:
     * 1. Stok Menipis/Kritis di seluruh lokasi (<= 10 pcs)
     * 2. Retur Karantina belum ditindaklanjuti
     */
    public function syncLiveAlerts() {
        $createdCount = 0;

        // 1. Pindai Stok Kritis per Produk di Seluruh Lokasi
        $sql_stok = "
            SELECT sl.product_id, sl.location_id, sl.quantity as stock, p.nama as nama_produk, l.name as nama_lokasi, l.type as tipe_lokasi
            FROM `stok_lokasi` sl
            JOIN `produk` p ON sl.product_id = p.id
            JOIN `locations` l ON sl.location_id = l.id
            WHERE sl.quantity <= 10
            ORDER BY sl.quantity ASC
            LIMIT 15
        ";
        $res_stok = $this->conn->query($sql_stok);
        if ($res_stok && $res_stok->num_rows > 0) {
            while ($stok = $res_stok->fetch_assoc()) {
                $pid = $stok['product_id'];
                $lid = $stok['location_id'];
                $qty = intval($stok['stock']);
                $namaProduk = $stok['nama_produk'];
                $namaLokasi = $stok['nama_lokasi'];
                $refId = "STOCK_LOW_{$pid}_{$lid}";

                if ($qty <= 0) {
                    $type = 'danger';
                    $title = "⚠️ Stok Habis: {$namaProduk}";
                    $msg = "Stok {$namaProduk} di {$namaLokasi} telah HABIS (0 pcs). Segera lakukan mutasi Delivery Order (DO) dari Gudang!";
                } elseif ($qty <= 3) {
                    $type = 'danger';
                    $title = "🔴 Stok Kritis: {$namaProduk}";
                    $msg = "Sisa stok {$namaProduk} di {$namaLokasi} tersisa {$qty} pcs! Perlu pasokan segera.";
                } else {
                    $type = 'warning';
                    $title = "⚡ Stok Menipis: {$namaProduk}";
                    $msg = "Sisa stok {$namaProduk} di {$namaLokasi} tersisa {$qty} pcs (Ambang batas aman: 10 pcs).";
                }

                $link = "dashboard.php?page=stok&filter_produk={$pid}&filter_lokasi={$lid}";
                if ($this->createNotification('stok_kritis', $type, $title, $msg, $link, $refId)) {
                    $createdCount++;
                }
            }
        }

        // 2. Pindai Retur Barang Baru
        $sql_retur = "
            SELECT r.id, r.return_number, r.return_type, r.qty, p.nama as nama_produk, r.created_at
            FROM `returns` r
            JOIN `produk` p ON r.product_id = p.id
            WHERE r.created_at >= NOW() - INTERVAL 48 HOUR
            ORDER BY r.created_at DESC
            LIMIT 5
        ";
        $res_retur = $this->conn->query($sql_retur);
        if ($res_retur && $res_retur->num_rows > 0) {
            while ($ret = $res_retur->fetch_assoc()) {
                $retId = $ret['id'];
                $refId = "RETURN_ALERT_{$retId}";
                $alasan = ucwords(str_replace('_', ' ', $ret['return_type'] ?? 'Rusak'));
                $title = "📦 Laporan Retur: {$ret['nama_produk']}";
                $msg = "Terdapat {$ret['qty']} pcs {$ret['nama_produk']} dilaporkan ({$alasan}). No: {$ret['return_number']}.";
                $link = "dashboard.php?page=retur";
                if ($this->createNotification('retur_barang', 'warning', $title, $msg, $link, $refId)) {
                    $createdCount++;
                }
            }
        }

        return $createdCount;
    }

    /**
     * Konversi waktu SQL ke format Time Ago Bahasa Indonesia yang ramah
     */
    private function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Baru saja';
        } elseif ($diff < 3600) {
            $mins = max(1, floor($diff / 60));
            return $mins . ' mnt lalu';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' jam lalu';
        } elseif ($diff < 172800) {
            return 'Kemarin, ' . date('H:i', $timestamp);
        } else {
            return date('d M Y, H:i', $timestamp);
        }
    }
}
