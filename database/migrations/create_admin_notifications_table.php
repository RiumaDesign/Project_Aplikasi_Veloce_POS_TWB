<?php
/**
 * Migration: Create admin_notifications table
 * Veloce POS - PT Taman Wisata Borobudur
 */

require_once dirname(__DIR__, 2) . '/config/database.php';

echo "=== MEMULAI MIGRASI TABEL NOTIFIKASI ADMIN ===\n";

$sql = "
CREATE TABLE IF NOT EXISTS `admin_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category` ENUM('stok_kritis', 'transaksi_baru', 'retur_barang', 'mutasi_stok', 'sistem') NOT NULL DEFAULT 'sistem',
    `type` ENUM('danger', 'warning', 'info', 'success') NOT NULL DEFAULT 'info',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link_url` VARCHAR(255) DEFAULT NULL,
    `reference_id` VARCHAR(100) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_category` (`category`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "• Berhasil membuat tabel `admin_notifications`.\n";
} else {
    echo "• Gagal membuat tabel: " . $conn->error . "\n";
    exit(1);
}

// Tambahkan beberapa notifikasi awal sistem jika masih kosong
$cek = $conn->query("SELECT COUNT(*) as tot FROM `admin_notifications`");
$row = $cek->fetch_assoc();
if (intval($row['tot']) === 0) {
    $now = date('Y-m-d H:i:s');
    $initial_notifs = [
        [
            'category' => 'sistem',
            'type' => 'success',
            'title' => 'Pusat Notifikasi Aktif',
            'message' => 'Sistem monitoring dan peringatan otomatis Admin Khusus PT TWB telah berhasil diaktifkan.',
            'link_url' => 'dashboard.php?page=analytics'
        ],
        [
            'category' => 'stok_kritis',
            'type' => 'warning',
            'title' => 'Monitoring Stok Multi-Outlet Aktif',
            'message' => 'Peringatan otomatis akan menyala jika ada produk dengan stok menipis (<= 10 pcs) di seluruh outlet atau vending machine.',
            'link_url' => 'dashboard.php?page=stok'
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO `admin_notifications` (`category`, `type`, `title`, `message`, `link_url`, `is_read`, `created_at`) VALUES (?, ?, ?, ?, ?, 0, ?)");
    foreach ($initial_notifs as $n) {
        $stmt->bind_param("ssssss", $n['category'], $n['type'], $n['title'], $n['message'], $n['link_url'], $now);
        $stmt->execute();
    }
    echo "• Berhasil mengisi 2 notifikasi awal sistem.\n";
}

echo "=== MIGRASI SELESAI DENGAN SUKSES ===\n";
