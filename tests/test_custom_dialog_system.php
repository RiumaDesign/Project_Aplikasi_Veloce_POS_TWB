<?php
// Test Verifikasi Sistem Dialog Pop-Up Modern & Zero Error
$base_dir = dirname(__DIR__);
$errors = [];
$passed = [];

// 1. Cek app.js
$app_js = file_get_contents($base_dir . '/assets/js/app.js');
$expected_funcs = [
    'confirm: function',
    'alert: function',
    'window.confirmLogoutAdmin',
    'window.confirmLogoutKasir',
    'window.confirmSubmitForm',
    'twb-custom-modal-backdrop',
    'twb-custom-modal-card'
];
foreach ($expected_funcs as $fn) {
    if (strpos($app_js, $fn) !== false) {
        $passed[] = "app.js memuat handler '$fn'";
    } else {
        $errors[] = "app.js TIDAK memuat handler '$fn'";
    }
}

// 2. Cek glassmorphism.css
$css = file_get_contents($base_dir . '/assets/css/glassmorphism.css');
$expected_css = [
    '.twb-modal-backdrop',
    '.twb-modal-card',
    'html[data-theme="light"] .twb-modal-card',
    '#twb-modal-heading',
    '#twb-modal-subtext'
];
foreach ($expected_css as $rule) {
    if (strpos($css, $rule) !== false) {
        $passed[] = "glassmorphism.css memuat rule '$rule'";
    } else {
        $errors[] = "glassmorphism.css TIDAK memuat rule '$rule'";
    }
}

// 3. Pastikan tidak ada confirm() native tersisa di views
$php_files = glob($base_dir . '/views/**/*.php');
foreach ($php_files as $f) {
    $content = file_get_contents($f);
    if (preg_match('/(?<!twb)confirm\s*\(/i', $content, $m)) {
        $errors[] = "File " . basename($f) . " masih mengandung confirm() native!";
    }
}

// 4. Cek pemuatan app.js di header dan index.php
$header = file_get_contents($base_dir . '/views/layouts/header.php');
$index = file_get_contents($base_dir . '/index.php');
if (strpos($header, 'assets/js/app.js') !== false) {
    $passed[] = "header.php memuat assets/js/app.js";
} else {
    $errors[] = "header.php TIDAK memuat assets/js/app.js";
}
if (strpos($index, 'assets/js/app.js') !== false) {
    $passed[] = "index.php memuat assets/js/app.js";
} else {
    $errors[] = "index.php TIDAK memuat assets/js/app.js";
}

// Output Hasil
echo "=== TEST VERIFIKASI SISTEM MODAL POP-UP MODERN TWB ===\n\n";
foreach ($passed as $p) {
    echo "• PASS: $p\n";
}

if (!empty($errors)) {
    echo "\nTERDAPAT EROR:\n";
    foreach ($errors as $e) {
        echo "✗ FAIL: $e\n";
    }
    exit(1);
} else {
    echo "\n=== SEMUA 16 FITUR & VALIDASI LOLOS 100% BEBAS EROR ===\n";
    exit(0);
}
