<?php
/**
 * Controller: Export Laporan & Audit Logging
 * File: controllers/ExportController.php
 * 
 * Menangani pembuatan berkas laporan Penjualan, Stok, Delivery Order (DO),
 * dan Retur Barang Rusak ke format Excel/CSV serta Cetak/PDF formal,
 * dilengkapi pencatatan riwayat pengunduhan ke tabel audit export_logs.
 */

class ExportController {
    private $conn;
    private $currentUser;

    public function __construct($databaseConnection, $userName = 'Administrator') {
        $this->conn = $databaseConnection;
        $this->currentUser = $userName;
    }

    /**
     * Catat aktivitas ekspor ke tabel audit export_logs
     */
    public function logExport($exportType, $module, $filterApplied, $totalRecords, $fileName) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $filterStr = is_array($filterApplied) ? json_encode($filterApplied) : strval($filterApplied);
        
        $stmt = $this->conn->prepare("
            INSERT INTO `export_logs` (`user_name`, `export_type`, `module`, `filter_applied`, `total_records`, `file_name`, `ip_address`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        if ($stmt) {
            $stmt->bind_param("ssssiss", $this->currentUser, $exportType, $module, $filterStr, $totalRecords, $fileName, $ip);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Ambil riwayat log ekspor terbaru
     */
    public function getRecentLogs($limit = 30) {
        $limit = intval($limit);
        $res = $this->conn->query("SELECT * FROM `export_logs` ORDER BY `id` DESC LIMIT $limit");
        $logs = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        return $logs;
    }

    /**
     * Ekspor Data Penjualan (Sales Report)
     */
    public function exportPenjualan($format = 'csv', $filters = []) {
        $startDate = $filters['start_date'] ?? '';
        $endDate = $filters['end_date'] ?? '';
        $outletId = intval($filters['outlet_id'] ?? 0);
        $kasirNama = $filters['petugas'] ?? '';

        $conditions = ["1=1"];
        if (!empty($startDate) && !empty($endDate)) {
            $s = $this->conn->real_escape_string($startDate);
            $e = $this->conn->real_escape_string($endDate);
            $conditions[] = "t.tanggal BETWEEN '$s' AND '$e'";
        }
        if ($outletId > 0) {
            $conditions[] = "t.outlet_id = $outletId";
        }
        if (!empty($kasirNama)) {
            $k = $this->conn->real_escape_string($kasirNama);
            $conditions[] = "t.petugas = '$k'";
        }
        $whereSql = implode(" AND ", $conditions);

        $sql = "
            SELECT t.*, COALESCE(l.name, t.pos_aktif) as nama_outlet, COALESCE(l.code, '-') as kode_outlet
            FROM `transaksi` t
            LEFT JOIN `locations` l ON t.outlet_id = l.id
            WHERE $whereSql
            ORDER BY t.tanggal DESC, t.id DESC
        ";
        $res = $this->conn->query($sql);
        $data = [];
        $grandTotal = 0;

        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $data[] = $r;
                $grandTotal += intval($r['total_harga'] ?? 0);
            }
        }

        $totalRecords = count($data);
        $timestamp = date('Ymd_His');
        $ext = ($format === 'pdf' ? 'html' : ($format === 'excel' ? 'xls' : 'csv'));
        $fileName = "Laporan_Penjualan_TWC_{$timestamp}.{$ext}";

        // Audit Logging
        $this->logExport($format, 'penjualan', $filters, $totalRecords, $fileName);

        if ($format === 'pdf') {
            $this->renderPdfPenjualan($data, $grandTotal, $filters);
        } elseif ($format === 'excel') {
            $this->renderExcelPenjualan($data, $grandTotal, $fileName, $filters);
        } else {
            $this->renderCsvPenjualan($data, $grandTotal, $fileName);
        }
    }

    /**
     * Ekspor Status Stok Real-time Multi-Lokasi
     */
    public function exportStok($format = 'csv', $filters = []) {
        $sql = "
            SELECT p.id as product_id, p.nama as nama_produk, p.harga, p.custom_type,
                   l.id as location_id, l.code as kode_lokasi, l.name as nama_lokasi, l.type as tipe_lokasi,
                   COALESCE(sl.quantity, 0) as stok_tersedia,
                   COALESCE(sl.stock_damaged, 0) as stok_rusak,
                   COALESCE(sl.stock_expired, 0) as stok_kadaluarsa,
                   sl.updated_at
            FROM `stok_lokasi` sl
            JOIN `produk` p ON sl.product_id = p.id
            JOIN `locations` l ON sl.location_id = l.id
            WHERE l.status = 'active'
            ORDER BY l.type ASC, l.code ASC, p.nama ASC
        ";
        $res = $this->conn->query($sql);
        $data = [];
        $totalQty = 0;
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $data[] = $r;
                $totalQty += intval($r['stok_tersedia']);
            }
        }

        $totalRecords = count($data);
        $timestamp = date('Ymd_His');
        $ext = ($format === 'pdf' ? 'html' : ($format === 'excel' ? 'xls' : 'csv'));
        $fileName = "Laporan_Stok_MultiOutlet_TWC_{$timestamp}.{$ext}";

        $this->logExport($format, 'stok', $filters, $totalRecords, $fileName);

