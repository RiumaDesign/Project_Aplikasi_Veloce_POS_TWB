<!-- ======================================================================== -->
<!-- MODAL TUTUP SHIFT KASIR (Z-REPORT) — TAMPILAN SOLID NON-TRANSPARAN      -->
<!-- Dilengkapi Shift Lifecycle State Machine (Update vs Buka Shift Baru)    -->
<!-- ======================================================================== -->
<div id="modal-closing-shift" class="fixed inset-0 z-50 hidden items-center justify-center p-4 transition-all duration-200" style="background-color: rgba(15, 23, 42, 0.75);">
    <div id="cs-modal-card" class="w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[94vh] animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header Modal (Solid & Tegas) -->
        <div id="cs-header" class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-xl shadow-sm" style="background-color: #fef3c7; border: 1px solid #fcd34d; color: #b45309;">
                    💵
                </div>
                <div>
                    <h3 id="cs-title" class="text-base font-black flex items-center gap-2 m-0">
                        <span id="cs-modal-heading">Tutup Shift Kasir</span>
                        <span style="background-color: #2563eb; color: #ffffff; font-size: 10px; padding: 2px 8px; border-radius: 9999px; font-weight: 800; letter-spacing: 0.5px;">Z-REPORT</span>
                    </h3>
                    <p id="cs-subtitle" class="text-xs m-0 mt-0.5 font-medium">
                        PT Taman Wisata Candi Borobudur &bull; <span style="font-weight: 700; color: #2563eb;">WIB (Waktu Indonesia Barat)</span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="tutupModal('modal-closing-shift')" id="cs-btn-close" class="p-2 rounded-xl transition cursor-pointer" title="Tutup Modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Body Modal (Solid Non-Transparan) -->
        <div class="p-6 overflow-y-auto custom-scroll space-y-4">

            <!-- Banner Notifikasi Jika Shift Sudah Ditutup (Anti-Double Info) -->
            <div id="cs-status-banner" class="hidden p-3.5 rounded-2xl flex items-center justify-between" style="background-color: #eff6ff; border: 1.5px solid #93c5fd; color: #1e40af;">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">ℹ️</span>
                    <div>
                        <div style="font-weight: 800; font-size: 12px;" id="cs-banner-title">Shift Ini Telah Ditutup Resmi</div>
                        <div style="font-size: 11px; color: #2563eb;" id="cs-banner-desc">Shift sebelumnya telah selesai. Anda dapat mengoreksi uang fisik atau memulai sesi baru.</div>
                    </div>
                </div>
                <span id="cs-banner-badge" style="background-color: #2563eb; color: #ffffff; font-size: 10px; padding: 2px 8px; border-radius: 6px; font-weight: 700;">TERKUNCI</span>
            </div>
            
            <!-- 1. Baris Informasi Petugas & Jam WIB (Solid Strip) -->
            <div id="cs-info-strip" class="p-3 rounded-2xl flex flex-wrap items-center justify-between gap-2 text-xs">
                <div>
                    <span id="cs-label-kasir" class="font-medium">Petugas:</span>
                    <strong id="cs-kasir-nama" class="ml-1 font-bold"><?= htmlspecialchars($kasir_aktif ?? 'Kasir') ?></strong>
                </div>
                <div>
                    <span id="cs-label-pos" class="font-medium">Terminal:</span>
                    <strong id="cs-pos-aktif" class="ml-1 font-bold" style="color: #2563eb;"><?= htmlspecialchars($pos_aktif ?? 'Kasir Utama') ?></strong>
                </div>
                <div>
                    <span id="cs-label-buka" class="font-medium">Buka Shift:</span>
                    <strong id="cs-opening-time" class="ml-1 font-bold font-mono">--:--:-- WIB</strong>
                </div>
                <div>
                    <span id="cs-label-tutup" class="font-medium">Tutup Shift:</span>
                    <strong id="cs-closing-time" class="ml-1 font-bold font-mono" style="color: #059669;"><?= date('H:i:s') ?> WIB</strong>
                </div>
            </div>

            <!-- 2. Tiga Kartu Ringkasan Penjualan (Solid Cards, Kontras Tinggi) -->
            <div class="grid grid-cols-3 gap-3">
                <!-- Penjualan Tunai -->
                <div class="p-3.5 rounded-2xl" style="background-color: #ecfdf5; border: 1.5px solid #a7f3d0;">
                    <span style="color: #065f46; font-size: 11px; font-weight: 800; display: block;">💵 Uang Tunai</span>
                    <span id="cs-cash-sales" style="color: #047857; font-size: 17px; font-weight: 900; display: block; margin-top: 2px;">Rp 0</span>
                    <span style="color: #059669; font-size: 10px; font-weight: 600; display: block;">Cash di Laci</span>
                </div>
                <!-- Penjualan QRIS -->
                <div class="p-3.5 rounded-2xl" style="background-color: #eff6ff; border: 1.5px solid #bfdbfe;">
                    <span style="color: #1e40af; font-size: 11px; font-weight: 800; display: block;">📱 Non-Tunai</span>
                    <span id="cs-qris-sales" style="color: #1d4ed8; font-size: 17px; font-weight: 900; display: block; margin-top: 2px;">Rp 0</span>
                    <span style="color: #2563eb; font-size: 10px; font-weight: 600; display: block;">QRIS Masuk</span>
                </div>
                <!-- Total Omzet -->
                <div class="p-3.5 rounded-2xl" style="background-color: #f5f3ff; border: 1.5px solid #ddd6fe;">
                    <span style="color: #4338ca; font-size: 11px; font-weight: 800; display: block;">💰 Total Omzet</span>
                    <span id="cs-total-sales" style="color: #4338ca; font-size: 17px; font-weight: 900; display: block; margin-top: 2px;">Rp 0</span>
                    <span id="cs-tx-count-label" style="color: #6366f1; font-size: 10px; font-weight: 600; display: block;"><span id="cs-tx-count">0</span> Nota Sukses</span>
                </div>
            </div>

            <!-- 3. Form Rekonsiliasi Kas Laci (Solid & Bersih) -->
            <div id="cs-reconciliation-box" class="p-4 rounded-2xl space-y-3.5">
                <div id="cs-rec-header" class="flex items-center justify-between pb-2">
                    <h4 id="cs-rec-title" class="text-xs font-black uppercase tracking-wider m-0 flex items-center gap-1.5">
                        <span>🧮</span> Penghitungan Fisik Kas
                    </h4>
                    <span style="font-size: 11px; font-weight: 700; color: #2563eb;">Kalkulasi Otomatis</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- Modal Awal Float -->
                    <div>
                        <label id="cs-label-float" class="block text-xs font-bold mb-1">
                            Modal Awal (*Float*):
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: #64748b;">Rp</span>
                            <input type="number" id="cs-input-float" value="100000" min="0" step="5000" oninput="hitungSelisihKas()" 
                                   class="w-full pl-10 pr-3 py-2.5 rounded-xl text-sm font-bold focus:outline-none shadow-sm transition">
                        </div>
                        <span id="cs-sub-float" class="text-[10px] mt-1 block">Uang kembalian awal saat buka shift.</span>
                    </div>

                    <!-- Fisik Kas Laci -->
                    <div>
                        <label class="block text-xs font-bold mb-1 flex items-center justify-between" style="color: #b45309;">
                            <span>Uang Fisik di Laci:</span>
                            <span style="color: #2563eb; font-size: 11px; font-weight: 800; cursor: pointer;" onclick="samakanUangFisik()" title="Isi otomatis dengan uang yang seharusnya ada di laci">[Isi Pas]</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: #b45309;">Rp</span>
                            <input type="number" id="cs-input-actual" value="100000" min="0" step="1000" oninput="hitungSelisihKas()" 
                                   class="w-full pl-10 pr-3 py-2.5 rounded-xl text-sm font-black focus:outline-none shadow-sm transition">
                        </div>
                        <span id="cs-sub-actual" class="text-[10px] mt-1 block">Total uang tunai kertas & koin di laci.</span>
                    </div>
                </div>

                <!-- Hasil Selisih Kas (Solid Banner) -->
                <div id="cs-diff-card" class="flex items-center justify-between p-3.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">⚖️</span>
                        <span class="text-xs font-black uppercase tracking-wider">Hasil Rekonsiliasi:</span>
                    </div>
                    <span id="cs-difference-badge" class="text-sm font-black">Rp 0 (Pas / Seimbang)</span>
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label id="cs-label-notes" class="block text-[11px] font-bold mb-1">Catatan Tambahan (Opsional):</label>
                    <input type="text" id="cs-input-notes" placeholder="Tuliskan keterangan serah terima kas jika ada..." 
                           class="w-full px-3 py-2 rounded-xl text-xs focus:outline-none shadow-sm transition">
                </div>
            </div>

        </div>

        <!-- Footer Modal (Solid & Kontras Tinggi dengan Multi-Aksi) -->
        <div id="cs-footer" class="px-6 py-4 flex items-center justify-between gap-2.5">
            <button type="button" onclick="tutupModal('modal-closing-shift')" id="cs-btn-cancel" class="px-3.5 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer">
                Batal
            </button>
            <div class="flex items-center gap-2">
                <button type="button" id="btn-print-zreport" onclick="cetakZReportThermal()" class="px-3.5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                    <span>🖨️</span> <span id="cs-lbl-print">Cetak Z-Report</span>
                </button>
                <button type="button" id="btn-new-shift" onclick="mulaiShiftBaru()" class="hidden px-3.5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm" style="background-color: #3b82f6; color: #ffffff;">
                    <span>🔓</span> Mulai Shift Baru
                </button>
                <button type="button" id="btn-submit-closing" onclick="simpanClosingShift()" class="px-4 py-2.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-md">
                    <span>💾</span> <span id="cs-lbl-submit">Selesaikan & Tutup Shift</span>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ======================================================================== -->
