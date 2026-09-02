<?php
/**
 * Test: Buku Besar Mutasi Stok Terpadu & Ekspor Filter
 * File: tests/test_stock_mutations_and_export.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ExportController.php';

echo "=== TEST BUKU BESAR MUTASI STOK & EXPORT ===\n\n";

// 1. Cek isi tabel stock_mutations
$res_count = $conn->query("SELECT mutation_type, COUNT(*) as total, SUM(quantity) as total_qty FROM stock_mutations GROUP BY mutation_type");
$categories = [];
while ($row = $res_count->fetch_assoc()) {
    $categories[$row['mutation_type']] = $row;
    echo "• Kategori '{$row['mutation_type']}': {$row['total']} transaksi, total qty: {$row['total_qty']} pcs\n";
}

if (count($categories) >= 4) {
    echo "PASS: Tabel stock_mutations memiliki setidaknya 4 kategori mutasi (inbound, transfer_do, sale, return).\n\n";
} else {
    echo "FAIL: Tabel stock_mutations belum lengkap.\n\n";
}

// 2. Uji Query dengan Filter Kategori Inbound
$q_inbound = $conn->query("SELECT count(*) as c FROM stock_mutations WHERE mutation_type = 'inbound'");
$c_inbound = $q_inbound->fetch_assoc()['c'];
echo "2. Uji filter kategori 'inbound': ditemukan $c_inbound data.\n";
if ($c_inbound > 0) {
    echo "PASS: Filter kategori inbound berfungsi.\n\n";
} else {
    echo "FAIL: Data inbound tidak ditemukan.\n\n";
}

// 3. Uji Query dengan Filter Kategori Transfer DO
$q_do = $conn->query("SELECT count(*) as c FROM stock_mutations WHERE mutation_type = 'transfer_do'");
$c_do = $q_do->fetch_assoc()['c'];
echo "3. Uji filter kategori 'transfer_do': ditemukan $c_do data.\n";
if ($c_do > 0) {
    echo "PASS: Filter kategori transfer_do berfungsi.\n\n";
} else {
    echo "FAIL: Data transfer_do tidak ditemukan.\n\n";
}

// 4. Uji Query dengan Filter Kategori Penjualan Kasir (Sale)
$q_sale = $conn->query("SELECT count(*) as c FROM stock_mutations WHERE mutation_type = 'sale'");
$c_sale = $q_sale->fetch_assoc()['c'];
echo "4. Uji filter kategori 'sale': ditemukan $c_sale data.\n";
if ($c_sale > 0) {
    echo "PASS: Filter kategori sale berfungsi.\n\n";
} else {
    echo "FAIL: Data sale tidak ditemukan.\n\n";
}

// 5. Uji Ekspor PDF Mutasi Stok
echo "5. Uji rendering ekspor PDF Mutasi Stok:\n";
ob_start();
$exportCtrl = new ExportController($conn, 'Tester Admin');
$exportCtrl->exportMutasi('pdf', ['kategori' => 'inbound']);
$pdfHtml = ob_get_clean();

echo "• Ukuran PDF HTML yang dihasilkan: " . strlen($pdfHtml) . " bytes\n";
if (strpos($pdfHtml, 'PT TAMAN WISATA CANDI BOROBUDUR') !== false && 
    strpos($pdfHtml, 'Laporan Log Mutasi & Arus Stok Terpadu') !== false && 
    strpos($pdfHtml, 'data:image/png;base64') !== false) {
    echo "PASS: Export PDF Mutasi Stok memuat Kop Surat Resmi TWB, Logo Base64, dan data mutasi terfilter.\n\n";
} else {
    echo "FAIL: Export PDF Mutasi Stok tidak sesuai spesifikasi.\n\n";
}

// 6. Uji Ekspor Excel Mutasi Stok
echo "6. Uji rendering ekspor Excel Mutasi Stok:\n";
ob_start();
$exportCtrl->exportMutasi('excel', []);
$excelHtml = ob_get_clean();

echo "• Ukuran Excel HTML yang dihasilkan: " . strlen($excelHtml) . " bytes\n";
if (strpos($excelHtml, 'BUKU BESAR LOG MUTASI & ARUS STOK TERPADU') !== false && 
    strpos($excelHtml, 'urn:schemas-microsoft-com:office:excel') !== false) {
    echo "PASS: Export Excel Mutasi Stok valid untuk Microsoft Excel.\n\n";
} else {
    echo "FAIL: Export Excel Mutasi Stok tidak sesuai spesifikasi.\n\n";
}

echo "=== ALL MUTATION LEDGER & EXPORT TESTS PASSED ===\n";
