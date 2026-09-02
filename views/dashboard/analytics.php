<?php
/**
 * View: Analisis & Grafik Penjualan
 * File: views/dashboard/analytics.php
 */

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$where_clause = "";
if (!empty($start_date) && !empty($end_date)) {
    $where_clause = " WHERE tanggal BETWEEN '$start_date' AND '$end_date' ";
} elseif (!empty($start_date)) {
    $where_clause = " WHERE tanggal >= '$start_date' ";
} elseif (!empty($end_date)) {
    $where_clause = " WHERE tanggal <= '$end_date' ";
}

// 0. Query Log Riwayat Aktivitas Ekspor Dokumen
$export_logs_res = null;
try {
    $export_logs_res = $conn->query("SELECT * FROM `export_logs` ORDER BY id DESC LIMIT 25");
} catch (Exception $e) {}

// 1. Data Referensi Seluruh Lokasi Outlet & Vending Machine
$locations_ref = [];
$res_loc_ref = $conn->query("SELECT id, code, name, type FROM `locations` WHERE `type` IN ('outlet', 'vm', 'pos') ORDER BY type ASC, id ASC");
if ($res_loc_ref) {
    while ($loc = $res_loc_ref->fetch_assoc()) {
        $locations_ref[$loc['id']] = $loc['name'];
    }
}

// 2. Data Transaksi & Akumulasi Omzet per Outlet / VM
$res_all = $conn->query("SELECT * FROM `transaksi` $where_clause ORDER BY id DESC");
$total_omset = 0; 
$total_trx = 0; 
$list_transaksi = []; 
$produk_terjual = []; 
$data_outlet_raw = [];

if ($res_all) {
    while ($row = $res_all->fetch_assoc()) {
        $total_trx++;
        $val_total = intval($row['total_harga'] ?? 0);
        $val_item = $row['item_singkat'] ?? '-';
        $val_id = $row['id_transaksi'] ?? ('TRX-' . $row['id']);
        $val_waktu = $row['waktu'] ?? '';
        $val_petugas = $row['petugas'] ?? 'Kasir';
        $val_metode = $row['metode'] ?? 'Cash';
        $val_outlet_id = intval($row['outlet_id'] ?? 0);
        $val_pos_aktif = trim($row['pos_aktif'] ?? '');
        $total_omset += $val_total;

        // Pemetaan nama outlet / vending machine yang akurat
        $nama_outlet = '';
        if ($val_outlet_id > 0 && isset($locations_ref[$val_outlet_id])) {
            $nama_outlet = $locations_ref[$val_outlet_id];
        } else {
            foreach ($locations_ref as $id => $name) {
                if (stripos($val_pos_aktif, $name) !== false || stripos($name, $val_pos_aktif) !== false) {
                    $nama_outlet = $name;
                    break;
                }
            }
            if (empty($nama_outlet)) {
                if (stripos($val_pos_aktif, 'POS A') !== false) {
                    $nama_outlet = $locations_ref[2] ?? 'Outlet Museum Samudra Raksa';
                } elseif (stripos($val_pos_aktif, 'POS B') !== false) {
                    $nama_outlet = $locations_ref[3] ?? 'Outlet Refreshment Barat';
                } else {
                    $nama_outlet = !empty($val_pos_aktif) ? $val_pos_aktif : 'Outlet / VM Lainnya';
                }
            }
        }

        $list_transaksi[] = [
            'id_nota' => $val_id, 
            'tanggal' => $row['tanggal'] ?? '', 
            'waktu_final' => $val_waktu,
            'petugas' => $val_petugas, 
            'outlet_name' => $nama_outlet,
            'metode' => $val_metode, 
            'item_final' => $val_item, 
            'total_final' => $val_total
        ];

        $data_outlet_raw[$nama_outlet] = ($data_outlet_raw[$nama_outlet] ?? 0) + $val_total;

        // Ambil dari transaksi_detail jika ada, atau parse item_singkat
        $tx_id = intval($row['id']);
        $q_det = $conn->query("SELECT td.qty, p.nama FROM `transaksi_detail` td JOIN `produk` p ON td.product_id = p.id WHERE td.transaksi_id = $tx_id");
        if ($q_det && $q_det->num_rows > 0) {
            while ($d = $q_det->fetch_assoc()) {
                $p_name = $d['nama'];
                $produk_terjual[$p_name] = ($produk_terjual[$p_name] ?? 0) + intval($d['qty']);
            }
        } else {
            $items = explode(", ", $val_item);
            foreach ($items as $item) {
                if (preg_match('/(\d+)x\s+(.+)/', $item, $matches)) {
                    $qty = intval($matches[1]); 
                    $nama_p = preg_replace('/\s*\(.*?\)\s*/', '', trim($matches[2]));
                    $produk_terjual[$nama_p] = ($produk_terjual[$nama_p] ?? 0) + $qty;
                }
            }
        }
    }
}
$rata_rata = $total_trx > 0 ? ($total_omset / $total_trx) : 0;
arsort($produk_terjual);
arsort($data_outlet_raw);
$labels_outlet_js = array_keys($data_outlet_raw);
$omset_outlet_js = array_values($data_outlet_raw);

