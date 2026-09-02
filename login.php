<?php
/**
 * Halaman Otentikasi Login - Front Controller
 * Veloce POS Multi-Outlet & Vending Machine System
 * Dilengkapi Dual-Mode Switch (Kasir vs Admin) & Quick Cashier Profile Selection
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Tangani permintaan logout atau switch terminal secara eksplisit
if (isset($_GET['action']) && in_array($_GET['action'], ['logout', 'switch', 'ganti_terminal'])) {
    logout_user('login.php');
}

// Jika sudah terotentikasi, alihkan langsung
redirect_if_authenticated();

$error = "";
$login_type = $_POST['login_type'] ?? ($_GET['type'] ?? 'kasir');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = attempt_login(
        $conn, 
        $_POST['username'] ?? '', 
        $_POST['password'] ?? '', 
        $_POST['outlet_id'] ?? null
    );
    if ($res['success']) {
        header("Location: " . $res['redirect']);
        exit();
    }
    $error = $res['error'];
}

// 1. Ambil daftar petugas kasir aktif untuk pilihan profil cepat
$kasir_list_res = $conn->query("SELECT id, nama, role FROM `kasir` WHERE `role` = 'kasir' OR `role` IS NULL ORDER BY `nama` ASC");
$kasir_list = [];
if ($kasir_list_res) {
    while ($k = $kasir_list_res->fetch_assoc()) {
        $kasir_list[] = $k;
    }
}

// 2. Ambil daftar lokasi terminal aktif untuk dropdown kasir
$outlets_res = $conn->query("SELECT id, code, name, type FROM locations WHERE type IN ('outlet', 'vm', 'pos') AND status = 'active' ORDER BY type ASC, code ASC");
$outlets_list = [];
if ($outlets_res) {
    while ($l = $outlets_res->fetch_assoc()) {
        $outlets_list[] = $l;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem — TWB POS Borobudur</title>
    <link rel="icon" type="image/png" href="assets/images/logo_twb.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/glassmorphism.css?v=<?= filemtime(__DIR__ . '/assets/css/glassmorphism.css') ?>">
    <script src="assets/js/theme.js?v=<?= filemtime(__DIR__ . '/assets/js/theme.js') ?>"></script>
    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; } 
    </style>
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Top-Right Theme Toggle -->
    <div class="absolute top-5 right-5 z-20">
        <button type="button" onclick="toggleTheme()" class="theme-toggle-btn glass-card-dark text-slate-300 border border-white/10 px-3.5 py-2 rounded-2xl text-xs font-bold transition flex items-center gap-2 hover:border-blue-500/40 cursor-pointer shadow-lg" title="Ganti Tema">
            <span class="theme-toggle-icon text-sm">☀️</span>
            <span class="theme-toggle-label text-[11px]">Mode Terang</span>
        </button>
    </div>

    <!-- Ambient Glow Background -->
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <?php require_once __DIR__ . '/views/auth/login_card.php'; ?>

    <script src="assets/js/app.js"></script>
</body>
</html>