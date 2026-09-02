<?php
/**
 * Test: Verifikasi Badge Kontras Tinggi pada Tema Terang
 */

require_once __DIR__ . '/../config/database.php';

// Cek keberadaan rules di assets/css/glassmorphism.css
$css = file_get_contents(__DIR__ . '/../assets/css/glassmorphism.css');
$header = file_get_contents(__DIR__ . '/../views/layouts/header.php');

$required_rules = [
    'color: #92400e', // Amber text kontras tinggi
    'background-color: #fef3c7', // Amber background hangat
    'color: #1e40af', // Blue text kontras tinggi
    'background-color: #eff6ff', // Blue background
    'color: #065f46', // Emerald text kontras tinggi
    'background-color: #ecfdf5', // Emerald background
    'color: #9f1239', // Rose text kontras tinggi
    'background-color: #fff1f2', // Rose background
    'color: #5b21b6', // Violet text kontras tinggi
    'background-color: #f5f3ff'  // Violet background
];

echo "=== TEST VERIFIKASI BADGE KONTRAS TINGGI LIGHT THEME ===\n\n";

$pass_count = 0;
foreach ($required_rules as $rule) {
    if (strpos($css, $rule) !== false && strpos($header, $rule) !== false) {
        echo "• PASS: Rule '{$rule}' terdaftar di glassmorphism.css dan header.php\n";
        $pass_count++;
    } else {
        echo "• FAIL: Rule '{$rule}' hilang!\n";
    }
}

if ($pass_count === count($required_rules)) {
    echo "\n=== SEMUA 10 RULE KONTRAS TINGGI TERVERIFIKASI 100% ===\n";
} else {
    echo "\n=== ADA RULE YANG KURANG ===\n";
}
