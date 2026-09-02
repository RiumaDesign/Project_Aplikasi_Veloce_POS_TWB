<?php
/**
 * Controller: Transaksi Kasir POS
 * File: controllers/TransactionController.php
 */

if (!function_exists('proses_transaksi_kasir')) {
    function proses_transaksi_kasir($conn, $kasir_aktif, $outlet_id, $pos_aktif, $post_data) {
        $id_transaksi = 'TRX-' . date('YmdHis') . '-' . rand(100, 999);
        $petugas = $conn->real_escape_string($kasir_aktif);
        $outlet_id = intval($outlet_id);
        $items_str = $conn->real_escape_string($post_data['items'] ?? '');
        $total_harga = intval($post_data['total_harga'] ?? 0);
        $metode = $conn->real_escape_string($post_data['metode'] ?? 'Cash');
        $tanggal = date('Y-m-d');
        $waktu = date('H:i:s');

        $detail_items = json_decode($post_data['detail_items'] ?? '[]', true);
        if (empty($detail_items)) {
            return ['status' => 'error', 'message' => 'Keranjang transaksi kosong!'];
        }

        $uang_diterima = intval($post_data['uang_diterima'] ?? 0);
        $kembalian = intval($post_data['kembalian'] ?? 0);
        if ($metode === 'QRIS' && $uang_diterima <= 0) {
            $uang_diterima = $total_harga;
            $kembalian = 0;
        }

        $conn->begin_transaction();

        try {
            // 1. Simpan Header Transaksi (Termasuk Nominal Uang Diterima & Kembalian)
            $query_tx = "INSERT INTO `transaksi` (`id_transaksi`, `tanggal`, `waktu`, `petugas`, `outlet_id`, `pos_aktif`, `metode`, `item_singkat`, `total_harga`, `uang_diterima`, `kembalian`) 
                         VALUES ('$id_transaksi', '$tanggal', '$waktu', '$petugas', $outlet_id, '$pos_aktif', '$metode', '$items_str', $total_harga, $uang_diterima, $kembalian)";
            if (!$conn->query($query_tx)) {
                throw new Exception("Gagal mencatat header transaksi: " . $conn->error);
            }
            $transaksi_id = $conn->insert_id;

            // 2. Simpan Item Detail & Potong Stok Multi-Lokasi
            foreach ($detail_items as $item) {
                $product_id = intval($item['id']);
                $qty_beli = intval($item['qty']);
                $harga_satuan = intval($item['harga'] ?? 0);
                $subtotal = $qty_beli * $harga_satuan;

                // Cek ketersediaan stok di stok_lokasi untuk outlet yang sedang aktif
                $cek_stok = $conn->query("SELECT sl.quantity, p.nama FROM `stok_lokasi` sl JOIN `produk` p ON sl.product_id = p.id WHERE sl.product_id = $product_id AND sl.location_id = $outlet_id LIMIT 1");
                
                if (!$cek_stok || $cek_stok->num_rows === 0) {
                    // Fallback cek di tabel produk jika stok_lokasi belum ada
                    $p_fb = $conn->query("SELECT nama FROM `produk` WHERE `id` = $product_id")->fetch_assoc();
                    $p_name = $p_fb['nama'] ?? "Produk #$product_id";
                    // Inisialisasi baris stok
                    $conn->query("INSERT IGNORE INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`) VALUES ($product_id, $outlet_id, 0)");
                    $sisa_stok = 0;
                } else {
                    $stok_row = $cek_stok->fetch_assoc();
                    $p_name = $stok_row['nama'];
                    $sisa_stok = intval($stok_row['quantity']);
                }

                if ($sisa_stok < $qty_beli) {
                    throw new Exception("Stok untuk '$p_name' di lokasi ini tidak mencukupi! (Sisa: $sisa_stok, Diminta: $qty_beli)");
                }

                // Simpan ke transaksi_detail
                $conn->query("INSERT INTO `transaksi_detail` (`transaksi_id`, `product_id`, `qty`, `harga_satuan`, `subtotal`) 
                              VALUES ($transaksi_id, $product_id, $qty_beli, $harga_satuan, $subtotal)");

                // Kurangi stok di stok_lokasi
                $conn->query("UPDATE `stok_lokasi` SET `quantity` = `quantity` - $qty_beli WHERE `product_id` = $product_id AND `location_id` = $outlet_id");

                // Catat ke buku besar mutasi stok terpadu (sale/pengurangan penjualan)
                $conn->query("INSERT INTO `stock_mutations` (`product_id`, `source_location_id`, `destination_location_id`, `quantity`, `mutation_type`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) 
                              VALUES ($product_id, $outlet_id, NULL, $qty_beli, 'sale', 'transaksi', '$id_transaksi', 'Penjualan Kasir ($metode)', '$petugas', NOW())");
            }

            $conn->commit();

            return [
                'status' => 'success',
                'message' => 'Transaksi berhasil diproses!',
                'id_transaksi' => $id_transaksi,
                'tanggal' => $tanggal,
                'waktu' => $waktu,
                'total_harga' => $total_harga,
                'uang_diterima' => $uang_diterima,
                'kembalian' => $kembalian,
                'metode' => $metode
            ];

        } catch (Exception $e) {
            $conn->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
