<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_nama'] = 'Admin Utama';
$_GET['module'] = 'penjualan';
$_GET['format'] = 'excel';

ob_start();
include 'export.php';
$out = ob_get_clean();

echo "SUCCESS: Exported " . strlen($out) . " bytes of Excel Spreadsheet.\n";
if (strpos($out, '<td colspan="9" class="title-company">') !== false) {
    echo "PASS: Excel Spreadsheet Title correctly merged across 9 columns.\n";
}
if (strpos($out, '<th style="width: 200px;">ID Transaksi</th>') !== false) {
    echo "PASS: Excel Spreadsheet contains real table columns.\n";
}
