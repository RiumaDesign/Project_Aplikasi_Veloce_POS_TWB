<?php
/**
 * Migration: Create cashier_shifts table
 * Veloce POS - PT Taman Wisata Borobudur
 */

require_once dirname(__DIR__, 2) . '/config/database.php';

echo "=== MEMULAI MIGRASI TABEL CASHIER SHIFTS ===\n";

$sql = "
CREATE TABLE IF NOT EXISTS `cashier_shifts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shift_number` VARCHAR(50) NOT NULL UNIQUE,
    `kasir_nama` VARCHAR(100) NOT NULL,
    `outlet_id` INT DEFAULT 1,
    `pos_aktif` VARCHAR(100) NOT NULL DEFAULT 'Kasir Utama',
    `opening_time` DATETIME NOT NULL,
    `closing_time` DATETIME NOT NULL,
    `opening_cash` INT NOT NULL DEFAULT 0,
    `cash_sales` INT NOT NULL DEFAULT 0,
    `qris_sales` INT NOT NULL DEFAULT 0,
    `total_sales` INT NOT NULL DEFAULT 0,
    `transaction_count` INT NOT NULL DEFAULT 0,
    `actual_cash` INT NOT NULL DEFAULT 0,
    `difference` INT NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'closed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_kasir_date` (`kasir_nama`, `closing_time`),
    INDEX `idx_outlet` (`outlet_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "• Berhasil membuat tabel `cashier_shifts`.\n";
} else {
    echo "• Gagal membuat tabel: " . $conn->error . "\n";
    exit(1);
}

echo "=== MIGRASI SELESAI SUKSES ===\n";
