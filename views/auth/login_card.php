<!-- Kartu Login Glassmorphism -->
<div class="w-full max-w-md glass-card-dark rounded-3xl p-8 border border-white/10 text-white relative z-10 shadow-2xl">
    <!-- Logo & Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center p-3 bg-white rounded-3xl shadow-xl border border-white/20 mb-3">
            <img src="assets/images/logo_twb.png" alt="Logo Resmi TWB" class="h-12 w-auto object-contain mx-auto">
        </div>
        <h1 class="text-2xl font-black tracking-tight text-white">TWB <span class="text-blue-400">POS</span></h1>
        <p class="text-xs text-slate-400 mt-1">Sistem Penjualan Kasir & Vending Machine Borobudur</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs font-semibold p-3.5 rounded-2xl mb-5 flex items-center gap-2">
            <span>⚠️</span> <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Form Login -->
    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Nama Pengguna</label>
            <input type="text" name="username" required placeholder="Contoh: Admin atau Andi Wijaya" 
                   class="w-full bg-slate-900 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition duration-200">
        </div>

        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Kata Sandi</label>
            <input type="password" name="password" required placeholder="••••••••" 
                   class="w-full bg-slate-900 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition duration-200">
        </div>

        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Lokasi Terminal / Outlet (Khusus Kasir)</label>
            <select name="outlet_id" class="w-full bg-slate-900 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white focus:outline-none focus:border-blue-500 transition duration-200">
                <?php while ($loc = $outlets_res->fetch_assoc()): ?>
                    <option value="<?= $loc['id'] ?>">
                        [<?= $loc['code'] ?>] <?= htmlspecialchars($loc['name']) ?> (<?= strtoupper($loc['type']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <p class="text-[10px] text-slate-500 mt-1.5">*Abaikan pilihan outlet ini jika login sebagai Admin Khusus</p>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-2xl text-xs transition duration-200 shadow-lg shadow-blue-600/30">
            Masuk Sistem ➔
        </button>
    </form>
</div>
