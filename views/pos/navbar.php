<!-- Top Navigation POS -->
<header class="bg-slate-900 border-b border-white/10 px-3 py-2.5 sm:px-6 sm:py-3.5 flex items-center justify-between shadow-lg relative z-20 shrink-0">
    <div class="flex items-center gap-2.5 min-w-0">
        <div class="h-9 sm:h-10 px-2 sm:px-2.5 bg-white rounded-xl sm:rounded-2xl flex items-center justify-center shadow-md border border-white/20 shrink-0">
            <img src="assets/images/logo_twb.png" alt="Logo Resmi TWB" class="h-5 sm:h-7 w-auto object-contain">
        </div>
        <div class="min-w-0">
            <h1 class="text-xs sm:text-base font-black text-white leading-none truncate">TWB <span class="text-blue-400">POS</span></h1>
            <span class="text-[10px] sm:text-[11px] font-semibold text-emerald-400 flex items-center gap-1 mt-0.5 truncate">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                <span class="truncate"><?= htmlspecialchars($pos_aktif) ?></span>
            </span>
        </div>
    </div>

    <!-- Shift & Kasir Info -->
    <div class="flex items-center gap-1.5 sm:gap-4 shrink-0">
        <!-- Mini Summary Badge (Desktop only) -->
        <div class="hidden md:flex items-center gap-3 bg-white/5 border border-white/10 px-4 py-2 rounded-2xl text-xs">
            <span class="text-slate-400">Shift Hari Ini:</span>
            <span class="font-bold text-white"><?= $total_nota ?> Nota</span>
            <span class="text-slate-600">|</span>
            <span class="font-bold text-emerald-400">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-2">
            <div class="text-right hidden xl:block">
                <span class="text-xs font-bold text-white block leading-none"><?= htmlspecialchars($kasir_aktif) ?></span>
                <span class="text-[10px] text-slate-400 font-mono">Petugas Kasir</span>
            </div>

            <!-- Segmented Theme Toggle Button (Icon on mobile, Pill on desktop) -->
            <button type="button" onclick="toggleTheme()" class="theme-toggle-btn p-2 sm:p-1 rounded-xl sm:rounded-2xl bg-slate-800/80 sm:bg-slate-900 border border-white/10 text-slate-300 hover:text-white transition cursor-pointer flex items-center justify-center" title="Beralih Tema Terang / Gelap">
                <span class="sm:hidden text-xs">🌓</span>
                <div class="hidden sm:flex theme-pill-dark items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition bg-blue-600 text-white shadow-md font-black">
                    <span>🌙</span> <span class="hidden md:inline text-[11px]">Gelap</span>
                </div>
                <div class="hidden sm:flex theme-pill-light items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition text-slate-400 hover:text-white">
                    <span>☀️</span> <span class="hidden md:inline text-[11px]">Terang</span>
                </div>
            </button>

            <!-- Ganti Terminal Button -->
            <button type="button" onclick="bukaModal('modal-ganti-terminal')" title="Pilih Terminal / Lokasi POS Lain" class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-white/10 p-2 sm:px-3 sm:py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <span>🔄</span> <span class="hidden sm:inline">Ganti Terminal</span>
            </button>

            <!-- Struk Terakhir (hidden on small mobile, visible sm+) -->
            <button type="button" onclick="cetakUlangNotaTerakhir()" title="Cetak Ulang Struk Transaksi Terakhir" class="hidden sm:flex bg-slate-800 hover:bg-slate-700 text-slate-300 border border-white/10 p-2 sm:px-3 sm:py-2 rounded-xl text-xs font-bold transition items-center gap-1.5 cursor-pointer">
                <span>🖨️</span> <span class="hidden md:inline">Struk</span>
            </button>

            <!-- Tutup Shift Button -->
            <button type="button" id="btn-navbar-closing-shift" onclick="bukaModalTutupShift()" title="Tutup Shift Kasir & Rekapitulasi Kas (Z-Report)" class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 p-2 sm:px-3 sm:py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <span>💵</span> <span class="hidden sm:inline">Tutup Shift</span>
            </button>

            <!-- Logout Button -->
            <a href="logout.php" title="Keluar / Logout Sesi" onclick="return confirmLogoutKasir(event)" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 p-2 sm:px-3 sm:py-2 rounded-xl text-xs font-bold transition flex items-center gap-1" title="Logout">
                <span>🚪</span> <span class="hidden md:inline">Keluar</span>
            </a>
        </div>
    </div>
</header>
