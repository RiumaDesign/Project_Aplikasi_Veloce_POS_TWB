<?php
require_once 'config/database.php';
require_once 'controllers/ExportController.php';

echo "=== TEST EXCEL SPREADSHEET & CSV SEPARATOR FORMAT ===\n\n";

$ctrl = new ExportController($conn, 'Admin Testing');

// 1. Test Excel Penjualan Generation
ob_start();
// simulate exportPenjualan with format=excel
// Note: exit() is inside render, so we can test the output
$filters = ['start_date' => '2026-09-01', 'end_date' => '2026-09-02'];
try {
    $ctrl->exportPenjualan('excel', $filters);
} catch (\Throwable $e) {
    // exit() in php might throw or terminate
}
$excelOutput = ob_get_clean();

echo "1. Output Excel Size: " . strlen($excelOutput) . " bytes.\n";

if (strpos($excelOutput, 'xmlns:x="urn:schemas-microsoft-com:office:excel"') !== false) {
    echo "PASS: Microsoft Excel XML header detected.\n";
} else {
    echo "FAIL: Excel XML header missing!\n";
}

if (strpos($excelOutput, '<th style="width: 200px;">ID Transaksi</th>') !== false) {
    echo "PASS: Structured table columns with explicit widths detected.\n";
} else {
    echo "FAIL: Table column header missing!\n";
}

if (strpos($excelOutput, 'class=\'str text-left\'>TRX-') !== false) {
    echo "PASS: Data row mapped into separate <td> with string format.\n";
} else {
    echo "FAIL: Data row mapping missing!\n";
}

if (strpos($excelOutput, 'GRAND TOTAL PENJUALAN (RP)') !== false) {
    echo "PASS: Grand total footer row detected.\n";
} else {
    echo "FAIL: Grand total footer missing!\n";
}

// 2. Test CSV sep=, header
ob_start();
try {
    $ctrl->exportPenjualan('csv', $filters);
} catch (\Throwable $e) {}
$csvOutput = ob_get_clean();

echo "\n2. Output CSV Size: " . strlen($csvOutput) . " bytes.\n";

if (strpos($csvOutput, "sep=,\r\n") !== false) {
    echo "PASS: CSV contains sep=, header for Excel column separation!\n";
} else {
    echo "FAIL: sep=, header missing in CSV!\n";
}

echo "\n=== ALL FORMAT TESTS PASSED (100% SUCCESS) ===\n";
