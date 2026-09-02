<?php
/**
 * View: Kelola Menu & Visibilitas Produk per Outlet
 * File: views/dashboard/menu.php
 */

$all_outlets = $conn->query("SELECT id, code, name, type FROM `locations` WHERE `type` IN ('outlet', 'vm') AND `status` = 'active' ORDER BY `type` ASC, `code` ASC");
$outlets_array = [];
while($o = $all_outlets->fetch_assoc()) {
    $outlets_array[] = $o;
}

$produks = $conn->query("SELECT * FROM `produk` ORDER BY `nama` ASC");
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">📋 Kelola Produk & Visibilitas Outlet</h2>
            <p class="text-xs text-slate-400 mt-1">Atur katalog produk, harga jual, gambar, serta ketersediaan produk pada masing-masing outlet.</p>
        </div>
        <button onclick="bukaModal('modal-add-produk')" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-4 py-3 rounded-2xl shadow-lg shadow-blue-600/20 flex items-center gap-2 transition">
            <span>＋</span> <span>Tambah Produk Baru</span>
        </button>
    </div>

    <!-- Tabel Produk -->
    <div class="glass-card-dark rounded-3xl border border-white/5 overflow-hidden shadow-xl">
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left text-xs min-w-[550px]">
                <thead>
                    <tr class="bg-white/[0.03] text-slate-400 font-bold uppercase tracking-wider border-b border-white/5">
                        <th class="p-4">Produk</th>
                        <th class="p-4 text-center">Harga Jual</th>
                        <th class="p-4 text-center">Stok Gudang</th>
                        <th class="p-4">Ketersediaan Outlet</th>
                        <th class="p-4 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-medium text-slate-300">
                    <?php while ($p = $produks->fetch_assoc()): 
                        $p_id = intval($p['id']);
                        // Hitung berapa outlet yang menjual produk ini
                        $q_out = $conn->query("SELECT COUNT(*) as total FROM `product_outlets` WHERE `product_id` = $p_id");
                        $assigned_outlets = $q_out ? intval($q_out->fetch_assoc()['total']) : 0;
                    ?>
                    <tr class="hover:bg-white/[0.02] transition">
                        <td class="p-4 flex items-center gap-3">
                            <?php $img_src = !empty($p['gambar']) && file_exists('uploads/' . $p['gambar']) ? 'uploads/' . $p['gambar'] : 'https://placehold.co/100x100?text=No+Image'; ?>
                            <img src="<?= $img_src ?>" class="w-10 h-10 object-cover rounded-xl border border-white/10 shadow-sm">
                            <div>
                                <span class="font-bold text-white text-sm block"><?= htmlspecialchars($p['nama']) ?></span>
                                <span class="text-[10px] text-slate-500 uppercase font-mono">Tipe: <?= htmlspecialchars($p['custom_type'] ?? 'Normal') ?></span>
                            </div>
                        </td>
                        <td class="p-4 text-center font-bold text-emerald-400">Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                        <td class="p-4 text-center font-bold text-blue-400"><?= $p['stok_gudang'] ?> pcs</td>
                        <td class="p-4">
                            <?php if ($assigned_outlets >= count($outlets_array)): ?>
                                <span class="bg-blue-500/20 text-blue-300 border border-blue-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold">Semua Outlet (Global)</span>
                            <?php elseif ($assigned_outlets > 0): ?>
                                <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold"><?= $assigned_outlets ?> Outlet Tertentu</span>
                            <?php else: ?>
                                <span class="bg-rose-500/20 text-rose-300 border border-rose-500/30 px-2.5 py-1 rounded-full text-[10px] font-bold">Tidak Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="bukaEditProduk(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama'], ENT_QUOTES) ?>', <?= $p['harga'] ?>, '<?= htmlspecialchars($p['custom_type'], ENT_QUOTES) ?>', <?= $p['stok_gudang'] ?>)" class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 px-3 py-1.5 rounded-xl font-bold transition">
                                    Ubah
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirmSubmitForm(event, this, { title: 'Hapus Produk?', message: 'Apakah Anda yakin ingin menghapus produk <?= htmlspecialchars($p['nama'], ENT_QUOTES) ?>? Data yang terhapus tidak dapat dikembalikan.', confirmText: 'Ya, Hapus Produk', icon: '🗑️' })">
                                    <input type="hidden" name="crud_action" value="delete_produk">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 px-3 py-1.5 rounded-xl font-bold transition">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Produk -->