<!-- CSS KHUSUS NON-TRANSPARAN (SOLID HIGH CONTRAST) MODAL TUTUP SHIFT         -->
<!-- ======================================================================== -->
<style id="twb-closing-shift-solid-style">
/* -------------------------------------------------------------
   1. TEMA TERANG: SOLID PUTIH BERSIH & BEBAS TRANSPARANSI
   ------------------------------------------------------------- */
html[data-theme="light"] #cs-modal-card,
html.light #cs-modal-card,
body.light-theme #cs-modal-card,
html:not(.dark) #cs-modal-card {
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    color: #0f172a !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
}

html[data-theme="light"] #cs-header,
html.light #cs-header,
body.light-theme #cs-header,
html:not(.dark) #cs-header {
    background-color: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

html[data-theme="light"] #cs-title,
html.light #cs-title,
body.light-theme #cs-title,
html:not(.dark) #cs-title {
    color: #0f172a !important;
}

html[data-theme="light"] #cs-subtitle,
html.light #cs-subtitle,
body.light-theme #cs-subtitle,
html:not(.dark) #cs-subtitle {
    color: #475569 !important;
}

html[data-theme="light"] #cs-btn-close,
html.light #cs-btn-close,
body.light-theme #cs-btn-close,
html:not(.dark) #cs-btn-close {
    background-color: #f1f5f9 !important;
    color: #475569 !important;
}
html[data-theme="light"] #cs-btn-close:hover,
html.light #cs-btn-close:hover,
body.light-theme #cs-btn-close:hover,
html:not(.dark) #cs-btn-close:hover {
    background-color: #e2e8f0 !important;
    color: #0f172a !important;
}

