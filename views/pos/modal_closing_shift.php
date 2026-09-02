<!-- MODAL TUTUP SHIFT KASIR & REKAPITULASI KAS (Z-REPORT) -->
<div id="modal-closing-shift" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md hidden items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200 text-slate-800 dark:text-white">
        
        <!-- Header Modal: Sederhana & Bersih -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-slate-950/60 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-xl text-amber-500 dark:text-amber-400 shadow-sm">
                    💵
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        Tutup Shift Kasir
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-600 text-white font-mono font-bold tracking-wider">Z-REPORT</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        PT Taman Wisata Candi Borobudur &bull; <span class="font-bold text-blue-600 dark:text-blue-400">Zona Waktu WIB</span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="tutupModal('modal-closing-shift')" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Body Modal: Ringkas, Mudah Dibaca & Kontras Tinggi -->
        <div class="p-6 overflow-y-auto custom-scroll space-y-4">
            
            <!-- 1. Baris Informasi Kasir & Jam WIB (Clean Strip) -->
            <div class="p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div>
                    <span class="text-slate-500 dark:text-slate-400">Petugas:</span>
                    <strong id="cs-kasir-nama" class="text-slate-900 dark:text-white ml-1"><?= htmlspecialchars($kasir_aktif ?? 'Kasir') ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 dark:text-slate-400">Terminal:</span>
                    <strong id="cs-pos-aktif" class="text-blue-600 dark:text-blue-400 ml-1"><?= htmlspecialchars($pos_aktif ?? 'Kasir Utama') ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 dark:text-slate-400">Buka:</span>
                    <strong id="cs-opening-time" class="font-mono text-slate-700 dark:text-slate-300 ml-1">--:--:-- WIB</strong>
                </div>
                <div>
                    <span class="text-slate-500 dark:text-slate-400">Tutup:</span>
                    <strong id="cs-closing-time" class="font-mono text-emerald-600 dark:text-emerald-400 font-bold ml-1"><?= date('H:i:s') ?> WIB</strong>
                </div>
            </div>

            <!-- 2. Ringkasan Omzet Shift (3 Kartu Utama) -->
            <div class="grid grid-cols-3 gap-3">
                <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20">
                    <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 block">💵 Uang Tunai</span>
                    <span id="cs-cash-sales" class="text-base sm:text-lg font-black text-emerald-700 dark:text-emerald-400 block mt-0.5">Rp 0</span>
                    <span class="text-[10px] text-emerald-600/80 dark:text-emerald-500/70 block">Cash di Laci</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20">
                    <span class="text-[11px] font-bold text-blue-700 dark:text-blue-400 block">📱 Non-Tunai</span>
                    <span id="cs-qris-sales" class="text-base sm:text-lg font-black text-blue-700 dark:text-blue-400 block mt-0.5">Rp 0</span>
                    <span class="text-[10px] text-blue-600/80 dark:text-blue-500/70 block">QRIS Masuk</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20">
                    <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-400 block">💰 Total Omzet</span>
                    <span id="cs-total-sales" class="text-base sm:text-lg font-black text-indigo-700 dark:text-indigo-300 block mt-0.5">Rp 0</span>
                    <span id="cs-tx-count-label" class="text-[10px] text-indigo-600/80 dark:text-indigo-400/70 block"><span id="cs-tx-count">0</span> Nota Sukses</span>
                </div>
            </div>

            <!-- 3. Form Rekonsiliasi Kas Laci (Sederhana & Mudah Dipahami) -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-white/10 space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-white/10 pb-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <span>🧮</span> Penghitungan Kas Fisik
                    </h4>
                    <span class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold">Otomatis Dihitung</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- Modal Awal Float -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Modal Awal (*Float*):
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                            <input type="number" id="cs-input-float" value="100000" min="0" step="5000" oninput="hitungSelisihKas()" 
                                   class="w-full pl-10 pr-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-white/20 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 shadow-sm">
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block">Uang kembalian awal saat shift dibuka.</span>
                    </div>

                    <!-- Fisik Kas Laci -->
                    <div>
                        <label class="block text-xs font-bold text-amber-800 dark:text-amber-400 mb-1 flex items-center justify-between">
                            <span>Uang Fisik di Laci:</span>
                            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-bold cursor-pointer hover:underline" onclick="samakanUangFisik()">[Isi Pas]</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                            <input type="number" id="cs-input-actual" value="100000" min="0" step="1000" oninput="hitungSelisihKas()" 
                                   class="w-full pl-10 pr-3 py-2 bg-white dark:bg-slate-900 border border-amber-500/60 dark:border-amber-500/40 rounded-xl text-sm font-black text-amber-700 dark:text-amber-300 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-inner">
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block">Total uang tunai di laci kasir saat ini.</span>
                    </div>
                </div>

                <!-- Hasil Status Selisih Kas (Besar & Jelas) -->
                <div id="cs-diff-card" class="flex items-center justify-between p-3.5 rounded-xl bg-emerald-100/70 dark:bg-emerald-500/15 border border-emerald-300 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 transition-all duration-200">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">⚖️</span>
                        <span class="text-xs font-black uppercase tracking-wider">Hasil Rekonsiliasi:</span>
                    </div>
                    <span id="cs-difference-badge" class="text-sm font-black">Rp 0 (Pas / Seimbang)</span>
                </div>

                <!-- Catatan Kasir (Opsional) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Catatan Tambahan (Opsional):</label>
                    <input type="text" id="cs-input-notes" placeholder="Tuliskan keterangan serah terima jika ada..." 
                           class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-white/20 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 shadow-sm">
                </div>
            </div>

        </div>

        <!-- Footer Modal: Bersih & Tombol Berbobot -->
        <div class="px-6 py-4 border-t border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-slate-950/80 flex items-center justify-between gap-3">
            <button type="button" onclick="tutupModal('modal-closing-shift')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-white/5 transition cursor-pointer">
                Batal
            </button>
            <div class="flex items-center gap-2.5">
                <button type="button" id="btn-print-zreport" onclick="cetakZReportThermal()" class="px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-white/20 text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                    <span>🖨️</span> Cetak Struk Z-Report
                </button>
                <button type="button" id="btn-submit-closing" onclick="simpanClosingShift()" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/30 transition flex items-center gap-1.5 cursor-pointer">
                    <span>💾</span> Selesaikan & Tutup Shift
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ======================================================================== -->
<!-- AREA CETAK STRUK THERMAL RESMI Z-REPORT (STANDAR 58mm / 80mm)             -->
<!-- ======================================================================== -->
<div id="area-cetak-zreport" class="hidden" style="background:#ffffff;color:#000000;font-family:'Courier New',Courier,monospace;padding:6px;width:76mm;margin:0 auto;">
    <div style="text-align: center; margin-bottom: 6px;">
        <img src="assets/images/logo_twb.png" alt="Logo TWB" style="max-height: 38px; margin: 0 auto 4px auto; display: block; filter: grayscale(100%) contrast(200%);">
        <div style="font-weight: bold; font-size: 13px; letter-spacing: 0.5px;">PT TAMAN WISATA BOROBUDUR</div>
        <div style="font-size: 10px; font-weight: bold;">REKAPITULASI PENUTUPAN KASIR</div>
        <div style="font-size: 9px; font-weight: bold; letter-spacing: 1px;">( Z - R E P O R T )</div>
    </div>

    <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
    
    <table style="width: 100%; font-size: 10px; line-height: 1.4; border-collapse: collapse;">
        <tr><td style="width:40%;">No. Shift:</td><td style="text-align: right; font-weight: bold;" id="zr-print-number">-</td></tr>
        <tr><td>Waktu Cetak:</td><td style="text-align: right;" id="zr-print-date"><?= date('d/m/Y H:i:s') ?> WIB</td></tr>
        <tr><td>Kasir:</td><td style="text-align: right; font-weight: bold;" id="zr-print-kasir">-</td></tr>
        <tr><td>Terminal:</td><td style="text-align: right;" id="zr-print-pos">-</td></tr>
        <tr><td>Buka Shift:</td><td style="text-align: right;" id="zr-print-open">-</td></tr>
        <tr><td>Tutup Shift:</td><td style="text-align: right;" id="zr-print-close">-</td></tr>
    </table>

    <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>

    <div style="font-size: 10px; font-weight: bold; margin-bottom: 2px;">RINGKASAN OMZET SISTEM:</div>
    <table style="width: 100%; font-size: 10px; line-height: 1.4; border-collapse: collapse;">
        <tr><td>Total Transaksi:</td><td style="text-align: right; font-weight: bold;" id="zr-print-tx">0 Nota</td></tr>
        <tr><td>Penjualan Tunai:</td><td style="text-align: right; font-weight: bold;" id="zr-print-cash">Rp 0</td></tr>
        <tr><td>Penjualan QRIS:</td><td style="text-align: right; font-weight: bold;" id="zr-print-qris">Rp 0</td></tr>
        <tr style="border-top: 1px solid #000; font-weight: bold;">
            <td>TOTAL PENJUALAN:</td>
            <td style="text-align: right;" id="zr-print-total">Rp 0</td>
        </tr>
    </table>

    <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>

    <div style="font-size: 10px; font-weight: bold; margin-bottom: 2px;">REKONSILIASI FISIK KAS:</div>
    <table style="width: 100%; font-size: 10px; line-height: 1.4; border-collapse: collapse;">
        <tr><td>Modal Awal (Float):</td><td style="text-align: right;" id="zr-print-float">Rp 0</td></tr>
        <tr><td>Kas Masuk (Tunai):</td><td style="text-align: right;" id="zr-print-cashin">Rp 0</td></tr>
        <tr><td style="font-weight: bold;">Uang Fisik di Laci:</td><td style="text-align: right; font-weight: bold;" id="zr-print-actual">Rp 0</td></tr>
        <tr style="border-top: 1px dashed #000; font-weight: bold;">
            <td>SELISIH KAS:</td>
            <td style="text-align: right;" id="zr-print-diff">Rp 0</td>
        </tr>
    </table>

    <div id="zr-print-notes-box" style="margin-top: 4px; font-size: 9px; font-style: italic;">
        Catatan: <span id="zr-print-notes">-</span>
    </div>

    <div style="border-top: 1px dashed #000; margin: 8px 0 4px 0;"></div>

    <table style="width: 100%; font-size: 9px; text-align: center; margin-top: 8px;">
        <tr>
            <td style="width: 50%;">Petugas Kasir,<br><br><br><br><span id="zr-sign-kasir">( .................. )</span></td>
            <td style="width: 50%;">Supervisor Keuangan,<br><br><br><br>( .................. )</td>
        </tr>
    </table>

    <div style="text-align: center; font-size: 8px; margin-top: 10px; color: #555;">
        Dicetak otomatis oleh Sistem POS Veloce TWB<br>
        Dokumen Sah Rekapitulasi Kas Unit Borobudur
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