// Ambil riwayat audit log ekspor
$export_logs_res = $conn->query("SELECT * FROM `export_logs` ORDER BY id DESC LIMIT 15");

// 2. Data Penjualan Seluruh Produk di Semua Outlet & Vending Machine
$all_prods_res = $conn->query("SELECT id, nama, harga FROM `produk` ORDER BY nama ASC");
$produk_sales_map = [];
if ($all_prods_res) {
    while ($pr = $all_prods_res->fetch_assoc()) {
        $pNama = $pr['nama'];
        $produk_sales_map[$pNama] = [
            'id'    => $pr['id'],
            'nama'  => $pNama,
            'harga' => intval($pr['harga']),
            'qty'   => 0,
            'omset' => 0
        ];
    }
}

// Sinkronkan penjualan riil dengan daftar produk
foreach ($produk_terjual as $pNama => $qty) {
    if (isset($produk_sales_map[$pNama])) {
        $produk_sales_map[$pNama]['qty'] = $qty;
        $produk_sales_map[$pNama]['omset'] = $qty * $produk_sales_map[$pNama]['harga'];
    } else {
        $produk_sales_map[$pNama] = [
            'id'    => 0,
            'nama'  => $pNama,
            'harga' => 0,
            'qty'   => $qty,
            'omset' => 0
        ];
    }
}

// Urutkan produk berdasarkan kuantitas terjual terbanyak
uasort($produk_sales_map, function($a, $b) {
    return $b['qty'] <=> $a['qty'];
});

$chart_penjualan_labels = [];
$chart_penjualan_qty    = [];
$chart_penjualan_omset  = [];
foreach ($produk_sales_map as $p) {
    $chart_penjualan_labels[] = $p['nama'];
    $chart_penjualan_qty[]    = $p['qty'];
    $chart_penjualan_omset[]  = $p['omset'];
}
?>