<div id="modal-add-produk" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto border border-white/10 text-white shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-white">＋ Tambah Produk Baru</h3>
            <button onclick="tutupModal('modal-add-produk')" class="text-slate-400 hover:text-white">✕</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="crud_action" value="add_produk">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nama Produk</label>
                <input type="text" name="nama" required placeholder="Contoh: Kopi Luwak Borobudur" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Harga Jual (Rp)</label>
                    <input type="number" name="harga" required min="100" placeholder="Contoh: 15000" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Stok Awal Gudang</label>
                    <input type="number" name="stok_gudang" min="0" value="0" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Foto / Gambar Produk</label>
                <input type="file" name="gambar" accept="image/*" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-xs text-slate-400">
            </div>

            <!-- Pengaturan Ketersediaan Outlet (Many-to-Many) -->
            <div class="pt-3 border-t border-white/10">
                <label class="block text-[10px] font-bold text-slate-300 uppercase mb-2">Pilih Ketersediaan Outlet</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-blue-400 font-semibold">
                        <input type="radio" name="outlet_selection" value="all" checked onchange="document.getElementById('outlet-checkboxes').classList.add('hidden')">
                        <span>Tersedia di Seluruh 11 Outlet / Mesin (Global)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300 font-semibold">
                        <input type="radio" name="outlet_selection" value="custom" onchange="document.getElementById('outlet-checkboxes').classList.remove('hidden')">
                        <span>Pilih Outlet Tertentu (Custom Selection)</span>
                    </label>
                </div>
                <div id="outlet-checkboxes" class="hidden mt-3 p-3 bg-slate-900/80 rounded-2xl border border-white/10 grid grid-cols-2 gap-2 max-h-40 overflow-y-auto">
                    <?php foreach($outlets_array as $ot): ?>
                    <label class="flex items-center gap-2 text-[11px] text-slate-300 cursor-pointer">
                        <input type="checkbox" name="outlet_ids[]" value="<?= $ot['id'] ?>" class="rounded bg-slate-800 border-white/20">
                        <span>[<?= $ot['code'] ?>] <?= htmlspecialchars($ot['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl text-xs shadow-lg shadow-blue-600/30 transition mt-2">
                Simpan Produk Baru ✓
            </button>
        </form>
    </div>
</div>

<!-- Modal Edit Produk -->
<div id="modal-edit-produk" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto border border-white/10 text-white shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-white">✏️ Ubah Data Produk</h3>
            <button onclick="tutupModal('modal-edit-produk')" class="text-slate-400 hover:text-white">✕</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="crud_action" value="edit_produk">
            <input type="hidden" name="id" id="edit-prod-id">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nama Produk</label>
                <input type="text" name="nama" id="edit-prod-nama" required class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Harga Jual (Rp)</label>
                    <input type="number" name="harga" id="edit-prod-harga" required min="100" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Stok Gudang</label>
                    <input type="number" name="stok_gudang" id="edit-prod-stok" min="0" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Ganti Foto Produk (Opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-xs text-slate-400">
            </div>

            <div class="pt-3 border-t border-white/10">
                <label class="block text-[10px] font-bold text-slate-300 uppercase mb-2">Visibilitas Outlet</label>
                <label class="flex items-center gap-2 cursor-pointer text-xs text-blue-400 font-semibold mb-2">
                    <input type="radio" name="outlet_selection" value="all" checked>
                    <span>Tersedia di Seluruh Outlet</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 rounded-xl text-xs shadow-lg shadow-amber-600/30 transition mt-2">
                Simpan Perubahan Produk ✓
            </button>
        </form>
    </div>
</div>

<script>
function bukaEditProduk(id, nama, harga, customType, stok) {
    document.getElementById('edit-prod-id').value = id;
    document.getElementById('edit-prod-nama').value = nama;
    document.getElementById('edit-prod-harga').value = harga;
    document.getElementById('edit-prod-stok').value = stok;
    bukaModal('modal-edit-produk');
}
</script>