/* Strip Info */
html[data-theme="light"] #cs-info-strip,
html.light #cs-info-strip,
body.light-theme #cs-info-strip,
html:not(.dark) #cs-info-strip {
    background-color: #f1f5f9 !important;
    border: 1px solid #e2e8f0 !important;
    color: #334155 !important;
}
html[data-theme="light"] #cs-kasir-nama,
html.light #cs-kasir-nama,
body.light-theme #cs-kasir-nama,
html:not(.dark) #cs-kasir-nama {
    color: #0f172a !important;
}
html[data-theme="light"] #cs-opening-time,
html.light #cs-opening-time,
body.light-theme #cs-opening-time,
html:not(.dark) #cs-opening-time {
    color: #1e293b !important;
}

/* Kotak Rekonsiliasi */
html[data-theme="light"] #cs-reconciliation-box,
html.light #cs-reconciliation-box,
body.light-theme #cs-reconciliation-box,
html:not(.dark) #cs-reconciliation-box {
    background-color: #f8fafc !important;
    border: 1px solid #cbd5e1 !important;
}
html[data-theme="light"] #cs-rec-header,
html.light #cs-rec-header,
body.light-theme #cs-rec-header,
html:not(.dark) #cs-rec-header {
    border-bottom: 1px solid #e2e8f0 !important;
}
html[data-theme="light"] #cs-rec-title,
html.light #cs-rec-title,
body.light-theme #cs-rec-title,
html:not(.dark) #cs-rec-title {
    color: #0f172a !important;
}
html[data-theme="light"] #cs-label-float,
html.light #cs-label-float,
body.light-theme #cs-label-float,
html:not(.dark) #cs-label-float {
    color: #1e293b !important;
}
html[data-theme="light"] #cs-label-notes,
html.light #cs-label-notes,
body.light-theme #cs-label-notes,
html:not(.dark) #cs-label-notes {
    color: #334155 !important;
}
html[data-theme="light"] #cs-sub-float,
html[data-theme="light"] #cs-sub-actual,
html.light #cs-sub-float,
html.light #cs-sub-actual,
body.light-theme #cs-sub-float,
body.light-theme #cs-sub-actual,
html:not(.dark) #cs-sub-float,
html:not(.dark) #cs-sub-actual {
    color: #64748b !important;
}

