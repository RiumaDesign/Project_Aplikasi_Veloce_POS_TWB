<!-- MODAL TUTUP SHIFT KASIR & REKAPITULASI KAS (Z-REPORT) -->
<div id="modal-closing-shift" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md hidden items-center justify-center p-4 transition-all duration-300">
    <div class="bg-slate-900 border border-white/10 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header Modal -->
        <div class="px-6 py-4 border-b border-white/10 bg-slate-950/60 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-xl text-amber-400">
                    💵
                </div>
                <div>
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        Tutup Shift & Rekapitulasi Kasir
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 font-mono font-bold">Z-Report</span>
                    </h3>
                    <p class="text-xs text-slate-400">PT Taman Wisata Candi Borobudur (TWB)</p>
                </div>
            </div>
            <button type="button" onclick="tutupModal('modal-closing-shift')" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="p-6 overflow-y-auto custom-scroll space-y-5">
            
            <!-- Metadata Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3 rounded-2xl bg-slate-950/50 border border-white/5 text-xs">
                <div>
                    <span class="text-[10px] text-slate-400 block">Petugas Kasir</span>
                    <span id="cs-kasir-nama" class="font-bold text-white"><?= htmlspecialchars($kasir_aktif ?? 'Kasir') ?></span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 block">Terminal / Lokasi</span>
                    <span id="cs-pos-aktif" class="font-bold text-blue-400"><?= htmlspecialchars($pos_aktif ?? 'Kasir Utama') ?></span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 block">Waktu Buka Shift</span>
                    <span id="cs-opening-time" class="font-mono text-slate-300">--:--:--</span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 block">Waktu Penutupan</span>
                    <span id="cs-closing-time" class="font-mono text-emerald-400 font-bold"><?= date('H:i:s') ?></span>
                </div>
            </div>

            <!-- Ringkasan Penjualan Sistem -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2.5 flex items-center gap-1.5">
                    <span>📊</span> Penjualan Tercatat Sistem
                </h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3.5 rounded-2xl bg-slate-800/40 border border-white/5">
                        <span class="text-[10px] text-slate-400 block">Total Nota Transaksi</span>
                        <span id="cs-tx-count" class="text-lg font-black text-white">0</span>
                        <span class="text-[10px] text-slate-500 block">Struk Berhasil</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                        <span class="text-[10px] text-emerald-400 block">Penjualan Tunai</span>
                        <span id="cs-cash-sales" class="text-lg font-black text-emerald-400">Rp 0</span>
                        <span class="text-[10px] text-emerald-500/70 block">Cash di Laci</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-blue-500/10 border border-blue-500/20">
                        <span class="text-[10px] text-blue-400 block">Penjualan QRIS</span>
                        <span id="cs-qris-sales" class="text-lg font-black text-blue-400">Rp 0</span>
                        <span class="text-[10px] text-blue-500/70 block">Non-Tunai Masuk</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-indigo-500/10 border border-indigo-500/20">
                        <span class="text-[10px] text-indigo-400 block">Grand Total Omzet</span>
                        <span id="cs-total-sales" class="text-lg font-black text-indigo-300">Rp 0</span>
                        <span class="text-[10px] text-indigo-400/70 block">Tunai + QRIS</span>
                    </div>
                </div>
            </div>

            <!-- Form Rekonsiliasi Kas Fisik -->
            <div class="p-4.5 rounded-2xl bg-slate-950/70 border border-white/10 space-y-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center justify-between">
                    <span class="flex items-center gap-1.5"><span>🧮</span> Rekonsiliasi & Penghitungan Fisik Laci</span>
                    <span class="text-[10px] text-blue-400 font-normal">Kalkulasi Otomatis</span>
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Modal Awal Kasir -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                            Modal Awal Kasir (*Float*):
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-500">Rp</span>
                            <input type="number" id="cs-input-float" value="100000" min="0" step="5000" oninput="hitungSelisihKas()" 
                                   class="w-full pl-10 pr-3 py-2 bg-slate-900 border border-white/10 rounded-xl text-sm font-bold text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Uang kembalian awal saat buka kasir.</span>
                    </div>

                    <!-- Uang Kas Fisik yang Dihitung -->
                    <div>
                        <label class="block text-xs font-semibold text-amber-400 mb-1.5 flex items-center justify-between">
                            <span>Uang Fisik Dihitung di Laci:</span>
                            <span class="text-[10px] text-slate-400 cursor-pointer hover:underline" onclick="samakanUangFisik()">[Isi Sesuai Target]</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-500">Rp</span>
                            <input type="number" id="cs-input-actual" value="100000" min="0" step="1000" oninput="hitungSelisihKas()" 
                                   class="w-full pl-10 pr-3 py-2 bg-slate-900 border border-amber-500/40 rounded-xl text-sm font-black text-amber-300 focus:outline-none focus:border-amber-400 shadow-inner">
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Hitung seluruh uang kertas & koin di laci.</span>
                    </div>
                </div>

                <!-- Kartu Hasil Rekonsiliasi (Expected vs Actual vs Difference) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-white/5">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900 border border-white/5">
                        <span class="text-xs text-slate-400">Target Kas Diharapkan:</span>
                        <span id="cs-expected-cash" class="text-sm font-bold text-white">Rp 100.000</span>
                    </div>
                    <div id="cs-diff-card" class="flex items-center justify-between p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
                        <span class="text-xs font-bold">Status Selisih:</span>
                        <span id="cs-difference-badge" class="text-sm font-black">Rp 0 (Pas)</span>
                    </div>
                </div>

                <!-- Catatan Penutupan Shift -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Catatan Kasir (Opsional):</label>
                    <textarea id="cs-input-notes" rows="2" placeholder="Tuliskan keterangan serah terima kas atau alasan selisih jika ada..." 
                              class="w-full p-2.5 bg-slate-900 border border-white/10 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"></textarea>
                </div>
            </div>

        </div>

        <!-- Footer Aksi Modal -->
        <div class="px-6 py-4 border-t border-white/10 bg-slate-950/80 flex items-center justify-between gap-3">
            <button type="button" onclick="tutupModal('modal-closing-shift')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-white/5 transition">
                Batal
            </button>
            <div class="flex items-center gap-2">
                <button type="button" id="btn-print-zreport" onclick="cetakZReportThermal()" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-white/10 text-xs font-bold transition flex items-center gap-1.5">
                    <span>🖨️</span> Cetak Z-Report
                </button>
                <button type="button" id="btn-submit-closing" onclick="simpanClosingShift()" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/30 transition flex items-center gap-1.5">
                    <span>💾</span> Simpan & Selesaikan Shift
                </button>
            </div>
        </div>

    </div>
