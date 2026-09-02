<!-- Modal Pembayaran & Cetak Struk -->
<div id="modal-bayar" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-md border border-white/10 text-white shadow-2xl space-y-4">
        <div class="flex justify-between items-center pb-3 border-b border-white/10">
            <h3 class="text-base font-bold text-white">💳 Pembayaran Pesanan</h3>
            <button onclick="tutupModal('modal-bayar')" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <div class="bg-blue-600/10 border border-blue-500/20 p-4 rounded-2xl text-center">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Yang Harus Dibayar</span>
            <div id="modal-total-display" class="text-2xl font-black text-emerald-400 mt-1">Rp 0</div>
        </div>

        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Metode Pembayaran</label>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="pilihMetode('Cash')" id="btn-metode-cash" class="p-3 rounded-xl border border-blue-500 bg-blue-500/20 text-blue-300 font-bold text-xs flex items-center justify-center gap-2">
                    <span>💵</span> <span>Tunai (Cash)</span>
                </button>
                <button type="button" onclick="pilihMetode('QRIS')" id="btn-metode-qris" class="p-3 rounded-xl border border-white/10 bg-slate-900 text-slate-400 font-bold text-xs flex items-center justify-center gap-2">
                    <span>📱</span> <span>QRIS Dinamis</span>
                </button>
            </div>
        </div>

        <div id="input-tunai-wrapper">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nominal Diterima (Rp)</label>
            <input type="number" id="nominal-bayar" onkeyup="hitungKembalian()" placeholder="Masukan nominal uang" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm text-white font-bold outline-none focus:border-blue-500">
            <div class="flex justify-between text-xs mt-2 text-slate-400 font-medium">
                <span>Kembalian:</span>
                <span id="kembalian-display" class="font-bold text-white">Rp 0</span>
            </div>
        </div>

        <button id="btn-proses-transaksi" onclick="prosesSimpanTransaksi()" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 rounded-2xl text-sm transition shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2">
            <span>Selesaikan & Cetak Nota</span> <span>✓</span>
        </button>
    </div>
</div>

<!-- Modal Struk / Cetak Nota -->
<div id="modal-struk" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="bg-white text-slate-900 rounded-3xl p-6 w-full max-w-sm shadow-2xl space-y-4">
        <div id="area-cetak-nota" class="font-mono text-xs text-slate-800 space-y-3 p-1">
            <div class="text-center pb-3 border-b border-dashed border-slate-400">
                <img src="assets/images/logo_twb.png" alt="Logo TWB" class="h-11 mx-auto mb-1.5 object-contain filter contrast-125">
                <h4 class="font-black text-xs uppercase tracking-tight text-slate-900 leading-tight">PT TAMAN WISATA BOROBUDUR</h4>
                <p class="text-[10px] text-slate-700 font-bold"><?= htmlspecialchars($pos_aktif) ?></p>
                <p class="text-[8.5px] text-slate-500">Jl. Badrawati, Kawasan Borobudur, Magelang, Jawa Tengah</p>
                <div class="mt-2 pt-2 border-t border-dotted border-slate-300 flex justify-between text-[9.5px]">
                    <span id="nota-date-time" class="text-slate-500"></span>
                    <span id="nota-id" class="font-bold text-slate-900 font-mono"></span>
                </div>
                <div class="flex justify-between text-[9.5px] text-slate-600 mt-0.5">
                    <span>Kasir: <b class="text-slate-800"><?= htmlspecialchars($kasir_aktif ?? 'Kasir TWB') ?></b></span>
                    <span>Status: <b class="text-emerald-700">LUNAS</b></span>
                </div>
            </div>
            
            <div id="nota-items" class="space-y-1.5 py-2 border-b border-dashed border-slate-400 text-[11px]"></div>

            <div class="space-y-1 text-xs pt-1">
                <div class="flex justify-between font-black text-sm text-slate-900">
                    <span>TOTAL BAYAR:</span>
                    <span id="nota-total" class="text-emerald-700 font-mono"></span>
                </div>
                <div class="flex justify-between text-slate-600 text-[11px]">
                    <span>Metode Pembayaran:</span>
                    <span id="nota-metode" class="font-bold text-slate-800"></span>
                </div>
            </div>

            <div class="text-center pt-3 border-t border-dashed border-slate-400 text-[9.5px] text-slate-500 leading-tight space-y-1">
                <p class="font-bold text-slate-700">Terima Kasih Atas Kunjungan Anda di Candi Borobudur</p>
                <p>Simpan struk nota ini sebagai bukti pembayaran yang sah.</p>
                <p class="text-[8px] text-slate-400 font-mono mt-1">Sistem Resmi POS - PT Taman Wisata Borobudur (TWB)</p>
            </div>
        </div>

        <div class="flex gap-2 pt-2">
            <button onclick="window.print()" class="flex-1 bg-slate-900 text-white font-bold py-3 rounded-xl text-xs hover:bg-slate-800 transition">
                🖨️ Cetak
            </button>
            <button onclick="tutupModalStruk()" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-xl text-xs hover:bg-blue-500 transition">
                Transaksi Baru ➔
            </button>
        </div>
    </div>
</div>