/**
 * Buka modal tutup shift dan ambil data transaksi berjalan via API
 */
async function bukaModalTutupShift() {
    bukaModal('modal-closing-shift');
    
    // Tampilkan status memuat
    document.getElementById('cs-tx-count').textContent = '...';
    document.getElementById('cs-cash-sales').textContent = '...';
    document.getElementById('cs-qris-sales').textContent = '...';
    document.getElementById('cs-total-sales').textContent = '...';

    // Jam penutupan real-time WIB
    const nowWib = new Date();
    const timeStrWib = nowWib.toLocaleTimeString('id-ID', { hour12: false }) + ' WIB';
    document.getElementById('cs-closing-time').textContent = timeStrWib;

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

            const openWib = d.opening_time ? (d.opening_time.split(' ')[1] + ' WIB') : '08:00:00 WIB';
            document.getElementById('cs-opening-time').textContent = openWib;
            document.getElementById('cs-tx-count').textContent = d.nota_count;
            document.getElementById('cs-cash-sales').textContent = formatRupiahJs(d.cash_sales);
            document.getElementById('cs-qris-sales').textContent = formatRupiahJs(d.qris_sales);
            document.getElementById('cs-total-sales').textContent = formatRupiahJs(d.total_sales);

            // Default perhitungan awal
            hitungSelisihKas();
        }
    } catch (e) {
        console.error("Gagal memuat statistik shift:", e);
    }
}

