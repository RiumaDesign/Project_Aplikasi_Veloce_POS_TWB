<?php
/**
 * Test: Render views/dashboard/stok.php & Verifikasi Kolom Dinamis Multi-Lokasi
 */
require_once __DIR__ . '/../config/database.php';

$_GET['page'] = 'stok';
$_GET['filter_kategori'] = 'inbound';

ob_start();
require __DIR__ . '/../views/dashboard/stok.php';
$html = ob_get_clean();

echo "1. Panjang HTML stok.php: " . strlen($html) . " bytes\n";
if (strpos($html, 'Buku Besar Log Mutasi & Arus Stok Terpadu') !== false && 
    strpos($html, 'Export Mutasi Excel') !== false &&
    strpos($html, 'Cetak Mutasi PDF') !== false &&
    strpos($html, 'filter_kategori') !== false) {
    echo "PASS: View stok.php memuat Buku Besar Log Mutasi dan Filter.\n";
} else {
    echo "FAIL: Komponen mutasi tidak ditemukan di HTML.\n";
}

echo "\n2. Verifikasi Kolom Dinamis Multi-Titik (VM 1 - VM 9 + Gudang + Outlet):\n";
$checks = ['Gudang Pusat', 'Outlet Museum', 'Outlet Barat', 'VM 1', 'VM 2', 'VM 3', 'VM 4', 'VM 5', 'VM 6', 'VM 7', 'VM 8', 'VM 9', 'TOTAL'];
$passed = 0;
foreach ($checks as $c) {
    if (strpos($html, $c) !== false) {
        echo "• PASS: Kolom '{$c}' ter-render dinamis.\n";
        $passed++;
    } else {
        echo "• FAIL: Kolom '{$c}' hilang!\n";
    }
}

if ($passed === count($checks)) {
    echo "\n=== SEMUA 13 KOLOM DINAMIS TERVERIFIKASI 100% ===\n";
} else {
    echo "\n=== ADA KOLOM YANG KURANG ===\n";
}