<div class="space-y-6">
    <!-- Header & Filter Form -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">📊 Analisis Penjualan & Performa Stok</h2>
            <p class="text-xs text-slate-400">Pantau omzet real-time, volume transaksi, dan komparasi stok multi-lokasi.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <!-- Tombol Pintasan Cepat Periode -->
            <div class="flex items-center gap-1 bg-slate-900/80 border border-white/10 p-1 rounded-2xl">
                <?php 
                $isToday = (!empty($start_date) && $start_date === date('Y-m-d') && $end_date === date('Y-m-d'));
                $isWeek  = (!empty($start_date) && $start_date === date('Y-m-d', strtotime('-6 days')) && $end_date === date('Y-m-d'));
                $isMonth = (!empty($start_date) && $start_date === date('Y-m-01') && $end_date === date('Y-m-t'));
                $isAll   = empty($start_date) && empty($end_date);
                ?>
                <a href="dashboard.php?page=analytics&start_date=<?= date('Y-m-d') ?>&end_date=<?= date('Y-m-d') ?>" class="px-2.5 py-1 text-[11px] font-bold rounded-xl transition <?= $isToday ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">☀️ Hari Ini</a>
                <a href="dashboard.php?page=analytics&start_date=<?= date('Y-m-d', strtotime('-6 days')) ?>&end_date=<?= date('Y-m-d') ?>" class="px-2.5 py-1 text-[11px] font-bold rounded-xl transition <?= $isWeek ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">🗓️ 7 Hari</a>
                <a href="dashboard.php?page=analytics&start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>" class="px-2.5 py-1 text-[11px] font-bold rounded-xl transition <?= $isMonth ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">📆 Bulan Ini</a>
                <a href="dashboard.php?page=analytics" class="px-2.5 py-1 text-[11px] font-bold rounded-xl transition <?= $isAll ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">Semua</a>
            </div>

            <form method="GET" action="dashboard.php" class="glass-card-dark border border-white/10 p-1.5 rounded-2xl flex flex-wrap items-center gap-2">
                <input type="hidden" name="page" value="analytics">
                <div class="flex items-center gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Dari:</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="bg-slate-900 border border-white/10 rounded-xl px-2 py-1 text-xs text-white outline-none">
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Sampai:</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="bg-slate-900 border border-white/10 rounded-xl px-2 py-1 text-xs text-white outline-none">
                </div>
                <div class="flex gap-1">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-2.5 py-1 rounded-xl transition shadow-md shadow-blue-600/20">Filter</button>
                    <?php if(!empty($start_date)): ?>
                        <a href="dashboard.php?page=analytics" class="bg-slate-800 text-slate-400 text-xs font-bold px-2.5 py-1 rounded-xl border border-white/10">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Export Buttons -->
            <div class="flex items-center gap-2">
                <a href="export.php?module=penjualan&format=excel&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2.5 rounded-2xl transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5" title="Unduh berkas spreadsheet Excel resmi (.xls)">
                    <span>📊</span> <span>Unduh Excel</span>
                </a>
                <a href="export.php?module=penjualan&format=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-white/10 text-xs font-bold px-2.5 py-2.5 rounded-2xl transition flex items-center gap-1" title="Unduh format data mentah CSV">
                    <span>📄</span> <span>CSV</span>
                </a>
                <a href="export.php?module=penjualan&format=pdf&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&autoprint=1" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-3 py-2.5 rounded-2xl transition shadow-md shadow-indigo-600/20 flex items-center gap-1.5" title="Cetak atau Simpan PDF Laporan">
                    <span>🖨️</span> <span>Cetak / PDF</span>
                </a>
                <button type="button" onclick="bukaModal('modal-audit-export')" class="glass-card-dark border border-white/10 hover:bg-white/10 text-slate-300 text-xs font-bold px-3 py-2.5 rounded-2xl transition flex items-center gap-1.5" title="Lihat Riwayat Audit Ekspor">
                    <span>🕒</span> <span>Audit Log</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="glass-card-dark p-6 rounded-3xl border border-white/10 shadow-xl">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Omset Penjualan</p>
            <h3 class="text-3xl font-black text-white mt-2">Rp <?= number_format($total_omset, 0, ',', '.') ?></h3>
            <p class="text-[11px] text-blue-400 mt-1 font-semibold">Tercatat di sistem</p>
        </div>
        <div onclick="bukaModal('modal-detail-transaksi')" class="glass-card-dark p-6 rounded-3xl border border-white/10 shadow-xl cursor-pointer hover:border-blue-500/50 hover:bg-white/[0.04] transition duration-200 group relative overflow-hidden">
            <div class="absolute right-4 top-4 text-[10px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30 px-2 py-0.5 rounded-lg opacity-0 group-hover:opacity-100 transition">Lihat Riwayat ➔</div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider group-hover:text-blue-400 transition">Volume Transaksi</p>
            <h3 class="text-3xl font-black text-white mt-2"><?= $total_trx ?> Nota</h3>
            <p class="text-[11px] text-slate-400 mt-1 italic">Klik kartu ini untuk detail transaksi</p>
        </div>
        <div class="glass-card-dark p-6 rounded-3xl border border-white/10 shadow-xl">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rata-Rata Nilai Nota</p>
            <h3 class="text-3xl font-black text-white mt-2">Rp <?= number_format($rata_rata, 0, ',', '.') ?></h3>
            <p class="text-[11px] text-emerald-400 mt-1 font-semibold">Per transaksi berhasil</p>
        </div>
    </div>

    <!-- GRAFIK UTAMA: PENJUALAN PRODUK SELURUH OUTLET & VM -->
    <div class="glass-card-dark p-6 rounded-3xl border border-white/10 shadow-xl flex flex-col min-h-[460px]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
            <div>
                <h4 class="font-black text-lg text-white flex items-center gap-2">
                    <span>📊</span> <span>Grafik Penjualan Produk Seluruh Outlet & Vending Machine</span>
                </h4>
                <p class="text-xs text-slate-400 mt-1">Perbandingan akumulasi volume terjual (Pcs) dan nilai omzet per produk di seluruh titik operasional Borobudur</p>
            </div>
            
            <!-- Tombol Aksi: Lihat Detail Transaksi & Menu Cetak Cepat Periode -->
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" onclick="bukaModal('modal-detail-transaksi')" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-lg shadow-blue-600/30 flex items-center gap-2 transition hover:scale-105 cursor-pointer">
                    <span>👁️</span> <span>Lihat Detail Transaksi</span>
                </button>
                
                <!-- Dropdown Cetak Laporan Periode -->
                <div class="relative inline-block text-left" id="dropdown-cetak-wrapper">
                    <button type="button" onclick="toggleDropdownCetak(event)" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-white/10 font-bold text-xs px-4 py-2.5 rounded-xl flex items-center gap-2 transition hover:border-white/20 cursor-pointer">
                        <span>🖨️</span> <span>Cetak Laporan Penjualan</span> <span class="text-[10px] text-slate-400">▼</span>
                    </button>
                    <div id="menu-dropdown-cetak" class="hidden absolute right-0 mt-2 w-64 rounded-2xl bg-slate-900 border border-white/15 shadow-2xl z-30 py-2 divide-y divide-white/5">
                        <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Periode Cetak PDF</div>
                        <a href="export.php?module=penjualan&format=pdf&period_type=harian&autoprint=1" target="_blank" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-slate-300 hover:text-white hover:bg-blue-600/20 font-semibold transition">
                            <span>☀️</span> <span>Cetak Harian (Hari Ini)</span>
                        </a>
                        <a href="export.php?module=penjualan&format=pdf&period_type=mingguan&autoprint=1" target="_blank" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-slate-300 hover:text-white hover:bg-blue-600/20 font-semibold transition">
                            <span>🗓️</span> <span>Cetak Mingguan (7 Hari)</span>
                        </a>
                        <a href="export.php?module=penjualan&format=pdf&period_type=bulanan&autoprint=1" target="_blank" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-slate-300 hover:text-white hover:bg-blue-600/20 font-semibold transition">
                            <span>📆</span> <span>Cetak Bulanan (Bulan Ini)</span>
                        </a>
                        <a href="export.php?module=penjualan&format=pdf<?= (!empty($start_date) && !empty($end_date)) ? '&start_date='.urlencode($start_date).'&end_date='.urlencode($end_date) : '' ?>&autoprint=1" target="_blank" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-slate-300 hover:text-white hover:bg-white/5 font-semibold transition">
                            <span>🌐</span> <span>Cetak Sesuai Filter / Semua</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Indikator Legend & Stat Ringkas (Adaptif Tema Terang & Gelap) -->
        <div class="chart-legend-strip flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-2xl mb-4 text-xs transition-colors duration-200">
            <div class="flex items-center gap-5">
                <span class="flex items-center gap-2 font-bold text-blue-500">
                    <span class="w-3.5 h-3.5 rounded-md bg-blue-500 inline-block shadow-sm"></span> Volume Terjual (Pcs)
                </span>
                <span class="flex items-center gap-2 font-bold text-emerald-500">
                    <span class="w-3.5 h-3.5 rounded-md bg-emerald-500 inline-block shadow-sm"></span> Nilai Omzet (Rp)
                </span>
            </div>
            <div class="text-[11px] text-slate-400 font-medium">
                Total Produk Terdaftar: <strong class="text-slate-800 dark:text-white font-bold"><?= count($produk_sales_map) ?> Item</strong> • Total Volume: <strong class="text-blue-500 font-bold"><?= number_format(array_sum($chart_penjualan_qty)) ?> pcs</strong> • Total Omzet: <strong class="text-emerald-500 font-bold">Rp <?= number_format($total_omset, 0, ',', '.') ?></strong>
            </div>
        </div>

        <div class="flex-1 relative min-h-[300px]">
            <canvas id="chartPenjualanProduk"></canvas>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass-card-dark p-6 rounded-3xl border border-white/10 shadow-xl flex flex-col h-[360px]">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-bold text-sm text-white flex items-center gap-2">
                    <span>🏪</span> <span>Omset Penjualan per Outlet & VM</span>
                </h4>
                <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-lg">Realtime</span>
            </div>
            <div class="flex-1 relative min-h-0"><canvas id="chartOutlet"></canvas></div>
        </div>
        <div class="glass-card-dark p-6 rounded-3xl border border-white/10 shadow-xl flex flex-col h-[360px]">
            <h4 class="font-bold text-sm text-white mb-4">🔥 Kuantitas Produk Terlaris</h4>
            <div class="flex-1 relative min-h-0"><canvas id="chartProduk"></canvas></div>
        </div>
    </div>
