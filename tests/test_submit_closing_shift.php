<?php
// Test API endpoint submit_closing_shift
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'kasir';
$_SESSION['kasir_nama'] = 'Andi Wijaya';
$_SESSION['pos_aktif'] = 'Vending Machine 1';

require_once __DIR__ . '/../config/database.php';

$testPayload = [
    'kasir_nama' => 'Andi Wijaya',
    'pos_aktif' => 'Vending Machine 1',
    'outlet_id' => 1,
    'opening_time' => '2026-09-02 08:00:00',
    'opening_cash' => 100000,
    'actual_cash' => 140000,
    'notes' => 'Testing closing shift submission via unit test'
];

// Simulasi eksekusi method closeShift di ShiftController
require_once __DIR__ . '/../controllers/ShiftController.php';
$ctrl = new ShiftController($conn);
$res = $ctrl->closeShift($testPayload);

echo "Status: " . ($res['status'] ?? 'none') . "\n";
echo "Message: " . ($res['message'] ?? 'none') . "\n";
if (isset($res['data']['shift_number'])) {
    echo "Shift Number: " . $res['data']['shift_number'] . "\n";
    echo "Actual Cash: Rp " . number_format($res['data']['actual_cash']) . "\n";
    echo "Difference: Rp " . number_format($res['data']['difference']) . "\n";
}
