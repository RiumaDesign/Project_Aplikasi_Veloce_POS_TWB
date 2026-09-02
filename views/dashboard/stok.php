<?php
/**
 * View: Distribusi Stok & Delivery Order (DO)
 * File: views/dashboard/stok.php
 */

// Query seluruh lokasi aktif secara dinamis (Gudang, Outlet, VM 1 s/d VM 9+, dsb.)
$dynamic_locations = [];
$res_locs = $conn->query("
    SELECT id, code, name, type 
    FROM `locations` 
    WHERE `status` = 'active' 
    ORDER BY (type = 'warehouse') DESC, (type = 'outlet') DESC, (type = 'vm') DESC, id ASC
");
while ($l = $res_locs->fetch_assoc()) {
    $dynamic_locations[] = $l;
}
$locations_list = $dynamic_locations;

// Query seluruh produk aktif
$all_prods_list = [];
$res_prods_all = $conn->query("SELECT id, nama, gambar, harga FROM `produk` ORDER BY nama ASC");
while ($p = $res_prods_all->fetch_assoc()) {
    $all_prods_list[] = $p;
}

// Matriks Stok [product_id][location_id]
$stock_matrix = [];
$res_matrix = $conn->query("SELECT product_id, location_id, quantity FROM `stok_lokasi`");
while ($row = $res_matrix->fetch_assoc()) {
    $stock_matrix[$row['product_id']][$row['location_id']] = intval($row['quantity']);
}

// Filter Log Mutasi & Arus Stok Terpadu
$filter_kategori = trim($_GET['filter_kategori'] ?? '');
$filter_produk   = intval($_GET['filter_produk'] ?? 0);
$filter_lokasi   = intval($_GET['filter_lokasi'] ?? 0);
$filter_start    = trim($_GET['filter_start'] ?? '');
$filter_end      = trim($_GET['filter_end'] ?? '');

$where_mutasi = ["1=1"];
if (!empty($filter_kategori)) {
    $safe_kat = $conn->real_escape_string($filter_kategori);
    $where_mutasi[] = "sm.mutation_type = '$safe_kat'";
}
if ($filter_produk > 0) {
    $where_mutasi[] = "sm.product_id = $filter_produk";
}
if ($filter_lokasi > 0) {
    $where_mutasi[] = "(sm.source_location_id = $filter_lokasi OR sm.destination_location_id = $filter_lokasi)";
}
if (!empty($filter_start) && !empty($filter_end)) {
    $safe_start = $conn->real_escape_string($filter_start);
    $safe_end   = $conn->real_escape_string($filter_end);
    $where_mutasi[] = "DATE(sm.created_at) BETWEEN '$safe_start' AND '$safe_end'";
}
$where_mutasi_sql = implode(" AND ", $where_mutasi);

$sql_mutasi = "
    SELECT sm.*, 
           p.nama AS nama_produk, p.gambar AS gambar_produk,
           COALESCE(ls.name, 'Supplier / Eksternal') AS nama_asal,
           COALESCE(ls.code, 'EXT') AS kode_asal,
           COALESCE(ld.name, 'Konsumen / Kasir') AS nama_tujuan,
           COALESCE(ld.code, 'CS') AS kode_tujuan
    FROM `stock_mutations` sm
    JOIN `produk` p ON sm.product_id = p.id
    LEFT JOIN `locations` ls ON sm.source_location_id = ls.id
    LEFT JOIN `locations` ld ON sm.destination_location_id = ld.id
    WHERE $where_mutasi_sql
    ORDER BY sm.created_at DESC, sm.id DESC
    LIMIT 100
";
$res_mutasi = $conn->query($sql_mutasi);

// Hitung Rekapitulasi Eksekutif Mutasi Persediaan untuk Pimpinan & Pengawas
$sql_summary_mutasi = "
    SELECT 
        SUM(CASE WHEN sm.mutation_type = 'inbound' THEN sm.quantity ELSE 0 END) as tot_inbound,
        SUM(CASE WHEN sm.mutation_type = 'transfer_do' THEN sm.quantity ELSE 0 END) as tot_transfer,
        SUM(CASE WHEN sm.mutation_type = 'sale' THEN sm.quantity ELSE 0 END) as tot_sale,
        SUM(CASE WHEN sm.mutation_type = 'return' THEN sm.quantity ELSE 0 END) as tot_return,
        COUNT(sm.id) as tot_transaksi
    FROM `stock_mutations` sm
    WHERE $where_mutasi_sql
";
$res_sum_mut = $conn->query($sql_summary_mutasi);
$mut_summary = $res_sum_mut ? $res_sum_mut->fetch_assoc() : [];
$kpi_inbound  = intval($mut_summary['tot_inbound'] ?? 0);
$kpi_transfer = intval($mut_summary['tot_transfer'] ?? 0);
$kpi_sale     = intval($mut_summary['tot_sale'] ?? 0);
$kpi_return   = intval($mut_summary['tot_return'] ?? 0);
$kpi_count    = intval($mut_summary['tot_transaksi'] ?? 0);

$all_prods_filter = $conn->query("SELECT id, nama FROM `produk` ORDER BY nama ASC");

$export_params = http_build_query([
    'module'        => 'mutasi',
    'kategori'      => $filter_kategori,
    'product_id'    => $filter_produk,
    'location_id'   => $filter_lokasi,
    'start_date'    => $filter_start,
    'end_date'      => $filter_end
]);
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">📦 Kelola Stok Barang</h2>
            <p class="text-xs text-slate-400 mt-1">Monitoring stok seluruh outlet & vending machine, tambah stok gudang, dan transfer barang antar-lokasi.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="export.php?module=stok&format=excel" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2.5 rounded-2xl transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5" title="Unduh status stok seluruh lokasi dalam format Excel (.xls)">
                <span>📊</span> <span>Unduh Stok Excel</span>
            </a>
            <a href="export.php?module=stok&format=pdf&autoprint=1" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-3 py-2.5 rounded-2xl transition shadow-md shadow-indigo-600/20 flex items-center gap-1.5" title="Cetak status stok multi-outlet">
                <span>🖨️</span> <span>Cetak Stok</span>
            </a>
        </div>
    </div>

    <style>
        .sticky-col-header {
            position: sticky;
            left: 0;
            z-index: 20;
            background-color: #0b1329;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.4);
        }
        .sticky-col-cell {
            position: sticky;
            left: 0;
            z-index: 10;
            background-color: #0b1329;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.4);
        }
        html[data-theme="light"] .sticky-col-header,
        html.light .sticky-col-header {
            background-color: #ffffff !important;
            color: #0f172a !important;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05) !important;
            border-right: 1px solid #e2e8f0 !important;
        }
        html[data-theme="light"] .sticky-col-cell,
        html.light .sticky-col-cell {
            background-color: #ffffff !important;
            color: #0f172a !important;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05) !important;
            border-right: 1px solid #e2e8f0 !important;
        }
        html[data-theme="light"] .sticky-col-cell span.text-white,
        html.light .sticky-col-cell span.text-white {
            color: #0f172a !important;
        }
    </style>

    <!-- 1. TABEL MONITORING STOK MULTI-TITIK DINAMIS (FLEKSIBEL VM 1 - VM 9+) -->
    <div class="glass-card-dark rounded-3xl border border-white/5 overflow-hidden shadow-xl flex flex-col">
        <div class="p-5 border-b border-white/5 bg-white/[0.02] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <span class="font-bold text-xs uppercase tracking-wider text-slate-300 flex items-center gap-2">
                    <span>📊</span> <span>Status Stok Produk Aktif (Multi-Titik Dinamis)</span>
                </span>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Kolom otomatis beradaptasi dengan seluruh titik operasional (Gudang, Outlet, dan Vending Machine 1 s/d 9+). Geser tabel ke kanan untuk melihat seluruh unit VM.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="bg-blue-600/20 text-blue-400 border border-blue-500/30 text-[10px] font-bold px-3 py-1 rounded-full whitespace-nowrap">
                    <?= count($dynamic_locations) ?> Titik Lokasi Aktif
                </span>
            </div>
        </div>

        <div class="overflow-x-auto custom-scroll flex-1">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-white/[0.03] text-slate-400 font-bold uppercase tracking-wider border-b border-white/5">
                        <th class="p-3.5 sticky-col-header min-w-[210px]">
                            <span class="text-white text-xs">Produk</span>
                        </th>
                        <?php foreach($dynamic_locations as $loc): ?>
                            <?php 
                                $loc_type = $loc['type'];
                                $th_style = match($loc_type) {
                                    'warehouse' => 'text-blue-400 bg-blue-500/10 border-b-2 border-blue-500',
                                    'outlet'    => 'text-emerald-400 bg-emerald-500/10 border-b-2 border-emerald-500',
                                    'vm'        => 'text-violet-400 bg-violet-500/10 border-b-2 border-violet-500',
                                    default     => 'text-slate-300 bg-slate-500/10'
                                };
                                
                                // Format nama ringkas agar muat rapi di kolom tabel
                                $short_name = $loc['name'];
                                if (preg_match('/Vending Machine (\d+)/i', $loc['name'], $m_name)) {
                                    $short_name = 'VM ' . $m_name[1];
                                } elseif ($loc['type'] === 'warehouse') {
                                    $short_name = 'Gudang Pusat';
                                } elseif (stripos($loc['name'], 'Museum') !== false) {
                                    $short_name = 'Outlet Museum';
                                } elseif (stripos($loc['name'], 'Barat') !== false) {
                                    $short_name = 'Outlet Barat';
                                }
                            ?>
                            <th class="p-3 text-center whitespace-nowrap min-w-[85px] <?= $th_style ?>" title="<?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['code']) ?>)">
                                <span class="block text-[11px] font-extrabold"><?= htmlspecialchars($short_name) ?></span>
                                <span class="block text-[8px] opacity-70 tracking-tight font-mono"><?= htmlspecialchars($loc['code']) ?></span>
                            </th>
                        <?php endforeach; ?>
                        <th class="p-3 text-center text-amber-400 bg-amber-500/10 border-b-2 border-amber-500 whitespace-nowrap min-w-[95px]">
                            <span class="block text-[11px] font-extrabold">TOTAL</span>
                            <span class="block text-[8px] opacity-70">SEMUA TITIK</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-medium text-slate-300">
                    <?php 
                    $col_totals = array_fill_keys(array_column($dynamic_locations, 'id'), 0);
                    $grand_total_all = 0;
                    foreach($all_prods_list as $p): 
                        $row_total = 0;
                        $img_src = !empty($p['gambar']) && file_exists('uploads/' . $p['gambar']) ? 'uploads/' . $p['gambar'] : 'https://placehold.co/100x100?text=No+Image';
                    ?>
                    <tr class="hover:bg-white/[0.02] transition border-b border-white/5">
                        <!-- Sticky Kolom Produk -->
                        <td class="p-3.5 sticky-col-cell flex items-center gap-2.5">
                            <img src="<?= $img_src ?>" class="w-7 h-7 object-cover rounded-lg border border-white/10 shrink-0">
                            <span class="font-bold text-white text-xs whitespace-nowrap"><?= htmlspecialchars($p['nama']) ?></span>
                        </td>
                        
                        <!-- Kolom Tiap Lokasi Dinamis -->
                        <?php foreach($dynamic_locations as $loc): 
                            $qty = $stock_matrix[$p['id']][$loc['id']] ?? 0;
                            $row_total += $qty;
                            $col_totals[$loc['id']] += $qty;
                            
                            $color_class = 'text-slate-500';
                            if ($qty > 0) {
                                $color_class = match($loc['type']) {
                                    'warehouse' => 'text-blue-400 font-extrabold bg-blue-500/5',
                                    'outlet'    => 'text-emerald-400 font-extrabold bg-emerald-500/5',
                                    'vm'        => 'text-violet-400 font-extrabold bg-violet-500/5',
                                    default     => 'text-white font-bold'
                                };
                            }
                        ?>
                        <td class="p-3 text-center text-xs <?= $color_class ?>">
                            <?= number_format($qty, 0, ',', '.') ?>
                        </td>
                        <?php endforeach; ?>
                        
                        <!-- Kolom Total Row Per Produk -->
                        <?php $grand_total_all += $row_total; ?>
                        <td class="p-3 text-center text-xs font-black text-amber-400 bg-amber-500/5">
                            <?= number_format($row_total, 0, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-white/[0.04] border-t-2 border-white/10 font-bold text-xs">
                        <td class="p-3.5 sticky-col-cell text-white uppercase text-[10px] tracking-wider">
                            TOTAL PER LOKASI
                        </td>
                        <?php foreach($dynamic_locations as $loc): ?>
                            <td class="p-3 text-center text-xs font-black text-white">
                                <?= number_format($col_totals[$loc['id']] ?? 0, 0, ',', '.') ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="p-3 text-center text-xs font-black text-amber-400 bg-amber-500/10">
                            <?= number_format($grand_total_all, 0, ',', '.') ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 2. FORM AKSI DUA KOLOM: TAMBAH STOK GUDANG & DELIVERY ORDER (DO) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- FORM 1: TAMBAH STOK GUDANG -->
        <div class="glass-card-dark p-6 rounded-3xl border border-white/5 shadow-xl">
            <h3 class="text-xs font-bold text-white mb-3 uppercase tracking-wider flex items-center gap-2">
                <span>📥</span> <span>Tambah Stok Masuk ke Gudang</span>
            </h3>
            <p class="text-[11px] text-slate-400 mb-4">
                Penerimaan pasokan barang baru dari vendor/distributor untuk disimpan di Gudang Pusat Borobudur.
            </p>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="crud_action" value="tambah_stok_gudang">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Pilih Produk</label>
                    <select name="id_produk" required class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none">
                        <?php foreach($all_prods_list as $p): ?>
                            <?php $sisa_wh = $stock_matrix[$p['id']][1] ?? 0; ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> (Sisa Gudang: <?= $sisa_wh ?> pcs)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Jumlah Kuantitas Stok Masuk</label>
                    <input type="number" name="jumlah_tambah" required min="1" placeholder="Contoh: 100" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 rounded-xl text-xs shadow-lg shadow-emerald-600/30 transition">
                    Konfirmasi Tambah Stok Gudang ✓
                </button>
            </form>
        </div>

        <!-- FORM 2: BUAT TRANSFER DO ANTAR LOKASI -->
        <div class="glass-card-dark p-6 rounded-3xl border border-white/5 shadow-xl">
            <h3 class="text-xs font-bold text-white mb-3 uppercase tracking-wider flex items-center gap-2">
                <span>🚚</span> <span>Delivery Order (DO) Antar Lokasi</span>
            </h3>
            <p class="text-[11px] text-slate-400 mb-4">
                Distribusi dan mutasi transfer barang dari gudang ke outlet atau antar-vending machine.
            </p>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="crud_action" value="transfer_stok">
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Pilih Produk</label>
                    <select name="id_produk" required class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none">
                        <?php foreach($all_prods_list as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Lokasi Asal</label>
                        <select name="source_location_id" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-[11px] text-white outline-none">
                            <?php foreach($dynamic_locations as $loc): ?>
                                <option value="<?= $loc['id'] ?>" <?= ($loc['id'] == 1) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc['name']) ?> (<?= strtoupper($loc['type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Lokasi Tujuan</label>
                        <select name="destination_location_id" required class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-[11px] text-white outline-none">
                            <option value="">-- Pilih Tujuan --</option>
                            <?php foreach($dynamic_locations as $loc): ?>
                                <?php if ($loc['id'] != 1): ?>
                                    <option value="<?= $loc['id'] ?>">
                                        <?= htmlspecialchars($loc['name']) ?> (<?= strtoupper($loc['type']) ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Jumlah Qty Transfer</label>
                    <input type="number" name="jumlah_transfer" required min="1" placeholder="Contoh: 24" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white outline-none focus:border-blue-500">
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded-xl text-xs shadow-lg shadow-blue-600/30 transition">
                    Kirim Barang (DO) ➔
                </button>
            </form>
        </div>
    </div>

    <!-- TABEL BUKU MUTASI & KONTROL PERSEDIAAN BARANG -->
    <div class="glass-card-dark rounded-3xl border border-white/5 overflow-hidden shadow-xl">
        <!-- Header & Tombol Export -->
        <div class="p-5 border-b border-white/5 bg-white/[0.02] flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="font-bold text-sm uppercase tracking-wider text-white flex items-center gap-2">
                    <span>📑</span> <span>Buku Mutasi & Kontrol Persediaan Barang</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Rekapitulasi resmi pergerakan stok: penerimaan pasokan gudang, mutasi DO antar-titik, penjualan kasir/VM, dan retur.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="export.php?<?= $export_params ?>&format=excel" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2 rounded-xl transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5" title="Unduh Buku Mutasi format Excel (.xls)">
                    <span>📊</span> <span>Export Mutasi Excel</span>
                </a>
                <a href="export.php?<?= $export_params ?>&format=pdf&autoprint=1" target="_blank" class="bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold px-3 py-2 rounded-xl transition shadow-md shadow-violet-600/20 flex items-center gap-1.5" title="Cetak Rekap Mutasi format PDF dengan Kop Resmi TWB">
                    <span>📄</span> <span>Cetak Mutasi PDF</span>
                </a>
            </div>
        </div>

        <!-- RINGKASAN EKSEKUTIF (EXECUTIVE SUMMARY KPI CARDS) UNTUK PIMPINAN & AUDITOR -->
        <div class="p-5 border-b border-white/5 bg-slate-900/30">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span>📊</span> <span>Ringkasan Eksekutif Mutasi (Sesuai Kriteria Filter)</span>
                </span>
                <span class="text-[10px] font-mono font-bold text-slate-400 bg-white/5 px-2.5 py-0.5 rounded-lg border border-white/10">
                    Total <?= number_format($kpi_count, 0, ',', '.') ?> Catatan Audit
                </span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- KPI 1: Penerimaan Pasokan -->
                <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs font-bold text-emerald-400 mb-1">
                        <span>📥 Pasokan Masuk</span>
                        <span class="text-[9px] bg-emerald-500/20 px-2 py-0.5 rounded-md uppercase tracking-wider font-extrabold">Inbound</span>
                    </div>
                    <div class="text-xl font-black text-white">+<?= number_format($kpi_inbound, 0, ',', '.') ?> <span class="text-xs font-semibold text-slate-400">pcs</span></div>
                    <p class="text-[10px] text-slate-400 mt-1 truncate">Penerimaan gudang dari supplier</p>
                </div>

                <!-- KPI 2: Distribusi DO -->
                <div class="p-3.5 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs font-bold text-blue-400 mb-1">
                        <span>🚚 Mutasi DO</span>
                        <span class="text-[9px] bg-blue-500/20 px-2 py-0.5 rounded-md uppercase tracking-wider font-extrabold">Transfer</span>
                    </div>
                    <div class="text-xl font-black text-white"><?= number_format($kpi_transfer, 0, ',', '.') ?> <span class="text-xs font-semibold text-slate-400">pcs</span></div>
                    <p class="text-[10px] text-slate-400 mt-1 truncate">Distribusi dari gudang ke Outlet / VM</p>
                </div>

                <!-- KPI 3: Penjualan Kasir & VM -->
                <div class="p-3.5 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs font-bold text-purple-400 mb-1">
                        <span>🛒 Penjualan Retail</span>
                        <span class="text-[9px] bg-purple-500/20 px-2 py-0.5 rounded-md uppercase tracking-wider font-extrabold">Outbound</span>
                    </div>
                    <div class="text-xl font-black text-white">-<?= number_format($kpi_sale, 0, ',', '.') ?> <span class="text-xs font-semibold text-slate-400">pcs</span></div>
                    <p class="text-[10px] text-slate-400 mt-1 truncate">Terjual ke pengunjung & konsumen</p>
                </div>

                <!-- KPI 4: Retur & Kerusakan -->
                <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs font-bold text-rose-400 mb-1">
                        <span>⚠️ Retur & Rusak</span>
                        <span class="text-[9px] bg-rose-500/20 px-2 py-0.5 rounded-md uppercase tracking-wider font-extrabold">Karantina</span>
                    </div>
                    <div class="text-xl font-black text-white">⇄ <?= number_format($kpi_return, 0, ',', '.') ?> <span class="text-xs font-semibold text-slate-400">pcs</span></div>
                    <p class="text-[10px] text-slate-400 mt-1 truncate">Barang cacat kemasan / expired</p>
                </div>
            </div>
        </div>

        <!-- FORM FILTER MUTASI MULTI-KRITERIA -->
        <div class="p-4 border-b border-white/5 bg-slate-900/40">
            <form method="GET" action="dashboard.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <input type="hidden" name="page" value="stok">
                
                <!-- 1. Filter Kategori Mutasi -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Kategori Mutasi</label>
                    <select name="filter_kategori" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-xs text-white outline-none focus:border-blue-500">
                        <option value="">-- Semua Kategori Mutasi --</option>
                        <option value="inbound" <?= ($filter_kategori === 'inbound') ? 'selected' : '' ?>>📥 Penambahan Gudang (Inbound)</option>
                        <option value="transfer_do" <?= ($filter_kategori === 'transfer_do') ? 'selected' : '' ?>>🚚 Transfer DO Antar-Lokasi</option>
                        <option value="sale" <?= ($filter_kategori === 'sale') ? 'selected' : '' ?>>🛒 Pengurangan / Penjualan Kasir</option>
                        <option value="return" <?= ($filter_kategori === 'return') ? 'selected' : '' ?>>⚠️ Retur Rusak & Expired</option>
                        <option value="adjustment" <?= ($filter_kategori === 'adjustment') ? 'selected' : '' ?>>⚖️ Penyesuaian Stok (Opname)</option>
                    </select>
                </div>

                <!-- 2. Filter Produk -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Filter Produk</label>
                    <select name="filter_produk" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-xs text-white outline-none focus:border-blue-500">
                        <option value="0">-- Semua Produk --</option>
                        <?php 
                        if ($all_prods_filter && $all_prods_filter->num_rows > 0) {
                            $all_prods_filter->data_seek(0);
                            while($p_opt = $all_prods_filter->fetch_assoc()) {
                                $selected = ($filter_produk == $p_opt['id']) ? 'selected' : '';
                                echo '<option value="' . $p_opt['id'] . '" ' . $selected . '>' . htmlspecialchars($p_opt['nama']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- 3. Filter Lokasi -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Filter Lokasi</label>
                    <select name="filter_lokasi" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-xs text-white outline-none focus:border-blue-500">
                        <option value="0">-- Semua Lokasi --</option>
                        <?php foreach($locations_list as $loc_opt): ?>
                            <option value="<?= $loc_opt['id'] ?>" <?= ($filter_lokasi == $loc_opt['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc_opt['name']) ?> (<?= strtoupper($loc_opt['type']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 4. Rentang Tanggal Mulai -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="filter_start" value="<?= htmlspecialchars($filter_start) ?>" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-xs text-white outline-none focus:border-blue-500">
                </div>

                <!-- 5. Rentang Tanggal Selesai -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="filter_end" value="<?= htmlspecialchars($filter_end) ?>" class="w-full bg-slate-900 border border-white/10 rounded-xl p-2 text-xs text-white outline-none focus:border-blue-500">
                </div>

                <!-- 6. Tombol Aksi Filter & Reset -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-xl text-xs shadow-md shadow-blue-600/30 transition flex items-center justify-center gap-1">
                        <span>🔍</span> <span>Filter</span>
                    </button>
                    <a href="dashboard.php?page=stok" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold p-2 rounded-xl text-xs transition border border-white/10" title="Reset Filter">
                        <span>🔄</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- TABEL DATA MUTASI STOK -->
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left text-xs min-w-[750px]">
                <thead>
                    <tr class="bg-white/[0.03] text-slate-400 font-bold uppercase tracking-wider border-b border-white/5">
                        <th class="p-4 w-12 text-center">No</th>
                        <th class="p-4">Waktu / Tanggal</th>
                        <th class="p-4">No. Dokumen</th>
                        <th class="p-4 text-center">Kategori Mutasi</th>
                        <th class="p-4">Kode & Nama Produk</th>
                        <th class="p-4">Mutasi Lokasi (Asal ➔ Tujuan)</th>
                        <th class="p-4 text-center">Volume (+ / -)</th>
                        <th class="p-4">Penanggung Jawab</th>
                        <th class="p-4">Catatan / Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-medium text-slate-300">
                    <?php 
                    if ($res_mutasi && $res_mutasi->num_rows > 0):
                        $no_m = 1;
                        while($m = $res_mutasi->fetch_assoc()):
                            $m_type = $m['mutation_type'] ?? '';
                            
                            // Badge Kategori Styling
                            $badge_class = match($m_type) {
                                'inbound'     => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
                                'transfer_do' => 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
                                'sale'        => 'bg-purple-500/20 text-purple-300 border border-purple-500/30',
                                'return'      => 'bg-rose-500/20 text-rose-400 border border-rose-500/30',
                                'adjustment'  => 'bg-amber-500/20 text-amber-400 border border-amber-500/30',
                                default       => 'bg-slate-700/50 text-slate-300 border border-slate-600'
                            };

                            $badge_text = match($m_type) {
                                'inbound'     => '📥 INBOUND',
                                'transfer_do' => '🚚 DO TRANSFER',
                                'sale'        => '🛒 PENJUALAN',
                                'return'      => '⚠️ RETUR RUSAK',
                                'adjustment'  => '⚖️ ADJUSTMENT',
                                default       => strtoupper($m_type)
                            };

                            // Indikator Qty
                            $qty_sign = match($m_type) {
                                'inbound' => '+',
                                'sale'    => '-',
                                'return'  => '⇄',
                                default   => ''
                            };
                            $qty_class = match($m_type) {
                                'inbound' => 'text-emerald-400 font-extrabold',
                                'sale'    => 'text-rose-400 font-extrabold',
                                'return'  => 'text-amber-400 font-extrabold',
                                default   => 'text-blue-400 font-extrabold'
                            };

                            $img_m = !empty($m['gambar_produk']) && file_exists('uploads/' . $m['gambar_produk']) ? 'uploads/' . $m['gambar_produk'] : 'https://placehold.co/80x80?text=Produk';
                    ?>
                    <tr class="hover:bg-white/[0.02] transition">
                        <td class="p-4 text-center text-slate-500"><?= $no_m++ ?></td>
                        <td class="p-4 text-slate-400 whitespace-nowrap">
                            <span class="font-bold text-white"><?= date('d M Y', strtotime($m['created_at'])) ?></span><br>
                            <span class="text-[10px] text-slate-500"><?= date('H:i:s', strtotime($m['created_at'])) ?> WIB</span>
                        </td>
                        <td class="p-4 font-mono font-bold text-blue-400 whitespace-nowrap">
                            <?= htmlspecialchars($m['reference_id'] ?? '-') ?>
                        </td>
                        <td class="p-4 text-center whitespace-nowrap">
                            <span class="<?= $badge_class ?> px-2.5 py-1 rounded-full text-[10px] font-bold uppercase inline-block">
                                <?= $badge_text ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2.5">
                                <img src="<?= $img_m ?>" class="w-8 h-8 object-cover rounded-lg border border-white/10 shrink-0">
                                <div>
                                    <span class="font-bold text-white block leading-tight"><?= htmlspecialchars($m['nama_produk']) ?></span>
                                    <span class="text-[10px] font-mono text-slate-400">#PRD-<?= str_pad($m['product_id'], 3, '0', STR_PAD_LEFT) ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 text-slate-300">
                            <span class="text-slate-400"><?= htmlspecialchars($m['nama_asal']) ?></span> 
                            <span class="text-slate-600 font-bold mx-1">➔</span> 
                            <span class="text-white font-bold"><?= htmlspecialchars($m['nama_tujuan']) ?></span>
                        </td>
                        <td class="p-4 text-center whitespace-nowrap">
                            <span class="<?= $qty_class ?> text-sm">
                                <?= $qty_sign ?> <?= number_format($m['quantity'], 0, ',', '.') ?> pcs
                            </span>
                        </td>
                        <td class="p-4 text-slate-400 whitespace-nowrap">
                            <?= htmlspecialchars($m['created_by'] ?? 'System') ?>
                        </td>
                        <td class="p-4 text-slate-400 max-w-xs truncate" title="<?= htmlspecialchars($m['notes'] ?? '-') ?>">
                            <?= htmlspecialchars($m['notes'] ?? '-') ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="9" class="p-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="text-3xl">📭</span>
                                <span class="font-bold text-slate-300 text-sm">Tidak ada riwayat mutasi yang cocok dengan kriteria filter.</span>
                                <span class="text-xs text-slate-500">Coba ubah filter kategori atau rentang tanggal pencarian Anda.</span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