/**
 * Isi otomatis uang fisik sesuai modal awal + penjualan tunai
 */
function samakanUangFisik() {
    const floatVal = parseInt(document.getElementById('cs-input-float').value) || 0;
    const target = floatVal + currentShiftData.cash_sales;
    document.getElementById('cs-input-actual').value = target;
    hitungSelisihKas();
}

/**
 * Hitung selisih kas fisik vs (float + cash sales)
 */
function hitungSelisihKas() {
    const floatVal = parseInt(document.getElementById('cs-input-float').value) || 0;
    const actualVal = parseInt(document.getElementById('cs-input-actual').value) || 0;
    
    currentShiftData.opening_cash = floatVal;
    currentShiftData.actual_cash = actualVal;

    const expected = floatVal + currentShiftData.cash_sales;
    currentShiftData.expected_cash = expected;

    const diff = actualVal - expected;
    currentShiftData.difference = diff;

    const badge = document.getElementById('cs-difference-badge');
    const card = document.getElementById('cs-diff-card');

    if (diff === 0) {
        badge.textContent = 'Rp 0 (Pas / Seimbang)';
        card.className = 'flex items-center justify-between p-3.5 rounded-xl bg-emerald-100/80 dark:bg-emerald-500/15 border border-emerald-300 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 transition-all duration-200';
    } else if (diff > 0) {
        badge.textContent = `+ ${formatRupiahJs(diff)} (Kelebihan Kas)`;
        card.className = 'flex items-center justify-between p-3.5 rounded-xl bg-amber-100/80 dark:bg-amber-500/15 border border-amber-300 dark:border-amber-500/30 text-amber-800 dark:text-amber-300 transition-all duration-200';
    } else {
        badge.textContent = `- ${formatRupiahJs(Math.abs(diff))} (Kekurangan Kas)`;
        card.className = 'flex items-center justify-between p-3.5 rounded-xl bg-rose-100/80 dark:bg-rose-500/15 border border-rose-300 dark:border-rose-500/30 text-rose-800 dark:text-rose-300 transition-all duration-200';
    }
}

