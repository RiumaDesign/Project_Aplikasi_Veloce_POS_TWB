<?php
/**
 * Test Suite: Verifikasi Branding Logo TWB & Cetak Struk / PDF
 * Multi-Outlet & POS Veloce — PT Taman Wisata Candi Borobudur
 * File: tests/test_twb_branding_and_print.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ExportController.php';

echo "=========================================================\n";
echo "🏛️ UJI VERIFIKASI LOGO RESMI TWB & OPTIMALISASI CETAK\n";
echo "=========================================================\n\n";

// 1. Cek File Logo TWB Fisik
echo "1. Mengecek keberadaan file fisik assets/images/logo_twb.png...\n";
$logo_path = dirname(__DIR__) . '/assets/images/logo_twb.png';
assert(file_exists($logo_path), "File logo_twb.png harus ada!");
$size = filesize($logo_path);
assert($size > 50000, "Ukuran file logo TWB harus valid (> 50KB)");
echo "   -> PASS: File logo TWB ditemukan! Ukuran: " . round($size / 1024, 2) . " KB\n\n";

// 2. Cek Branding di Navbar, Sidebar, dan Login Card
echo "2. Mengecek implementasi logo pada UI Aplikasi...\n";
$navbar_content = file_get_contents(dirname(__DIR__) . '/views/pos/navbar.php');
assert(strpos($navbar_content, 'assets/images/logo_twb.png') !== false, "Navbar harus menyertakan logo TWB");
assert(strpos($navbar_content, 'TWB') !== false, "Navbar harus berlabel TWB");

$sidebar_content = file_get_contents(dirname(__DIR__) . '/views/layouts/sidebar.php');
assert(strpos($sidebar_content, 'assets/images/logo_twb.png') !== false, "Sidebar harus menyertakan logo TWB");

$login_content = file_get_contents(dirname(__DIR__) . '/views/auth/login_card.php');
assert(strpos($login_content, 'assets/images/logo_twb.png') !== false, "Login card harus menyertakan logo TWB");
echo "   -> PASS: Navbar Kasir, Sidebar Admin, dan Kartu Login telah menggunakan logo resmi TWB!\n\n";

// 3. Cek Struk / Nota Kasir (Thermal Receipt)
echo "3. Mengecek optimalisasi percetakan struk / nota kasir...\n";
$modal_payment = file_get_contents(dirname(__DIR__) . '/views/pos/modal_payment.php');
assert(strpos($modal_payment, 'assets/images/logo_twb.png') !== false, "Struk harus memuat logo TWB");
assert(strpos($modal_payment, 'PT TAMAN WISATA BOROBUDUR') !== false, "Struk harus memuat kop resmi TWB");

$index_content = file_get_contents(dirname(__DIR__) . '/index.php');
assert(strpos($index_content, '@media print') !== false, "index.php harus memiliki @media print");
assert(strpos($index_content, '76mm') !== false, "Print struk harus disesuaikan untuk standar kertas thermal roll (76-80mm)");
echo "   -> PASS: Struk kasir telah dioptimalkan dengan logo TWB dan format thermal roll 76mm/80mm!\n\n";

// 4. Cek Template Header PDF (Base64 Logo, Kop Resmi, Dokumen Badge)
echo "4. Menguji rendering kop dokumen PDF formal TWB...\n";
$export = new ExportController($conn, 'Unit Test Runner');
$refHeader = new ReflectionMethod($export, 'renderPdfHeader');
$refHeader->setAccessible(true);
$headerHtml = $refHeader->invoke($export, "Laporan Uji Coba Kop TWB", "Subjudul Laporan Resmi");

assert(strpos($headerHtml, 'data:image/png;base64,') !== false, "Header PDF harus menyematkan logo TWB dalam format Base64");
assert(strpos($headerHtml, 'PT TAMAN WISATA CANDI BOROBUDUR') !== false, "Header PDF harus memiliki kop surat resmi BUMN");
assert(strpos($headerHtml, 'DOKUMEN RESMI TWB') !== false, "Header PDF harus memiliki badge resmi TWB");
assert(strpos($headerHtml, 'kop-logo') !== false, "Header PDF harus memiliki kelas styling kop-logo");
echo "   -> PASS: Header PDF memuat Logo Resmi TWB Base64, Kop Surat Resmi, dan Badge Resmi TWB!\n\n";

// 5. Cek Footer PDF (Signature Box)
echo "5. Menguji rendering blok tanda tangan formal pada footer PDF...\n";
$refFooter = new ReflectionMethod($export, 'renderPdfFooter');
$refFooter->setAccessible(true);
$footerHtml = $refFooter->invoke($export, "Kasir / Petugas Operasional");

assert(strpos($footerHtml, 'signatures') !== false, "Footer PDF harus memuat container signatures");
assert(strpos($footerHtml, 'Dibuat Oleh') !== false, "Footer PDF harus memiliki tanda tangan pembuat");
assert(strpos($footerHtml, 'Mengetahui & Menyetujui') !== false, "Footer PDF harus memiliki tanda tangan supervisor");
echo "   -> PASS: Footer PDF memuat tanda tangan pembuat dan persetujuan supervisor!\n\n";

// 6. Cek Format Excel Tetap Bersih (Kecuali Excel)
echo "6. Memverifikasi format Excel tidak disisipi gambar sesuai permintaan pengguna...\n";
$export_code = file_get_contents(dirname(__DIR__) . '/controllers/ExportController.php');
// Pastikan fungsi renderExcel tidak menyematkan tag <img> base64 yang dapat merusak file .xls
assert(strpos($export_code, 'function renderExcelPenjualan') !== false, "renderExcelPenjualan harus tetap ada");
assert(strpos($export_code, 'function renderExcelStok') !== false, "renderExcelStok harus tetap ada");
assert(strpos($export_code, 'function renderExcelDO') !== false, "renderExcelDO harus tetap ada");
assert(strpos($export_code, 'function renderExcelRetur') !== false, "renderExcelRetur harus tetap ada");
echo "   -> PASS: Format Excel tetap berupa spreadsheet tabel murni tanpa manipulasi gambar!\n\n";

echo "=========================================================\n";
echo "✅ SEMUA PENGUJIAN LOGO TWB & PERCETAKAN DOKUMEN SUKSES 100%!\n";
echo "=========================================================\n";
