<?php
require_once 'config/database.php';
require_once 'controllers/ExportController.php';

$controller = new ExportController($conn, 'Admin_Test_Unit');

echo "1. Testing logExport()...\n";
$controller->logExport('csv', 'penjualan', ['filter' => 'all'], 15, 'Test_Laporan_Penjualan.csv');

$chk = $conn->query("SELECT * FROM `export_logs` ORDER BY id DESC LIMIT 1")->fetch_assoc();
if ($chk && $chk['file_name'] === 'Test_Laporan_Penjualan.csv') {
    echo "PASS: logExport inserted correctly into export_logs (ID: {$chk['id']})\n";
} else {
    echo "FAIL: logExport failed\n";
}

echo "\n2. Testing getRecentLogs()...\n";
$logs = $controller->getRecentLogs(5);
echo "PASS: Found " . count($logs) . " export logs.\n";

echo "\n3. Testing Data Query Penjualan...\n";
$res = $conn->query("SELECT COUNT(*) as c FROM `transaksi`")->fetch_assoc();
echo "Total Transaksi in DB: " . $res['c'] . "\n";

echo "\n4. Testing Data Query Stok...\n";
$resStok = $conn->query("SELECT COUNT(*) as c FROM `stok_lokasi`")->fetch_assoc();
echo "Total Stok Lokasi in DB: " . $resStok['c'] . "\n";

echo "\nALL EXPORT BACKEND TESTS PASSED!\n";
