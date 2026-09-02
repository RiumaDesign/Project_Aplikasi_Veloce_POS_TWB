<?php
/**
 * Master Admin Dashboard - Front Controller
 * Veloce POS Multi-Outlet & Vending Machine System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// 1. Tangani Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout_user('login.php');
}

// 2. Proteksi Akses Khusus Admin
require_admin('login.php');

// 3. Eksekusi Aksi Controller jika ada request POST
require_once __DIR__ . '/controllers/DashboardActionHandler.php';

// 4. Tentukan Halaman Aktif (Routing)
$page = $_GET['page'] ?? 'analytics';
$allowed_pages = ['analytics', 'outlet', 'stok', 'menu', 'kasir', 'retur'];
if (!in_array($page, $allowed_pages)) {
    $page = 'analytics';
}

// 5. Render Layout & View Terpilih
require_once __DIR__ . '/views/layouts/header.php';
require_once __DIR__ . '/views/layouts/sidebar.php';

echo '<div class="flex-1 flex flex-col min-w-0 h-screen h-[100dvh] overflow-hidden">';
require_once __DIR__ . '/views/layouts/topbar.php';
echo '<main id="main-content" class="flex-1 p-3.5 sm:p-6 md:p-8 w-full overflow-y-auto min-w-0 transition-all duration-300 custom-scroll pb-24 md:pb-8" style="-webkit-overflow-scrolling: touch; touch-action: pan-y;">';
require_once __DIR__ . '/views/dashboard/' . $page . '.php';
echo '</main>';
echo '</div>';

require_once __DIR__ . '/views/layouts/footer.php';