/* Input Fields Solid */
html[data-theme="light"] #cs-input-float,
html.light #cs-input-float,
body.light-theme #cs-input-float,
html:not(.dark) #cs-input-float {
    background-color: #ffffff !important;
    border: 2px solid #cbd5e1 !important;
    color: #0f172a !important;
}
html[data-theme="light"] #cs-input-float:focus,
html.light #cs-input-float:focus,
body.light-theme #cs-input-float:focus,
html:not(.dark) #cs-input-float:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2) !important;
}

html[data-theme="light"] #cs-input-actual,
html.light #cs-input-actual,
body.light-theme #cs-input-actual,
html:not(.dark) #cs-input-actual {
    background-color: #ffffff !important;
    border: 2px solid #f59e0b !important;
    color: #b45309 !important;
}
html[data-theme="light"] #cs-input-actual:focus,
html.light #cs-input-actual:focus,
body.light-theme #cs-input-actual:focus,
html:not(.dark) #cs-input-actual:focus {
    border-color: #d97706 !important;
    box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.2) !important;
}

html[data-theme="light"] #cs-input-notes,
html.light #cs-input-notes,
body.light-theme #cs-input-notes,
html:not(.dark) #cs-input-notes {
    background-color: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    color: #0f172a !important;
}
html[data-theme="light"] #cs-input-notes:focus,
html.light #cs-input-notes:focus,
body.light-theme #cs-input-notes:focus,
html:not(.dark) #cs-input-notes:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2) !important;
}

/* Footer Solid */
html[data-theme="light"] #cs-footer,
html.light #cs-footer,
body.light-theme #cs-footer,
html:not(.dark) #cs-footer {
    background-color: #f8fafc !important;
    border-top: 1px solid #e2e8f0 !important;
}
html[data-theme="light"] #cs-btn-cancel,
html.light #cs-btn-cancel,
body.light-theme #cs-btn-cancel,
html:not(.dark) #cs-btn-cancel {
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    color: #334155 !important;
}
html[data-theme="light"] #cs-btn-cancel:hover,
html.light #cs-btn-cancel:hover,
body.light-theme #cs-btn-cancel:hover,
html:not(.dark) #cs-btn-cancel:hover {
    background-color: #e2e8f0 !important;
    color: #0f172a !important;
}

html[data-theme="light"] #btn-print-zreport,
html.light #btn-print-zreport,
body.light-theme #btn-print-zreport,
html:not(.dark) #btn-print-zreport {
    background-color: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    color: #0f172a !important;
}
html[data-theme="light"] #btn-print-zreport:hover,
html.light #btn-print-zreport:hover,
body.light-theme #btn-print-zreport:hover,
html:not(.dark) #btn-print-zreport:hover {
    background-color: #f1f5f9 !important;
    border-color: #94a3b8 !important;
}

/* -------------------------------------------------------------
   2. TEMA GELAP: SOLID DARK SLATE & KONTRAS TINGGI
   ------------------------------------------------------------- */
