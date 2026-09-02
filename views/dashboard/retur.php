<?php
/**
 * View: Retur & Pengelolaan Barang Rusak / Expired
 * File: views/dashboard/retur.php
 */

$all_outlets = $conn->query("SELECT id, code, name, type FROM `locations` WHERE `type` IN ('outlet', 'vm', 'pos') AND `status` = 'active' ORDER BY `type` ASC, `code` ASC");
$outlets_array = [];
while($o = $all_outlets->fetch_assoc()) {
    $outlets_array[] = $o;
}

$produks_all = $conn->query("SELECT id, nama FROM `produk` ORDER BY `nama` ASC");

// Riwayat Retur dari tabel returns & return_items
$returns_res = $conn->query("
    SELECT r.*, p.nama as nama_produk, ls.name as nama_asal, ld.name as nama_tujuan
    FROM `returns` r
    JOIN `produk` p ON r.product_id = p.id
    JOIN `locations` ls ON r.source_location_id = ls.id
    LEFT JOIN `locations` ld ON r.destination_location_id = ld.id
    ORDER BY r.id DESC
    LIMIT 30
");
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">⚠️ Pengelolaan Barang Rusak & Retur Expired</h2>
            <p class="text-xs text-slate-400 mt-1">Catat produk cacat, kemasan rusak, atau kadaluarsa untuk dikarantina tanpa merusak stok siap jual.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="export.php?module=retur&format=excel" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-3 rounded-2xl shadow-lg shadow-emerald-600/20 flex items-center gap-1.5 transition" title="Unduh data retur format Excel (.xls)">
                <span>📊</span> <span>Export Excel</span>
            </a>
            <a href="export.php?module=retur&format=pdf&autoprint=1" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs px-3.5 py-3 rounded-2xl shadow-lg shadow-indigo-600/20 flex items-center gap-1.5 transition" title="Cetak Berita Acara Retur PDF">
                <span>🖨️</span> <span>Cetak PDF</span>
            </a>
            <button onclick="bukaModal('modal-add-retur')" class="bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs px-4 py-3 rounded-2xl shadow-lg shadow-rose-600/20 flex items-center gap-2 transition">
                <span>＋</span> <span>Catat Retur Baru</span>
            </button>
        </div>
    </div>

    <!-- Stats Mini Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card-dark p-5 rounded-3xl border border-white/5">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Barang Rusak Fisik</span>
            <?php 
            $cnt_dmg = $conn->query("SELECT SUM(stock_damaged) FROM `stok_lokasi`")->fetch_row()[0] ?? 0;
            ?>
            <h3 class="text-2xl font-black text-rose-400 mt-1"><?= intval($cnt_dmg) ?> pcs</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Tercatat di gudang & outlet</p>
        </div>
        <div class="glass-card-dark p-5 rounded-3xl border border-white/5">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Barang Expired</span>
            <?php 
            $cnt_exp = $conn->query("SELECT SUM(stock_expired) FROM `stok_lokasi`")->fetch_row()[0] ?? 0;
            ?>
            <h3 class="text-2xl font-black text-amber-400 mt-1"><?= intval($cnt_exp) ?> pcs</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Melewati batas masa simpan</p>
        </div>
        <div class="glass-card-dark p-5 rounded-3xl border border-white/5">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Lokasi Karantina Utama</span>
            <h3 class="text-2xl font-black text-blue-400 mt-1">Gudang Pusat</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Penyimpanan produk non-aktif</p>
        </div>
    </div>

    <!-- Tabel Riwayat Retur -->
    <div class="glass-card-dark rounded-3xl border border-white/5 overflow-hidden shadow-xl">
        <div class="p-5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-300">📋 Log Riwayat Retur & Barang Rusak</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-white/[0.03] text-slate-400 font-bold uppercase tracking-wider border-b border-white/5">
                        <th class="p-4">No. Retur</th>
                        <th class="p-4">Tanggal</th>
                        <th class="p-4">Nama Produk</th>
                        <th class="p-4">Asal ➔ Alokasi</th>
                        <th class="p-4 text-center">Kategori</th>
                        <th class="p-4 text-center">Jumlah</th>
                        <th class="p-4">Alasan / Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-medium text-slate-300">
                    <?php if ($returns_res && $returns_res->num_rows > 0): 
                        while($r = $returns_res->fetch_assoc()):
                    ?>
                    <tr class="hover:bg-white/[0.02] transition">
                        <td class="p-4 font-mono font-bold text-rose-400"><?= htmlspecialchars($r['return_number']) ?></td>
                        <td class="p-4 text-slate-400"><?= htmlspecialchars($r['return_date']) ?></td>
                        <td class="p-4 font-bold text-white"><?= htmlspecialchars($r['nama_produk']) ?></td>
                        <td class="p-4 text-slate-300"><?= htmlspecialchars($r['nama_asal']) ?> ➔ <span class="text-blue-400"><?= htmlspecialchars($r['nama_tujuan'] ?? 'Karantina') ?></span></td>
                        <td class="p-4 text-center">
                            <?php if ($r['return_type'] === 'expired'): ?>
                                <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Expired</span>
                            <?php else: ?>
                                <span class="bg-rose-500/20 text-rose-300 border border-rose-500/30 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Rusak Fisik</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center font-bold text-white"><?= $r['qty'] ?> pcs</td>
                        <td class="p-4 text-slate-400 italic"><?= htmlspecialchars($r['reason'] ?? '-') ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">Belum ada riwayat retur barang rusak.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Retur -->
<div id="modal-add-retur" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-md border border-white/10 text-white shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-white">⚠️ Form Catat Retur / Barang Rusak</h3>
            <button onclick="tutupModal('modal-add-retur')" class="text-slate-400 hover:text-white">✕</button>
        </div>
        <form method="POST" class="space-y-3.5">
            <input type="hidden" name="crud_action" value="add_retur">
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Pilih Produk</label>
                <select name="id_produk" required class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none">
                    <?php while($pr = $produks_all->fetch_assoc()): ?>
                        <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['nama']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Outlet / Lokasi Asal</label>
                    <select name="outlet_asal_id" required class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-[11px] text-white outline-none">
                        <?php foreach($outlets_array as $o): ?>
                            <option value="<?= $o['id'] ?>">[<?= $o['code'] ?>] <?= htmlspecialchars($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Alokasi Karantina</label>
                    <select name="tujuan_id" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-[11px] text-white outline-none">
                        <option value="1">Gudang Pusat Borobudur</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Jumlah Rusak (Qty)</label>
                    <input type="number" name="jumlah" required min="1" placeholder="Contoh: 5" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-xs text-white outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Kategori Retur</label>
                    <select name="tipe_retur" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-[11px] text-white outline-none">
                        <option value="rusak">Rusak Fisik / Bocor</option>
                        <option value="expired">Expired / Kadaluarsa</option>
                        <option value="overstock">Kemasan Rusak</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Keterangan / Alasan Retur</label>
                <input type="text" name="alasan" required placeholder="Contoh: Kaleng penyok saat pengisian vending machine" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
            </div>

            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-500 text-white font-bold py-3 rounded-xl text-xs shadow-lg shadow-rose-600/30 transition mt-2">
                Konfirmasi Simpan Retur ✓
            </button>
        </form>
    </div>
</div>