</div>

<!-- AREA CETAK STRUK THERMAL RESMI Z-REPORT (58mm/80mm) -->
<div id="area-cetak-zreport" class="hidden">
    <div style="text-align: center; margin-bottom: 8px;">
        <img src="assets/images/logo_twb.png" alt="Logo TWB" style="max-height: 40px; margin: 0 auto 4px auto; display: block;">
        <div style="font-weight: bold; font-size: 13px;">PT TAMAN WISATA BOROBUDUR</div>
        <div style="font-size: 10px;">REKAPITULASI PENUTUPAN KASIR</div>
        <div style="font-size: 9px; font-weight: bold;">( Z - R E P O R T )</div>
    </div>
    <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
    <table style="width: 100%; font-size: 10px; line-height: 1.4;">
        <tr><td>No. Shift:</td><td style="text-align: right; font-weight: bold;" id="zr-print-number">-</td></tr>
        <tr><td>Tanggal:</td><td style="text-align: right;" id="zr-print-date"><?= date('d/m/Y H:i') ?></td></tr>
        <tr><td>Petugas:</td><td style="text-align: right; font-weight: bold;" id="zr-print-kasir">-</td></tr>
        <tr><td>Terminal:</td><td style="text-align: right;" id="zr-print-pos">-</td></tr>
        <tr><td>Buka Shift:</td><td style="text-align: right;" id="zr-print-open">-</td></tr>
        <tr><td>Tutup Shift:</td><td style="text-align: right;" id="zr-print-close">-</td></tr>
    </table>
    <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
    <table style="width: 100%; font-size: 10px; line-height: 1.4;">
        <tr><td>Total Transaksi:</td><td style="text-align: right; font-weight: bold;" id="zr-print-tx">0 Nota</td></tr>
        <tr><td>Penjualan Tunai:</td><td style="text-align: right; font-weight: bold;" id="zr-print-cash">Rp 0</td></tr>
        <tr><td>Penjualan QRIS:</td><td style="text-align: right; font-weight: bold;" id="zr-print-qris">Rp 0</td></tr>
        <tr style="border-top: 1px solid #000;">
            <td style="font-weight: bold;">TOTAL OMZET:</td>
            <td style="text-align: right; font-weight: bold;" id="zr-print-total">Rp 0</td>
        </tr>
    </table>
    <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
    <div style="font-size: 10px; font-weight: bold; margin-bottom: 2px;">REKONSILIASI KAS TUNAI:</div>
    <table style="width: 100%; font-size: 10px; line-height: 1.4;">
        <tr><td>Modal Awal (Float):</td><td style="text-align: right;" id="zr-print-float">Rp 0</td></tr>
        <tr><td>Kas Masuk (Tunai):</td><td style="text-align: right;" id="zr-print-cashin">Rp 0</td></tr>
        <tr style="border-top: 1px solid #000;"><td>Target Uang Tunai:</td><td style="text-align: right; font-weight: bold;" id="zr-print-expected">Rp 0</td></tr>
        <tr><td style="font-weight: bold;">Uang Fisik di Laci:</td><td style="text-align: right; font-weight: bold;" id="zr-print-actual">Rp 0</td></tr>
        <tr style="border-top: 1px dashed #000; font-weight: bold;">
            <td>SELISIH KAS:</td>
            <td style="text-align: right;" id="zr-print-diff">Rp 0</td>
        </tr>
    </table>
    <div id="zr-print-notes-box" style="margin-top: 4px; font-size: 9px; font-style: italic;">
        Catatan: <span id="zr-print-notes">-</span>
    </div>
    <div style="border-top: 1px dashed #000; margin: 10px 0 6px 0;"></div>
    <table style="width: 100%; font-size: 9px; text-align: center; margin-top: 12px;">
        <tr>
            <td style="width: 50%;">Petugas Kasir,<br><br><br><br><span id="zr-sign-kasir">( .................. )</span></td>
            <td style="width: 50%;">Supervisor Keuangan,<br><br><br><br>( .................. )</td>
        </tr>
    </table>
    <div style="text-align: center; font-size: 8px; margin-top: 12px; color: #555;">
        Dicetak otomatis oleh Sistem POS Veloce TWB<br>
        Dokumen Sah Rekapitulasi Kas Kasir
    </div>