/**
 * Simpan data penutupan shift ke server via REST API
 */
async function simpanClosingShift() {
    const btn = document.getElementById('btn-submit-closing');
    btn.disabled = true;
    btn.innerHTML = `<span>⏳</span> Menyimpan...`;

    const kasirNamaVal = currentShiftData.kasir_nama || (document.getElementById('cs-kasir-nama') ? document.getElementById('cs-kasir-nama').textContent.trim() : '') || '<?= htmlspecialchars($kasir_aktif ?? "Kasir") ?>';
    const posAktifVal = currentShiftData.pos_aktif || (document.getElementById('cs-pos-aktif') ? document.getElementById('cs-pos-aktif').textContent.trim() : '') || '<?= htmlspecialchars($pos_aktif ?? "Kasir Utama") ?>';

    const payload = {
        kasir_nama: kasirNamaVal,
        pos_aktif: posAktifVal,
        outlet_id: <?= intval($outlet_id ?? 1) ?>,
        opening_time: currentShiftData.opening_time || '<?= date("Y-m-d 08:00:00") ?>',
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
            
            // Konfirmasi cetak struk thermal
            if (confirm("Shift berhasil ditutup dan tersimpan resmi ke sistem!\n\nNomor Shift: " + json.data.shift_number + "\n\nApakah Anda ingin mencetak struk Z-Report sekarang?")) {
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
        btn.innerHTML = `<span>💾</span> Selesaikan & Tutup Shift`;
    }
}

/**
 * Cetak struk thermal Z-Report (58mm/80mm) dengan unhide otomatis
 */
function cetakZReportThermal() {
    const area = document.getElementById('area-cetak-zreport');
    if (!area) return;

    // Pastikan data thermal terisi dengan format WIB
    const nowWib = new Date();
    const timeStrWib = nowWib.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    const dateStrWib = nowWib.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });

    document.getElementById('zr-print-number').textContent = currentShiftData.shift_number || ('SFT-' + nowWib.toISOString().slice(0,10).replace(/-/g,'') + '-PRE');
    document.getElementById('zr-print-date').textContent = dateStrWib + ' ' + timeStrWib + ' WIB';
    document.getElementById('zr-print-kasir').textContent = currentShiftData.kasir_nama;
    document.getElementById('zr-sign-kasir').textContent = `( ${currentShiftData.kasir_nama} )`;
    document.getElementById('zr-print-pos').textContent = currentShiftData.pos_aktif;
    document.getElementById('zr-print-open').textContent = (currentShiftData.opening_time ? currentShiftData.opening_time.split(' ')[1] : '08:00:00') + ' WIB';
    document.getElementById('zr-print-close').textContent = timeStrWib + ' WIB';
    document.getElementById('zr-print-tx').textContent = currentShiftData.nota_count + ' Nota';
    document.getElementById('zr-print-cash').textContent = formatRupiahJs(currentShiftData.cash_sales);
    document.getElementById('zr-print-qris').textContent = formatRupiahJs(currentShiftData.qris_sales);
    document.getElementById('zr-print-total').textContent = formatRupiahJs(currentShiftData.total_sales);
    document.getElementById('zr-print-float').textContent = formatRupiahJs(currentShiftData.opening_cash);
    document.getElementById('zr-print-cashin').textContent = formatRupiahJs(currentShiftData.cash_sales);
    document.getElementById('zr-print-actual').textContent = formatRupiahJs(currentShiftData.actual_cash);
    
    const diff = currentShiftData.difference;
    document.getElementById('zr-print-diff').textContent = (diff === 0) ? 'Rp 0 (PAS)' : ((diff > 0 ? '+ ' : '- ') + formatRupiahJs(Math.abs(diff)));
    document.getElementById('zr-print-notes').textContent = document.getElementById('cs-input-notes').value.trim() || 'Tidak ada catatan.';

    // Buka tampilan cetak secara fisik agar terbaca printer thermal
    area.classList.remove('hidden');
    area.style.display = 'block';

    // Panggil dialog print browser
    window.print();

    // Sembunyikan kembali setelah proses cetak
    setTimeout(function() {
        area.classList.add('hidden');
        area.style.display = 'none';
    }, 1200);
}
</script>
