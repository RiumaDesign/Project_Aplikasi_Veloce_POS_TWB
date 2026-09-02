<?php
/**
 * View: Kelola Petugas Kasir
 * File: views/dashboard/kasir.php
 */

$kasirs = $conn->query("SELECT * FROM `kasir` ORDER BY `id` DESC");
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">👥 Manajemen Petugas Kasir</h2>
            <p class="text-xs text-slate-400 mt-1">Tambah, ubah nama, dan atur kata sandi akun kasir aktif.</p>
        </div>
        <button onclick="bukaModal('modal-add-kasir')" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-4 py-3 rounded-2xl shadow-lg shadow-blue-600/20 flex items-center gap-2 transition">
            <span>＋</span> <span>Tambah Kasir Baru</span>
        </button>
    </div>

    <!-- Tabel Petugas Kasir -->
    <div class="glass-card-dark rounded-3xl border border-white/5 overflow-hidden shadow-xl">
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left text-xs min-w-[500px]">
            <thead>
                <tr class="bg-white/[0.03] text-slate-400 font-bold uppercase tracking-wider border-b border-white/5">
                    <th class="p-4">ID</th>
                    <th class="p-4">Nama Lengkap Petugas</th>
                    <th class="p-4 text-center">Hak Akses (Role)</th>
                    <th class="p-4 text-right">Opsi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 font-medium text-slate-300">
                <?php while ($k = $kasirs->fetch_assoc()): ?>
                <tr class="hover:bg-white/[0.02] transition">
                    <td class="p-4 font-mono font-bold text-slate-500">#<?= $k['id'] ?></td>
                    <td class="p-4 text-sm font-bold text-white"><?= htmlspecialchars($k['nama']) ?></td>
                    <td class="p-4 text-center">
                        <?php if (($k['role'] ?? '') === 'admin'): ?>
                            <span class="bg-blue-500/20 text-blue-300 border border-blue-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Admin Khusus</span>
                        <?php else: ?>
                            <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Kasir Terminal</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="bukaEditKasir(<?= $k['id'] ?>, '<?= htmlspecialchars($k['nama'], ENT_QUOTES) ?>')" class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 px-3 py-1.5 rounded-xl font-bold transition">
                                Ubah
                            </button>
                            <?php if (($k['role'] ?? '') !== 'admin'): ?>
                            <form method="POST" class="inline" onsubmit="return confirmSubmitForm(event, this, { title: 'Hapus Petugas Kasir?', message: 'Apakah Anda yakin ingin menghapus petugas kasir <?= htmlspecialchars($k['nama'], ENT_QUOTES) ?>? Akun ini tidak akan dapat login lagi.', confirmText: 'Ya, Hapus Kasir', icon: '🗑️' })">
                                <input type="hidden" name="crud_action" value="delete_kasir">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                <button type="submit" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 px-3 py-1.5 rounded-xl font-bold transition">
                                    Hapus
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TABEL REKAPITULASI & RIWAYAT TUTUP SHIFT KASIR (Z-REPORT AUDIT)           -->
    <!-- ========================================================================= -->
    <?php
    $shift_history_res = $conn->query("
        SELECT cs.*, l.name as outlet_name 
        FROM `cashier_shifts` cs
        LEFT JOIN `locations` l ON cs.outlet_id = l.id
        ORDER BY cs.id DESC
        LIMIT 30
    ");
    $shifts_count = $shift_history_res ? $shift_history_res->num_rows : 0;
    ?>
    <div class="space-y-3 pt-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-lg font-black text-white flex items-center gap-2">
                    <span>💵</span> <span>Rekapitulasi Tutup Shift Kasir (Z-Report)</span>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-mono font-bold">
                        <?= $shifts_count ?> Laporan Tersimpan
                    </span>
                </h3>
                <p class="text-xs text-slate-400">Bukti serah terima setoran kas, rekonsiliasi tunai vs QRIS, dan transparansi selisih fisik.</p>
            </div>
            <div class="text-xs text-slate-400">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-900 border border-white/10 font-medium">
                    <span>🏛️</span> Standar Akuntansi Resmi PT TWB
                </span>
            </div>
        </div>

        <div class="glass-card-dark rounded-3xl border border-white/5 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[850px]">
                    <thead>
                        <tr class="bg-white/[0.03] text-slate-400 font-bold uppercase tracking-wider border-b border-white/5">
                            <th class="p-3.5">No. Referensi Shift</th>
                            <th class="p-3.5">Waktu Closing</th>
                            <th class="p-3.5">Petugas & Terminal</th>
                            <th class="p-3.5 text-center">Nota</th>
                            <th class="p-3.5 text-right">Penjualan Tunai</th>
                            <th class="p-3.5 text-right">Penjualan QRIS</th>
                            <th class="p-3.5 text-right font-black text-white">Grand Total</th>
                            <th class="p-3.5 text-right">Fisik di Laci</th>
                            <th class="p-3.5 text-center">Selisih Kas</th>
                            <th class="p-3.5">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 font-medium text-slate-300">
                        <?php if ($shifts_count === 0): ?>
                            <tr>
                                <td colspan="10" class="p-8 text-center text-slate-400">
                                    <span class="text-3xl block mb-2">📋</span>
                                    <span class="text-sm font-bold text-slate-300">Belum Ada Rekapitulasi Shift Tersimpan</span>
                                    <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                                        Saat petugas kasir menekan tombol <strong class="text-amber-400">"Tutup Shift"</strong> pada terminal kasir POS, bukti setoran resmi (Z-Report) akan tersimpan dan tampil di tabel ini secara otomatis.
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($s = $shift_history_res->fetch_assoc()): 
                                $diff = intval($s['difference']);
                                $diffBadge = match(true) {
                                    $diff === 0 => '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">✓ Rp 0 (Pas)</span>',
                                    $diff > 0   => '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">+ Rp ' . number_format($diff, 0, ',', '.') . ' (Lebih)</span>',
                                    default     => '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">- Rp ' . number_format(abs($diff), 0, ',', '.') . ' (Kurang)</span>'
                                };
                            ?>
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="p-3.5 font-mono font-bold text-blue-400">
                                    <?= htmlspecialchars($s['shift_number']) ?>
                                </td>
                                <td class="p-3.5 whitespace-nowrap">
                                    <span class="text-white font-bold block"><?= date('d M Y', strtotime($s['closing_time'])) ?></span>
                                    <span class="text-[10px] text-slate-400 font-mono"><?= date('H:i', strtotime($s['closing_time'])) ?> WIB</span>
                                </td>
                                <td class="p-3.5">
                                    <span class="text-white font-bold block"><?= htmlspecialchars($s['kasir_nama']) ?></span>
                                    <span class="text-[10px] text-slate-400"><?= htmlspecialchars($s['pos_aktif']) ?></span>
                                </td>
                                <td class="p-3.5 text-center font-bold text-white">
                                    <?= $s['transaction_count'] ?>
                                </td>
                                <td class="p-3.5 text-right font-mono text-emerald-400">
                                    Rp <?= number_format($s['cash_sales'], 0, ',', '.') ?>
                                </td>
                                <td class="p-3.5 text-right font-mono text-blue-400">
                                    Rp <?= number_format($s['qris_sales'], 0, ',', '.') ?>
                                </td>
                                <td class="p-3.5 text-right font-mono font-black text-white">
                                    Rp <?= number_format($s['total_sales'], 0, ',', '.') ?>
                                </td>
                                <td class="p-3.5 text-right font-mono font-bold text-amber-300">
                                    Rp <?= number_format($s['actual_cash'], 0, ',', '.') ?>
                                </td>
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    <?= $diffBadge ?>
                                </td>
                                <td class="p-3.5 text-[11px] text-slate-400 max-w-[150px] truncate" title="<?= htmlspecialchars($s['notes'] ?? '') ?>">
                                    <?= !empty($s['notes']) ? htmlspecialchars($s['notes']) : '-' ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Kasir -->
<div id="modal-add-kasir" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-md border border-white/10 text-white shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-white">＋ Tambah Petugas Kasir Baru</h3>
            <button onclick="tutupModal('modal-add-kasir')" class="text-slate-400 hover:text-white">✕</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="crud_action" value="add_kasir">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nama Petugas</label>
                <input type="text" name="nama" required placeholder="Contoh: Rian Pratama" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Kata Sandi</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl text-xs shadow-lg shadow-blue-600/30 transition mt-2">
                Simpan Akun Kasir ✓
            </button>
        </form>
    </div>
</div>

<!-- Modal Edit Kasir -->
<div id="modal-edit-kasir" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-md border border-white/10 text-white shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-white">✏️ Ubah Akun Kasir</h3>
            <button onclick="tutupModal('modal-edit-kasir')" class="text-slate-400 hover:text-white">✕</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="crud_action" value="edit_kasir">
            <input type="hidden" name="id" id="edit-kasir-id">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nama Petugas</label>
                <input type="text" name="nama" id="edit-kasir-nama" required class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                <input type="password" name="password" placeholder="••••••••" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 rounded-xl text-xs shadow-lg shadow-amber-600/30 transition mt-2">
                Simpan Perubahan Akun ✓
            </button>
        </form>
    </div>
</div>

<script>
function bukaEditKasir(id, nama) {
    document.getElementById('edit-kasir-id').value = id;
    document.getElementById('edit-kasir-nama').value = nama;
    bukaModal('modal-edit-kasir');
}
</script>