html.dark #cs-modal-card,
html[data-theme="dark"] #cs-modal-card {
    background-color: #0f172a !important;
    border: 1px solid #334155 !important;
    color: #ffffff !important;
}
html.dark #cs-header,
html[data-theme="dark"] #cs-header {
    background-color: #1e293b !important;
    border-bottom: 1px solid #334155 !important;
}
html.dark #cs-title,
html[data-theme="dark"] #cs-title {
    color: #ffffff !important;
}
html.dark #cs-subtitle,
html[data-theme="dark"] #cs-subtitle {
    color: #94a3b8 !important;
}
html.dark #cs-btn-close,
html[data-theme="dark"] #cs-btn-close {
    background-color: #334155 !important;
    color: #94a3b8 !important;
}
html.dark #cs-info-strip,
html[data-theme="dark"] #cs-info-strip {
    background-color: #1e293b !important;
    border: 1px solid #334155 !important;
    color: #94a3b8 !important;
}
html.dark #cs-reconciliation-box,
html[data-theme="dark"] #cs-reconciliation-box {
    background-color: #1e293b !important;
    border: 1px solid #334155 !important;
}
html.dark #cs-rec-header,
html[data-theme="dark"] #cs-rec-header {
    border-bottom: 1px solid #334155 !important;
}
html.dark #cs-rec-title,
html[data-theme="dark"] #cs-rec-title {
    color: #f1f5f9 !important;
}
html.dark #cs-label-float,
html.dark #cs-label-notes,
html[data-theme="dark"] #cs-label-float,
html[data-theme="dark"] #cs-label-notes {
    color: #cbd5e1 !important;
}
html.dark #cs-input-float,
html.dark #cs-input-notes,
html[data-theme="dark"] #cs-input-float,
html[data-theme="dark"] #cs-input-notes {
    background-color: #0f172a !important;
    border: 1.5px solid #475569 !important;
    color: #ffffff !important;
}
html.dark #cs-input-actual,
html[data-theme="dark"] #cs-input-actual {
    background-color: #0f172a !important;
    border: 2px solid #f59e0b !important;
    color: #fbbf24 !important;
}
html.dark #cs-footer,
html[data-theme="dark"] #cs-footer {
    background-color: #1e293b !important;
    border-top: 1px solid #334155 !important;
}
html.dark #cs-btn-cancel,
html[data-theme="dark"] #cs-btn-cancel {
    background-color: #334155 !important;
    border: 1px solid #475569 !important;
    color: #cbd5e1 !important;
}
html.dark #btn-print-zreport,
html[data-theme="dark"] #btn-print-zreport {
    background-color: #334155 !important;
    border: 1px solid #475569 !important;
    color: #ffffff !important;
}
</style>

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
    shift_state: 'ACTIVE_PENDING',
    shift_id: null,
    shift_number: null,
    kasir_nama: '<?= htmlspecialchars($kasir_aktif ?? 'Kasir') ?>',
    pos_aktif: '<?= htmlspecialchars($pos_aktif ?? 'Kasir Utama') ?>',
    opening_time: '',
    closing_time: '',
    nota_count: 0,
    cash_sales: 0,
    qris_sales: 0,
    total_sales: 0,
    opening_cash: 100000,
    actual_cash: 100000,
    expected_cash: 100000,
    difference: 0,
    notes: ''
};

