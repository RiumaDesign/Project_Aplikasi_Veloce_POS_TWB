<?php
/**
 * Migration: Add shift_id to transaksi table
 * Veloce POS - PT Taman Wisata Borobudur
 */

require_once dirname(__DIR__, 2) . '/config/database.php';

echo "=== MEMULAI MIGRASI SHIFT_ID KE TABEL TRANSAKSI ===\n";

// Cek apakah kolom shift_id sudah ada di tabel transaksi
$checkCol = $conn->query("SHOW COLUMNS FROM `transaksi` LIKE 'shift_id'");
if ($checkCol && $checkCol->num_rows === 0) {
    $alterSql = "ALTER TABLE `transaksi` ADD COLUMN `shift_id` INT NULL DEFAULT NULL AFTER `pos_aktif`, ADD INDEX `idx_shift_id` (`shift_id`)";
    if ($conn->query($alterSql) === TRUE) {
        echo "• Berhasil menambahkan kolom `shift_id` ke tabel `transaksi`.\n";
    } else {
        echo "• Gagal menambahkan kolom `shift_id`: " . $conn->error . "\n";
        exit(1);
    }
} else {
    echo "• Kolom `shift_id` sudah ada di tabel `transaksi`.\n";
}

// Cek tabel cashier_shifts apakah kolom updated_at sudah ada
$checkUpdateCol = $conn->query("SHOW COLUMNS FROM `cashier_shifts` LIKE 'updated_at'");
if ($checkUpdateCol && $checkUpdateCol->num_rows === 0) {
    $alterCs = "ALTER TABLE `cashier_shifts` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`";
    if ($conn->query($alterCs) === TRUE) {
        echo "• Berhasil menambahkan kolom `updated_at` ke tabel `cashier_shifts`.\n";
    }
}

// Samakan collation agar join selalu konsisten
$conn->query("ALTER TABLE `transaksi` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->query("ALTER TABLE `cashier_shifts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// Relasikan transaksi yang sudah ada hari ini ke shift_id penutupan terakhir jika belum terikat
$conn->query("
    UPDATE `transaksi` t
    INNER JOIN (
        SELECT id, kasir_nama, closing_time 
        FROM `cashier_shifts` 
        ORDER BY id DESC
    ) cs ON t.petugas = cs.kasir_nama AND DATE(t.tanggal) = DATE(cs.closing_time)
    SET t.shift_id = cs.id
    WHERE t.shift_id IS NULL OR t.shift_id = 0
");
echo "• Berhasil mengikat transaksi historis ke shift penutupan terkait.\n";

echo "=== MIGRASI SELESAI SUKSES ===\n";
