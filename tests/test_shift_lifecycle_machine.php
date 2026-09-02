<?php
/**
 * Test: Shift Lifecycle State Machine & Anti-Double Entry Prevention
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ShiftController.php';

echo "=== TEST SIKLUS HIDUP SHIFT KASIR & ANTI-DUPLIKASI ===\n";

$shiftCtrl = new ShiftController($conn);
$kasirTest = 'Kasir Uji Coba';
$posTest = 'Vending Machine Test';

// 1. Bersihkan data test lama jika ada
$conn->query("DELETE FROM cashier_shifts WHERE kasir_nama = '$kasirTest'");
$conn->query("DELETE FROM transaksi WHERE petugas = '$kasirTest'");

// 2. Simulasikan 2 transaksi awal
$conn->query("
    INSERT INTO `transaksi` (`id_transaksi`, `tanggal`, `waktu`, `petugas`, `pos_aktif`, `metode`, `total_harga`, `uang_diterima`, `kembalian`)
    VALUES 
    ('TRX-TEST-001', CURDATE(), '10:00:00', '$kasirTest', '$posTest', 'Tunai', 25000, 50000, 25000),
    ('TRX-TEST-002', CURDATE(), '10:05:00', '$kasirTest', '$posTest', 'QRIS', 35000, 35000, 0)
");

// 3. Cek stats awal: harus berstatus ACTIVE_PENDING dengan 2 nota
$stats1 = $shiftCtrl->getCurrentShiftStats($kasirTest, $posTest);
echo "1. Status Awal: {$stats1['shift_state']} | Nota: {$stats1['nota_count']} | Total: {$stats1['total_sales']}\n";
assert($stats1['shift_state'] === 'ACTIVE_PENDING');
assert($stats1['nota_count'] === 2);

// 4. Lakukan penutupan shift pertama (INSERT shift baru)
$close1 = $shiftCtrl->closeShift([
    'kasir_nama' => $kasirTest,
    'pos_aktif' => $posTest,
    'opening_cash' => 100000,
    'actual_cash' => 125000,
    'notes' => 'Tutup shift pertama'
]);
echo "2. Shift 1 Ditutup: Status: {$close1['status']} | Shift No: {$close1['shift_number']} | ID: {$close1['shift_id']}\n";
assert($close1['status'] === 'success');
$shift1Id = $close1['shift_id'];

// Verifikasi transaksi terikat ke shift1Id
$boundCount = $conn->query("SELECT COUNT(*) FROM transaksi WHERE petugas = '$kasirTest' AND shift_id = $shift1Id")->fetch_row()[0];
echo "   -> Transaksi terikat ke Shift ID $shift1Id: $boundCount nota\n";
assert($boundCount == 2);

// 5. Cek stats pasca penutupan shift: harus ALREADY_CLOSED, 0 nota pending!
$stats2 = $shiftCtrl->getCurrentShiftStats($kasirTest, $posTest);
echo "3. Status Pasca Closing: {$stats2['shift_state']} | Shift ID: {$stats2['shift_id']}\n";
assert($stats2['shift_state'] === 'ALREADY_CLOSED');
assert($stats2['shift_id'] == $shift1Id);

// 6. Uji Anti-Duplikasi: Jika kasir menekan tombol simpan lagi saat ALREADY_CLOSED, harus UPDATE bukan INSERT
$close2 = $shiftCtrl->closeShift([
    'kasir_nama' => $kasirTest,
    'pos_aktif' => $posTest,
    'opening_cash' => 100000,
    'actual_cash' => 125000,
    'notes' => 'Revisi catatan tanpa duplikasi'
]);
echo "4. Proteksi Duplikasi: Action: {$close2['action_type']} | Shift ID: {$close2['shift_id']}\n";
assert($close2['action_type'] === 'updated');

// Hitung total baris cashier_shifts untuk kasir ini: HARUS TETAP 1 BARIS!
$totalShifts = $conn->query("SELECT COUNT(*) FROM cashier_shifts WHERE kasir_nama = '$kasirTest'")->fetch_row()[0];
echo "   -> Total baris di cashier_shifts untuk kasir ini: $totalShifts baris (Harus 1!)\n";
assert($totalShifts == 1);

// 7. Uji Koreksi Langsung (UPDATE): ubah uang fisik
$updateRes = $shiftCtrl->updateClosedShift([
    'shift_id' => $shift1Id,
    'actual_cash' => 130000,
    'notes' => 'Koreksi kas fisik lebih 5rb'
]);
echo "5. Koreksi Shift: {$updateRes['message']} | Diff: {$updateRes['difference']}\n";
assert($updateRes['status'] === 'success');
assert($updateRes['difference'] == 5000); // 130.000 - (100.000 + 25.000) = 5.000

// 8. Simulasikan Transaksi Baru untuk Sesi Shift Berikutnya
$conn->query("
    INSERT INTO `transaksi` (`id_transaksi`, `tanggal`, `waktu`, `petugas`, `pos_aktif`, `metode`, `total_harga`, `uang_diterima`, `kembalian`)
    VALUES ('TRX-TEST-003', CURDATE(), '11:00:00', '$kasirTest', '$posTest', 'Tunai', 50000, 50000, 0)
");

// 9. Cek stats sesi baru: harus otomatis beralih ke ACTIVE_PENDING hanya untuk 1 nota baru!
$stats3 = $shiftCtrl->getCurrentShiftStats($kasirTest, $posTest);
echo "6. Status Transaksi Baru: {$stats3['shift_state']} | Nota Pending: {$stats3['nota_count']} | Omzet: {$stats3['total_sales']}\n";
assert($stats3['shift_state'] === 'ACTIVE_PENDING');
assert($stats3['nota_count'] === 1);
assert($stats3['total_sales'] === 50000);

// 10. Bersihkan data test
$conn->query("DELETE FROM cashier_shifts WHERE kasir_nama = '$kasirTest'");
$conn->query("DELETE FROM transaksi WHERE petugas = '$kasirTest'");

echo "=== SELURUH TEST SIKLUS HIDUP SHIFT BERHASIL 100% ===\n";
