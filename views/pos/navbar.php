<!-- Top Navigation POS -->
<header class="bg-slate-900 border-b border-white/10 px-6 py-4 flex items-center justify-between shadow-lg relative z-20">
    <div class="flex items-center gap-3">
        <div class="h-10 px-2.5 bg-white rounded-2xl flex items-center justify-center shadow-md border border-white/20">
            <img src="assets/images/logo_twb.png" alt="Logo Resmi TWB" class="h-7 w-auto object-contain">
        </div>
        <div>
            <h1 class="text-base font-black text-white leading-none">TWB <span class="text-blue-400">POS</span></h1>
            <span class="text-[11px] font-semibold text-emerald-400 flex items-center gap-1.5 mt-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <?= htmlspecialchars($pos_aktif) ?>
            </span>
        </div>
    </div>

    <!-- Shift & Kasir Info -->
    <div class="flex items-center gap-4">
        <!-- Mini Summary Badge -->
        <div class="hidden sm:flex items-center gap-3 bg-white/5 border border-white/10 px-4 py-2 rounded-2xl text-xs">
            <span class="text-slate-400">Shift Hari Ini:</span>
            <span class="font-bold text-white"><?= $total_nota ?> Nota</span>
            <span class="text-slate-600">|</span>
            <span class="font-bold text-emerald-400">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
        </div>

        <div class="flex items-center gap-2">
            <div class="text-right hidden sm:block">
                <span class="text-xs font-bold text-white block leading-none"><?= htmlspecialchars($kasir_aktif) ?></span>
                <span class="text-[10px] text-slate-400 font-mono">Petugas Kasir</span>
            </div>
            <!-- Segmented Theme Toggle Button (Dark / Light) -->
            <button type="button" onclick="toggleTheme()" class="theme-toggle-btn group flex items-center bg-slate-900 border border-white/10 rounded-2xl p-1 transition shadow-inner cursor-pointer hover:border-blue-500/40" title="Beralih Tema Terang / Gelap">
                <div class="theme-pill-dark flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition bg-blue-600 text-white shadow-md font-black">
                    <span>🌙</span> <span class="hidden md:inline text-[11px]">Gelap</span>
                </div>
                <div class="theme-pill-light flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition text-slate-400 hover:text-white">
                    <span>☀️</span> <span class="hidden md:inline text-[11px]">Terang</span>
                </div>
            </button>
            <button type="button" onclick="bukaModal('modal-ganti-terminal')" title="Pilih Terminal / Lokasi POS Lain" class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-white/10 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <span>🔄</span> <span class="hidden sm:inline">Ganti Terminal</span>
            </button>
            <button type="button" onclick="bukaModalTutupShift()" title="Tutup Shift Kasir & Rekapitulasi Kas (Z-Report)" class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <span>💵</span> <span class="hidden sm:inline">Tutup Shift</span>
            </button>
            <a href="logout.php" title="Keluar / Logout Sesi" onclick="return confirmLogoutKasir(event)" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1" title="Logout">
                <span>🚪</span> <span class="hidden md:inline">Keluar</span>
            </a>
        </div>
    </div>
</header>
