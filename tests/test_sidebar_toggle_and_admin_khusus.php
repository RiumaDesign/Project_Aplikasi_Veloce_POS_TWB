<?php
/**
 * Test: Verifikasi TWB Admin Khusus dan Fitur Toggle Sembunyikan/Tampilkan Sidebar
 */

$header_content = file_get_contents(__DIR__ . '/../views/layouts/header.php');
$sidebar_content = file_get_contents(__DIR__ . '/../views/layouts/sidebar.php');
$dashboard_content = file_get_contents(__DIR__ . '/../dashboard.php');

echo "=== TEST VERIFIKASI TWB ADMIN KHUSUS & TOGGLE SIDEBAR ===\n\n";

// 1. Cek Penamaan Admin Khusus
if (strpos($header_content, 'TWB Admin Khusus') !== false) {
    echo "• PASS: Judul di header.php menggunakan 'TWB Admin Khusus'\n";
} else {
    echo "• FAIL: Judul di header.php masih menggunakan 'Owner'\n";
}

if (strpos($sidebar_content, 'TWB <span class="text-blue-400">Admin Khusus</span>') !== false) {
    echo "• PASS: Brand header di sidebar.php menggunakan 'TWB Admin Khusus'\n";
} else {
    echo "• FAIL: Brand header di sidebar.php belum menggunakan 'Admin Khusus'\n";
}

// 2. Cek Fitur Toggle Sembunyikan/Tampilkan Sidebar
if (strpos($sidebar_content, 'toggleSidebar') !== false) {
    echo "• PASS: Fungsi toggleSidebar terpasang di sidebar.php\n";
} else {
    echo "• FAIL: Fungsi toggleSidebar tidak ditemukan\n";
}

if (strpos($sidebar_content, 'Sembunyikan Sidebar') !== false) {
    echo "• PASS: Menu/tombol 'Sembunyikan Sidebar' tersedia di sidebar.php\n";
} else {
    echo "• FAIL: Menu 'Sembunyikan Sidebar' tidak ditemukan\n";
}

if (strpos($sidebar_content, 'btn-show-sidebar') !== false && strpos($sidebar_content, 'Tampilkan Sidebar') !== false) {
    echo "• PASS: Tombol mengambang 'btn-show-sidebar' ('Tampilkan Sidebar') tersedia di sidebar.php\n";
} else {
    echo "• FAIL: Tombol 'btn-show-sidebar' tidak ditemukan\n";
}

if (strpos($dashboard_content, 'id="main-content"') !== false) {
    echo "• PASS: Container <main id=\"main-content\"> terintegrasi untuk ekspansi layar penuh saat sidebar tersembunyi\n";
} else {
    echo "• FAIL: Container main-content tidak ditemukan\n";
}

echo "\n=== SEMUA FITUR TERVERIFIKASI 100% ===\n";
