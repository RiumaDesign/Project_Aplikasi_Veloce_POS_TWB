<!-- Modal Ganti Terminal POS -->
<div id="modal-ganti-terminal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-md border border-white/10 text-white shadow-2xl animate-fade-in">
        <div class="flex justify-between items-center pb-4 border-b border-white/10 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 border border-blue-500/30 flex items-center justify-center text-lg font-bold">🔄</div>
                <div>
                    <h3 class="text-base font-bold text-white">Ganti Terminal POS</h3>
                    <p class="text-xs text-slate-400">Pilih outlet atau vending machine aktif</p>
                </div>
            </div>
            <button type="button" onclick="tutupModal('modal-ganti-terminal')" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form method="POST" action="index.php" class="space-y-4">
            <input type="hidden" name="action" value="ganti_terminal">
            
            <div>
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-2">Terminal / Outlet Tujuan:</label>
                <div class="relative">
                    <select name="new_outlet_id" required class="w-full bg-slate-900/90 border border-white/15 rounded-2xl p-3 text-xs text-white outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <?php foreach($terminal_list as $t): ?>
                            <?php 
                            $icon = ($t['type'] === 'vm') ? '🤖' : (($t['type'] === 'outlet') ? '🏪' : '💻');
                            $is_current = ($t['id'] == $outlet_id);
                            ?>
                            <option value="<?= $t['id'] ?>" <?= $is_current ? 'selected' : '' ?>>
                                <?= $icon ?> <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['code']) ?>) <?= $is_current ? '— [AKTIF]' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="text-[11px] text-slate-400 mt-2">
                    Saat berganti terminal, katalog produk dan pencatatan nota transaksi akan disesuaikan otomatis dengan lokasi yang dipilih.
                </p>
            </div>

            <div class="pt-3 border-t border-white/10 flex items-center justify-between gap-3">
                <a href="logout.php" onclick="return confirmLogoutKasir(event)" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                    <span>🚪</span> <span>Keluar / Logout</span>
                </a>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="tutupModal('modal-ganti-terminal')" class="bg-slate-800 text-slate-300 text-xs font-bold px-3 py-2.5 rounded-xl hover:bg-slate-700 transition">
                        Batal
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-lg shadow-blue-600/30 transition flex items-center gap-1.5">
                        <span>✓</span> <span>Terapkan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
