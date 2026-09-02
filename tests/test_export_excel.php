<?php
session_start();
$_SESSION['admin_nama'] = 'Admin Khusus';
$_SESSION['admin_id'] = 1;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ExportController.php';

$ctrl = new ExportController($conn, 'Admin Khusus');

// Test Stok
ob_start();
$ctrl->exportStok('excel', []);
$stkExcel = ob_get_clean();
echo "Stok Excel length: " . strlen($stkExcel) . " bytes\n";

// Test DO
ob_start();
$ctrl->exportDO('excel', []);
$doExcel = ob_get_clean();
echo "DO Excel length: " . strlen($doExcel) . " bytes\n";

// Test Retur
ob_start();
$ctrl->exportRetur('excel', []);
$returExcel = ob_get_clean();
echo "Retur Excel length: " . strlen($returExcel) . " bytes\n";

// Test Mutasi
ob_start();
$ctrl->exportMutasi('excel', []);
$mutasiExcel = ob_get_clean();
echo "Mutasi Excel length: " . strlen($mutasiExcel) . " bytes\n";