function formatRupiahJs(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

/**
 * Buka modal tutup shift dan periksa status siklus shift via API
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
            currentShiftData.shift_state  = d.shift_state;
            currentShiftData.shift_id     = d.shift_id;
            currentShiftData.shift_number = d.shift_number;
            currentShiftData.opening_time = d.opening_time;
            currentShiftData.closing_time = d.closing_time;
            currentShiftData.nota_count   = d.nota_count;
            currentShiftData.cash_sales   = d.cash_sales;
            currentShiftData.qris_sales   = d.qris_sales;
            currentShiftData.total_sales  = d.total_sales;
            currentShiftData.opening_cash = d.opening_cash;
            currentShiftData.actual_cash  = d.actual_cash;
            currentShiftData.difference   = d.difference;
            currentShiftData.notes        = d.notes || '';

            const banner = document.getElementById('cs-status-banner');
            const submitBtn = document.getElementById('btn-submit-closing');
            const submitLbl = document.getElementById('cs-lbl-submit');
            const newShiftBtn = document.getElementById('btn-new-shift');
            const printLbl = document.getElementById('cs-lbl-print');
            const heading = document.getElementById('cs-modal-heading');

            if (d.shift_state === 'ALREADY_CLOSED') {
                // KONDISI: Shift Terakhir Sudah Ditutup & Belum Ada Transaksi Baru
                banner.classList.remove('hidden');
                document.getElementById('cs-banner-title').textContent = `Shift Terakhir Telah Ditutup (No: ${d.shift_number})`;
                document.getElementById('cs-banner-desc').textContent = `Selesai pukul ${d.closing_time ? d.closing_time.split(' ')[1] : ''} WIB. Belum ada transaksi baru. Anda dapat mengoreksi uang fisik/catatan tanpa membuat data ganda di admin.`;
                
                heading.textContent = 'Rekapitulasi Shift (Sudah Ditutup)';
                submitLbl.textContent = 'Simpan Koreksi Shift';
                submitBtn.style.backgroundColor = '#2563eb';
                submitBtn.style.color = '#ffffff';
                newShiftBtn.classList.remove('hidden');
                printLbl.textContent = 'Cetak Ulang Z-Report';

                // Pre-fill input
                document.getElementById('cs-input-float').value = d.opening_cash;
                document.getElementById('cs-input-actual').value = d.actual_cash;
                document.getElementById('cs-input-notes').value = d.notes || '';

                // Update tombol navbar kasir
                const navBtn = document.getElementById('btn-navbar-closing-shift');
                if (navBtn) {
                    navBtn.className = "bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer";
                    navBtn.innerHTML = `<span>✅</span> <span class="hidden sm:inline">Shift Selesai</span>`;
                }
            } else {
                // KONDISI: Shift Baru / Ada Transaksi Pending yang Belum Ditutup
                banner.classList.add('hidden');
                heading.textContent = 'Tutup Shift Kasir';
                submitLbl.textContent = 'Selesaikan & Tutup Shift';
                submitBtn.style.backgroundColor = '#d97706';
                submitBtn.style.color = '#ffffff';
                newShiftBtn.classList.add('hidden');
                printLbl.textContent = 'Cetak Struk Z-Report';

                document.getElementById('cs-input-float').value = d.opening_cash;
                document.getElementById('cs-input-actual').value = d.actual_cash;
                document.getElementById('cs-input-notes').value = '';

                // Update tombol navbar kasir
                const navBtn = document.getElementById('btn-navbar-closing-shift');
                if (navBtn) {
                    navBtn.className = "bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer";
                    navBtn.innerHTML = `<span>💵</span> <span class="hidden sm:inline">Tutup Shift</span>`;
                }
            }

            const openWib = d.opening_time ? (d.opening_time.split(' ')[1] + ' WIB') : '08:00:00 WIB';
            document.getElementById('cs-opening-time').textContent = openWib;
            if (d.closing_time) {
                document.getElementById('cs-closing-time').textContent = (d.closing_time.split(' ')[1] + ' WIB');
            }
            document.getElementById('cs-tx-count').textContent = d.nota_count;
            document.getElementById('cs-cash-sales').textContent = formatRupiahJs(d.cash_sales);
            document.getElementById('cs-qris-sales').textContent = formatRupiahJs(d.qris_sales);
            document.getElementById('cs-total-sales').textContent = formatRupiahJs(d.total_sales);

            // Hitung selisih
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
        card.style.backgroundColor = '#dcfce7';
        card.style.border = '1.5px solid #86efac';
        card.style.color = '#14532d';
    } else if (diff > 0) {
        badge.textContent = `+ ${formatRupiahJs(diff)} (Kelebihan Kas)`;
        card.style.backgroundColor = '#fef3c7';
        card.style.border = '1.5px solid #fcd34d';
        card.style.color = '#92400e';
    } else {
        badge.textContent = `- ${formatRupiahJs(Math.abs(diff))} (Kekurangan Kas)`;
        card.style.backgroundColor = '#fee2e2';
        card.style.border = '1.5px solid #fca5a5';
        card.style.color = '#991b1b';
    }
}

/**
 * Simpan data penutupan shift (Bisa INSERT shift baru atau UPDATE shift lama)
 */
async function simpanClosingShift() {
    const btn = document.getElementById('btn-submit-closing');
    btn.disabled = true;
    btn.innerHTML = `<span>⏳</span> Menyimpan...`;

    const floatVal = parseInt(document.getElementById('cs-input-float').value) || 100000;
    const actualVal = parseInt(document.getElementById('cs-input-actual').value) || 0;
    const notesVal = document.getElementById('cs-input-notes').value.trim();

    try {
        if (currentShiftData.shift_state === 'ALREADY_CLOSED') {
            // MODE UPDATE: Mengoreksi data shift terakhir (Anti-Duplicate)
            const payload = {
                shift_id: currentShiftData.shift_id,
                opening_cash: floatVal,
                actual_cash: actualVal,
                notes: notesVal
            };

            const res = await fetch('api.php?action=update_closing_shift', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();

            if (json.status === 'success') {
                alert("Koreksi data shift berhasil disimpan!\n\nNomor Shift: " + json.data.shift_number + "\nData telah diperbarui di sistem admin tanpa duplikasi.");
                tutupModal('modal-closing-shift');
            } else {
                alert("Gagal mengoreksi shift: " + (json.message || 'Terjadi kesalahan.'));
            }
        } else {
            // MODE BARU: Menutup shift aktif & mengunci transaksi
            const kasirNamaVal = currentShiftData.kasir_nama || (document.getElementById('cs-kasir-nama') ? document.getElementById('cs-kasir-nama').textContent.trim() : '') || '<?= htmlspecialchars($kasir_aktif ?? "Kasir") ?>';
            const posAktifVal = currentShiftData.pos_aktif || (document.getElementById('cs-pos-aktif') ? document.getElementById('cs-pos-aktif').textContent.trim() : '') || '<?= htmlspecialchars($pos_aktif ?? "Kasir Utama") ?>';

            const payload = {
                kasir_nama: kasirNamaVal,
                pos_aktif: posAktifVal,
                outlet_id: <?= intval($outlet_id ?? 1) ?>,
                opening_time: currentShiftData.opening_time || '<?= date("Y-m-d 08:00:00") ?>',
                opening_cash: floatVal,
                actual_cash: actualVal,
                notes: notesVal
            };

            const res = await fetch('api.php?action=submit_closing_shift', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();

            if (json.status === 'success' && json.data) {
                currentShiftData.shift_number = json.data.shift_number;
                currentShiftData.shift_id = json.data.shift_id;
                currentShiftData.shift_state = 'ALREADY_CLOSED';
                
                // Konfirmasi cetak struk thermal
                if (confirm("Shift berhasil ditutup dan tersimpan resmi ke sistem!\n\nNomor Shift: " + json.data.shift_number + "\n\nApakah Anda ingin mencetak struk Z-Report sekarang?")) {
                    cetakZReportThermal();
                }
                tutupModal('modal-closing-shift');
                // Segarkan status modal dan tombol navbar secara asinkron tanpa me-reload seluruh halaman
                setTimeout(() => {
                    bukaModalTutupShift();
                }, 800);
            } else {
                alert("Gagal menutup shift: " + (json.message || 'Terjadi kesalahan sistem.'));
            }
        }
    } catch (e) {
        alert("Terjadi kesalahan jaringan saat memproses data shift.");
        console.error(e);
    } finally {
        btn.disabled = false;
        btn.innerHTML = (currentShiftData.shift_state === 'ALREADY_CLOSED') 
            ? `<span>💾</span> Simpan Koreksi Shift`
            : `<span>💾</span> Selesaikan & Tutup Shift`;
    }
}

/**
 * Memulai sesi shift baru untuk kasir
 */
function mulaiShiftBaru() {
    if (confirm("Mulai sesi shift baru untuk kasir ini?\n\nTransaksi penjualan yang dibuat setelah ini akan otomatis masuk ke rekapitulasi shift baru.")) {
        tutupModal('modal-closing-shift');
        const navBtn = document.getElementById('btn-navbar-closing-shift');
        if (navBtn) {
            navBtn.className = "bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer";
            navBtn.innerHTML = `<span>💵</span> <span class="hidden sm:inline">Tutup Shift</span>`;
        }
        alert("Sesi shift baru siap. Silakan melayani transaksi baru.");
    }
}

/**
 * Cetak struk thermal Z-Report (58mm/80mm) dengan unhide otomatis & isolasi print
 */
function cetakZReportThermal() {
    const area = document.getElementById('area-cetak-zreport');
    if (!area) return;

    // Aktifkan mode isolasi print Z-Report (sembunyikan nota belanja)
    document.body.classList.add('printing-zreport');
    document.body.classList.remove('printing-nota');

    const areaNota = document.getElementById('area-cetak-nota');
    if (areaNota) {
        areaNota.classList.add('hidden');
        areaNota.style.display = 'none';
    }

    // Pastikan data thermal terisi dengan format WIB
    const nowWib = new Date();
    const timeStrWib = nowWib.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    const dateStrWib = nowWib.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });

    // Fallback data lengkap agar tidak muncul tanda minus atau 0 jika belum ter-fetch
    const kasirNamaVal = currentShiftData.kasir_nama || (document.getElementById('cs-kasir-nama') ? document.getElementById('cs-kasir-nama').textContent.trim() : '') || 'Kasir';
    const posAktifVal = currentShiftData.pos_aktif || (document.getElementById('cs-pos-aktif') ? document.getElementById('cs-pos-aktif').textContent.trim() : '') || 'Kasir Utama';
    const floatVal = currentShiftData.opening_cash || parseInt(document.getElementById('cs-input-float')?.value) || 100000;
    const actualVal = currentShiftData.actual_cash || parseInt(document.getElementById('cs-input-actual')?.value) || floatVal;
    const cashSales = (currentShiftData.cash_sales !== undefined && currentShiftData.cash_sales !== null) ? currentShiftData.cash_sales : 0;
    const qrisSales = (currentShiftData.qris_sales !== undefined && currentShiftData.qris_sales !== null) ? currentShiftData.qris_sales : 0;
    const totalSales = (currentShiftData.total_sales !== undefined && currentShiftData.total_sales !== null) ? currentShiftData.total_sales : (cashSales + qrisSales);
    const txCount = currentShiftData.nota_count || (document.getElementById('cs-tx-count') ? parseInt(document.getElementById('cs-tx-count').textContent) : 0) || 0;
    const notesVal = document.getElementById('cs-input-notes')?.value.trim() || currentShiftData.notes || 'Tidak ada catatan.';
    const diff = actualVal - (floatVal + cashSales);

    document.getElementById('zr-print-number').textContent = currentShiftData.shift_number || ('SFT-' + nowWib.toISOString().slice(0,10).replace(/-/g,'') + '-RESMI');
    document.getElementById('zr-print-date').textContent = dateStrWib + ' ' + timeStrWib + ' WIB';
    document.getElementById('zr-print-kasir').textContent = kasirNamaVal;
    document.getElementById('zr-sign-kasir').textContent = `( ${kasirNamaVal} )`;
    document.getElementById('zr-print-pos').textContent = posAktifVal;
    document.getElementById('zr-print-open').textContent = (currentShiftData.opening_time ? currentShiftData.opening_time.split(' ')[1] : '08:00:00') + ' WIB';
    document.getElementById('zr-print-close').textContent = timeStrWib + ' WIB';
    document.getElementById('zr-print-tx').textContent = txCount + ' Nota';
    document.getElementById('zr-print-cash').textContent = formatRupiahJs(cashSales);
    document.getElementById('zr-print-qris').textContent = formatRupiahJs(qrisSales);
    document.getElementById('zr-print-total').textContent = formatRupiahJs(totalSales);
    document.getElementById('zr-print-float').textContent = formatRupiahJs(floatVal);
    document.getElementById('zr-print-cashin').textContent = formatRupiahJs(cashSales);
    document.getElementById('zr-print-actual').textContent = formatRupiahJs(actualVal);
    document.getElementById('zr-print-diff').textContent = (diff === 0) ? 'Rp 0 (PAS)' : ((diff > 0 ? '+ ' : '- ') + formatRupiahJs(Math.abs(diff)));
    document.getElementById('zr-print-notes').textContent = notesVal;

    // Buka tampilan cetak secara fisik agar terbaca printer thermal
    area.classList.remove('hidden');
    area.style.display = 'block';

    // Panggil dialog print browser
    window.print();

    // Sembunyikan kembali setelah proses cetak
    setTimeout(function() {
        document.body.classList.remove('printing-zreport');
        area.classList.add('hidden');
        area.style.display = 'none';
    }, 1500);
}
</script>
