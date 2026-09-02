<?php
/**
 * Terminal Kasir POS - Front Controller
 * Veloce POS Multi-Outlet & Vending Machine System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// 1. Proteksi Akses Kasir
require_kasir('login.php');

$outlet_id = $_SESSION['outlet_id'] ?? 2;
$pos_aktif = $_SESSION['pos_aktif'] ?? 'Outlet Museum Samudra Raksa';
$kasir_aktif = $_SESSION['kasir_nama'] ?? 'Kasir';
$hari_ini = date('Y-m-d');

// 2. Tangani Ganti Terminal Cepat Tanpa Logout
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ganti_terminal' && !empty($_POST['new_outlet_id'])) {
    $new_id = intval($_POST['new_outlet_id']);
    $q_loc = $conn->query("SELECT id, name FROM `locations` WHERE `id` = $new_id AND `status` = 'active' LIMIT 1");
    if ($q_loc && $q_loc->num_rows > 0) {
        $loc_data = $q_loc->fetch_assoc();
        $_SESSION['outlet_id'] = intval($loc_data['id']);
        $_SESSION['pos_aktif'] = $loc_data['name'];
    }
    header("Location: index.php");
    exit();
}

// 2b. Tangani Permintaan Logout Langsung
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout_user('login.php');
}

// 3. Tangani Transaksi AJAX
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_transaksi') {
    require_once __DIR__ . '/controllers/TransactionController.php';
    $hasil = proses_transaksi_kasir($conn, $kasir_aktif, $outlet_id, $pos_aktif, $_POST);
    header('Content-Type: application/json');
    echo json_encode($hasil);
    exit();
}

// 3. Ambil Katalog Produk Sesuai Hak Akses Outlet Ini
$produk_res = $conn->query("
    SELECT p.*, COALESCE(sl.quantity, 0) as stok_lokasi
    FROM `produk` p
    JOIN `product_outlets` po ON p.id = po.product_id AND po.outlet_id = $outlet_id
    LEFT JOIN `stok_lokasi` sl ON p.id = sl.product_id AND sl.location_id = $outlet_id
    ORDER BY p.nama ASC
");

// 4. Data Ringkasan Shift Hari Ini
$summary_tx = $conn->query("
    SELECT COUNT(*) as total_nota, COALESCE(SUM(total_harga), 0) as grand_total 
    FROM `transaksi` 
    WHERE `petugas` = '$kasir_aktif' AND `outlet_id` = $outlet_id AND `tanggal` = '$hari_ini'
");
$sum_row = $summary_tx->fetch_assoc();
$total_nota = intval($sum_row['total_nota'] ?? 0);
$grand_total = intval($sum_row['grand_total'] ?? 0);

// 5. Ambil Daftar Terminal Aktif untuk Modal Ganti Terminal
$terminals_q = $conn->query("SELECT id, code, name, type FROM `locations` WHERE `type` IN ('outlet', 'vm', 'pos') AND `status` = 'active' ORDER BY `type` ASC, `code` ASC");
$terminal_list = [];
if ($terminals_q) {
    while ($t = $terminals_q->fetch_assoc()) {
        $terminal_list[] = $t;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TWB POS — <?= htmlspecialchars($pos_aktif) ?></title>
    <link rel="icon" type="image/png" href="assets/images/logo_twb.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/glassmorphism.css?v=<?= filemtime(__DIR__ . '/assets/css/glassmorphism.css') ?>">
    <script src="assets/js/theme.js?v=<?= filemtime(__DIR__ . '/assets/js/theme.js') ?>"></script>
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            body * { visibility: hidden; }
            #area-cetak-nota, #area-cetak-nota * { visibility: visible; }
            #area-cetak-nota {
                position: absolute;
                left: 0;
                top: 0;
                width: 76mm;
                max-width: 76mm;
                margin: 0 auto;
                padding: 4mm 6mm;
                background: #ffffff !important;
                color: #000000 !important;
                font-family: 'Courier New', Courier, monospace;
            }
            #area-cetak-nota img {
                filter: grayscale(100%) contrast(200%);
                max-height: 42px !important;
                display: block;
                margin: 0 auto 6px auto;
            }
        }
        /* Custom smooth scrollbar */
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.4);
            border-radius: 8px;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.4);
            border-radius: 8px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.8);
        }

        /* Direct Light Theme Overrides - Solid & High Contrast Buttons */
        html[data-theme="light"], html.light { color-scheme: light; }
        html[data-theme="light"] body, html.light body, body.light-theme { background-color: #f1f5f9 !important; color: #0f172a !important; }
        html[data-theme="light"] header, html.light header, html[data-theme="light"] aside, html.light aside { background-color: #ffffff !important; border-color: #e2e8f0 !important; color: #0f172a !important; }
        html[data-theme="light"] .bg-slate-950, html.light .bg-slate-950, html[data-theme="light"] .bg-slate-900, html.light .bg-slate-900 { background-color: #ffffff !important; }
        html[data-theme="light"] .glass-card-dark, html.light .glass-card-dark { background: #ffffff !important; border-color: #e2e8f0 !important; box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important; color: #0f172a !important; }
        html[data-theme="light"] .item-produk, html.light .item-produk { background: #ffffff !important; border: 1px solid #e2e8f0 !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05) !important; }
        html[data-theme="light"] .item-produk:hover, html.light .item-produk:hover { border-color: #2563eb !important; box-shadow: 0 10px 20px -3px rgba(37,99,235,0.15) !important; }
        
        /* Tombol Beli (+ Beli) Solid Biru Kontras Tinggi */
        html[data-theme="light"] .item-produk button:not(:disabled), html.light .item-produk button:not(:disabled),
        html[data-theme="light"] .bg-blue-600\/20, html.light .bg-blue-600\/20 {
            background-color: #2563eb !important; color: #ffffff !important; border: 1px solid #1d4ed8 !important; font-weight: 700 !important; box-shadow: 0 2px 8px rgba(37,99,235,0.25) !important;
        }
        html[data-theme="light"] .item-produk button:not(:disabled):hover, html.light .item-produk button:not(:disabled):hover {
            background-color: #1d4ed8 !important; box-shadow: 0 4px 12px rgba(37,99,235,0.4) !important;
        }

        /* Tombol Kosong / Disabled */
        html[data-theme="light"] button:disabled, html.light button:disabled,
        html[data-theme="light"] .bg-slate-800:disabled, html.light .bg-slate-800:disabled {
            background-color: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #94a3b8 !important; cursor: not-allowed !important; box-shadow: none !important;
        }

        /* Tombol Aksi Netral (Ganti Terminal, CSV, Cetak) */
        html[data-theme="light"] .bg-slate-800:not(:disabled), html.light .bg-slate-800:not(:disabled),
        html[data-theme="light"] button.bg-slate-800, html.light button.bg-slate-800 {
            background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #1e293b !important; font-weight: 700 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
        }
        html[data-theme="light"] .bg-slate-800:not(:disabled):hover, html.light .bg-slate-800:not(:disabled):hover,
        html[data-theme="light"] button.bg-slate-800:hover, html.light button.bg-slate-800:hover {
            background-color: #f1f5f9 !important; border-color: #94a3b8 !important; color: #0f172a !important;
        }

        /* Tombol Keluar / Hapus Merah */
        html[data-theme="light"] .bg-rose-500\/10, html.light .bg-rose-500\/10 {
            background-color: #fee2e2 !important; border: 1px solid #fca5a5 !important; color: #b91c1c !important; font-weight: 700 !important;
        }
        html[data-theme="light"] .bg-rose-500\/10:hover, html.light .bg-rose-500\/10:hover {
            background-color: #fecaca !important; border-color: #ef4444 !important;
        }

        /* Tombol Ubah Kuning */
        html[data-theme="light"] .bg-amber-500\/10, html.light .bg-amber-500\/10 {
            background-color: #fef3c7 !important; border: 1px solid #fcd34d !important; color: #92400e !important; font-weight: 700 !important;
        }

        /* Segmented Theme Switcher */
        html[data-theme="light"] .theme-toggle-btn, html.light .theme-toggle-btn {
            background-color: #e2e8f0 !important; border: 1px solid #cbd5e1 !important; box-shadow: inset 0 1px 3px rgba(0,0,0,0.08) !important;
        }
        html[data-theme="light"] .theme-pill-light, html.light .theme-pill-light {
            background-color: #ffffff !important; color: #0f172a !important; border: 1px solid #cbd5e1 !important; box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important; font-weight: 800 !important;
        }
        html[data-theme="light"] .theme-pill-dark, html.light .theme-pill-dark {
            background-color: transparent !important; color: #64748b !important; box-shadow: none !important;
        }

        /* Teks Kontras Tinggi */
        html[data-theme="light"] .text-white, html.light .text-white, html[data-theme="light"] .text-slate-100, html.light .text-slate-100 { color: #0f172a !important; }
        html[data-theme="light"] .text-slate-300, html.light .text-slate-300, html[data-theme="light"] .text-slate-400, html.light .text-slate-400 { color: #475569 !important; }
        html[data-theme="light"] .text-slate-500, html.light .text-slate-500 { color: #64748b !important; }
        html[data-theme="light"] .text-blue-400, html.light .text-blue-400 { color: #2563eb !important; font-weight: 800 !important; }
        html[data-theme="light"] .text-emerald-400, html.light .text-emerald-400 { color: #059669 !important; font-weight: 800 !important; }
        
        /* Tombol & Elemen Solid */
        html[data-theme="light"] .bg-blue-600, html.light .bg-blue-600, html[data-theme="light"] .bg-blue-600 *, html.light .bg-blue-600 * { color: #ffffff !important; }
        html[data-theme="light"] .bg-emerald-600, html.light .bg-emerald-600, html[data-theme="light"] .bg-emerald-600 *, html.light .bg-emerald-600 * { color: #ffffff !important; }
        html[data-theme="light"] .border-white\/5, html.light .border-white\/5, html[data-theme="light"] .border-white\/10, html.light .border-white\/10 { border-color: #e2e8f0 !important; }
        html[data-theme="light"] input, html.light input, html[data-theme="light"] select, html.light select { background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important; }

        /* Badge Status & Pills Kontras Tinggi */
        html[data-theme="light"] .bg-amber-500\/20, html.light .bg-amber-500\/20,
        html[data-theme="light"] .bg-amber-500\/10, html.light .bg-amber-500\/10 { background-color: #fef3c7 !important; border: 1px solid #fcd34d !important; color: #92400e !important; }
        html[data-theme="light"] .text-amber-300, html.light .text-amber-300,
        html[data-theme="light"] .text-amber-400, html.light .text-amber-400 { color: #92400e !important; font-weight: 800 !important; }
        html[data-theme="light"] .bg-blue-500\/20, html.light .bg-blue-500\/20,
        html[data-theme="light"] .bg-blue-500\/10, html.light .bg-blue-500\/10 { background-color: #eff6ff !important; border: 1px solid #bfdbfe !important; color: #1e40af !important; }
        html[data-theme="light"] .text-blue-300, html.light .text-blue-300,
        html[data-theme="light"] .text-blue-400, html.light .text-blue-400 { color: #1e40af !important; font-weight: 800 !important; }
        html[data-theme="light"] .bg-rose-500\/20, html.light .bg-rose-500\/20,
        html[data-theme="light"] .bg-rose-500\/10, html.light .bg-rose-500\/10 { background-color: #fff1f2 !important; border: 1px solid #fecdd3 !important; color: #9f1239 !important; }
        html[data-theme="light"] .text-rose-300, html.light .text-rose-300,
        html[data-theme="light"] .text-rose-400, html.light .text-rose-400 { color: #9f1239 !important; font-weight: 800 !important; }
        html[data-theme="light"] .bg-emerald-500\/20, html.light .bg-emerald-500\/20,
        html[data-theme="light"] .bg-emerald-500\/10, html.light .bg-emerald-500\/10 { background-color: #ecfdf5 !important; border: 1px solid #a7f3d0 !important; color: #065f46 !important; }
        html[data-theme="light"] .text-emerald-300, html.light .text-emerald-300,
        html[data-theme="light"] .text-emerald-400, html.light .text-emerald-400 { color: #065f46 !important; font-weight: 800 !important; }

        /* ============================================================= */
        /* OPTIMALISASI PENUH KERANJANG BELANJA (CART SIDEBAR)          */
        /* ============================================================= */
        html[data-theme="light"] aside.cart-sidebar-panel,
        html.light aside.cart-sidebar-panel,
        html[data-theme="light"] aside,
        html.light aside {
            background-color: #ffffff !important;
            border-left: 1px solid #e2e8f0 !important;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.04) !important;
        }
        html[data-theme="light"] .cart-header-panel,
        html.light .cart-header-panel {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }
        html[data-theme="light"] .cart-header-panel h2,
        html.light .cart-header-panel h2 {
            color: #0f172a !important;
        }
        html[data-theme="light"] #cart-item-count,
        html.light #cart-item-count {
            color: #64748b !important;
        }
        html[data-theme="light"] #cart-items-wrapper,
        html.light #cart-items-wrapper {
            background-color: #f8fafc !important;
        }
        html[data-theme="light"] #empty-cart-state p,
        html.light #empty-cart-state p {
            color: #64748b !important;
        }
        html[data-theme="light"] #cart-items-wrapper h4,
        html.light #cart-items-wrapper h4 {
            color: #0f172a !important;
        }
        html[data-theme="light"] #cart-items-wrapper p,
        html.light #cart-items-wrapper p {
            color: #64748b !important;
        }
        html[data-theme="light"] #cart-items-wrapper .bg-slate-950,
        html.light #cart-items-wrapper .bg-slate-950 {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
        }
        html[data-theme="light"] #cart-items-wrapper button,
        html.light #cart-items-wrapper button {
            color: #1e293b !important;
        }
        html[data-theme="light"] #cart-items-wrapper button:hover,
        html.light #cart-items-wrapper button:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
        html[data-theme="light"] #cart-items-wrapper span.text-emerald-400,
        html.light #cart-items-wrapper span.text-emerald-400 {
            color: #059669 !important;
        }

        /* Panel Checkout Bawah (Menghilangkan Kontras Gelap / Hitam pada Latar Abu-abu) */
        html[data-theme="light"] #cart-checkout-box,
        html.light #cart-checkout-box,
        html[data-theme="light"] .cart-checkout-panel,
        html.light .cart-checkout-panel,
        html[data-theme="light"] .bg-slate-950\/60,
        html.light .bg-slate-950\/60 {
            background-color: #ffffff !important;
            border-top: 1px solid #e2e8f0 !important;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05) !important;
        }
        html[data-theme="light"] .label-subtotal,
        html.light .label-subtotal,
        html[data-theme="light"] #cart-checkout-box span.text-slate-400,
        html.light #cart-checkout-box span.text-slate-400 {
            color: #64748b !important;
            font-weight: 600 !important;
        }
        html[data-theme="light"] #cart-subtotal,
        html.light #cart-subtotal {
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        html[data-theme="light"] .border-divider-subtotal,
        html.light .border-divider-subtotal,
        html[data-theme="light"] #cart-checkout-box .border-white\/5,
        html.light #cart-checkout-box .border-white\/5 {
            border-color: #e2e8f0 !important;
        }
        html[data-theme="light"] .label-total-tagihan,
        html.light .label-total-tagihan,
        html[data-theme="light"] #cart-checkout-box span.text-white,
        html.light #cart-checkout-box span.text-white {
            color: #0f172a !important;
            font-weight: 900 !important;
        }
        html[data-theme="light"] #cart-grand-total,
        html.light #cart-grand-total {
            color: #059669 !important;
            font-weight: 900 !important;
        }
        html[data-theme="light"] #btn-checkout:not(:disabled),
        html.light #btn-checkout:not(:disabled) {
            background-color: #2563eb !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important;
        }
        html[data-theme="light"] #btn-checkout:not(:disabled):hover,
        html.light #btn-checkout:not(:disabled):hover {
            background-color: #1d4ed8 !important;
        }
        html[data-theme="light"] #btn-checkout:disabled,
        html.light #btn-checkout:disabled {
            background-color: #f1f5f9 !important;
            color: #94a3b8 !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: none !important;
        }

        /* ============================================================= */
        /* MODAL PEMBAYARAN & MODAL STRUK PADA MODE TERANG              */
        /* ============================================================= */
        html[data-theme="light"] #modal-bayar .glass-card-dark,
        html.light #modal-bayar .glass-card-dark {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        }
        html[data-theme="light"] #modal-bayar h3,
        html.light #modal-bayar h3 {
            color: #0f172a !important;
        }
        html[data-theme="light"] #modal-bayar .bg-blue-600\/10,
        html.light #modal-bayar .bg-blue-600\/10 {
            background-color: #eff6ff !important;
            border: 1px solid #bfdbfe !important;
        }
        html[data-theme="light"] #modal-bayar #modal-total-display,
        html.light #modal-bayar #modal-total-display {
            color: #059669 !important;
            font-weight: 900 !important;
        }
        html[data-theme="light"] #modal-bayar #btn-metode-cash,
        html.light #modal-bayar #btn-metode-cash {
            background-color: #eff6ff !important;
            border: 1.5px solid #2563eb !important;
            color: #1d4ed8 !important;
        }
        html[data-theme="light"] #modal-bayar #btn-metode-qris,
        html.light #modal-bayar #btn-metode-qris {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
        }
        html[data-theme="light"] #modal-bayar #nominal-bayar,
        html.light #modal-bayar #nominal-bayar {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
        }
        html[data-theme="light"] #modal-bayar #kembalian-display,
        html.light #modal-bayar #kembalian-display {
            color: #0f172a !important;
            font-weight: 800 !important;
        }
        html[data-theme="light"] #modal-bayar #btn-proses-transaksi,
        html.light #modal-bayar #btn-proses-transaksi {
            background-color: #059669 !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3) !important;
        }
        html[data-theme="light"] #modal-bayar #btn-proses-transaksi:hover,
        html.light #modal-bayar #btn-proses-transaksi:hover {
            background-color: #047857 !important;
        }

        /* Badge Sisa Stok Produk di Tema Terang */
        html[data-theme="light"] .badge-sisa-stok,
        html.light .badge-sisa-stok,
        html[data-theme="light"] .item-produk .badge-sisa-stok,
        html.light .item-produk .badge-sisa-stok {
            background-color: #ecfdf5 !important;
            border: 1.5px solid #a7f3d0 !important;
            color: #065f46 !important;
            font-weight: 800 !important;
            box-shadow: 0 2px 4px rgba(6, 95, 70, 0.12) !important;
        }

        /* Kotak Pembungkus Gambar Produk Bersih */
        html[data-theme="light"] .product-img-wrapper,
        html.light .product-img-wrapper,
        html[data-theme="light"] .item-produk .aspect-square,
        html.light .item-produk .aspect-square {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
        }

        /* Overlay & Badge Stok Habis di Mode Terang */
        html[data-theme="light"] .product-empty-overlay,
        html.light .product-empty-overlay {
            background-color: rgba(255, 255, 255, 0.88) !important;
        }
        html[data-theme="light"] .badge-stok-habis,
        html.light .badge-stok-habis {
            background-color: #fee2e2 !important;
            border: 1.5px solid #fca5a5 !important;
            color: #9f1239 !important;
            font-weight: 900 !important;
            box-shadow: 0 2px 5px rgba(185, 28, 28, 0.12) !important;
        }

        /* Judul & Harga Produk di Mode Terang */
        html[data-theme="light"] .item-produk h3,
        html.light .item-produk h3 {
            color: #0f172a !important;
            font-weight: 800 !important;
        }
        html[data-theme="light"] .item-produk p.text-blue-400,
        html.light .item-produk p.text-blue-400 {
            color: #1d4ed8 !important;
            font-weight: 900 !important;
        }

        /* Input Pencarian Produk */
        html[data-theme="light"] #cari-produk,
        html.light #cari-produk {
            background-color: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #0f172a !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        }
        html[data-theme="light"] #cari-produk::placeholder,
        html.light #cari-produk::placeholder {
            color: #94a3b8 !important;
        }

        /* Stepper Keranjang Belanja (+/- Item) di Mode Terang */
        html[data-theme="light"] .cart-stepper,
        html.light .cart-stepper {
            background-color: #f1f5f9 !important;
            border: 1px solid #cbd5e1 !important;
        }
        html[data-theme="light"] .cart-stepper button,
        html.light .cart-stepper button {
            color: #334155 !important;
        }
        html[data-theme="light"] .cart-stepper button:hover,
        html.light .cart-stepper button:hover {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
        html[data-theme="light"] .cart-stepper span,
        html.light .cart-stepper span {
            color: #0f172a !important;
            font-weight: 800 !important;
        }

        /* Mini Summary Badge Navbar di Mode Terang */
        html[data-theme="light"] header .bg-white\/5,
        html.light header .bg-white\/5 {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased h-screen h-[100dvh] flex flex-col overflow-hidden select-none">
    
    <!-- 1. Header / Navbar Kasir -->
    <?php require_once __DIR__ . '/views/pos/navbar.php'; ?>

    <!-- 2. Konten Utama: Product Grid & Cart Sidebar -->
    <div class="flex-1 flex overflow-hidden min-h-0">
        <?php require_once __DIR__ . '/views/pos/product_grid.php'; ?>
        <?php require_once __DIR__ . '/views/pos/cart_sidebar.php'; ?>
    </div>

    <!-- 3. Modal Pembayaran & Cetak Nota -->
    <?php require_once __DIR__ . '/views/pos/modal_payment.php'; ?>

    <!-- 4. Modal Ganti Terminal POS -->
    <?php require_once __DIR__ . '/views/pos/modal_terminal.php'; ?>

    <!-- 5. Modal Konfirmasi & Alert Kustom -->
    <?php require_once __DIR__ . '/views/layouts/modal_custom.php'; ?>

    <!-- 6. Skrip Eksternal -->
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <script src="assets/js/pos.js?v=<?= filemtime(__DIR__ . '/assets/js/pos.js') ?>"></script>
    <script>
        function bukaModal(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.remove('hidden'); el.classList.add('flex'); }
        }
        function tutupModal(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.add('hidden'); el.classList.remove('flex'); }
        }
    </script>
</body>
</html>