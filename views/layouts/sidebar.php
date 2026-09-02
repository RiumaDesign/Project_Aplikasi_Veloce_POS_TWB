<!-- SIDEBAR NAVIGATION -->
<aside id="sidebar-nav" class="w-64 bg-slate-950 text-white h-screen sticky top-0 p-4 hidden md:flex flex-col flex-shrink-0 border-r border-white/10 relative z-30 overflow-y-auto custom-scroll transition-all duration-300 justify-between">
    <!-- Brand Logo & Collapse Button -->
    <div class="flex items-center justify-between gap-2 mb-4 shrink-0">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="h-10 px-2 bg-white rounded-xl flex items-center justify-center shadow-lg border border-white/20 shrink-0">
                <img src="assets/images/logo_twb.png" alt="Logo Resmi TWB" class="h-6 w-auto object-contain">
            </div>
            <div class="overflow-hidden">
                <h1 class="text-xs font-black tracking-tight leading-none text-white whitespace-nowrap">TWB <span class="text-blue-400">Admin Khusus</span></h1>
                <span class="text-[9px] text-slate-400 font-medium whitespace-nowrap block mt-1">Taman Wisata Borobudur</span>
            </div>
        </div>
        <!-- Tombol Sembunyikan Sidebar di Header -->
        <button type="button" onclick="toggleSidebar()" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition border border-transparent hover:border-white/10 shrink-0" title="Sembunyikan Sidebar (Lebarkan Layar)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
        </button>
    </div>

<?php
// Query lencana indikator dinamis sidebar (Stok kritis & retur pending)
$countKritisStok = 0;
$countReturPending = 0;
try {
    if (isset($conn) && $conn instanceof mysqli) {
        $stkRes = $conn->query("SELECT COUNT(*) FROM `stok_lokasi` WHERE `quantity` <= 3");
        if ($stkRes) {
            $countKritisStok = intval($stkRes->fetch_row()[0] ?? 0);
        }

        $retRes = $conn->query("SELECT COUNT(*) FROM `returns` WHERE `status` = 'pending' OR `created_at` >= DATE_SUB(NOW(), INTERVAL 48 HOUR)");
        if ($retRes) {
            $countReturPending = intval($retRes->fetch_row()[0] ?? 0);
        }
    }
} catch (Exception $e) {
    // Safe fallback jika tabel belum siap
}
?>
    <!-- Navigation Menu Items -->
    <nav class="space-y-1.5 flex-1 overflow-y-auto custom-scroll pr-0.5">
        <a href="dashboard.php?page=analytics" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-200 <?= ($page === 'analytics') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
            <span class="text-base">📊</span> <span>Grafik & Analisis</span>
        </a>

        <a href="dashboard.php?page=menu" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-200 <?= ($page === 'menu') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
            <span class="text-base">📋</span> <span>Kelola Produk</span>
        </a>

        <a href="dashboard.php?page=stok" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-200 <?= ($page === 'stok') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
            <div class="flex items-center gap-2.5">
                <span class="text-base">📦</span> <span>Kelola Stok Barang</span>
            </div>
            <?php if ($countKritisStok > 0): ?>
                <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-500 text-white shadow-sm shadow-rose-500/50 animate-pulse" title="<?= $countKritisStok ?> produk stok kritis/habis">
                    <?= $countKritisStok ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="dashboard.php?page=kasir" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-200 <?= ($page === 'kasir') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
            <span class="text-base">👥</span> <span>Kelola Petugas Kasir</span>
        </a>

        <a href="dashboard.php?page=outlet" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-200 <?= ($page === 'outlet') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
            <span class="text-base">🏪</span> <span>Kelola Outlet</span>
        </a>

        <a href="dashboard.php?page=retur" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-200 <?= ($page === 'retur') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
            <div class="flex items-center gap-2.5">
                <span class="text-base">⚠️</span> <span>Retur & Barang Rusak</span>
            </div>
            <?php if ($countReturPending > 0): ?>
                <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-500 text-slate-950 shadow-sm shadow-amber-500/50" title="<?= $countReturPending ?> retur barang menunggu tindakan">
                    <?= $countReturPending ?>
                </span>
            <?php endif; ?>
        </a>
    </nav>

    <!-- Bottom Actions: Tema & Logout (Always Visible & Prominent) -->
    <div class="pt-3 border-t border-white/10 shrink-0 space-y-1.5 mt-2">
        <!-- Segmented Theme Toggle Button -->
        <div class="sidebar-theme-card bg-slate-900 border border-white/10 rounded-xl p-1.5 flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-400 pl-1.5">Tema:</span>
            <div onclick="toggleTheme()" class="theme-toggle-btn flex items-center bg-slate-950 rounded-lg p-0.5 cursor-pointer border border-white/10 hover:border-blue-500/30 transition" title="Ganti Tema">
                <div class="theme-pill-dark flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold transition bg-blue-600 text-white shadow-sm">
                    <span>🌙</span> <span>Gelap</span>
                </div>
                <div class="theme-pill-light flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold transition text-slate-400 hover:text-white">
                    <span>☀️</span> <span>Terang</span>
                </div>
            </div>
        </div>

        <a href="dashboard.php?action=logout" onclick="return confirmLogoutAdmin(event)" class="sidebar-logout-btn flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition bg-rose-500/10 text-rose-400 border border-rose-500/30 hover:bg-rose-500/20 cursor-pointer">
            <span class="flex items-center gap-2 pointer-events-none"><span>🚪</span> Keluar Sesi Admin</span>
            <span class="text-[9px] bg-rose-500/20 text-rose-300 px-1.5 py-0.5 rounded font-mono uppercase tracking-wider pointer-events-none">Keluar</span>
        </a>
    </div>
