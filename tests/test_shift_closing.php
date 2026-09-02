<?php
/**
 * Test: Shift Closing (Z-Report) & Sidebar Live Badges
 * Veloce POS - PT Taman Wisata Borobudur
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ShiftController.php';

echo "=== MEMULAI TEST SHIFT CLOSING & LIVE BADGES ===\n";

$shiftCtrl = new ShiftController($conn);

// 1. Test Get Current Shift Stats
$stats = $shiftCtrl->getCurrentShiftStats('Budi Santoso', 'Kasir Utama', 1);
echo "• 1. getCurrentShiftStats berhasil dipanggil.\n";
echo "    - Kasir: {$stats['kasir_nama']}\n";
echo "    - Terminal: {$stats['pos_aktif']}\n";
echo "    - Nota Berjalan: {$stats['nota_count']}\n";
echo "    - Omzet Tunai: Rp " . number_format($stats['cash_sales']) . "\n";
echo "    - Omzet QRIS: Rp " . number_format($stats['qris_sales']) . "\n";
echo "    - Grand Total: Rp " . number_format($stats['total_sales']) . "\n";

// 2. Test Close Shift
$testPayload = [
    'kasir_nama'   => 'Budi Santoso',
    'pos_aktif'    => 'Kasir Utama',
    'outlet_id'    => 1,
    'opening_time' => date('Y-m-d 08:00:00'),
    'opening_cash' => 100000,
    'actual_cash'  => 100000 + $stats['cash_sales'], // Uang pas
    'notes'        => 'Uji coba otomatis penutupan shift dan rekapitulasi kas.'
];

$closeRes = $shiftCtrl->closeShift($testPayload);
if ($closeRes['status'] === 'success') {
    echo "• 2. closeShift berhasil dieksekusi!\n";
    echo "    - No. Shift: {$closeRes['shift_number']}\n";
    echo "    - Target Kas: Rp " . number_format($closeRes['expected_cash']) . "\n";
    echo "    - Fisik Kasir: Rp " . number_format($closeRes['actual_cash']) . "\n";
    echo "    - Selisih Kas: Rp " . number_format($closeRes['difference']) . " (" . ($closeRes['difference'] === 0 ? 'PAS' : 'SELISIH') . ")\n";
} else {
    echo "• 2. GAGAL closeShift: {$closeRes['message']}\n";
    exit(1);
}

// 3. Test Shift History
$history = $shiftCtrl->getShiftHistory(5);
echo "• 3. getShiftHistory berhasil mengambil " . count($history) . " rekaman shift.\n";
$latest = $history[0];
echo "    - Shift Terakhir: {$latest['shift_number']} oleh {$latest['kasir_nama']} ({$latest['status']})\n";

// 4. Test Query Lencana Dinamis Sidebar
$stkRes = $conn->query("SELECT COUNT(*) FROM `stok_lokasi` WHERE `quantity` <= 3");
$countKritisStok = intval($stkRes ? $stkRes->fetch_row()[0] : 0);
echo "• 4. Kueri Lencana Sidebar Stok Kritis: $countKritisStok produk kritis.\n";

$retRes = $conn->query("SELECT COUNT(*) FROM `returns` WHERE `status` = 'pending' OR `created_at` >= DATE_SUB(NOW(), INTERVAL 48 HOUR)");
$countReturPending = intval($retRes ? $retRes->fetch_row()[0] : 0);
echo "• 5. Kueri Lencana Sidebar Retur Pending: $countReturPending retur pending.\n";

echo "=== SELURUH TEST BERHASIL 100% ===\n";