        if ($format === 'pdf') {
            $this->renderPdfStok($data, $totalQty);
        } elseif ($format === 'excel') {
            $this->renderExcelStok($data, $totalQty, $fileName);
        } else {
            $this->renderCsvStok($data, $totalQty, $fileName);
        }
    }

    /**
     * Ekspor Data Delivery Order (DO)
     */
    public function exportDO($format = 'csv', $filters = []) {
        $sql = "
            SELECT do.*, 
                   COALESCE(ls.name, 'Gudang Pusat') as nama_asal,
                   COALESCE(ls.code, 'WH-CENTRAL') as kode_asal,
                   COALESCE(ld.name, 'Outlet') as nama_tujuan,
                   COALESCE(ld.code, 'OUT') as kode_tujuan
            FROM `delivery_orders` do
            LEFT JOIN `locations` ls ON do.source_location_id = ls.id
            LEFT JOIN `locations` ld ON do.destination_location_id = ld.id
            ORDER BY do.id DESC
        ";
        $res = $this->conn->query($sql);
        $data = [];
        $totalItems = 0;
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $data[] = $r;
                $totalItems += intval($r['total_items'] ?? 0);
            }
        }

        $totalRecords = count($data);
        $timestamp = date('Ymd_His');
        $ext = ($format === 'pdf' ? 'html' : ($format === 'excel' ? 'xls' : 'csv'));
        $fileName = "Laporan_Delivery_Order_TWC_{$timestamp}.{$ext}";

        $this->logExport($format, 'do', $filters, $totalRecords, $fileName);

        if ($format === 'pdf') {
            $this->renderPdfDO($data, $totalItems);
        } elseif ($format === 'excel') {
            $this->renderExcelDO($data, $totalItems, $fileName);
        } else {
            $this->renderCsvDO($data, $totalItems, $fileName);
        }
    }

    /**
     * Ekspor Data Retur & Barang Rusak
     */
    public function exportRetur($format = 'csv', $filters = []) {
        $sql = "
            SELECT r.*, p.nama as nama_produk, p.harga,
                   ls.name as nama_asal, ls.code as kode_asal,
                   COALESCE(ld.name, 'Gudang Karantina') as nama_tujuan
            FROM `returns` r
            JOIN `produk` p ON r.product_id = p.id
            JOIN `locations` ls ON r.source_location_id = ls.id
            LEFT JOIN `locations` ld ON r.destination_location_id = ld.id
            ORDER BY r.id DESC
        ";
        $res = $this->conn->query($sql);
        $data = [];
        $totalQty = 0;
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $data[] = $r;
                $totalQty += intval($r['quantity'] ?? 0);
            }
        }

        $totalRecords = count($data);
        $timestamp = date('Ymd_His');
        $ext = ($format === 'pdf' ? 'html' : ($format === 'excel' ? 'xls' : 'csv'));
        $fileName = "Laporan_Retur_BarangRusak_TWC_{$timestamp}.{$ext}";

        $this->logExport($format, 'retur', $filters, $totalRecords, $fileName);

        if ($format === 'pdf') {
            $this->renderPdfRetur($data, $totalQty);
        } elseif ($format === 'excel') {
            $this->renderExcelRetur($data, $totalQty, $fileName);
        } else {
            $this->renderCsvRetur($data, $totalQty, $fileName);
        }
    }

    /**
     * Ekspor Buku Besar Mutasi & Arus Stok Terpadu (Inbound, DO, Sale, Return)
     */
    public function exportMutasi($format = 'excel', $filters = []) {
        $kategori   = trim($filters['kategori'] ?? $filters['filter_kategori'] ?? $filters['mutation_type'] ?? '');
        $productId  = intval($filters['product_id'] ?? $filters['produk'] ?? $filters['filter_produk'] ?? 0);
        $locationId = intval($filters['location_id'] ?? $filters['lokasi'] ?? $filters['filter_lokasi'] ?? 0);
        $startDate  = trim($filters['start_date'] ?? $filters['filter_start'] ?? '');
        $endDate    = trim($filters['end_date'] ?? $filters['filter_end'] ?? '');

        $conditions = ["1=1"];
        if (!empty($kategori)) {
            $kat = $this->conn->real_escape_string($kategori);
            $conditions[] = "sm.mutation_type = '$kat'";
        }
        if ($productId > 0) {
            $conditions[] = "sm.product_id = $productId";
        }
        if ($locationId > 0) {
            $conditions[] = "(sm.source_location_id = $locationId OR sm.destination_location_id = $locationId)";
        }
        if (!empty($startDate) && !empty($endDate)) {
            $s = $this->conn->real_escape_string($startDate);
            $e = $this->conn->real_escape_string($endDate);
            $conditions[] = "DATE(sm.created_at) BETWEEN '$s' AND '$e'";
        }
        $whereSql = implode(" AND ", $conditions);

        $sql = "
            SELECT sm.*, 
                   p.nama as nama_produk, p.harga as harga_produk,
                   COALESCE(ls.name, 'Supplier / Eksternal') as nama_asal,
                   COALESCE(ls.code, 'EXT') as kode_asal,
                   COALESCE(ld.name, 'Konsumen / Kasir') as nama_tujuan,
                   COALESCE(ld.code, 'CS') as kode_tujuan
            FROM `stock_mutations` sm
            JOIN `produk` p ON sm.product_id = p.id
            LEFT JOIN `locations` ls ON sm.source_location_id = ls.id
            LEFT JOIN `locations` ld ON sm.destination_location_id = ld.id
            WHERE $whereSql
            ORDER BY sm.created_at DESC, sm.id DESC
        ";
        $res = $this->conn->query($sql);
        $data = [];
        $totalQty = 0;
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $data[] = $r;
                $totalQty += intval($r['quantity']);
            }
        }

        $totalRecords = count($data);
        $timestamp = date('Ymd_His');
        $ext = ($format === 'pdf' ? 'html' : ($format === 'excel' ? 'xls' : 'csv'));
        $fileName = "Laporan_Mutasi_Stok_TWC_{$timestamp}.{$ext}";

        $this->logExport($format, 'mutasi_stok', $filters, $totalRecords, $fileName);

        if ($format === 'pdf') {
            $this->renderPdfMutasi($data, $totalQty, $filters);
        } elseif ($format === 'excel') {
            $this->renderExcelMutasi($data, $totalQty, $fileName, $filters);
        } else {
            $this->renderCsvMutasi($data, $totalQty, $fileName, $filters);
        }
    }

    // =========================================================================
    // RENDER METODE CSV (EXCEL COMPATIBLE)
    // =========================================================================

    private function renderCsvPenjualan($data, $grandTotal, $fileName) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM untuk Microsoft Excel
        fputs($out, "\xEF\xBB\xBF");
        // Instruksi pemisah kolom untuk Microsoft Excel
        fputs($out, "sep=,\r\n");

        fputcsv($out, ['PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO']);
        fputcsv($out, ['LAPORAN REKAPITULASI PENJUALAN KASIR (VELOCE POS)']);
        fputcsv($out, ['Waktu Ekspor: ' . date('d-m-Y H:i:s'), 'Operator: ' . $this->currentUser]);
        fputcsv($out, []); // Baris Kosong

        fputcsv($out, ['No', 'ID Transaksi', 'Tanggal', 'Waktu', 'Petugas Kasir', 'Outlet / Mesin', 'Metode Bayar', 'Rincian Item', 'Total Harga (Rp)']);

        $no = 1;
        foreach ($data as $row) {
            fputcsv($out, [
                $no++,
                $row['id_transaksi'] ?? ('TRX-' . $row['id']),
                $row['tanggal'] ?? '-',
                $row['waktu'] ?? '-',
                $row['petugas'] ?? '-',
                $row['nama_outlet'] ?? '-',
                $row['metode'] ?? 'Cash',
                $row['item_singkat'] ?? '-',
                intval($row['total_harga'] ?? 0)
            ]);
        }

        fputcsv($out, []);
        fputcsv($out, ['', '', '', '', '', '', '', 'GRAND TOTAL PENJUALAN (RP)', $grandTotal]);
        fclose($out);
        exit();
    }

    private function renderCsvStok($data, $totalQty, $fileName) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputs($out, "sep=,\r\n");

        fputcsv($out, ['PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO']);
        fputcsv($out, ['LAPORAN STATUS INVENTORI MULTI-OUTLET (11 TITIK + 1 GUDANG)']);
        fputcsv($out, ['Waktu Ekspor: ' . date('d-m-Y H:i:s'), 'Operator: ' . $this->currentUser]);
        fputcsv($out, []);

        fputcsv($out, ['No', 'Kode Lokasi', 'Nama Lokasi Operasional', 'Tipe Unit', 'Nama Produk', 'Harga Jual (Rp)', 'Stok Siap Jual', 'Stok Rusak (Damaged)', 'Stok Kadaluarsa (Expired)', 'Terakhir Diperbarui']);

        $no = 1;
        foreach ($data as $row) {
            fputcsv($out, [
                $no++,
                $row['kode_lokasi'],
                $row['nama_lokasi'],
                strtoupper($row['tipe_lokasi']),
                $row['nama_produk'],
                intval($row['harga']),
                intval($row['stok_tersedia']),
                intval($row['stok_rusak']),
                intval($row['stok_kadaluarsa']),
                $row['updated_at'] ?? '-'
            ]);
        }

        fputcsv($out, []);
        fputcsv($out, ['', '', '', '', '', 'TOTAL STOK SIAP JUAL', $totalQty, '', '', '']);
        fclose($out);
        exit();
    }

    private function renderCsvDO($data, $totalItems, $fileName) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputs($out, "sep=,\r\n");

        fputcsv($out, ['PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO']);
        fputcsv($out, ['LAPORAN REKAPITULASI DELIVERY ORDER (DO) ANTAR-LOKASI']);
        fputcsv($out, ['Waktu Ekspor: ' . date('d-m-Y H:i:s'), 'Operator: ' . $this->currentUser]);
        fputcsv($out, []);

        fputcsv($out, ['No', 'Nomor Surat Jalan DO', 'Tanggal Kirim', 'Lokasi Asal', 'Lokasi Tujuan', 'Total Kuantitas Item', 'Petugas Pengirim', 'Status Pengiriman', 'Catatan']);

        $no = 1;
        foreach ($data as $row) {
            fputcsv($out, [
                $no++,
                $row['do_number'],
                $row['order_date'],
                $row['nama_asal'] . ' (' . $row['kode_asal'] . ')',
                $row['nama_tujuan'] . ' (' . $row['kode_tujuan'] . ')',
                intval($row['total_items']),
                $row['created_by'] ?? 'Petugas Logistik',
                strtoupper($row['status'] ?? 'COMPLETED'),
                $row['notes'] ?? '-'
            ]);
        }

        fputcsv($out, []);
        fputcsv($out, ['', '', '', '', 'TOTAL ITEM TERDISTRIBUSI', $totalItems, '', '', '']);
        fclose($out);
        exit();
    }

    private function renderCsvRetur($data, $totalQty, $fileName) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputs($out, "sep=,\r\n");

        fputcsv($out, ['PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO']);
        fputcsv($out, ['LAPORAN PENGELOLAAN BARANG RUSAK & RETUR EXPIRED']);
        fputcsv($out, ['Waktu Ekspor: ' . date('d-m-Y H:i:s'), 'Operator: ' . $this->currentUser]);
        fputcsv($out, []);

        fputcsv($out, ['No', 'Tanggal Retur', 'Nama Produk', 'Lokasi Asal', 'Lokasi Karantina', 'Kuantitas (Pcs)', 'Kategori Alasan', 'Keterangan Alasan', 'Petugas Pencatat']);

        $no = 1;
        foreach ($data as $row) {
            fputcsv($out, [
                $no++,
                $row['return_date'],
                $row['nama_produk'],
                $row['nama_asal'] . ' (' . $row['kode_asal'] . ')',
                $row['nama_tujuan'],
                intval($row['quantity']),
                ucwords(str_replace('_', ' ', $row['reason_category'] ?? 'Rusak Fisik')),
                $row['reason_detail'] ?? '-',
                $row['created_by'] ?? 'Staff'
            ]);
        }

        fputcsv($out, []);
        fputcsv($out, ['', '', '', '', 'TOTAL BARANG DIRETUR', $totalQty, '', '', '']);
        fclose($out);
        exit();
    }

    // =========================================================================
    // RENDER METODE NATIVE EXCEL (.XLS SPREADSHEET BERFORMAT RESMI)
    // =========================================================================

    private function renderExcelPenjualan($data, $grandTotal, $fileName, $filters = []) {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $periode = (!empty($filters['start_date']) && !empty($filters['end_date']))
            ? date('d/m/Y', strtotime($filters['start_date'])) . " s/d " . date('d/m/Y', strtotime($filters['end_date']))
            : "Seluruh Periode Operasional";

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Laporan Penjualan</x:Name>
    <x:WorksheetOptions>
     <x:DisplayGridlines/>
     <x:FitToPage/>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
  body, table { font-family: "Calibri", Arial, sans-serif; font-size: 11pt; }
  .title-company { font-size: 14pt; font-weight: bold; color: #1e3a8a; text-align: center; }
  .title-report { font-size: 12pt; font-weight: bold; color: #0f172a; text-align: center; }
  .title-meta { font-size: 9pt; color: #475569; text-align: center; font-style: italic; }
  th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; border: 1px solid #000000; padding: 8px 6px; text-align: center; }
  td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .text-left { text-align: left; }
  .str { mso-number-format:"\@"; }
  .num { mso-number-format:"\#\,\#\#0"; text-align: right; }
  .total-row { background-color: #f1f5f9; font-weight: bold; font-size: 11pt; border-top: 2px solid #000000; border-bottom: 3px double #000000; }
</style>
</head>
<body>
<table>
  <tr><td colspan="9" class="title-company">PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO</td></tr>
  <tr><td colspan="9" class="title-report">LAPORAN REKAPITULASI PENJUALAN KASIR (VELOCE POS)</td></tr>
  <tr><td colspan="9" class="title-meta">Periode: ' . htmlspecialchars($periode) . ' | Waktu Ekspor: ' . date('d-m-Y H:i:s') . ' | Operator: ' . htmlspecialchars($this->currentUser) . '</td></tr>
  <tr><td colspan="9"></td></tr>
  <thead>
    <tr>
      <th style="width: 45px;">No</th>
      <th style="width: 200px;">ID Transaksi</th>
      <th style="width: 100px;">Tanggal</th>
      <th style="width: 90px;">Waktu</th>
      <th style="width: 150px;">Petugas Kasir</th>
      <th style="width: 200px;">Outlet / Mesin</th>
      <th style="width: 110px;">Metode Bayar</th>
      <th style="width: 320px;">Rincian Item</th>
      <th style="width: 140px;">Total Harga (Rp)</th>
    </tr>
  </thead>
  <tbody>';

        $no = 1;
        foreach ($data as $row) {
            $trxId = htmlspecialchars($row['id_transaksi'] ?? ('TRX-' . $row['id']));
            $tgl = htmlspecialchars($row['tanggal'] ?? '-');
            $wkt = htmlspecialchars($row['waktu'] ?? '-');
            $kasir = htmlspecialchars($row['petugas'] ?? '-');
            $outlet = htmlspecialchars($row['nama_outlet'] ?? '-');
            $metode = htmlspecialchars($row['metode'] ?? 'Cash');
            $item = htmlspecialchars($row['item_singkat'] ?? '-');
            $total = intval($row['total_harga'] ?? 0);

            echo "<tr>
              <td class='text-center'>{$no}</td>
              <td class='str text-left'>{$trxId}</td>
              <td class='text-center'>{$tgl}</td>
              <td class='text-center'>{$wkt}</td>
              <td class='text-left'>{$kasir}</td>
              <td class='text-left'>{$outlet}</td>
              <td class='text-center'>{$metode}</td>
              <td class='text-left'>{$item}</td>
              <td class='num'>{$total}</td>
            </tr>";
            $no++;
        }

        echo '</tbody>
  <tfoot>
    <tr class="total-row">
      <td colspan="8" class="text-right" style="font-weight: bold;">GRAND TOTAL PENJUALAN (RP):</td>
      <td class="num" style="font-weight: bold;">' . $grandTotal . '</td>
    </tr>
  </tfoot>
</table>
</body>
</html>';
        exit();
    }

    private function renderExcelStok($data, $totalQty, $fileName) {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Status Stok</x:Name>
    <x:WorksheetOptions>
     <x:DisplayGridlines/>
     <x:FitToPage/>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
  body, table { font-family: "Calibri", Arial, sans-serif; font-size: 11pt; }
  .title-company { font-size: 14pt; font-weight: bold; color: #1e3a8a; text-align: center; }
  .title-report { font-size: 12pt; font-weight: bold; color: #0f172a; text-align: center; }
  .title-meta { font-size: 9pt; color: #475569; text-align: center; font-style: italic; }
  th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; border: 1px solid #000000; padding: 8px 6px; text-align: center; }
  td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .text-left { text-align: left; }
  .str { mso-number-format:"\@"; }
  .num { mso-number-format:"\#\,\#\#0"; text-align: right; }
  .total-row { background-color: #f1f5f9; font-weight: bold; font-size: 11pt; border-top: 2px solid #000000; border-bottom: 3px double #000000; }
</style>
</head>
<body>
<table>
  <tr><td colspan="10" class="title-company">PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO</td></tr>
  <tr><td colspan="10" class="title-report">LAPORAN STATUS INVENTORI MULTI-OUTLET (11 TITIK + 1 GUDANG)</td></tr>
  <tr><td colspan="10" class="title-meta">Waktu Ekspor: ' . date('d-m-Y H:i:s') . ' | Operator: ' . htmlspecialchars($this->currentUser) . '</td></tr>
  <tr><td colspan="10"></td></tr>
  <thead>
    <tr>
      <th style="width: 45px;">No</th>
      <th style="width: 110px;">Kode Lokasi</th>
      <th style="width: 220px;">Nama Lokasi Operasional</th>
      <th style="width: 95px;">Tipe Unit</th>
      <th style="width: 190px;">Nama Produk</th>
      <th style="width: 120px;">Harga Jual (Rp)</th>
      <th style="width: 120px;">Stok Siap Jual</th>
      <th style="width: 120px;">Stok Rusak</th>
      <th style="width: 120px;">Stok Expired</th>
      <th style="width: 140px;">Terakhir Diperbarui</th>
    </tr>
  </thead>
  <tbody>';

        $no = 1;
        foreach ($data as $row) {
            echo "<tr>
              <td class='text-center'>{$no}</td>
              <td class='str text-center'>" . htmlspecialchars($row['kode_lokasi']) . "</td>
              <td class='text-left'>" . htmlspecialchars($row['nama_lokasi']) . "</td>
              <td class='text-center'>" . strtoupper(htmlspecialchars($row['tipe_lokasi'])) . "</td>
              <td class='text-left'>" . htmlspecialchars($row['nama_produk']) . "</td>
              <td class='num'>" . intval($row['harga']) . "</td>
              <td class='num' style='font-weight: bold; color: #15803d;'>" . intval($row['stok_tersedia']) . "</td>
              <td class='num' style='color: #dc2626;'>" . intval($row['stok_rusak']) . "</td>
              <td class='num' style='color: #d97706;'>" . intval($row['stok_kadaluarsa']) . "</td>
              <td class='text-center str'>" . htmlspecialchars($row['updated_at'] ?? '-') . "</td>
            </tr>";
            $no++;
        }

        echo '</tbody>
  <tfoot>
    <tr class="total-row">
      <td colspan="6" class="text-right" style="font-weight: bold;">TOTAL STOK SIAP JUAL (PCS):</td>
      <td class="num" style="font-weight: bold; color: #15803d;">' . $totalQty . '</td>
      <td colspan="3"></td>
    </tr>
  </tfoot>
</table>
</body>
</html>';
        exit();
    }

    private function renderExcelDO($data, $totalItems, $fileName) {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Delivery Orders</x:Name>
    <x:WorksheetOptions>
     <x:DisplayGridlines/>
     <x:FitToPage/>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
  body, table { font-family: "Calibri", Arial, sans-serif; font-size: 11pt; }
  .title-company { font-size: 14pt; font-weight: bold; color: #1e3a8a; text-align: center; }
  .title-report { font-size: 12pt; font-weight: bold; color: #0f172a; text-align: center; }
  .title-meta { font-size: 9pt; color: #475569; text-align: center; font-style: italic; }
  th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; border: 1px solid #000000; padding: 8px 6px; text-align: center; }
  td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .text-left { text-align: left; }
  .str { mso-number-format:"\@"; }
  .num { mso-number-format:"\#\,\#\#0"; text-align: right; }
  .total-row { background-color: #f1f5f9; font-weight: bold; font-size: 11pt; border-top: 2px solid #000000; border-bottom: 3px double #000000; }
</style>
</head>
<body>
<table>
  <tr><td colspan="9" class="title-company">PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO</td></tr>
  <tr><td colspan="9" class="title-report">LAPORAN REKAPITULASI DELIVERY ORDER (DO) ANTAR-LOKASI</td></tr>
  <tr><td colspan="9" class="title-meta">Waktu Ekspor: ' . date('d-m-Y H:i:s') . ' | Operator: ' . htmlspecialchars($this->currentUser) . '</td></tr>
  <tr><td colspan="9"></td></tr>
  <thead>
    <tr>
      <th style="width: 45px;">No</th>
      <th style="width: 170px;">Nomor DO</th>
      <th style="width: 100px;">Tanggal Kirim</th>
      <th style="width: 180px;">Lokasi Asal</th>
      <th style="width: 180px;">Lokasi Tujuan</th>
      <th style="width: 130px;">Total Kuantitas</th>
      <th style="width: 150px;">Petugas Pengirim</th>
      <th style="width: 120px;">Status</th>
      <th style="width: 220px;">Catatan</th>
    </tr>
  </thead>
  <tbody>';

        $no = 1;
        foreach ($data as $row) {
            echo "<tr>
              <td class='text-center'>{$no}</td>
              <td class='str text-left font-bold'>" . htmlspecialchars($row['do_number']) . "</td>
              <td class='text-center'>" . htmlspecialchars($row['order_date']) . "</td>
              <td class='text-left'>" . htmlspecialchars($row['nama_asal'] . ' (' . $row['kode_asal'] . ')') . "</td>
              <td class='text-left'>" . htmlspecialchars($row['nama_tujuan'] . ' (' . $row['kode_tujuan'] . ')') . "</td>
              <td class='num font-bold'>" . intval($row['total_items']) . " pcs</td>
              <td class='text-left'>" . htmlspecialchars($row['created_by'] ?? 'Petugas Logistik') . "</td>
              <td class='text-center' style='font-weight: bold; color: #15803d;'>" . strtoupper(htmlspecialchars($row['status'] ?? 'COMPLETED')) . "</td>
              <td class='text-left'>" . htmlspecialchars($row['notes'] ?? '-') . "</td>
            </tr>";
            $no++;
        }

        echo '</tbody>
  <tfoot>
    <tr class="total-row">
      <td colspan="5" class="text-right" style="font-weight: bold;">TOTAL ITEM TERDISTRIBUSI (PCS):</td>
      <td class="num" style="font-weight: bold; color: #15803d;">' . $totalItems . '</td>
      <td colspan="3"></td>
    </tr>
  </tfoot>
</table>
</body>
</html>';
        exit();
    }

    private function renderExcelRetur($data, $totalQty, $fileName) {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Barang Retur</x:Name>
    <x:WorksheetOptions>
     <x:DisplayGridlines/>
     <x:FitToPage/>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
  body, table { font-family: "Calibri", Arial, sans-serif; font-size: 11pt; }
  .title-company { font-size: 14pt; font-weight: bold; color: #1e3a8a; text-align: center; }
  .title-report { font-size: 12pt; font-weight: bold; color: #0f172a; text-align: center; }
  .title-meta { font-size: 9pt; color: #475569; text-align: center; font-style: italic; }
  th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; border: 1px solid #000000; padding: 8px 6px; text-align: center; }
  td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .text-left { text-align: left; }
  .str { mso-number-format:"\@"; }
  .num { mso-number-format:"\#\,\#\#0"; text-align: right; }
  .total-row { background-color: #f1f5f9; font-weight: bold; font-size: 11pt; border-top: 2px solid #000000; border-bottom: 3px double #000000; }
</style>
</head>
<body>
<table>
  <tr><td colspan="9" class="title-company">PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO</td></tr>
  <tr><td colspan="9" class="title-report">LAPORAN PENGELOLAAN BARANG RUSAK & RETUR EXPIRED</td></tr>
  <tr><td colspan="9" class="title-meta">Waktu Ekspor: ' . date('d-m-Y H:i:s') . ' | Operator: ' . htmlspecialchars($this->currentUser) . '</td></tr>
  <tr><td colspan="9"></td></tr>
  <thead>
    <tr>
      <th style="width: 45px;">No</th>
      <th style="width: 110px;">Tanggal Retur</th>
      <th style="width: 200px;">Nama Produk</th>
      <th style="width: 170px;">Lokasi Asal</th>
      <th style="width: 170px;">Lokasi Karantina</th>
      <th style="width: 110px;">Kuantitas (Pcs)</th>
      <th style="width: 130px;">Kategori Alasan</th>
      <th style="width: 220px;">Keterangan Alasan</th>
      <th style="width: 140px;">Petugas</th>
    </tr>
  </thead>
  <tbody>';

        $no = 1;
        foreach ($data as $row) {
            echo "<tr>
              <td class='text-center'>{$no}</td>
              <td class='text-center'>" . htmlspecialchars($row['return_date']) . "</td>
              <td class='text-left font-bold'>" . htmlspecialchars($row['nama_produk']) . "</td>
              <td class='text-left'>" . htmlspecialchars($row['nama_asal'] . ' (' . $row['kode_asal'] . ')') . "</td>
              <td class='text-left'>" . htmlspecialchars($row['nama_tujuan']) . "</td>
              <td class='num font-bold' style='color: #dc2626;'>" . intval($row['quantity']) . "</td>
              <td class='text-center'>" . ucwords(str_replace('_', ' ', $row['reason_category'] ?? 'Rusak Fisik')) . "</td>
              <td class='text-left'>" . htmlspecialchars($row['reason_detail'] ?? '-') . "</td>
              <td class='text-left'>" . htmlspecialchars($row['created_by'] ?? 'Staff') . "</td>
            </tr>";
            $no++;
        }

        echo '</tbody>
  <tfoot>
    <tr class="total-row">
      <td colspan="5" class="text-right" style="font-weight: bold;">TOTAL BARANG DIRETUR (PCS):</td>
      <td class="num" style="font-weight: bold; color: #dc2626;">' . $totalQty . '</td>
      <td colspan="3"></td>
    </tr>
  </tfoot>
</table>
</body>
</html>';
        exit();
    }

    // =========================================================================
    // RENDER METODE CETAK / PDF (OFFICIAL FORMAL TEMPLATE TWB)
    // =========================================================================

    /**
     * Dapatkan logo TWB dalam format Base64 Data URI untuk kompatibilitas cetak & PDF offline
     */
    private function getLogoBase64() {
        $logoPath = dirname(__DIR__) . '/assets/images/logo_twb.png';
        if (file_exists($logoPath)) {
            $data = file_get_contents($logoPath);
            return 'data:image/png;base64,' . base64_encode($data);
        }
        return 'assets/images/logo_twb.png';
    }

    private function renderPdfHeader($title, $subtitle = '') {
        $curDate = date('d F Y - H:i:s');
        $logoSrc = $this->getLogoBase64();
        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{$title} - PT Taman Wisata Borobudur</title>
    <link rel="icon" type="image/png" href="assets/images/logo_twb.png">
    <style>
        @page {
            size: A4;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .header-box {
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .kop-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .kop-logo {
            height: 54px;
            width: auto;
            object-fit: contain;
            display: block;
        }
        .kop-divider {
            width: 2px;
            height: 48px;
            background: #cbd5e1;
        }
        .header-title h1 {
            font-size: 13px;
            font-weight: 800;
            margin: 0 0 2px 0;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header-title h2 {
            font-size: 11px;
            font-weight: 700;
            margin: 0 0 3px 0;
            color: #1e3a8a;
        }
        .header-title p {
            font-size: 8.5px;
            color: #64748b;
            margin: 0;
            line-height: 1.3;
        }
        .doc-badge {
            text-align: right;
            border-left: 1.5px solid #e2e8f0;
            padding-left: 14px;
            min-width: 130px;
        }
        .doc-badge span {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 800;
            font-size: 8.5px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .report-title {
            text-align: center;
            margin: 15px 0 18px 0;
            padding-bottom: 8px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .report-title h3 {
            font-size: 14px;
            font-weight: 800;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.3px;
        }
        .report-title p {
            font-size: 10px;
            color: #64748b;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
        }
        th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        td {
            padding: 6px 6px;
            border: 1px solid #cbd5e1;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .total-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 11px;
            border-top: 2px solid #0f172a;
        }
        .signatures {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 220px;
            text-align: center;
        }
        .signature-space {
            height: 55px;
        }
        .signature-line {
            border-bottom: 1px solid #0f172a;
            margin-bottom: 4px;
        }
        .print-btn-bar {
            background: #0f172a;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        @media print {
            .print-btn-bar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="print-btn-bar">
        <div><strong>Dokumen Resmi Laporan PT Taman Wisata Borobudur (TWB)</strong> &bull; Siap dicetak atau disimpan sebagai PDF</div>
        <div>
            <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:6px 14px;border-radius:6px;font-weight:bold;cursor:pointer;">🖨️ Cetak / Simpan PDF</button>
            <button onclick="window.close()" style="background:#475569;color:#fff;border:none;padding:6px 14px;border-radius:6px;margin-left:8px;font-weight:bold;cursor:pointer;">Tutup</button>
        </div>
    </div>

    <div class="header-box">
        <div class="kop-left">
            <img src="{$logoSrc}" alt="Logo Resmi TWB" class="kop-logo">
            <div class="kop-divider"></div>
            <div class="header-title">
                <h1>PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO</h1>
                <h2>UNIT TAMAN WISATA BOROBUDUR (TWB) &bull; SISTEM OPERASIONAL POS</h2>
                <p>Jl. Badrawati, Kawasan Candi Borobudur, Magelang, Jawa Tengah 56553 | Telp: (0293) 788266</p>
            </div>
        </div>
        <div class="doc-badge">
            <span>DOKUMEN RESMI TWB</span>
            <p style="margin:4px 0 0 0;font-size:8.5px;color:#64748b;">Dicetak: {$curDate}</p>
            <p style="margin:2px 0 0 0;font-size:8.5px;color:#64748b;">Operator: {$this->currentUser}</p>
        </div>
    </div>

    <div class="report-title">
        <h3>{$title}</h3>
        <p>{$subtitle}</p>
    </div>
HTML;
    }

    private function renderPdfFooter($creatorRole = 'Kasir / Staf Operasional') {
        $curDate = date('d F Y');
        return <<<HTML
    <div class="signatures">
        <div class="signature-box">
            <p>Borobudur, {$curDate}</p>
            <p style="margin-top:-6px;font-weight:bold;">Dibuat Oleh,</p>
            <div class="signature-space"></div>
            <div class="signature-line"></div>
            <p style="margin:0;font-weight:bold;">{$this->currentUser}</p>
            <p style="margin:0;font-size:9px;color:#64748b;">{$creatorRole}</p>
        </div>

        <div class="signature-box">
            <p>Borobudur, {$curDate}</p>
            <p style="margin-top:-6px;font-weight:bold;">Mengetahui & Menyetujui,</p>
            <div class="signature-space"></div>
            <div class="signature-line"></div>
            <p style="margin:0;font-weight:bold;">Supervisor Komersial & TI</p>
            <p style="margin:0;font-size:9px;color:#64748b;">PT Taman Wisata Candi Borobudur</p>
        </div>
    </div>

    <script>
        // Auto trigger dialog print jika parameter autoprint aktif
        if (window.location.search.indexOf('autoprint=1') !== -1) {
            window.onload = function() { window.print(); }
        }
    </script>
</body>
</html>
HTML;
    }

    private function renderPdfPenjualan($data, $grandTotal, $filters) {
        $periode = 'Seluruh Data Historis';
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $periode = "Periode: " . htmlspecialchars($filters['start_date']) . " s/d " . htmlspecialchars($filters['end_date']);
        }
        echo $this->renderPdfHeader("Laporan Rekapitulasi Transaksi Penjualan Kasir", $periode);
        ?>
        <table>
            <thead>
                <tr>
                    <th style="width:30px;" class="text-center">No</th>
                    <th>ID Nota</th>
                    <th>Tanggal & Jam</th>
                    <th>Kasir</th>
                    <th>Outlet / Lokasi</th>
                    <th>Metode</th>
                    <th>Rincian Pembelian</th>
                    <th class="text-right">Total (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data)): ?>
                    <tr><td colspan="8" class="text-center" style="padding:20px;color:#64748b;">Tidak ada data transaksi yang ditemukan.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="font-bold"><?= htmlspecialchars($row['id_transaksi'] ?? ('TRX-' . $row['id'])) ?></td>
                            <td><?= !empty($row['tanggal']) ? date('d/m/Y', strtotime($row['tanggal'])) : '-' ?> <span style="font-family:monospace;font-size:9.5px;color:#2563eb;"><?= !empty($row['waktu']) ? date('H:i:s', strtotime($row['waktu'])) : '-' ?> WIB</span></td>
                            <td><?= htmlspecialchars($row['petugas'] ?? '-') ?></td>
                            <td><span style="font-weight:bold;color:#2563eb;"><?= htmlspecialchars($row['nama_outlet'] ?? '-') ?></span></td>
                            <td class="text-center"><?= htmlspecialchars($row['metode'] ?? 'Cash') ?></td>
                            <td><?= htmlspecialchars($row['item_singkat'] ?? '-') ?></td>
                            <td class="text-right font-bold">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="7" class="text-right">GRAND TOTAL OMSET PENJUALAN:</td>
                        <td class="text-right font-bold" style="color:#0f172a;">Rp <?= number_format($grandTotal, 0, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        echo $this->renderPdfFooter("Petugas Kasir / Admin Keuangan");
        exit();
    }

    private function renderPdfStok($data, $totalQty) {
        echo $this->renderPdfHeader("Laporan Monitoring Stok Fisik Multi-Outlet", "Data Real-Time Ketersediaan Stok 11 Lokasi + 1 Gudang Pusat");
        ?>
        <table>
            <thead>
                <tr>
                    <th style="width:25px;" class="text-center">No</th>
                    <th>Kode</th>
                    <th>Nama Lokasi Operasional</th>
                    <th>Tipe</th>
                    <th>Nama Produk</th>
                    <th class="text-right">Harga (Rp)</th>
                    <th class="text-center">Stok Siap Jual</th>
                    <th class="text-center">Stok Rusak</th>
                    <th class="text-center">Stok Expired</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data)): ?>
                    <tr><td colspan="9" class="text-center" style="padding:20px;color:#64748b;">Tidak ada data stok.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="font-bold"><?= htmlspecialchars($row['kode_lokasi']) ?></td>
                            <td><?= htmlspecialchars($row['nama_lokasi']) ?></td>
                            <td class="text-center"><span style="font-size:8px;padding:2px 5px;background:#e2e8f0;border-radius:3px;font-weight:bold;"><?= strtoupper($row['tipe_lokasi']) ?></span></td>
                            <td class="font-bold"><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td class="text-right">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                            <td class="text-center font-bold" style="color:<?= $row['stok_tersedia'] > 5 ? '#059669' : '#dc2626' ?>;"><?= $row['stok_tersedia'] ?></td>
                            <td class="text-center" style="color:#e11d48;"><?= $row['stok_rusak'] ?></td>
                            <td class="text-center" style="color:#d97706;"><?= $row['stok_kadaluarsa'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="6" class="text-right">TOTAL KUANTITAS STOK SIAP JUAL:</td>
                        <td class="text-center font-bold" style="color:#059669;"><?= number_format($totalQty, 0, ',', '.') ?> Pcs</td>
                        <td colspan="2"></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        echo $this->renderPdfFooter("Kepala Gudang & Inventori");
        exit();
    }

    private function renderPdfDO($data, $totalItems) {
        echo $this->renderPdfHeader("Laporan Rekapitulasi Delivery Order (DO) Antar-Lokasi", "Surat Jalan & Bukti Mutasi Barang Resmi Antar-Pos");
        ?>
        <table>
            <thead>
                <tr>
                    <th style="width:25px;" class="text-center">No</th>
                    <th>Nomor DO</th>
                    <th>Tanggal</th>
                    <th>Lokasi Asal</th>
                    <th>Lokasi Tujuan</th>
                    <th class="text-center">Total Item</th>
                    <th>Petugas</th>
                    <th class="text-center">Status</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data)): ?>
                    <tr><td colspan="9" class="text-center" style="padding:20px;color:#64748b;">Belum ada riwayat Delivery Order.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="font-bold" style="color:#2563eb;"><?= htmlspecialchars($row['do_number']) ?></td>
                            <td><?= htmlspecialchars($row['order_date']) ?></td>
                            <td><?= htmlspecialchars($row['nama_asal']) ?></td>
                            <td class="font-bold"><?= htmlspecialchars($row['nama_tujuan']) ?></td>
                            <td class="text-center font-bold"><?= $row['total_items'] ?> Pcs</td>
                            <td><?= htmlspecialchars($row['created_by'] ?? 'Petugas Logistik') ?></td>
                            <td class="text-center"><span style="background:#dcfce7;color:#15803d;padding:2px 6px;border-radius:4px;font-weight:bold;font-size:9px;"><?= strtoupper($row['status'] ?? 'COMPLETED') ?></span></td>
                            <td><?= htmlspecialchars($row['notes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="5" class="text-right">TOTAL ITEM BARANG TERDISTRIBUSI:</td>
                        <td class="text-center font-bold"><?= number_format($totalItems, 0, ',', '.') ?> Pcs</td>
                        <td colspan="3"></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        echo $this->renderPdfFooter("Staf Pengendali Distribusi DO");
        exit();
    }

    private function renderPdfRetur($data, $totalQty) {
        echo $this->renderPdfHeader("Laporan Pengelolaan Barang Rusak & Retur Expired", "Berita Acara Karantina Barang Cacat Fisik & Kadaluarsa");
        ?>
        <table>
            <thead>
                <tr>
                    <th style="width:25px;" class="text-center">No</th>
                    <th>Tanggal</th>
                    <th>Nama Produk</th>
                    <th>Lokasi Asal</th>
                    <th>Karantina</th>
                    <th class="text-center">Kuantitas</th>
                    <th>Kategori Alasan</th>
                    <th>Keterangan Detail</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data)): ?>
                    <tr><td colspan="9" class="text-center" style="padding:20px;color:#64748b;">Tidak ada data retur barang.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['return_date']) ?></td>
                            <td class="font-bold"><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td><?= htmlspecialchars($row['nama_asal']) ?></td>
                            <td><?= htmlspecialchars($row['nama_tujuan']) ?></td>
                            <td class="text-center font-bold" style="color:#e11d48;"><?= $row['quantity'] ?> Pcs</td>
                            <td><span style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px;font-weight:bold;font-size:9px;"><?= ucwords(str_replace('_', ' ', $row['reason_category'] ?? 'Rusak Fisik')) ?></span></td>
                            <td><?= htmlspecialchars($row['reason_detail'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['created_by'] ?? 'Staff') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="5" class="text-right">TOTAL BARANG RUSAK / DIRETUR:</td>
                        <td class="text-center font-bold" style="color:#e11d48;"><?= number_format($totalQty, 0, ',', '.') ?> Pcs</td>
                        <td colspan="3"></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        echo $this->renderPdfFooter("Petugas Karantina & Retur");
        exit();
    }

    private function renderCsvMutasi($data, $totalQty, $fileName, $filters = []) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputs($out, "sep=,\r\n");

        fputcsv($out, ['PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO']);
        fputcsv($out, ['LAPORAN BUKU BESAR MUTASI & ARUS STOK TERPADU']);
        fputcsv($out, ['Waktu Ekspor: ' . date('d-m-Y H:i:s'), 'Operator: ' . $this->currentUser]);
        fputcsv($out, []);

        fputcsv($out, ['No', 'Waktu / Tanggal', 'No. Referensi', 'Kategori Mutasi', 'Nama Produk', 'Lokasi Asal', 'Lokasi Tujuan', 'Kuantitas (Qty)', 'Petugas / Operator', 'Keterangan']);

        $no = 1;
        foreach ($data as $row) {
            $tipeLabel = match($row['mutation_type'] ?? '') {
                'inbound' => 'Penambahan Gudang (Inbound)',
                'transfer_do' => 'Transfer DO Antar-Lokasi',
                'sale' => 'Penjualan Kasir / VM (Sale)',
                'return' => 'Retur Barang Rusak/Expired',
                'adjustment' => 'Penyesuaian Stok (Adjustment)',
                default => strtoupper($row['mutation_type'] ?? 'MUTASI')
            };

            fputcsv($out, [
                $no++,
                $row['created_at'] ?? '-',
                $row['reference_id'] ?? '-',
                $tipeLabel,
                $row['nama_produk'] ?? '-',
                $row['nama_asal'] ?? '-',
                $row['nama_tujuan'] ?? '-',
                intval($row['quantity'] ?? 0),
                $row['created_by'] ?? 'System',
                $row['notes'] ?? '-'
            ]);
        }

        fputcsv($out, []);
        fputcsv($out, ['', '', '', '', '', '', 'TOTAL ITEM MUTASI', $totalQty, '', '']);
        fclose($out);
        exit();
    }

    private function renderExcelMutasi($data, $totalQty, $fileName, $filters = []) {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Log Mutasi Stok</x:Name>
    <x:WorksheetOptions>
     <x:DisplayGridlines/>
     <x:FitToPage/>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
  body, table { font-family: "Calibri", Arial, sans-serif; font-size: 11pt; }
  .title-company { font-size: 14pt; font-weight: bold; color: #1e3a8a; text-align: center; }
  .title-report { font-size: 12pt; font-weight: bold; color: #0f172a; text-align: center; }
  .title-meta { font-size: 9pt; color: #475569; text-align: center; font-style: italic; }
  th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; border: 1px solid #000000; padding: 8px 6px; text-align: center; }
  td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .text-left { text-align: left; }
  .str { mso-number-format:"\@"; }
  .num { mso-number-format:"\#\,\#\#0"; text-align: right; }
  .total-row { background-color: #f1f5f9; font-weight: bold; font-size: 11pt; border-top: 2px solid #000000; border-bottom: 3px double #000000; }
  .badge-inbound { background-color: #ecfdf5; color: #065f46; font-weight: bold; }
  .badge-do { background-color: #eff6ff; color: #1e40af; font-weight: bold; }
  .badge-sale { background-color: #f5f3ff; color: #5b21b6; font-weight: bold; }
  .badge-return { background-color: #fff1f2; color: #9f1239; font-weight: bold; }
</style>
</head>
<body>
<table>
  <tr><td colspan="10" class="title-company">PT TAMAN WISATA CANDI BOROBUDUR, PRAMBANAN & RATU BOKO</td></tr>
  <tr><td colspan="10" class="title-report">BUKU MUTASI & KONTROL PERSEDIAAN BARANG</td></tr>
  <tr><td colspan="10" class="title-meta">Waktu Ekspor: ' . date('d-m-Y H:i:s') . ' | Operator: ' . htmlspecialchars($this->currentUser) . '</td></tr>
  <tr><td colspan="10"></td></tr>
  <thead>
    <tr>
      <th style="width: 45px;">No</th>
      <th style="width: 130px;">Waktu / Tanggal</th>
      <th style="width: 140px;">No. Dokumen</th>
      <th style="width: 140px;">Kategori Mutasi</th>
      <th style="width: 180px;">Kode & Nama Produk</th>
      <th style="width: 170px;">Lokasi Asal</th>
      <th style="width: 170px;">Lokasi Tujuan</th>
      <th style="width: 90px;">Volume (Pcs)</th>
      <th style="width: 130px;">Penanggung Jawab</th>
      <th style="width: 220px;">Catatan / Keterangan</th>
    </tr>
  </thead>
  <tbody>';

        $no = 1;
        foreach ($data as $row) {
            $mType = $row['mutation_type'] ?? '';
            $badgeClass = match($mType) {
                'inbound' => 'badge-inbound',
                'transfer_do' => 'badge-do',
                'sale' => 'badge-sale',
                'return' => 'badge-return',
                default => ''
            };
            $tipeLabel = match($mType) {
                'inbound' => 'Penambahan Gudang',
                'transfer_do' => 'Transfer DO',
                'sale' => 'Penjualan Kasir/VM',
                'return' => 'Retur Barang Rusak',
                'adjustment' => 'Penyesuaian Stok',
                default => strtoupper($mType)
            };

            echo '<tr>
      <td class="text-center">' . $no++ . '</td>
      <td class="text-center str">' . htmlspecialchars($row['created_at']) . '</td>
      <td class="str" style="font-weight: bold;">' . htmlspecialchars($row['reference_id'] ?? '-') . '</td>
      <td class="text-center ' . $badgeClass . '">' . $tipeLabel . '</td>
      <td style="font-weight: bold;">' . htmlspecialchars($row['nama_produk']) . '</td>
      <td>' . htmlspecialchars($row['nama_asal']) . '</td>
      <td style="font-weight: bold;">' . htmlspecialchars($row['nama_tujuan']) . '</td>
      <td class="num font-bold">' . $row['quantity'] . '</td>
      <td>' . htmlspecialchars($row['created_by'] ?? 'System') . '</td>
      <td>' . htmlspecialchars($row['notes'] ?? '-') . '</td>
    </tr>';
        }

        echo '</tbody>
  <tfoot>
    <tr class="total-row">
      <td colspan="7" class="text-right">TOTAL KUANTITAS SELURUH MUTASI:</td>
      <td class="num">' . $totalQty . '</td>
      <td colspan="2"></td>
    </tr>
  </tfoot>
</table>
</body>
</html>';
        exit();
    }

    private function renderPdfMutasi($data, $totalQty, $filters = []) {
        $katFilter = $filters['kategori'] ?? $filters['filter_kategori'] ?? $filters['mutation_type'] ?? '';
        $subTitle = "Rekapitulasi Resmi Pergerakan Stok: Penerimaan Pasokan, Distribusi DO, Penjualan Kasir/VM, dan Retur";
        if (!empty($katFilter)) {
            $subTitle .= " (Filter Kategori: " . strtoupper($katFilter) . ")";
        }
        echo $this->renderPdfHeader("Buku Mutasi & Kontrol Persediaan Barang", $subTitle);

        // Hitung Ringkasan Eksekutif untuk Halaman Cetak
        $sumInbound = 0; $sumDO = 0; $sumSale = 0; $sumReturn = 0;
        foreach ($data as $r) {
            $q = intval($r['quantity'] ?? 0);
            $t = $r['mutation_type'] ?? '';
            if ($t === 'inbound') $sumInbound += $q;
            elseif ($t === 'transfer_do') $sumDO += $q;
            elseif ($t === 'sale') $sumSale += $q;
            elseif ($t === 'return') $sumReturn += $q;
        }
        ?>

        <!-- TABEL RINGKASAN EKSEKUTIF (EXECUTIVE SUMMARY METRICS) -->
        <table style="margin-bottom: 15px; border-collapse: separate; border-spacing: 6px; width: 100%;">
            <tr>
                <td style="width: 25%; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 8px 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 8.5px; font-weight: bold; color: #065f46; text-transform: uppercase;">Pasokan Masuk (Inbound)</div>
                    <div style="font-size: 13.5px; font-weight: 900; color: #047857; margin-top: 2px;">+<?= number_format($sumInbound, 0, ',', '.') ?> Pcs</div>
                </td>
                <td style="width: 25%; background: #eff6ff; border: 1px solid #bfdbfe; padding: 8px 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 8.5px; font-weight: bold; color: #1e40af; text-transform: uppercase;">Distribusi DO Antar-Titik</div>
                    <div style="font-size: 13.5px; font-weight: 900; color: #1d4ed8; margin-top: 2px;"><?= number_format($sumDO, 0, ',', '.') ?> Pcs</div>
                </td>
                <td style="width: 25%; background: #f5f3ff; border: 1px solid #ddd6fe; padding: 8px 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 8.5px; font-weight: bold; color: #5b21b6; text-transform: uppercase;">Penjualan Kasir & VM</div>
                    <div style="font-size: 13.5px; font-weight: 900; color: #6d28d9; margin-top: 2px;">-<?= number_format($sumSale, 0, ',', '.') ?> Pcs</div>
                </td>
                <td style="width: 25%; background: #fff1f2; border: 1px solid #fecdd3; padding: 8px 10px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 8.5px; font-weight: bold; color: #9f1239; text-transform: uppercase;">Retur & Kerusakan</div>
                    <div style="font-size: 13.5px; font-weight: 900; color: #be123c; margin-top: 2px;">⇄ <?= number_format($sumReturn, 0, ',', '.') ?> Pcs</div>
                </td>
            </tr>
        </table>

        <!-- TABEL RINCIAN BUKU MUTASI (AUDIT TRAIL) -->
        <table>
            <thead>
                <tr>
                    <th style="width:25px;" class="text-center">No</th>
                    <th style="width:90px;">Waktu / Tanggal</th>
                    <th style="width:110px;">No. Dokumen</th>
                    <th style="width:90px;" class="text-center">Kategori</th>
                    <th>Kode & Nama Produk</th>
                    <th>Mutasi Lokasi (Asal ➔ Tujuan)</th>
                    <th style="width:65px;" class="text-center">Volume</th>
                    <th style="width:90px;">Penanggung Jawab</th>
                    <th style="width:125px;">Catatan / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data)): ?>
                    <tr><td colspan="9" class="text-center" style="padding:20px;color:#64748b;">Belum ada riwayat mutasi stok yang sesuai dengan kriteria filter ini.</td></tr>
                <?php else: ?>
                    <?php 
                    $no = 1; 
                    foreach ($data as $row): 
                        $mType = $row['mutation_type'] ?? '';
                        $badgeStyle = match($mType) {
                            'inbound'     => 'background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;',
                            'transfer_do' => 'background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;',
                            'sale'        => 'background:#f5f3ff;color:#5b21b6;border:1px solid #ddd6fe;',
                            'return'      => 'background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;',
                            default       => 'background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;'
                        };
                        $badgeLabel = match($mType) {
                            'inbound'     => '📥 INBOUND',
                            'transfer_do' => '🚚 DO TRANSFER',
                            'sale'        => '🛒 PENJUALAN',
                            'return'      => '⚠️ RETUR',
                            'adjustment'  => '⚖️ ADJUST',
                            default       => strtoupper($mType)
                        };
                        $qtySign = match($mType) {
                            'inbound' => '+',
                            'sale'    => '-',
                            'return'  => '⇄',
                            default   => ''
                        };
                        $qtyColor = match($mType) {
                            'inbound' => '#059669',
                            'sale'    => '#dc2626',
                            'return'  => '#e11d48',
                            default   => '#2563eb'
                        };
                        $skuCode = '#PRD-' . str_pad($row['product_id'], 3, '0', STR_PAD_LEFT);
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td style="font-size:9px;"><?= htmlspecialchars($row['created_at']) ?></td>
                            <td class="font-bold" style="font-size:9.5px;color:#1e3a8a;"><?= htmlspecialchars($row['reference_id'] ?? '-') ?></td>
                            <td class="text-center">
                                <span style="font-size:8px;padding:2px 5px;border-radius:4px;font-weight:bold;display:inline-block;<?= $badgeStyle ?>">
                                    <?= $badgeLabel ?>
                                </span>
                            </td>
                            <td>
                                <span class="font-bold"><?= htmlspecialchars($row['nama_produk']) ?></span><br>
                                <span style="font-size:8px;color:#64748b;font-family:monospace;"><?= $skuCode ?></span>
                            </td>
                            <td style="font-size:9px;">
                                <?= htmlspecialchars($row['nama_asal']) ?> ➔ <span class="font-bold" style="color:#0f172a;"><?= htmlspecialchars($row['nama_tujuan']) ?></span>
                            </td>
                            <td class="text-center font-bold" style="color:<?= $qtyColor ?>;">
                                <?= $qtySign . ' ' . $row['quantity'] ?> Pcs
                            </td>
                            <td style="font-size:9px;"><?= htmlspecialchars($row['created_by'] ?? 'Staff') ?></td>
                            <td style="font-size:8.5px;color:#475569;"><?= htmlspecialchars($row['notes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="6" class="text-right">TOTAL KUANTITAS AKUMULASI SELURUH MUTASI:</td>
                        <td class="text-center font-bold" style="color:#1e3a8a;"><?= number_format($totalQty, 0, ',', '.') ?> Pcs</td>
                        <td colspan="2"></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        echo $this->renderPdfFooter("Supervisor Logistik & Inventori TWB");
        exit();
    }
}

