<?php
/**
 * Test: Verifikasi Pencatatan Uang Diterima & Kembalian Transaksi Kasir
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/TransactionController.php';

echo "=== UJI TRANSAKSI KASIR: UANG DITERIMA & KEMBALIAN ===\n";

// Ambil salah satu produk dan outlet aktif untuk pengujian
$p = $conn->query("SELECT id, nama, harga FROM `produk` LIMIT 1")->fetch_assoc();
$l = $conn->query("SELECT id, name FROM `locations` WHERE type IN ('outlet', 'vm', 'pos') LIMIT 1")->fetch_assoc();

if (!$p || !$l) {
    die("Data produk atau lokasi tidak ditemukan untuk pengujian.\n");
}

$productId = intval($p['id']);
$productName = $p['nama'];
$productPrice = intval($p['harga']);
$outletId = intval($l['id']);
$outletName = $l['name'];
$kasirTest = 'Kasir QA Test';

// Pastikan ada stok lokasi sementara
$conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`) VALUES ($productId, $outletId, 100) ON DUPLICATE KEY UPDATE `quantity` = `quantity` + 50");

// 1. TEST KASUS A: TRANSAKSI TUNAI (CASH DENGAN KEMBALIAN)
$totalA = $productPrice * 2;
$bayarA = $totalA + 15000;
$kembalianA = 15000;

$postDataA = [
    'items' => "2x $productName",
    'total_harga' => $totalA,
    'uang_diterima' => $bayarA,
    'kembalian' => $kembalianA,
    'metode' => 'Cash',
    'detail_items' => json_encode([
        ['id' => $productId, 'nama' => $productName, 'harga' => $productPrice, 'qty' => 2]
    ])
];

$resA = proses_transaksi_kasir($conn, $kasirTest, $outletId, $outletName, $postDataA);
echo "1. Hasil Transaksi Tunai: {$resA['status']} | ID: {$resA['id_transaksi']}\n";
assert($resA['status'] === 'success');
assert($resA['uang_diterima'] == $bayarA);
assert($resA['kembalian'] == $kembalianA);

// Cek di tabel transaksi database
$qDbA = $conn->query("SELECT total_harga, uang_diterima, kembalian, metode FROM `transaksi` WHERE `id_transaksi` = '{$resA['id_transaksi']}'")->fetch_assoc();
echo "   -> DB: Total={$qDbA['total_harga']} | Diterima={$qDbA['uang_diterima']} | Kembali={$qDbA['kembalian']} | Metode={$qDbA['metode']}\n";
assert(intval($qDbA['uang_diterima']) === $bayarA);
assert(intval($qDbA['kembalian']) === $kembalianA);

// 2. TEST KASUS B: TRANSAKSI NON-TUNAI (QRIS PAS)
$totalB = $productPrice * 1;
$postDataB = [
    'items' => "1x $productName",
    'total_harga' => $totalB,
    'uang_diterima' => 0, // QRIS otomatis diisi total di backend jika 0
    'kembalian' => 0,
    'metode' => 'QRIS',
    'detail_items' => json_encode([
        ['id' => $productId, 'nama' => $productName, 'harga' => $productPrice, 'qty' => 1]
    ])
];

$resB = proses_transaksi_kasir($conn, $kasirTest, $outletId, $outletName, $postDataB);
echo "2. Hasil Transaksi QRIS: {$resB['status']} | ID: {$resB['id_transaksi']}\n";
assert($resB['status'] === 'success');
assert($resB['uang_diterima'] == $totalB);
assert($resB['kembalian'] == 0);

$qDbB = $conn->query("SELECT total_harga, uang_diterima, kembalian, metode FROM `transaksi` WHERE `id_transaksi` = '{$resB['id_transaksi']}'")->fetch_assoc();
echo "   -> DB: Total={$qDbB['total_harga']} | Diterima={$qDbB['uang_diterima']} | Kembali={$qDbB['kembalian']} | Metode={$qDbB['metode']}\n";
assert(intval($qDbB['uang_diterima']) === $totalB);
assert(intval($qDbB['kembalian']) === 0);

// Bersihkan data pengujian
$conn->query("DELETE FROM `transaksi_detail` WHERE `transaksi_id` IN (SELECT id FROM `transaksi` WHERE `petugas` = '$kasirTest')");
$conn->query("DELETE FROM `stock_mutations` WHERE `created_by` = '$kasirTest'");
$conn->query("DELETE FROM `transaksi` WHERE `petugas` = '$kasirTest'");

echo "=== SELURUH PENGUJIAN TRANSAKSI KASIR SUKSES 100% ===\n";
