<?php
/**
 * Dashboard Action Handler - Veloce POS
 * Mengelola semua mutasi data POST admin (CRUD Outlet, Produk, Kasir, Stok, Retur)
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crud_action'])) {
    $action = $_POST['crud_action'];

    // -------------------------------------------------------------
    // 1. CRUD MASTER OUTLET & MESIN
    // -------------------------------------------------------------
    if ($action === 'add_outlet') {
        $code = strtoupper(trim($_POST['code']));
        $name = trim($_POST['name']);
        $type = $_POST['type'] ?? 'outlet';
        $status = $_POST['status'] ?? 'active';

        $stmt = $conn->prepare("INSERT INTO `locations` (`code`, `name`, `type`, `status`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $code, $name, $type, $status);
        $stmt->execute();
        $new_loc_id = $stmt->insert_id;

        // Inisialisasi slot stok untuk semua produk yang ada
        $all_prod = $conn->query("SELECT id FROM `produk`");
        while ($pr = $all_prod->fetch_assoc()) {
            $p_id = intval($pr['id']);
            $conn->query("INSERT IGNORE INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`, `stock_damaged`, `stock_expired`) VALUES ($p_id, $new_loc_id, 0, 0, 0)");
            $conn->query("INSERT IGNORE INTO `product_outlets` (`product_id`, `outlet_id`) VALUES ($p_id, $new_loc_id)");
        }

        header("Location: dashboard.php?page=outlet&msg=added");
        exit();

    } elseif ($action === 'edit_outlet') {
        $id = intval($_POST['id']);
        $code = strtoupper(trim($_POST['code']));
        $name = trim($_POST['name']);
        $type = $_POST['type'] ?? 'outlet';
        $status = $_POST['status'] ?? 'active';

        $stmt = $conn->prepare("UPDATE `locations` SET `code` = ?, `name` = ?, `type` = ?, `status` = ? WHERE `id` = ?");
        $stmt->bind_param("ssssi", $code, $name, $type, $status, $id);
        $stmt->execute();

        header("Location: dashboard.php?page=outlet&msg=updated");
        exit();

    } elseif ($action === 'toggle_outlet_status') {
        $id = intval($_POST['id']);
        $curr = $conn->query("SELECT status FROM `locations` WHERE id = $id")->fetch_assoc();
        $new_status = ($curr['status'] === 'active') ? 'inactive' : 'active';

        $conn->query("UPDATE `locations` SET `status` = '$new_status' WHERE id = $id");
        header("Location: dashboard.php?page=outlet&msg=status_changed");
        exit();

    } elseif ($action === 'delete_outlet') {
        $id = intval($_POST['id']);

        // Proteksi Gudang Pusat Borobudur (ID: 1)
        if ($id === 1) {
            header("Location: dashboard.php?page=outlet&error=gudang_cannot_delete");
            exit();
        }

        // Nonaktifkan foreign key checks sementara untuk pembersihan menyeluruh yang aman
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        // Amankan relasi histori transaksi kasir (simpan nama outlet di pos_aktif, putus relasi outlet_id)
        $conn->query("UPDATE `transaksi` SET `outlet_id` = NULL WHERE `outlet_id` = $id");

        // Bersihkan seluruh data relasi terkait lokasi ini
        $conn->query("DELETE FROM `product_outlets` WHERE `outlet_id` = $id");
        $conn->query("DELETE FROM `stok_lokasi` WHERE `location_id` = $id");
        $conn->query("DELETE FROM `damaged_goods_transfers` WHERE `source_location_id` = $id OR `destination_location_id` = $id");
        $conn->query("DELETE FROM `delivery_orders` WHERE `source_location_id` = $id OR `destination_location_id` = $id");
        $conn->query("DELETE FROM `returns` WHERE `source_location_id` = $id OR `destination_location_id` = $id");
        $conn->query("UPDATE `locations` SET `parent_id` = NULL WHERE `parent_id` = $id");

        // Hapus lokasi dari master locations
        $conn->query("DELETE FROM `locations` WHERE `id` = $id");

        // Aktifkan kembali foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        header("Location: dashboard.php?page=outlet&msg=deleted");
        exit();
    }

    // -------------------------------------------------------------
    // 2. CRUD KASIR (DENGAN ENKRIPSI PASSWORD)
    // -------------------------------------------------------------
    if ($action === 'add_kasir') {
        $nama = $conn->real_escape_string(trim($_POST['nama']));
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $conn->query("INSERT INTO `kasir` (`nama`, `password`, `role`) VALUES ('$nama', '$password', 'kasir')");
        header("Location: dashboard.php?page=kasir&msg=added"); 
        exit();

    } elseif ($action === 'edit_kasir') {
        $id = intval($_POST['id']);
        $nama = $conn->real_escape_string(trim($_POST['nama']));
        
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $conn->query("UPDATE `kasir` SET `nama` = '$nama', `password` = '$password' WHERE `id` = $id");
        } else {
            $conn->query("UPDATE `kasir` SET `nama` = '$nama' WHERE `id` = $id");
        }
        header("Location: dashboard.php?page=kasir&msg=updated"); 
        exit();

    } elseif ($action === 'delete_kasir') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM `kasir` WHERE `id` = $id");
        header("Location: dashboard.php?page=kasir&msg=deleted"); 
        exit();
    }

    // -------------------------------------------------------------
    // 3. CRUD PRODUK & MAPPING KETERSEDIAAN OUTLET
    // -------------------------------------------------------------
    if ($action === 'add_produk') {
        $nama = $conn->real_escape_string(trim($_POST['nama']));
        $harga = intval($_POST['harga']);
        $custom_type = $conn->real_escape_string($_POST['custom_type'] ?? 'opsi-suhu');
        $stok_gudang = intval($_POST['stok_gudang'] ?? 0);
        $outlet_selection = $_POST['outlet_selection'] ?? 'all'; // 'all' atau array of ids

        $nama_gambar = NULL;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $nama_gambar = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['gambar']['tmp_name'], 'uploads/' . $nama_gambar);
        }

        $conn->query("INSERT INTO `produk` (`nama`, `harga`, `custom_type`, `gambar`, `stok_gudang`) 
                      VALUES ('$nama', $harga, '$custom_type', " . ($nama_gambar ? "'$nama_gambar'" : "NULL") . ", $stok_gudang)");
        $id_baru = $conn->insert_id;

        // Catat stok gudang ke tabel stok_lokasi (Gudang Pusat ID 1)
        $conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`, `stock_damaged`, `stock_expired`) 
                      VALUES ($id_baru, 1, $stok_gudang, 0, 0) ON DUPLICATE KEY UPDATE `quantity` = $stok_gudang");

        // Catat ketersediaan ke product_outlets
        if ($outlet_selection === 'all') {
            $all_out = $conn->query("SELECT id FROM `locations` WHERE `type` IN ('outlet', 'vm')");
            while ($o = $all_out->fetch_assoc()) {
                $conn->query("INSERT IGNORE INTO `product_outlets` (`product_id`, `outlet_id`) VALUES ($id_baru, {$o['id']})");
            }
        } elseif (is_array($_POST['outlet_ids'])) {
            foreach ($_POST['outlet_ids'] as $oid) {
                $oid = intval($oid);
                $conn->query("INSERT IGNORE INTO `product_outlets` (`product_id`, `outlet_id`) VALUES ($id_baru, $oid)");
            }
        }

        // Catat log
        if ($stok_gudang > 0) {
            $conn->query("INSERT INTO `log_stok` (`id_produk`, `tipe_aktivitas`, `jumlah`) VALUES ($id_baru, 'tambah_gudang', $stok_gudang)");
        }

        header("Location: dashboard.php?page=menu&msg=added");
        exit();

    } elseif ($action === 'edit_produk') {
        $id = intval($_POST['id']);
        $nama = $conn->real_escape_string(trim($_POST['nama']));
        $harga = intval($_POST['harga']);
        $custom_type = $conn->real_escape_string($_POST['custom_type'] ?? 'opsi-suhu');
        $stok_gudang = intval($_POST['stok_gudang'] ?? 0);
        $outlet_selection = $_POST['outlet_selection'] ?? 'all';

        $old_data = $conn->query("SELECT gambar, stok_gudang FROM `produk` WHERE `id` = $id")->fetch_assoc();
        $nama_gambar = $old_data['gambar'];
        $stok_lama = intval($old_data['stok_gudang']);

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            if ($nama_gambar && file_exists('uploads/' . $nama_gambar)) {
                @unlink('uploads/' . $nama_gambar);
            }
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $nama_gambar = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['gambar']['tmp_name'], 'uploads/' . $nama_gambar);
        }

        $conn->query("UPDATE `produk` SET `nama` = '$nama', `harga` = $harga, `custom_type` = '$custom_type', 
                      `gambar` = " . ($nama_gambar ? "'$nama_gambar'" : "NULL") . ", `stok_gudang` = $stok_gudang WHERE `id` = $id");

        // Sinkronkan ke stok_lokasi Gudang (ID 1)
        $conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`) VALUES ($id, 1, $stok_gudang) 
                      ON DUPLICATE KEY UPDATE `quantity` = $stok_gudang");

        // Update product_outlets mapping
        $conn->query("DELETE FROM `product_outlets` WHERE `product_id` = $id");
        if ($outlet_selection === 'all') {
            $all_out = $conn->query("SELECT id FROM `locations` WHERE `type` IN ('outlet', 'vm')");
            while ($o = $all_out->fetch_assoc()) {
                $conn->query("INSERT IGNORE INTO `product_outlets` (`product_id`, `outlet_id`) VALUES ($id, {$o['id']})");
            }
        } elseif (isset($_POST['outlet_ids']) && is_array($_POST['outlet_ids'])) {
            foreach ($_POST['outlet_ids'] as $oid) {
                $oid = intval($oid);
                $conn->query("INSERT IGNORE INTO `product_outlets` (`product_id`, `outlet_id`) VALUES ($id, $oid)");
            }
        }

        header("Location: dashboard.php?page=menu&msg=updated");
        exit();

    } elseif ($action === 'delete_produk') {
        $id = intval($_POST['id']);
        $old_data = $conn->query("SELECT gambar FROM `produk` WHERE `id` = $id")->fetch_assoc();
        if ($old_data['gambar'] && file_exists('uploads/' . $old_data['gambar'])) {
            @unlink('uploads/' . $old_data['gambar']);
        }
        $conn->query("DELETE FROM `produk` WHERE `id` = $id");
        $conn->query("DELETE FROM `stok_lokasi` WHERE `product_id` = $id");
        $conn->query("DELETE FROM `product_outlets` WHERE `product_id` = $id");

        header("Location: dashboard.php?page=menu&msg=deleted");
        exit();
    }

    // -------------------------------------------------------------
    // 4. DISTRIBUSI STOK GUDANG & DELIVERY ORDER (DO)
    // -------------------------------------------------------------
    if ($action === 'tambah_stok_gudang') {
        $id_produk = intval($_POST['id_produk']);
        $jumlah = intval($_POST['jumlah_tambah']);

        if ($jumlah <= 0) {
            header("Location: dashboard.php?page=stok&error=" . urlencode('Jumlah stok harus lebih dari 0!'));
            exit();
        }

        // Update stok gudang di tabel produk & stok_lokasi (WH-CENTRAL ID 1)
        $conn->query("UPDATE `produk` SET `stok_gudang` = `stok_gudang` + $jumlah WHERE `id` = $id_produk");
        $conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`) VALUES ($id_produk, 1, $jumlah) 
                      ON DUPLICATE KEY UPDATE `quantity` = `quantity` + $jumlah");
        $conn->query("INSERT INTO `log_stok` (`id_produk`, `tipe_aktivitas`, `jumlah`) VALUES ($id_produk, 'tambah_gudang', $jumlah)");
        
        // Catat ke buku besar mutasi stok terpadu
        $ref_inb = 'INB-' . date('YmdHis');
        $conn->query("INSERT INTO `stock_mutations` (`product_id`, `source_location_id`, `destination_location_id`, `quantity`, `mutation_type`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) 
                      VALUES ($id_produk, NULL, 1, $jumlah, 'inbound', 'restock_gudang', '$ref_inb', 'Penambahan Stok Masuk Gudang Pusat', 'Staff Gudang TWB', NOW())");

        header("Location: dashboard.php?page=stok&msg=stock_added");
        exit();
    }

    if ($action === 'transfer_stok') {
        $id_produk = intval($_POST['id_produk']);
        $source_id = intval($_POST['source_location_id'] ?? 1); // Default Gudang Pusat
        $destination_id = intval($_POST['destination_location_id'] ?? 0);
        $jumlah = intval($_POST['jumlah_transfer']);

        if ($jumlah <= 0 || $destination_id <= 0 || $source_id === $destination_id) {
            header("Location: dashboard.php?page=stok&error=" . urlencode('Pilihan lokasi transfer atau jumlah tidak valid!'));
            exit();
        }

        // Cek stok asal di stok_lokasi
        $q_source = $conn->query("SELECT quantity FROM `stok_lokasi` WHERE `product_id` = $id_produk AND `location_id` = $source_id");
        $avail = $q_source && $q_source->num_rows > 0 ? intval($q_source->fetch_assoc()['quantity']) : 0;

        if ($avail < $jumlah) {
            header("Location: dashboard.php?page=stok&error=" . urlencode("Stok di lokasi asal tidak mencukupi! (Tersedia: $avail)"));
            exit();
        }

        // Kurangi asal, tambah tujuan secara transaksional
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE `stok_lokasi` SET `quantity` = `quantity` - $jumlah WHERE `product_id` = $id_produk AND `location_id` = $source_id");
            $conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`) VALUES ($id_produk, $destination_id, $jumlah) 
                          ON DUPLICATE KEY UPDATE `quantity` = `quantity` + $jumlah");

            // Jika asal gudang, sinkronkan juga produk.stok_gudang
            if ($source_id == 1) {
                $conn->query("UPDATE `produk` SET `stok_gudang` = `stok_gudang` - $jumlah WHERE `id` = $id_produk");
            }

            // Catat DO resmi
            $do_num = 'DO/' . date('Ymd') . '/' . strtoupper(substr(uniqid(), -4));
            $today = date('Y-m-d');
            $conn->query("INSERT INTO `delivery_orders` (`do_number`, `do_date`, `source_location_id`, `destination_location_id`, `product_id`, `qty`, `status`) 
                          VALUES ('$do_num', '$today', $source_id, $destination_id, $id_produk, $jumlah, 'received')");

            // Catat ke buku besar mutasi stok terpadu
            $conn->query("INSERT INTO `stock_mutations` (`product_id`, `source_location_id`, `destination_location_id`, `quantity`, `mutation_type`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) 
                          VALUES ($id_produk, $source_id, $destination_id, $jumlah, 'transfer_do', 'delivery_order', '$do_num', 'Transfer Delivery Order (DO)', 'Petugas DO Logistik', NOW())");

            $conn->commit();
            header("Location: dashboard.php?page=stok&msg=do_transferred");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: dashboard.php?page=stok&error=" . urlencode('Gagal transfer: ' . $e->getMessage()));
            exit();
        }
    }

    // -------------------------------------------------------------
    // 5. RETUR BARANG RUSAK & EXPIRED
    // -------------------------------------------------------------
    if ($action === 'add_retur') {
        $id_produk = intval($_POST['id_produk']);
        $outlet_asal = intval($_POST['outlet_asal_id']);
        $tujuan_id = intval($_POST['tujuan_id'] ?? 1); // default ke gudang karantina
        $jumlah = intval($_POST['jumlah']);
        $tipe_retur = $_POST['tipe_retur'] ?? 'rusak';
        $alasan = $conn->real_escape_string(trim($_POST['alasan'] ?? 'Barang cacat'));

        if ($jumlah <= 0 || $outlet_asal <= 0) {
            header("Location: dashboard.php?page=retur&error=" . urlencode('Data retur tidak valid!'));
            exit();
        }

        // Cek stok di outlet asal
        $q_stok = $conn->query("SELECT quantity FROM `stok_lokasi` WHERE `product_id` = $id_produk AND `location_id` = $outlet_asal");
        $current_qty = $q_stok && $q_stok->num_rows > 0 ? intval($q_stok->fetch_assoc()['quantity']) : 0;

        if ($current_qty < $jumlah) {
            header("Location: dashboard.php?page=retur&error=" . urlencode("Stok di outlet asal tidak mencukupi untuk diretur! Tersedia: $current_qty"));
            exit();
        }

        $conn->begin_transaction();
        try {
            // Potong stok layak jual di outlet asal
            $conn->query("UPDATE `stok_lokasi` SET `quantity` = `quantity` - $jumlah WHERE `product_id` = $id_produk AND `location_id` = $outlet_asal");

            // Tambahkan ke counter barang rusak/expired di outlet penampung atau lokasi bersangkutan
            $col_retur = ($tipe_retur === 'expired') ? 'stock_expired' : 'stock_damaged';
            $conn->query("UPDATE `stok_lokasi` SET `$col_retur` = `$col_retur` + $jumlah WHERE `product_id` = $id_produk AND `location_id` = $tujuan_id");

            // Catat transaksi retur
            $retur_num = 'RT/' . date('Ymd') . '/' . strtoupper(substr(uniqid(), -4));
            $today = date('Y-m-d');
            $conn->query("INSERT INTO `returns` (`return_number`, `return_date`, `product_id`, `qty`, `source_location_id`, `destination_location_id`, `return_type`, `reason`, `status`) 
                          VALUES ('$retur_num', '$today', $id_produk, $jumlah, $outlet_asal, $tujuan_id, '$tipe_retur', '$alasan', 'completed')");

            // Catat ke buku besar mutasi stok terpadu
            $conn->query("INSERT INTO `stock_mutations` (`product_id`, `source_location_id`, `destination_location_id`, `quantity`, `mutation_type`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) 
                          VALUES ($id_produk, $outlet_asal, $tujuan_id, $jumlah, 'return', 'return', '$retur_num', 'Retur: $tipe_retur - $alasan', 'Petugas Retur', NOW())");

            $conn->commit();
            header("Location: dashboard.php?page=retur&msg=returned");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: dashboard.php?page=retur&error=" . urlencode('Gagal retur: ' . $e->getMessage()));
            exit();
        }
    }
}
