<?php
require_once __DIR__ . '/../config/database.php';

echo "=== DAFTAR CASHIER SHIFTS SEBELUM DIBERSIHKAN ===\n";
$res = $conn->query("SELECT id, shift_number, kasir_nama, closing_time, total_sales, transaction_count FROM cashier_shifts ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | {$row['shift_number']} | {$row['kasir_nama']} | {$row['closing_time']} | Sales: {$row['total_sales']} | Tx: {$row['transaction_count']}\n";
}

// Hapus duplikat pengujian sebelumnya untuk Andi Wijaya kecuali ID terbaru (atau ikat transaksi ke ID tersebut)
// Cari ID shift Andi Wijaya hari ini
$qAndi = $conn->query("SELECT id FROM cashier_shifts WHERE kasir_nama = 'Andi Wijaya' AND DATE(closing_time) = CURDATE() ORDER BY id DESC");
$andiIds = [];
while ($r = $qAndi->fetch_assoc()) {
    $andiIds[] = $r['id'];
}

if (count($andiIds) > 1) {
    $keepId = $andiIds[0]; // simpan yang paling mutakhir
    $deleteIds = array_slice($andiIds, 1);
    $deleteStr = implode(',', $deleteIds);
    
    // Pastikan transaksi terikat ke keepId
    $conn->query("UPDATE transaksi SET shift_id = $keepId WHERE shift_id IN ($deleteStr)");
    
    // Hapus duplikat
    $conn->query("DELETE FROM cashier_shifts WHERE id IN ($deleteStr)");
    echo "• Berhasil membersihkan duplikat shift ID: $deleteStr. Menyimpan shift resmi ID: $keepId.\n";
}

echo "=== DAFTAR CASHIER SHIFTS SETELAH DIBERSIHKAN ===\n";
$res2 = $conn->query("SELECT id, shift_number, kasir_nama, closing_time, total_sales, transaction_count FROM cashier_shifts ORDER BY id ASC");
while ($row = $res2->fetch_assoc()) {
    echo "ID: {$row['id']} | {$row['shift_number']} | {$row['kasir_nama']} | {$row['closing_time']} | Sales: {$row['total_sales']} | Tx: {$row['transaction_count']}\n";
}
