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
        <table class="w-full text-left text-xs">
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
