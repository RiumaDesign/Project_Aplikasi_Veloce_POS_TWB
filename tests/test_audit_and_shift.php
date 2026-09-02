<?php
// Unit Test: Verifikasi Penghapusan Kas Diharapkan, Audit Log Ekspor & Layout
session_start();
$_SESSION['admin_nama'] = 'Admin Khusus';
$_SESSION['admin_id'] = 1;

require_once __DIR__ . '/../config/database.php';

// 1. Verifikasi Modal Tutup Shift Kasir
$modalShiftContent = file_get_contents(__DIR__ . '/../views/pos/modal_closing_shift.php');

$hasTargetKasBox = (strpos($modalShiftContent, 'Target Kas Diharapkan:') !== false);
echo "1. Modal Closing: Kotak 'Target Kas Diharapkan' sudah dihapus: " . (!$hasTargetKasBox ? "PASS [OK]" : "FAIL") . "\n";

$hasTargetStruk = (strpos($modalShiftContent, 'Target Uang Tunai:') !== false);
echo "2. Struk Thermal: 'Target Uang Tunai' sudah dihapus: " . (!$hasTargetStruk ? "PASS [OK]" : "FAIL") . "\n";

$hasStatusSelisih = (strpos($modalShiftContent, 'Status Selisih Kas:') !== false);
echo "3. Modal Closing: Status Selisih Kas mandiri aktif: " . ($hasStatusSelisih ? "PASS [OK]" : "FAIL") . "\n";

// 2. Verifikasi Audit Log Ekspor
$logRes = $conn->query("SELECT COUNT(*) FROM `export_logs`");
$logCount = $logRes ? intval($logRes->fetch_row()[0]) : 0;
echo "4. Database: Jumlah riwayat export_logs: " . $logCount . " (" . ($logCount > 0 ? "PASS [OK]" : "WARN") . ")\n";

// 3. Verifikasi analytics.php memiliki query $export_logs_res
$analyticsContent = file_get_contents(__DIR__ . '/../views/dashboard/analytics.php');
$hasExportLogsQuery = (strpos($analyticsContent, '$export_logs_res = $conn->query') !== false);
echo "5. Analytics View: Query export_logs_res aktif: " . ($hasExportLogsQuery ? "PASS [OK]" : "FAIL") . "\n";

// 4. Verifikasi Chart Legend Strip adaptif
$hasLegendStrip = (strpos($analyticsContent, 'chart-legend-strip') !== false);
echo "6. Analytics View: Class chart-legend-strip adaptif aktif: " . ($hasLegendStrip ? "PASS [OK]" : "FAIL") . "\n";

// 5. Verifikasi Live Theme Changed listener di Chart.js
$hasThemeListener = (strpos($analyticsContent, 'themeChanged') !== false);
echo "7. Analytics View: Listener live themeChanged aktif: " . ($hasThemeListener ? "PASS [OK]" : "FAIL") . "\n";
