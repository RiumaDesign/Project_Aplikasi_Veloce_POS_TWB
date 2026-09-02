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
}

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

// 2. Data Stok Realtime dari Database Multi-Lokasi
$labels_stok = [];
$stok_gudang_data = [];
$stok_museum_data = [];
$stok_barat_data = [];

$res_stok = $conn->query("
    SELECT p.nama,
        COALESCE(sg.quantity, 0) as stok_gudang,
        COALESCE(sm.quantity, 0) as stok_museum,
        COALESCE(sb.quantity, 0) as stok_barat
    FROM `produk` p
    LEFT JOIN `stok_lokasi` sg ON p.id = sg.product_id AND sg.location_id = 1
    LEFT JOIN `stok_lokasi` sm ON p.id = sm.product_id AND sm.location_id = 2
    LEFT JOIN `stok_lokasi` sb ON p.id = sb.product_id AND sb.location_id = 3
    ORDER BY p.nama ASC
");
while ($ps = $res_stok->fetch_assoc()) {
    $labels_stok[] = $ps['nama'];
    $stok_gudang_data[] = intval($ps['stok_gudang']);
    $stok_museum_data[] = intval($ps['stok_museum']);
    $stok_barat_data[] = intval($ps['stok_barat']);
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
            <form method="GET" action="dashboard.php" class="glass-card-dark border border-white/10 p-2 rounded-2xl flex flex-wrap items-center gap-2">
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

    <!-- GRAFIK UTAMA: MONITORING STOK REALTIME MULTI-LOKASI -->
    <div class="glass-card-dark p-6 rounded-3xl border border-white/10 shadow-xl flex flex-col h-[400px]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
            <div>
                <h4 class="font-bold text-base text-white">📦 Grafik Real-Time Stok Multi-Outlet</h4>
                <p class="text-xs text-slate-400">Perbandingan ketersediaan stok fisik di Gudang Pusat vs Outlet Museum vs Outlet Barat</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-bold">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Gudang Pusat</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Outlet Museum</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-indigo-500 inline-block"></span> Outlet Barat</span>
            </div>
        </div>
        <div class="flex-1 relative min-h-0">
            <canvas id="chartStokRealtime"></canvas>
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

<!-- Modal Riwayat Transaksi Lengkap -->
<div id="modal-detail-transaksi" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
    <div class="glass-card-dark rounded-3xl p-6 w-full max-w-3xl max-h-[85vh] flex flex-col border border-white/10 text-white shadow-2xl">
        <div class="flex justify-between items-center pb-4 border-b border-white/10 mb-4">
            <div>
                <h3 class="text-base font-bold text-white">📑 Riwayat Seluruh Transaksi Nota</h3>
                <p class="text-xs text-slate-400">Menampilkan detail transaksi tercatat</p>
            </div>
            <button onclick="tutupModal('modal-detail-transaksi')" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>
        <div class="overflow-y-auto flex-1 divide-y divide-white/5 pr-2">
            <?php if (empty($list_transaksi)): ?>
                <div class="p-8 text-center text-slate-400 text-xs">Belum ada data transaksi tercatat.</div>
            <?php else: ?>
                <?php foreach($list_transaksi as $tx): ?>
                <div class="py-3 flex items-center justify-between gap-4 text-xs">
                    <div>
                        <span class="font-mono font-bold text-blue-400"><?= htmlspecialchars($tx['id_nota']) ?></span>
                        <span class="text-slate-400 ml-2 font-medium"><?= htmlspecialchars($tx['tanggal']) ?> <?= htmlspecialchars($tx['waktu_final']) ?></span>
                        <p class="text-slate-300 mt-1 font-semibold"><?= htmlspecialchars($tx['item_final']) ?></p>
                    </div>
                    <div class="text-right">
                        <span class="font-bold text-emerald-400 text-sm">Rp <?= number_format($tx['total_final'], 0, ',', '.') ?></span>
                        <p class="text-[10px] text-slate-400 mt-0.5"><b class="text-slate-200"><?= htmlspecialchars($tx['outlet_name']) ?></b> • Petugas: <b><?= htmlspecialchars($tx['petugas']) ?></b> (<?= htmlspecialchars($tx['metode']) ?>)</p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="pt-4 border-t border-white/10 flex justify-between items-center text-xs">
            <span class="text-slate-400">Total <?= count($list_transaksi) ?> transaksi ditampilkan</span>
            <div class="flex gap-2">
                <a href="export.php?module=penjualan&format=excel&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-1.5 rounded-xl transition text-xs flex items-center gap-1">
                    <span>📊</span> <span>Export Excel</span>
                </a>
                <a href="export.php?module=penjualan&format=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-white/10 font-bold px-2.5 py-1.5 rounded-xl transition text-xs">
                    CSV
                </a>
                <a href="export.php?module=penjualan&format=pdf&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&autoprint=1" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold px-3 py-1.5 rounded-xl transition text-xs flex items-center gap-1">
                    <span>🖨️</span> <span>Cetak PDF</span>
                </a>
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

<!-- Inisialisasi Chart.js -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Chart Stok Realtime
    const ctxStok = document.getElementById('chartStokRealtime');
    if (ctxStok) {
        new Chart(ctxStok, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_stok) ?>,
                datasets: [
                    { label: 'Gudang Pusat', data: <?= json_encode($stok_gudang_data) ?>, backgroundColor: 'rgba(59, 130, 246, 0.85)', borderRadius: 8 },
                    { label: 'Outlet Museum', data: <?= json_encode($stok_museum_data) ?>, backgroundColor: 'rgba(16, 185, 129, 0.85)', borderRadius: 8 },
                    { label: 'Outlet Barat', data: <?= json_encode($stok_barat_data) ?>, backgroundColor: 'rgba(99, 102, 241, 0.85)', borderRadius: 8 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8', font: { size: 10 } } }
                }
            }
        });
    }

    // 2. Chart Omset Penjualan per Seluruh Outlet / Vending Machine
    const ctxOutlet = document.getElementById('chartOutlet');
    if (ctxOutlet) {
        new Chart(ctxOutlet, {
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
                            color: '#cbd5e1',
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
        new Chart(ctxProduk, {
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
                    x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } },
                    y: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } }
                }
            }
        });
    }
});
</script>