</aside>

<!-- TOMBOL MENGAMBANG UNTUK MENAMPILKAN KEMBALI SIDEBAR (KETIKA DISEMBUNYIKAN) -->
<button id="btn-show-sidebar" type="button" onclick="toggleSidebar()" class="fixed top-4 left-4 z-40 bg-blue-600 hover:bg-blue-500 text-white px-3.5 py-2 rounded-2xl shadow-2xl shadow-blue-600/40 items-center gap-2 text-xs font-bold transition-all duration-200 border border-blue-400/30 hover:scale-105 hidden" title="Tampilkan Sidebar">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    <span>Tampilkan Sidebar</span>
</button>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar-nav');
    const btnShow = document.getElementById('btn-show-sidebar');
    const mainContent = document.getElementById('main-content');
    if (!sidebar) return;

    const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
    if (btnShow) {
        if (isCollapsed) {
            btnShow.classList.remove('hidden');
            btnShow.classList.add('flex');
        } else {
            btnShow.classList.add('hidden');
            btnShow.classList.remove('flex');
        }
    }
    if (mainContent) {
        if (isCollapsed) {
            mainContent.classList.remove('max-w-7xl');
            mainContent.classList.add('max-w-full');
        } else {
            mainContent.classList.add('max-w-7xl');
            mainContent.classList.remove('max-w-full');
        }
    }
    localStorage.setItem('twb_sidebar_collapsed', isCollapsed ? 'true' : 'false');
}

// Pulihkan preferensi saat halaman dimuat
(function() {
    const isCollapsed = localStorage.getItem('twb_sidebar_collapsed') === 'true';
    if (isCollapsed) {
        const sidebar = document.getElementById('sidebar-nav');
        const btnShow = document.getElementById('btn-show-sidebar');
        const mainContent = document.getElementById('main-content');
        if (sidebar) sidebar.classList.add('sidebar-collapsed');
        if (btnShow) {
            btnShow.classList.remove('hidden');
            btnShow.classList.add('flex');
        }
        if (mainContent) {
            mainContent.classList.remove('max-w-7xl');
            mainContent.classList.add('max-w-full');
        }
    }
})();
</script>
