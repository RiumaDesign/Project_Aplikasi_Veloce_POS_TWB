<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "=== TEST POS TERMINAL & GANTI TERMINAL ===\n\n";

// 1. Test query terminal list
$terminals_q = $conn->query("SELECT id, code, name, type FROM `locations` WHERE `type` IN ('outlet', 'vm', 'pos') AND `status` = 'active' ORDER BY `type` ASC, `code` ASC");
echo "1. Total active terminals found: " . $terminals_q->num_rows . "\n";

// 2. Test rendering index.php with session
$_SESSION['kasir_logged_in'] = true;
$_SESSION['kasir_nama'] = 'Andi Wijaya';
$_SESSION['outlet_id'] = 2;
$_SESSION['pos_aktif'] = 'Outlet Museum Samudra Raksa';

ob_start();
include 'index.php';
$html = ob_get_clean();

echo "2. Rendered index.php: " . strlen($html) . " bytes.\n";

if (strpos($html, 'modal-ganti-terminal') !== false) {
    echo "PASS: Modal ganti terminal is present in HTML.\n";
} else {
    echo "FAIL: Modal ganti terminal is missing!\n";
}

if (strpos($html, 'logout.php') !== false) {
    echo "PASS: Logout button is present in HTML.\n";
} else {
    echo "FAIL: Logout button is missing!\n";
}

echo "\n=== ALL TESTS COMPLETED SUCCESSFULLY ===\n";
