<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TWB Admin Khusus — Master Multi-Outlet & Vending Machine Borobudur</title>
    <link rel="icon" type="image/png" href="assets/images/logo_twb.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/glassmorphism.css?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/css/glassmorphism.css') ?>">
    <script src="assets/js/theme.js?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/js/theme.js') ?>"></script>
    <script src="assets/js/app.js?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/js/app.js') ?>"></script>
    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; } 
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }

        /* Toggle Sidebar Collapsed State */
        #sidebar-nav.sidebar-collapsed {
            display: none !important;
        }

        /* Direct Light Theme Overrides - Solid & High Contrast */
        html[data-theme="light"], html.light { color-scheme: light; }
        html[data-theme="light"] body, html.light body, body.light-theme { background-color: #f1f5f9 !important; color: #0f172a !important; }
        html[data-theme="light"] aside, html.light aside, html[data-theme="light"] header, html.light header { background-color: #ffffff !important; border-color: #e2e8f0 !important; color: #0f172a !important; }
        html[data-theme="light"] .bg-slate-900, html.light .bg-slate-900, html[data-theme="light"] .bg-slate-950, html.light .bg-slate-950 { background-color: #ffffff !important; }
        html[data-theme="light"] .glass-card-dark, html.light .glass-card-dark { background: #ffffff !important; border: 1px solid #e2e8f0 !important; box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important; color: #0f172a !important; }
        html[data-theme="light"] .text-white, html.light .text-white, html[data-theme="light"] .text-slate-100, html.light .text-slate-100 { color: #0f172a !important; }
        html[data-theme="light"] .text-slate-300, html.light .text-slate-300, html[data-theme="light"] .text-slate-400, html.light .text-slate-400 { color: #475569 !important; }
        
        /* ========================================================================= */
        /* BADGE STATUS & PILL KONTRAS TINGGI DI SEMUA MENU (LIGHT THEME OPTIMIZED) */
        /* ========================================================================= */
        
        /* 1. KUNING / AMBER (10 Outlet Tertentu, Expired, Adjustment, Ubah) */
        html[data-theme="light"] .bg-amber-500\/20, html.light .bg-amber-500\/20,
        html[data-theme="light"] .bg-amber-500\/10, html.light .bg-amber-500\/10,
        html[data-theme="light"] .bg-yellow-500\/20, html.light .bg-yellow-500\/20 { 
            background-color: #fef3c7 !important; 
            border: 1px solid #fcd34d !important; 
            color: #92400e !important; 
        }
        html[data-theme="light"] .text-amber-300, html.light .text-amber-300,
        html[data-theme="light"] .text-amber-400, html.light .text-amber-400,
        html[data-theme="light"] .text-yellow-300, html.light .text-yellow-300,
        html[data-theme="light"] .text-yellow-400, html.light .text-yellow-400 { 
            color: #92400e !important; 
            font-weight: 800 !important; 
        }

        /* 2. BIRU (Semua Outlet Global, Admin/Owner, DO Transfer, Titik Lokasi) */
        html[data-theme="light"] .bg-blue-500\/20, html.light .bg-blue-500\/20,
        html[data-theme="light"] .bg-blue-500\/10, html.light .bg-blue-500\/10,
        html[data-theme="light"] .bg-blue-600\/20, html.light .bg-blue-600\/20 { 
            background-color: #eff6ff !important; 
            border: 1px solid #bfdbfe !important; 
            color: #1e40af !important; 
        }
        html[data-theme="light"] .text-blue-300, html.light .text-blue-300,
        html[data-theme="light"] .text-blue-400, html.light .text-blue-400 { 
            color: #1e40af !important; 
            font-weight: 800 !important; 
        }

        /* 3. HIJAU / EMERALD (Kasir Terminal, Outlet Fisik, Aktif, Inbound) */
        html[data-theme="light"] .bg-emerald-500\/20, html.light .bg-emerald-500\/20,
        html[data-theme="light"] .bg-emerald-500\/10, html.light .bg-emerald-500\/10,
        html[data-theme="light"] .bg-emerald-600\/20, html.light .bg-emerald-600\/20 { 
            background-color: #ecfdf5 !important; 
            border: 1px solid #a7f3d0 !important; 
            color: #065f46 !important; 
        }
        html[data-theme="light"] .text-emerald-300, html.light .text-emerald-300,
        html[data-theme="light"] .text-emerald-400, html.light .text-emerald-400 { 
            color: #065f46 !important; 
            font-weight: 800 !important; 
        }

        /* 4. MERAH / ROSE (Rusak Fisik, Tidak Aktif, Stok Habis, Nonaktif, Hapus) */
        html[data-theme="light"] .bg-rose-500\/20, html.light .bg-rose-500\/20,
        html[data-theme="light"] .bg-rose-500\/10, html.light .bg-rose-500\/10,
        html[data-theme="light"] .bg-red-500\/20, html.light .bg-red-500\/20 { 
            background-color: #fff1f2 !important; 
            border: 1px solid #fecdd3 !important; 
            color: #9f1239 !important; 
        }
        html[data-theme="light"] .text-rose-300, html.light .text-rose-300,
        html[data-theme="light"] .text-rose-400, html.light .text-rose-400 { 
            color: #9f1239 !important; 
            font-weight: 800 !important; 
        }

        /* 5. UNGU / INDIGO / VIOLET (Vending Machine, Penjualan Kasir) */
        html[data-theme="light"] .bg-indigo-500\/20, html.light .bg-indigo-500\/20,
        html[data-theme="light"] .bg-indigo-500\/10, html.light .bg-indigo-500\/10,
        html[data-theme="light"] .bg-violet-500\/20, html.light .bg-violet-500\/20,
        html[data-theme="light"] .bg-purple-500\/20, html.light .bg-purple-500\/20 { 
            background-color: #f5f3ff !important; 
            border: 1px solid #ddd6fe !important; 
            color: #5b21b6 !important; 
        }
        html[data-theme="light"] .text-indigo-300, html.light .text-indigo-300,
        html[data-theme="light"] .text-indigo-400, html.light .text-indigo-400,
        html[data-theme="light"] .text-violet-300, html.light .text-violet-300,
        html[data-theme="light"] .text-violet-400, html.light .text-violet-400,
        html[data-theme="light"] .text-purple-300, html.light .text-purple-300,
        html[data-theme="light"] .text-purple-400, html.light .text-purple-400 { 
            color: #5b21b6 !important; 
            font-weight: 800 !important; 
        }

        /* 6. ABU-ABU / SLATE (Netral & Tipe Umum) */
        html[data-theme="light"] .bg-slate-500\/20, html.light .bg-slate-500\/20,
        html[data-theme="light"] .bg-slate-700\/50, html.light .bg-slate-700\/50 { 
            background-color: #f1f5f9 !important; 
            border: 1px solid #cbd5e1 !important; 
            color: #334155 !important; 
        }

        /* Border Pill & Badges */
        html[data-theme="light"] .border-amber-500\/30, html.light .border-amber-500\/30 { border-color: #fcd34d !important; }
        html[data-theme="light"] .border-blue-500\/30, html.light .border-blue-500\/30 { border-color: #bfdbfe !important; }
        html[data-theme="light"] .border-emerald-500\/30, html.light .border-emerald-500\/30 { border-color: #a7f3d0 !important; }
        html[data-theme="light"] .border-rose-500\/30, html.light .border-rose-500\/30 { border-color: #fecdd3 !important; }
        html[data-theme="light"] .border-indigo-500\/30, html.light .border-indigo-500\/30,
        html[data-theme="light"] .border-purple-500\/30, html.light .border-purple-500\/30 { border-color: #ddd6fe !important; }

        /* Tombol Aksi Tabel Dashboard (Solid & Berwarna Jelas) */
        html[data-theme="light"] .bg-slate-800:not(:disabled), html.light .bg-slate-800:not(:disabled) { background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #1e293b !important; font-weight: 700 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important; }
        html[data-theme="light"] .bg-slate-800:not(:disabled):hover, html.light .bg-slate-800:not(:disabled):hover { background-color: #f1f5f9 !important; border-color: #94a3b8 !important; color: #0f172a !important; }
        
        /* Tombol Utama */
        html[data-theme="light"] .bg-blue-600, html.light .bg-blue-600, html[data-theme="light"] .bg-blue-600 *, html.light .bg-blue-600 * { color: #ffffff !important; }
        html[data-theme="light"] .bg-emerald-600, html.light .bg-emerald-600, html[data-theme="light"] .bg-emerald-600 *, html.light .bg-emerald-600 * { color: #ffffff !important; }
        
        /* Input & Select */
        html[data-theme="light"] input, html.light input, html[data-theme="light"] select, html.light select { background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important; }
        html[data-theme="light"] .border-white\/5, html.light .border-white\/5, html[data-theme="light"] .border-white\/10, html.light .border-white\/10 { border-color: #e2e8f0 !important; }
        html[data-theme="light"] .divide-white\/5 > :not([hidden]) ~ :not([hidden]), html.light .divide-white\/5 > :not([hidden]) ~ :not([hidden]) { border-color: #f1f5f9 !important; }

        /* ========================================================================= */
        /* BOTTOM ACTIONS SIDEBAR (TEMA SWITCHER & KELUAR SESI) DI TEMA TERANG      */
        /* ========================================================================= */
        html[data-theme="light"] .sidebar-theme-card,
        html.light .sidebar-theme-card {
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
        }
        html[data-theme="light"] .theme-toggle-btn,
        html.light .theme-toggle-btn {
            background-color: #e2e8f0 !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.06) !important;
        }
        html[data-theme="light"] .theme-pill-light,
        html.light .theme-pill-light {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
            font-weight: 800 !important;
        }
        html[data-theme="light"] .theme-pill-dark,
        html.light .theme-pill-dark {
            background-color: transparent !important;
            color: #64748b !important;
            box-shadow: none !important;
        }
        html[data-theme="light"] .sidebar-pos-link,
        html.light .sidebar-pos-link {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            color: #1e293b !important;
            font-weight: 700 !important;
        }
        html[data-theme="light"] .sidebar-pos-link:hover,
        html.light .sidebar-pos-link:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
        html[data-theme="light"] .sidebar-logout-btn,
        html.light .sidebar-logout-btn {
            background-color: #fee2e2 !important;
            border: 1px solid #fca5a5 !important;
            color: #b91c1c !important;
            font-weight: 800 !important;
            box-shadow: 0 1px 2px rgba(185, 28, 28, 0.08) !important;
        }
        html[data-theme="light"] .sidebar-logout-btn:hover,
        html.light .sidebar-logout-btn:hover {
            background-color: #fecaca !important;
            border-color: #f87171 !important;
            color: #991b1b !important;
        }

        /* ========================================================================= */
        /* SOLID NOTIFICATION DROPDOWN (DARK & LIGHT MODE ZERO BLEED THROUGH)       */
        /* ========================================================================= */
        #notif-dropdown-menu {
            background-color: #0b1120 !important; /* Solid 100% Opaque Dark Navy/Slate */
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.95), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
        }
        #notif-dropdown-menu .notif-header {
            background-color: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        #notif-dropdown-menu .notif-tabs-bar {
            background-color: #070b14 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        #notif-dropdown-menu #notif-items-list {
            background-color: #0b1120 !important;
        }
        #notif-dropdown-menu .notif-item {
            background-color: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
        }
        #notif-dropdown-menu .notif-item:hover {
            background-color: #1e293b !important;
        }
        #notif-dropdown-menu .notif-item.border-rose-500 {
            background-color: #1f121d !important;
        }
        #notif-dropdown-menu .notif-item.border-amber-500 {
            background-color: #211c12 !important;
        }
        #notif-dropdown-menu .notif-item.border-emerald-500 {
            background-color: #0d1e17 !important;
        }
        #notif-dropdown-menu .notif-item.border-blue-500 {
            background-color: #111e33 !important;
        }
        #notif-dropdown-menu .notif-footer {
            background-color: #0f172a !important;
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* Light Mode Overrides */
        html[data-theme="light"] #admin-topbar,
        html.light #admin-topbar {
            background-color: rgba(255, 255, 255, 0.98) !important;
            border-bottom-color: #e2e8f0 !important;
        }
        html[data-theme="light"] #notif-dropdown-menu,
        html.light #notif-dropdown-menu {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.15), 0 0 0 1px #e2e8f0 !important;
        }
        html[data-theme="light"] #notif-dropdown-menu .notif-header,
        html.light #notif-dropdown-menu .notif-header {
            background-color: #f8fafc !important;
            border-bottom-color: #e2e8f0 !important;
        }
        html[data-theme="light"] #notif-dropdown-menu .notif-tabs-bar,
        html.light #notif-dropdown-menu .notif-tabs-bar {
            background-color: #f1f5f9 !important;
            border-bottom-color: #e2e8f0 !important;
        }
        html[data-theme="light"] #notif-dropdown-menu #notif-items-list,
        html.light #notif-dropdown-menu #notif-items-list {
            background-color: #ffffff !important;
        }
        html[data-theme="light"] #notif-dropdown-menu .notif-item,
        html.light #notif-dropdown-menu .notif-item {
            background-color: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }
        html[data-theme="light"] #notif-dropdown-menu .notif-item:hover,
        html.light #notif-dropdown-menu .notif-item:hover {
            background-color: #f8fafc !important;
        }
        html[data-theme="light"] #notif-dropdown-menu .notif-item.border-rose-500,
        html.light #notif-dropdown-menu .notif-item.border-rose-500 {
            background-color: #fff1f2 !important;
        }
        html[data-theme="light"] #notif-dropdown-menu .notif-item.border-amber-500,
        html.light #notif-dropdown-menu .notif-item.border-amber-500 {
            background-color: #fffbeb !important;
        }
        html[data-theme="light"] #notif-dropdown-menu .notif-item.border-emerald-500,
        html.light #notif-dropdown-menu .notif-item.border-emerald-500 {
            background-color: #f0fdf4 !important;
        }
        html[data-theme="light"] #notif-dropdown-menu .notif-item.border-blue-500,
        html.light #notif-dropdown-menu .notif-item.border-blue-500 {
            background-color: #eff6ff !important;
        }
        html[data-theme="light"] #notif-dropdown-menu .notif-footer,
        html.light #notif-dropdown-menu .notif-footer {
            background-color: #f8fafc !important;
            border-top-color: #e2e8f0 !important;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen flex">
