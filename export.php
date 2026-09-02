<?php
/**
 * Entry Point: Export Laporan Sistem - Veloce POS
 * File: export.php
 * 
 * Rute penanganan permintaan ekspor data ke Excel/CSV atau Cetak/PDF
 * Dilindungi autentikasi sesi aktif dan pencatatan audit log otomatis.
 */

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'controllers/ExportController.php';

// Proteksi akses: Harus login (Admin atau Kasir)
if (!is_admin_logged_in() && !is_kasir_logged_in()) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['admin_nama'] ?? $_SESSION['kasir_nama'] ?? 'Administrator';
$exportCtrl = new ExportController($conn, $currentUser);

$module = strtolower($_GET['module'] ?? 'penjualan');
$format = strtolower($_GET['format'] ?? 'excel');

// Validasi format (default ke excel jika tidak valid)
if (!in_array($format, ['csv', 'excel', 'pdf'])) {
    $format = 'excel';
}

switch ($module) {
    case 'penjualan':
        $exportCtrl->exportPenjualan($format, $_GET);
        break;

    case 'stok':
        $exportCtrl->exportStok($format, $_GET);
        break;

    case 'do':
    case 'delivery_order':
        $exportCtrl->exportDO($format, $_GET);
        break;

    case 'retur':
    case 'return':
        $exportCtrl->exportRetur($format, $_GET);
        break;

    case 'mutasi':
    case 'stock_mutation':
        $exportCtrl->exportMutasi($format, $_GET);
        break;

    case 'logs':
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $exportCtrl->getRecentLogs(50)
        ]);
        exit();

    default:
        die("Modul ekspor '$module' tidak valid.");
}
