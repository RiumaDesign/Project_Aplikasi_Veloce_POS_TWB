<?php
// Unit Test: Render views/dashboard/analytics.php
session_start();
$_SESSION['admin_nama'] = 'Admin Khusus';
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'superadmin';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$_GET['page'] = 'analytics';

ob_start();
include __DIR__ . '/../views/dashboard/analytics.php';
$output = ob_get_clean();

echo "Analytics View Output Length: " . strlen($output) . " bytes\n";
echo "1. Chart Penjualan Produk Canvas: " . (strpos($output, 'id="chartPenjualanProduk"') !== false ? "PASS [OK]" : "FAIL") . "\n";
echo "2. Modal Detail Transaksi: " . (strpos($output, 'id="modal-detail-transaksi"') !== false ? "PASS [OK]" : "FAIL") . "\n";
echo "3. Filter Live Search Input: " . (strpos($output, 'id="cari-transaksi-input"') !== false ? "PASS [OK]" : "FAIL") . "\n";
echo "4. Tombol Cetak Dokumen PDF Resmi: " . (strpos($output, 'Cetak / PDF Dokumen Resmi') !== false ? "PASS [OK]" : "FAIL") . "\n";
echo "5. Fungsi JS filterTabelDetailTransaksi: " . (strpos($output, 'function filterTabelDetailTransaksi()') !== false ? "PASS [OK]" : "FAIL") . "\n";
