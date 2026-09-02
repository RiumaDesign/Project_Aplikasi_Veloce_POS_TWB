<?php
/**
 * View: Master Outlet & Mesin (11 Lokasi)
 * File: views/dashboard/outlet.php
 */

$outlets = $conn->query("SELECT * FROM `locations` WHERE `type` IN ('outlet', 'vm', 'pos') ORDER BY `type` ASC, `code` ASC");
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">🏪 Kelola Outlet & Vending Machine</h2>
            <p class="text-xs text-slate-400 mt-1">Kelola terminal POS, mesin Vending Machine (VM 1 - VM 9+), dan outlet fisik.</p>
        </div>
        <button onclick="bukaModal('modal-add-outlet')" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-4 py-3 rounded-2xl shadow-lg shadow-blue-600/20 flex items-center gap-2 transition">
            <span>＋</span> <span>Tambah Outlet / Mesin</span>
        </button>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="p-4 bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs rounded-2xl flex items-center gap-2 animate-fade-in">
            <span>✓</span> <span>Titik penjualan / terminal berhasil dihapus secara permanen dari sistem.</span>
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'gudang_cannot_delete'): ?>
        <div class="p-4 bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs rounded-2xl flex items-center gap-2 animate-fade-in">
            <span>⚠️</span> <span>Gudang Pusat Borobudur merupakan master inventori logistik utama dan tidak dapat dihapus.</span>
        </div>
    <?php endif; ?>

    <!-- Stats Mini Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card-dark p-5 rounded-3xl border border-white/5">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Mesin VM</span>
            <h3 class="text-2xl font-black text-blue-400 mt-1">9 Unit</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">VM 1 s/d VM 9 (Siap Operasional)</p>
        </div>
        <div class="glass-card-dark p-5 rounded-3xl border border-white/5">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Outlet Fisik</span>
            <h3 class="text-2xl font-black text-emerald-400 mt-1">2 Outlet</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Museum Samudra Raksa & Barat</p>
        </div>
        <div class="glass-card-dark p-5 rounded-3xl border border-white/5">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pusat Distribusi</span>
            <h3 class="text-2xl font-black text-indigo-400 mt-1">1 Gudang</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Gudang Pusat Borobudur (Hub DO)</p>
        </div>
    </div>

    <!-- Tabel Master Outlet -->
    <div class="glass-card-dark rounded-3xl border border-white/5 overflow-hidden shadow-xl">
        <div class="p-5 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Daftar Titik Penjualan & Terminal</h3>
        </div>
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left text-xs min-w-[550px]">
                <thead>
                    <tr class="bg-white/[0.03] text-slate-400 font-bold uppercase tracking-wider border-b border-white/5">
                        <th class="p-4">Kode</th>
                        <th class="p-4">Nama Lokasi / Mesin</th>
                        <th class="p-4 text-center">Tipe</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-medium text-slate-300">
                    <?php while ($loc = $outlets->fetch_assoc()): ?>
                    <tr class="hover:bg-white/[0.02] transition">
                        <td class="p-4 font-mono font-bold text-blue-400"><?= htmlspecialchars($loc['code']) ?></td>
                        <td class="p-4 font-bold text-white"><?= htmlspecialchars($loc['name']) ?></td>
                        <td class="p-4 text-center">
                            <?php if ($loc['type'] === 'vm'): ?>
                                <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Vending Machine</span>
                            <?php elseif ($loc['type'] === 'outlet'): ?>
                                <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Outlet Fisik</span>
                            <?php else: ?>
                                <span class="bg-slate-500/20 text-slate-300 border border-slate-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase"><?= htmlspecialchars($loc['type']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center">
                            <?php if ($loc['status'] === 'active'): ?>
                                <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold">● Aktif</span>
                            <?php else: ?>
                                <span class="bg-rose-500/10 text-rose-400 border border-rose-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold">○ Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button onclick="bukaEditOutlet(<?= $loc['id'] ?>, '<?= htmlspecialchars($loc['code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($loc['name'], ENT_QUOTES) ?>', '<?= $loc['type'] ?>', '<?= $loc['status'] ?>')" class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2.5 py-1.5 rounded-xl font-bold text-xs transition">
                                    Ubah
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirmSubmitForm(event, this, { title: 'Ubah Status Lokasi?', message: 'Apakah Anda ingin mengubah status operasional lokasi <?= htmlspecialchars($loc['name'], ENT_QUOTES) ?>?', type: 'warning', icon: '🔄', confirmText: 'Ya, Ubah Status' })">
                                    <input type="hidden" name="crud_action" value="toggle_outlet_status">
                                    <input type="hidden" name="id" value="<?= $loc['id'] ?>">
                                    <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1.5 rounded-xl font-bold text-xs border border-white/10 transition">
                                        <?= ($loc['status'] === 'active') ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                                <?php if ($loc['id'] != 1): ?>
                                <form method="POST" class="inline" onsubmit="return confirmSubmitForm(event, this, { title: 'Hapus Lokasi Outlet / VM?', message: 'Apakah Anda yakin ingin menghapus permanen <?= htmlspecialchars($loc['name'], ENT_QUOTES) ?> (<?= htmlspecialchars($loc['code'], ENT_QUOTES) ?>)? Tindakan ini tidak dapat dibatalkan.', type: 'danger', icon: '🗑️', confirmText: 'Ya, Hapus Lokasi' })">
                                    <input type="hidden" name="crud_action" value="delete_outlet">
                                    <input type="hidden" name="id" value="<?= $loc['id'] ?>">
                                    <button type="submit" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 px-2.5 py-1.5 rounded-xl font-bold text-xs transition flex items-center gap-1" title="Hapus Titik Outlet / Terminal">
                                        <span>🗑️</span> <span>Hapus</span>
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
</div>

<!-- Modal Tambah Outlet -->
<div id="modal-add-outlet" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-md border border-white/10 text-white shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-white">＋ Tambah Titik Outlet / Mesin</h3>
            <button onclick="tutupModal('modal-add-outlet')" class="text-slate-400 hover:text-white">✕</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="crud_action" value="add_outlet">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Kode Unik</label>
                <input type="text" name="code" required placeholder="Contoh: VM-12 atau OUT-TIMUR" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nama Lokasi / Outlet</label>
                <input type="text" name="name" required placeholder="Contoh: Vending Machine 12 - Zona Candi" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Tipe Unit</label>
                    <select name="type" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none">
                        <option value="vm">Vending Machine</option>
                        <option value="outlet">Outlet Fisik</option>
                        <option value="pos">POS Terminal</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Status Awal</label>
                    <select name="status" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl text-xs shadow-lg shadow-blue-600/30 transition mt-2">
                Simpan Lokasi Baru ✓
            </button>
        </form>
    </div>
</div>

<!-- Modal Edit Outlet -->
<div id="modal-edit-outlet" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-md border border-white/10 text-white shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-white">✏️ Ubah Data Outlet / Mesin</h3>
            <button onclick="tutupModal('modal-edit-outlet')" class="text-slate-400 hover:text-white">✕</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="crud_action" value="edit_outlet">
            <input type="hidden" name="id" id="edit-outlet-id">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Kode Unik</label>
                <input type="text" name="code" id="edit-outlet-code" required class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nama Lokasi / Outlet</label>
                <input type="text" name="name" id="edit-outlet-name" required class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Tipe Unit</label>
                    <select name="type" id="edit-outlet-type" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none">
                        <option value="vm">Vending Machine</option>
                        <option value="outlet">Outlet Fisik</option>
                        <option value="pos">POS Terminal</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Status</label>
                    <select name="status" id="edit-outlet-status" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 rounded-xl text-xs shadow-lg shadow-amber-600/30 transition mt-2">
                Simpan Perubahan ✓
            </button>
        </form>
    </div>
</div>

<script>
function bukaEditOutlet(id, code, name, type, status) {
    document.getElementById('edit-outlet-id').value = id;
    document.getElementById('edit-outlet-code').value = code;
    document.getElementById('edit-outlet-name').value = name;
    document.getElementById('edit-outlet-type').value = type;
    document.getElementById('edit-outlet-status').value = status;
    bukaModal('modal-edit-outlet');
}
</script>