</div>

<!-- Modal Rincian Penjualan Produk Seluruh Outlet (Lengkap dengan Jam/Waktu Transaksi & Filter Periode) -->
<div id="modal-detail-transaksi" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-5xl max-h-[90vh] flex flex-col border border-white/10 text-white shadow-2xl">
        
        <!-- Modal Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-white/10 mb-4 gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl">📑</span>
                    <h3 class="text-base font-black text-white">Detail Penjualan Produk Seluruh Outlet & Vending Machine</h3>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Rincian data transaksi real-time mencakup jam/waktu transaksi, produk, titik operasional, kasir, dan nilai nota.</p>
            </div>
            <button onclick="tutupModal('modal-detail-transaksi')" class="self-end sm:self-auto p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition text-lg leading-none">✕</button>
        </div>

        <!-- Filter Periode Cepat: Harian, Mingguan, Bulanan, Semua -->
        <div class="p-3 rounded-2xl bg-slate-900/80 border border-white/10 mb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5" id="btn-group-periode-modal">
                <button type="button" onclick="pilihPeriodeModal('all')" id="btn-period-all" class="btn-period-tab px-3 py-1.5 rounded-xl text-xs font-bold transition bg-blue-600 text-white shadow-sm flex items-center gap-1.5">
                    <span>🌐</span> <span>Semua Transaksi</span>
                </button>
                <button type="button" onclick="pilihPeriodeModal('harian')" id="btn-period-harian" class="btn-period-tab px-3 py-1.5 rounded-xl text-xs font-bold transition bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 flex items-center gap-1.5">
                    <span>☀️</span> <span>Harian (Hari Ini)</span>
                </button>
                <button type="button" onclick="pilihPeriodeModal('mingguan')" id="btn-period-mingguan" class="btn-period-tab px-3 py-1.5 rounded-xl text-xs font-bold transition bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 flex items-center gap-1.5">
                    <span>🗓️</span> <span>Mingguan (7 Hari)</span>
                </button>
                <button type="button" onclick="pilihPeriodeModal('bulanan')" id="btn-period-bulanan" class="btn-period-tab px-3 py-1.5 rounded-xl text-xs font-bold transition bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 flex items-center gap-1.5">
                    <span>📆</span> <span>Bulanan (Bulan Ini)</span>
                </button>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-slate-400">Periode Aktif:</span>
                <span id="badge-periode-aktif" class="font-mono font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 px-2.5 py-1 rounded-lg">
                    Seluruh Riwayat Transaksi
                </span>
            </div>
        </div>

        <!-- Filter Pencarian Cepat & Quick Summary -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div class="relative flex-1 max-w-md">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                <input type="text" id="cari-transaksi-input" onkeyup="filterTabelDetailTransaksi()" 
                       placeholder="Cari no. nota, nama produk, outlet/VM, kasir..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-900 border border-white/10 rounded-xl text-xs text-white placeholder-slate-500 outline-none focus:border-blue-500">
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="text-slate-400">
                    Menampilkan: <strong id="count-transaksi-filtered" class="text-white"><?= count($list_transaksi) ?></strong> dari <span id="count-total-transaksi"><?= count($list_transaksi) ?></span> Transaksi
                </span>
                <span id="badge-total-omzet" class="px-2.5 py-1 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-bold">
                    Total Omzet: Rp <?= number_format($total_omset, 0, ',', '.') ?>
                </span>
            </div>
        </div>

        <!-- Tabel Transaksi Lengkap -->
        <div class="overflow-y-auto flex-1 rounded-2xl border border-white/5 bg-slate-950/40 custom-scroll">
            <table class="w-full text-left text-xs">
                <thead class="sticky top-0 bg-slate-900/90 backdrop-blur border-b border-white/10 text-slate-400 uppercase text-[10px] font-bold">
                    <tr>
                        <th class="p-3 text-center w-12">No</th>
                        <th class="p-3">Waktu Transaksi</th>
                        <th class="p-3">No. Nota</th>
                        <th class="p-3">Outlet / Titik Lokasi</th>
                        <th class="p-3">Rincian Produk Dibeli</th>
                        <th class="p-3">Petugas & Metode</th>
                        <th class="p-3 text-right">Nilai Transaksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-detail-transaksi" class="divide-y divide-white/5 text-slate-300">
                    <?php if (empty($list_transaksi)): ?>
                        <tr id="row-empty-msg">
                            <td colspan="7" class="p-8 text-center text-slate-400 text-xs">Belum ada data transaksi yang tercatat.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach($list_transaksi as $tx): 
                            $tglRaw = $tx['tanggal'] ?? '';
                            $tglFmt = !empty($tglRaw) ? date('d M Y', strtotime($tglRaw)) : '-';
                            $jamFmt = !empty($tx['waktu_final']) ? (date('H:i:s', strtotime($tx['waktu_final'])) . ' WIB') : '-';
                        ?>
                        <tr class="hover:bg-white/[0.02] transition row-trx-detail" data-tanggal="<?= htmlspecialchars($tglRaw) ?>" data-total="<?= intval($tx['total_final']) ?>">
                            <td class="p-3 text-center text-slate-500 font-mono cell-number"><?= $no++ ?></td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="font-bold text-white block"><?= $tglFmt ?></span>
                                <span class="text-[10px] text-blue-400 font-mono font-semibold"><?= $jamFmt ?></span>
                            </td>
                            <td class="p-3 font-mono font-bold text-blue-400 whitespace-nowrap">
                                <?= htmlspecialchars($tx['id_nota']) ?>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-slate-800 border border-white/5 text-[11px] font-bold text-slate-200">
                                    <span>🏪</span> <?= htmlspecialchars($tx['outlet_name']) ?>
                                </span>
                            </td>
                            <td class="p-3 max-w-xs">
                                <span class="font-semibold text-slate-200 block"><?= htmlspecialchars($tx['item_final']) ?></span>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="text-xs text-white font-medium block"><?= htmlspecialchars($tx['petugas']) ?></span>
                                <span class="text-[10px] font-bold <?= (stripos($tx['metode'], 'qris') !== false) ? 'text-blue-400' : 'text-emerald-400' ?>">
                                    <?= htmlspecialchars($tx['metode']) ?>
                                </span>
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-emerald-400 whitespace-nowrap">
                                Rp <?= number_format($tx['total_final'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal Footer: Cetak Laporan, Export Excel, CSV Sesuai Periode -->
        <div class="pt-4 border-t border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs mt-4">
            <span class="text-slate-400">Pilih periode di atas untuk mencetak atau mengunduh data spesifik.</span>
            <div class="flex flex-wrap items-center gap-2">
                <a id="btn-export-excel-modal" href="export.php?module=penjualan&format=excel&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3.5 py-2 rounded-xl transition text-xs flex items-center gap-1.5 shadow-sm shadow-emerald-600/30">
                    <span>📊</span> <span>Export Excel</span>
                </a>
                <a id="btn-export-csv-modal" href="export.php?module=penjualan&format=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-white/10 font-bold px-3 py-2 rounded-xl transition text-xs">
                    CSV
                </a>
                <a id="btn-cetak-pdf-modal" href="export.php?module=penjualan&format=pdf&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&autoprint=1" target="_blank" class="bg-blue-600 hover:bg-blue-500 text-white font-black px-4 py-2 rounded-xl transition text-xs flex items-center gap-1.5 shadow-lg shadow-blue-600/30">
                    <span>🖨️</span> <span id="label-cetak-modal">Cetak / PDF Dokumen Resmi</span>
                </a>
                <button type="button" onclick="tutupModal('modal-detail-transaksi')" class="px-3 py-2 rounded-xl text-slate-400 hover:text-white transition font-bold">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Riwayat Audit Log Ekspor -->
<div id="modal-audit-export" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-4xl max-h-[85vh] flex flex-col border border-white/10 text-white shadow-2xl">
        <div class="flex justify-between items-center pb-4 border-b border-white/10 mb-4">
            <div>
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <span>🕒</span> <span>Jejak Audit Log Ekspor Dokumen</span>
                </h3>
                <p class="text-xs text-slate-400">Pencatatan riwayat setiap unduhan berkas laporan untuk kepatuhan & transparansi data</p>
            </div>
            <button onclick="tutupModal('modal-audit-export')" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>
        <div class="overflow-y-auto flex-1 divide-y divide-white/5 pr-1">
            <?php if (!$export_logs_res || $export_logs_res->num_rows === 0): ?>
                <div class="p-8 text-center text-slate-400 text-xs">Belum ada aktivitas ekspor yang tercatat di sistem.</div>
            <?php else: ?>
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-slate-400 border-b border-white/10">
                            <th class="py-2.5 px-3">Waktu</th>
                            <th class="py-2.5 px-3">Operator</th>
                            <th class="py-2.5 px-3">Modul</th>
                            <th class="py-2.5 px-3">Format</th>
                            <th class="py-2.5 px-3">Jumlah Baris</th>
                            <th class="py-2.5 px-3">Nama Berkas</th>
                            <th class="py-2.5 px-3">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php while($lg = $export_logs_res->fetch_assoc()): ?>
                        <tr class="hover:bg-white/[0.02] transition">
                            <td class="py-2.5 px-3 font-mono text-slate-300"><?= htmlspecialchars($lg['created_at']) ?></td>
                            <td class="py-2.5 px-3 font-bold text-white"><?= htmlspecialchars($lg['user_name']) ?></td>
                            <td class="py-2.5 px-3">
                                <span class="bg-blue-500/20 text-blue-300 border border-blue-500/30 px-2 py-0.5 rounded-md font-bold text-[10px] uppercase">
                                    <?= htmlspecialchars($lg['module']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase <?= $lg['export_type'] === 'pdf' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' ?>">
                                    <?= htmlspecialchars($lg['export_type']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-slate-200"><?= number_format($lg['total_records']) ?> data</td>
                            <td class="py-2.5 px-3 text-slate-400 font-mono text-[11px]"><?= htmlspecialchars($lg['file_name']) ?></td>
                            <td class="py-2.5 px-3 text-slate-500 font-mono text-[10px]"><?= htmlspecialchars($lg['ip_address'] ?? '-') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Inisialisasi Chart.js Dinamis dengan Deteksi & Live Switch Tema Terang/Gelap -->
<script>
function getTwbThemeConfig() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light' || document.documentElement.classList.contains('light');
    return {
        isLight: isLight,
        textX: isLight ? '#0f172a' : '#e2e8f0',
        textY: isLight ? '#334155' : '#94a3b8',
        legendText: isLight ? '#0f172a' : '#cbd5e1',
        gridColor: isLight ? 'rgba(0, 0, 0, 0.07)' : 'rgba(255, 255, 255, 0.06)',
        tooltipBg: isLight ? '#ffffff' : '#0f172a',
        tooltipTitle: isLight ? '#0f172a' : '#ffffff',
        tooltipBody: isLight ? '#334155' : '#cbd5e1',
        tooltipBorder: isLight ? '#cbd5e1' : 'rgba(255, 255, 255, 0.15)'
    };
}

document.addEventListener("DOMContentLoaded", function() {
    const tc = getTwbThemeConfig();

    // 1. Chart Penjualan Produk Seluruh Outlet & Vending Machine
    const ctxPenjualan = document.getElementById('chartPenjualanProduk');
    if (ctxPenjualan) {
        window.twbChartPenjualan = new Chart(ctxPenjualan, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_penjualan_labels) ?>,
                datasets: [
                    {
                        label: 'Volume Terjual (Pcs)',
                        data: <?= json_encode($chart_penjualan_qty) ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                        borderColor: '#2563eb',
                        borderWidth: 1.5,
                        borderRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Nilai Omzet (Rp)',
                        data: <?= json_encode($chart_penjualan_omset) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#059669',
                        borderWidth: 1.5,
                        borderRadius: 8,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: tc.tooltipBg,
                        titleColor: tc.tooltipTitle,
                        bodyColor: tc.tooltipBody,
                        borderColor: tc.tooltipBorder,
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.yAxisID === 'y1') {
                                    return ' 💵 Nilai Omzet: Rp ' + Number(context.raw).toLocaleString('id-ID');
                                } else {
                                    return ' 📦 Volume Terjual: ' + context.raw + ' pcs';
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: tc.textX,
                            font: { size: 11, weight: 'bold' }
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Volume Terjual (Pcs)',
                            color: '#2563eb',
                            font: { size: 10, weight: 'bold' }
                        },
                        grid: { color: tc.gridColor },
                        ticks: {
                            color: tc.textY,
                            font: { size: 10, weight: 'bold' },
                            precision: 0
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Nilai Omzet (Rp)',
                            color: '#059669',
                            font: { size: 10, weight: 'bold' }
                        },
                        grid: { drawOnChartArea: false },
                        ticks: {
                            color: tc.textY,
                            font: { size: 10, weight: 'bold' },
                            callback: function(val) {
                                return 'Rp ' + (val >= 1000 ? (val/1000) + 'k' : val);
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Chart Omset Penjualan per Seluruh Outlet / Vending Machine
    const ctxOutlet = document.getElementById('chartOutlet');
    if (ctxOutlet) {
        window.twbChartOutlet = new Chart(ctxOutlet, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_outlet_js) ?>,
                datasets: [{
                    data: <?= json_encode($omset_outlet_js) ?>,
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899',
                        '#06b6d4', '#f97316', '#6366f1', '#14b8a6', '#e11d48', '#84cc16'
                    ],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: tc.legendText,
                            font: { size: 11, weight: 'bold' },
                            padding: 12,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                return ' ' + context.label + ': Rp ' + val.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Chart Produk Terlaris
    const ctxProduk = document.getElementById('chartProduk');
    if (ctxProduk) {
        window.twbChartProduk = new Chart(ctxProduk, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_slice(array_keys($produk_terjual), 0, 5)) ?>,
                datasets: [{
                    label: 'Terjual (pcs)',
                    data: <?= json_encode(array_slice(array_values($produk_terjual), 0, 5)) ?>,
                    backgroundColor: 'rgba(245, 158, 11, 0.85)',
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: tc.gridColor }, ticks: { color: tc.textY, font: { weight: 'bold' } } },
                    y: { grid: { display: false }, ticks: { color: tc.textX, font: { size: 11, weight: 'bold' } } }
                }
            }
        });
    }

    // 4. Listener Live Theme Changed (Update warna chart seketika)
    window.addEventListener('themeChanged', function() {
        const c = getTwbThemeConfig();
        if (window.twbChartPenjualan) {
            window.twbChartPenjualan.options.scales.x.ticks.color = c.textX;
            window.twbChartPenjualan.options.scales.y.ticks.color = c.textY;
            window.twbChartPenjualan.options.scales.y.grid.color = c.gridColor;
            window.twbChartPenjualan.options.scales.y1.ticks.color = c.textY;
            window.twbChartPenjualan.options.plugins.tooltip.backgroundColor = c.tooltipBg;
            window.twbChartPenjualan.options.plugins.tooltip.titleColor = c.tooltipTitle;
            window.twbChartPenjualan.options.plugins.tooltip.bodyColor = c.tooltipBody;
            window.twbChartPenjualan.options.plugins.tooltip.borderColor = c.tooltipBorder;
            window.twbChartPenjualan.update();
        }
        if (window.twbChartOutlet) {
            window.twbChartOutlet.options.plugins.legend.labels.color = c.legendText;
            window.twbChartOutlet.update();
        }
        if (window.twbChartProduk) {
            window.twbChartProduk.options.scales.x.ticks.color = c.textY;
            window.twbChartProduk.options.scales.x.grid.color = c.gridColor;
            window.twbChartProduk.options.scales.y.ticks.color = c.textX;
            window.twbChartProduk.update();
        }
    });
});

let currentModalPeriod = 'all';

/**
 * Toggle menu dropdown cetak periode di kartu grafik
 */
function toggleDropdownCetak(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('menu-dropdown-cetak');
    if (menu) menu.classList.toggle('hidden');
}

window.addEventListener('click', function(e) {
    const wrap = document.getElementById('dropdown-cetak-wrapper');
    const menu = document.getElementById('menu-dropdown-cetak');
    if (wrap && menu && !wrap.contains(e.target)) {
        menu.classList.add('hidden');
    }
});

/**
 * Filter Periode Harian / Mingguan / Bulanan / Semua pada Modal
 */
function pilihPeriodeModal(tipe) {
    currentModalPeriod = tipe;

    // Update style tombol tab aktif
    document.querySelectorAll('.btn-period-tab').forEach(b => {
        b.classList.remove('bg-blue-600', 'text-white', 'shadow-sm');
        b.classList.add('bg-slate-800', 'text-slate-300');
    });
    const activeBtn = document.getElementById('btn-period-' + tipe);
    if (activeBtn) {
        activeBtn.classList.remove('bg-slate-800', 'text-slate-300');
        activeBtn.classList.add('bg-blue-600', 'text-white', 'shadow-sm');
    }

    // Tentukan tanggal batas
    const todayObj = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const todayStr = todayObj.getFullYear() + '-' + pad(todayObj.getMonth() + 1) + '-' + pad(todayObj.getDate());

    const weekAgoObj = new Date();
    weekAgoObj.setDate(weekAgoObj.getDate() - 6);
    const weekAgoStr = weekAgoObj.getFullYear() + '-' + pad(weekAgoObj.getMonth() + 1) + '-' + pad(weekAgoObj.getDate());

    const firstDayMonthStr = todayObj.getFullYear() + '-' + pad(todayObj.getMonth() + 1) + '-01';
    const lastDayObj = new Date(todayObj.getFullYear(), todayObj.getMonth() + 1, 0);
    const lastDayMonthStr = lastDayObj.getFullYear() + '-' + pad(lastDayObj.getMonth() + 1) + '-' + pad(lastDayObj.getDate());

    let startDate = '';
    let endDate = '';
    let labelBadge = 'Seluruh Riwayat Transaksi';
    let labelCetak = 'Cetak / PDF Dokumen Resmi';

    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    if (tipe === 'harian') {
        startDate = todayStr;
        endDate = todayStr;
        labelBadge = 'Harian (' + pad(todayObj.getDate()) + ' ' + monthNames[todayObj.getMonth()] + ' ' + todayObj.getFullYear() + ')';
        labelCetak = 'Cetak / PDF Laporan Harian';
    } else if (tipe === 'mingguan') {
        startDate = weekAgoStr;
        endDate = todayStr;
        labelBadge = 'Mingguan (' + weekAgoStr + ' s/d ' + todayStr + ')';
        labelCetak = 'Cetak / PDF Laporan Mingguan';
    } else if (tipe === 'bulanan') {
        startDate = firstDayMonthStr;
        endDate = lastDayMonthStr;
        labelBadge = 'Bulanan (' + monthNames[todayObj.getMonth()] + ' ' + todayObj.getFullYear() + ')';
        labelCetak = 'Cetak / PDF Laporan Bulanan';
    }

    const badgeEl = document.getElementById('badge-periode-aktif');
    if (badgeEl) badgeEl.textContent = labelBadge;

    const labelCetakEl = document.getElementById('label-cetak-modal');
    if (labelCetakEl) labelCetakEl.textContent = labelCetak;

    // Update URL tombol cetak & ekspor di footer modal
    const pParam = '&period_type=' + tipe + (startDate ? '&start_date=' + startDate + '&end_date=' + endDate : '');
    const btnPdf = document.getElementById('btn-cetak-pdf-modal');
    if (btnPdf) btnPdf.href = 'export.php?module=penjualan&format=pdf' + pParam + '&autoprint=1';
    
    const btnExcel = document.getElementById('btn-export-excel-modal');
    if (btnExcel) btnExcel.href = 'export.php?module=penjualan&format=excel' + pParam;

    const btnCsv = document.getElementById('btn-export-csv-modal');
    if (btnCsv) btnCsv.href = 'export.php?module=penjualan&format=csv' + pParam;

    // Terapkan filter baris tabel
    applyModalFilters();
}

/**
 * Filter gabungan: Berdasarkan rentang periode tanggal + teks pencarian
 */
function applyModalFilters() {
    const input = document.getElementById('cari-transaksi-input');
    const query = input ? input.value.toLowerCase().trim() : '';
    const rows = document.querySelectorAll('.row-trx-detail');

    const todayObj = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const todayStr = todayObj.getFullYear() + '-' + pad(todayObj.getMonth() + 1) + '-' + pad(todayObj.getDate());

    const weekAgoObj = new Date();
    weekAgoObj.setDate(weekAgoObj.getDate() - 6);
    const weekAgoStr = weekAgoObj.getFullYear() + '-' + pad(weekAgoObj.getMonth() + 1) + '-' + pad(weekAgoObj.getDate());

    const firstDayMonthStr = todayObj.getFullYear() + '-' + pad(todayObj.getMonth() + 1) + '-01';
    const lastDayObj = new Date(todayObj.getFullYear(), todayObj.getMonth() + 1, 0);
    const lastDayMonthStr = lastDayObj.getFullYear() + '-' + pad(lastDayObj.getMonth() + 1) + '-' + pad(lastDayObj.getDate());

    let visibleCount = 0;
    let omzetVisible = 0;

    rows.forEach(function(row) {
        const rowTgl = (row.getAttribute('data-tanggal') || '').trim();
        const rowTotal = parseInt(row.getAttribute('data-total') || '0', 10);
        const rowText = row.textContent.toLowerCase();

        // 1. Cek Periode
        let matchPeriod = false;
        if (currentModalPeriod === 'all') {
            matchPeriod = true;
        } else if (currentModalPeriod === 'harian') {
            matchPeriod = (rowTgl === todayStr);
        } else if (currentModalPeriod === 'mingguan') {
            matchPeriod = (rowTgl >= weekAgoStr && rowTgl <= todayStr);
        } else if (currentModalPeriod === 'bulanan') {
            matchPeriod = (rowTgl >= firstDayMonthStr && rowTgl <= lastDayMonthStr);
        }

        // 2. Cek Pencarian
        let matchQuery = (query === '' || rowText.indexOf(query) !== -1);

        if (matchPeriod && matchQuery) {
            row.style.display = '';
            visibleCount++;
            omzetVisible += rowTotal;
            const cellNum = row.querySelector('.cell-number');
            if (cellNum) cellNum.textContent = visibleCount;
        } else {
            row.style.display = 'none';
        }
    });

    const countFiltered = document.getElementById('count-transaksi-filtered');
    if (countFiltered) countFiltered.textContent = visibleCount;

    const badgeOmzet = document.getElementById('badge-total-omzet');
    if (badgeOmzet) {
        badgeOmzet.textContent = 'Total Omzet: Rp ' + omzetVisible.toLocaleString('id-ID');
    }
}

function filterTabelDetailTransaksi() {
    applyModalFilters();
}
</script>
