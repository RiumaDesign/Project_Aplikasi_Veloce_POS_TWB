<?php
// Unit Test: Export Periods
session_start();
$_SESSION['admin_nama'] = 'Admin Khusus';
$_SESSION['admin_id'] = 1;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ExportController.php';

$ctrl = new ExportController($conn, 'Admin Khusus');

// Test PDF Harian
ob_start();
$ctrl->exportPenjualan('pdf', ['period_type' => 'harian']);
$pdfHarian = ob_get_clean();
echo "1. PDF Harian: " . (strpos($pdfHarian, 'Laporan Rekapitulasi Penjualan Harian') !== false ? "PASS [OK]" : "FAIL") . "\n";

// Test PDF Mingguan
ob_start();
$ctrl->exportPenjualan('pdf', ['period_type' => 'mingguan']);
$pdfMingguan = ob_get_clean();
echo "2. PDF Mingguan: " . (strpos($pdfMingguan, 'Laporan Rekapitulasi Penjualan Mingguan') !== false ? "PASS [OK]" : "FAIL") . "\n";

// Test PDF Bulanan
ob_start();
$ctrl->exportPenjualan('pdf', ['period_type' => 'bulanan']);
$pdfBulanan = ob_get_clean();
echo "3. PDF Bulanan: " . (strpos($pdfBulanan, 'Laporan Rekapitulasi Penjualan Bulanan') !== false ? "PASS [OK]" : "FAIL") . "\n";
