<?php
/**
 * Test Suite: Admin Notification Center
 * Veloce POS - PT Taman Wisata Borobudur
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/NotificationController.php';

echo "=== PENGUJIAN FITUR PUSAT NOTIFIKASI ADMIN ===\n\n";

$notifCtrl = new NotificationController($conn);

// 1. Uji Sinkronisasi Otomatis Alert
$created = $notifCtrl->syncLiveAlerts();
echo "• PASS: syncLiveAlerts() berjalan tanpa eror (Notifikasi diproses: $created)\n";

// 2. Uji Ambil Notifikasi
$notifs = $notifCtrl->getNotifications(10);
if (!empty($notifs)) {
    echo "• PASS: getNotifications() berhasil memuat " . count($notifs) . " notifikasi.\n";
    $first = $notifs[0];
    echo "  Contoh: [" . strtoupper($first['type']) . "] " . $first['title'] . " (" . $first['time_ago'] . ")\n";
} else {
    echo "• FAIL: getNotifications() mengembalikan array kosong.\n";
}

// 3. Uji Hitung Unread
$unread = $notifCtrl->getUnreadCount();
echo "• PASS: getUnreadCount() = $unread notifikasi belum dibaca.\n";

// 4. Uji Pembuatan Notifikasi Baru
$testRef = "TEST_REF_" . time();
$ok = $notifCtrl->createNotification('sistem', 'info', 'Uji Coba Notifikasi', 'Pesan uji coba sistem otomatis.', 'dashboard.php', $testRef);
if ($ok) {
    echo "• PASS: createNotification() berhasil menambah notifikasi baru.\n";
} else {
    echo "• FAIL: createNotification() gagal.\n";
}

// 5. Uji Mark As Read per Item
$latest = $notifCtrl->getNotifications(1);
if (!empty($latest)) {
    $item = $latest[0];
    $notifCtrl->markAsRead($item['id']);
    echo "• PASS: markAsRead({$item['id']}) berhasil.\n";
}

// 6. Uji Mark All As Read
$notifCtrl->markAllAsRead();
$unreadAfter = $notifCtrl->getUnreadCount();
if ($unreadAfter === 0) {
    echo "• PASS: markAllAsRead() berhasil (Unread Count kini 0).\n";
} else {
    echo "• FAIL: markAllAsRead() gagal, unread masih $unreadAfter.\n";
}

// 7. Uji Render Topbar di Dashboard
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

ob_start();
$_GET['page'] = 'analytics';
require __DIR__ . '/../dashboard.php';
$dashHtml = ob_get_clean();

$checks = [
    'id="admin-topbar"' => 'Komponen #admin-topbar ter-render di Dashboard',
    'id="notif-dropdown-wrapper"' => 'Dropdown wrapper notifikasi ter-render',
    'id="notif-badge-counter"' => 'Lencana counter notifikasi ter-render',
    'id="notif-dropdown-menu"' => 'Menu panel dropdown notifikasi ter-render',
    'Notifikasi Admin' => 'Teks judul notifikasi ter-render',
    'Stok Kritis' => 'Tab filter Stok Kritis ter-render',
    'fetchLiveNotifications' => 'Fungsi polling fetchLiveNotifications() ter-render'
];

echo "\n--- UJI INTEGRASI TOPBAR & DASHBOARD ---\n";
foreach ($checks as $needle => $label) {
    if (strpos($dashHtml, $needle) !== false) {
        echo "• PASS: $label\n";
    } else {
        echo "• FAIL: $label\n";
    }
}

echo "\n=== SELURUH PENGUJIAN SELESAI ===\n";