</div>

<script>
let currentShiftData = {
    kasir_nama: '<?= htmlspecialchars($kasir_aktif ?? 'Kasir') ?>',
    pos_aktif: '<?= htmlspecialchars($pos_aktif ?? 'Kasir Utama') ?>',
    opening_time: '',
    nota_count: 0,
    cash_sales: 0,
    qris_sales: 0,
    total_sales: 0,
    opening_cash: 100000,
    actual_cash: 100000,
    expected_cash: 100000,
    difference: 0
};

function formatRupiahJs(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

async function bukaModalTutupShift() {
    bukaModal('modal-closing-shift');
    
    // Tampilkan loading sementara
    document.getElementById('cs-tx-count').textContent = '...';
    document.getElementById('cs-cash-sales').textContent = '...';
    document.getElementById('cs-qris-sales').textContent = '...';
    document.getElementById('cs-total-sales').textContent = '...';

    try {
        const res = await fetch(`api.php?action=get_shift_stats&kasir=${encodeURIComponent(currentShiftData.kasir_nama)}&pos=${encodeURIComponent(currentShiftData.pos_aktif)}`);
        const json = await res.json();
        if (json.status === 'success' && json.data) {
            const d = json.data;
            currentShiftData.opening_time = d.opening_time;
            currentShiftData.nota_count = d.nota_count;
            currentShiftData.cash_sales = d.cash_sales;
            currentShiftData.qris_sales = d.qris_sales;
            currentShiftData.total_sales = d.total_sales;

            document.getElementById('cs-opening-time').textContent = d.opening_time ? d.opening_time.split(' ')[1] : '08:00:00';
            document.getElementById('cs-tx-count').textContent = d.nota_count;
            document.getElementById('cs-cash-sales').textContent = formatRupiahJs(d.cash_sales);
            document.getElementById('cs-qris-sales').textContent = formatRupiahJs(d.qris_sales);
            document.getElementById('cs-total-sales').textContent = formatRupiahJs(d.total_sales);

            // Default initial calculation
            hitungSelisihKas();
        }
    } catch (e) {
        console.error("Gagal memuat statistik shift:", e);
    }
}

function samakanUangFisik() {
    const floatVal = parseInt(document.getElementById('cs-input-float').value) || 0;
    const target = floatVal + currentShiftData.cash_sales;
    document.getElementById('cs-input-actual').value = target;
    hitungSelisihKas();
}

function hitungSelisihKas() {
    const floatVal = parseInt(document.getElementById('cs-input-float').value) || 0;
    const actualVal = parseInt(document.getElementById('cs-input-actual').value) || 0;
    
    currentShiftData.opening_cash = floatVal;
    currentShiftData.actual_cash = actualVal;

    const expected = floatVal + currentShiftData.cash_sales;
    currentShiftData.expected_cash = expected;

    const diff = actualVal - expected;
    currentShiftData.difference = diff;

    document.getElementById('cs-expected-cash').textContent = formatRupiahJs(expected);

    const badge = document.getElementById('cs-difference-badge');
    const card = document.getElementById('cs-diff-card');

    if (diff === 0) {
        badge.textContent = 'Rp 0 (Pas / Seimbang)';
        card.className = 'flex items-center justify-between p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400';
    } else if (diff > 0) {
        badge.textContent = `+ ${formatRupiahJs(diff)} (Kelebihan Kas)`;
        card.className = 'flex items-center justify-between p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400';
    } else {
        badge.textContent = `- ${formatRupiahJs(Math.abs(diff))} (Kurang Kas)`;
        card.className = 'flex items-center justify-between p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400';
    }
}

async function simpanClosingShift() {
    const btn = document.getElementById('btn-submit-closing');
    btn.disabled = true;
    btn.innerHTML = `<span>⏳</span> Memproses...`;

    const payload = {
        kasir_nama: currentShiftData.kasir_nama,
        pos_aktif: currentShiftData.pos_aktif,
        outlet_id: 1,
        opening_time: currentShiftData.opening_time,
        opening_cash: currentShiftData.opening_cash,
        actual_cash: currentShiftData.actual_cash,
        notes: document.getElementById('cs-input-notes').value.trim()
    };

    try {
        const res = await fetch('api.php?action=submit_closing_shift', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            currentShiftData.shift_number = json.data.shift_number;
            
            // Konfirmasi cetak struk
            if (confirm("Shift berhasil ditutup dan tersimpan ke database!\n\nApakah Anda ingin mencetak struk Z-Report sekarang?")) {
                cetakZReportThermal();
            }
            tutupModal('modal-closing-shift');
            // Refresh halaman kasir
            window.location.reload();
        } else {
            alert("Gagal menutup shift: " + (json.message || 'Terjadi kesalahan sistem.'));
        }
    } catch (e) {
        alert("Terjadi kesalahan jaringan saat menyimpan penutupan shift.");
        console.error(e);
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<span>💾</span> Simpan & Selesaikan Shift`;
    }
}

function cetakZReportThermal() {
    document.getElementById('zr-print-number').textContent = currentShiftData.shift_number || ('SFT-' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '-PRE');
    document.getElementById('zr-print-kasir').textContent = currentShiftData.kasir_nama;
    document.getElementById('zr-sign-kasir').textContent = `( ${currentShiftData.kasir_nama} )`;
    document.getElementById('zr-print-pos').textContent = currentShiftData.pos_aktif;
    document.getElementById('zr-print-open').textContent = currentShiftData.opening_time || '08:00:00';
    document.getElementById('zr-print-close').textContent = new Date().toLocaleTimeString('id-ID');
    document.getElementById('zr-print-tx').textContent = currentShiftData.nota_count + ' Nota';
    document.getElementById('zr-print-cash').textContent = formatRupiahJs(currentShiftData.cash_sales);
    document.getElementById('zr-print-qris').textContent = formatRupiahJs(currentShiftData.qris_sales);
    document.getElementById('zr-print-total').textContent = formatRupiahJs(currentShiftData.total_sales);
    document.getElementById('zr-print-float').textContent = formatRupiahJs(currentShiftData.opening_cash);
    document.getElementById('zr-print-cashin').textContent = formatRupiahJs(currentShiftData.cash_sales);
    document.getElementById('zr-print-expected').textContent = formatRupiahJs(currentShiftData.expected_cash);
    document.getElementById('zr-print-actual').textContent = formatRupiahJs(currentShiftData.actual_cash);
    
    const diff = currentShiftData.difference;
    document.getElementById('zr-print-diff').textContent = (diff === 0) ? 'Rp 0 (PAS)' : ((diff > 0 ? '+ ' : '- ') + formatRupiahJs(Math.abs(diff)));
    document.getElementById('zr-print-notes').textContent = document.getElementById('cs-input-notes').value.trim() || 'Tidak ada catatan.';

    // Jalankan perintah cetak browser
    window.print();
}
</script>
