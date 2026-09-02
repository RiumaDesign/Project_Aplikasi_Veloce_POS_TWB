<?php
/**
 * Migration & Reconciliation Script - Day 2
 * Veloce POS Multi-Outlet System
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "=================================================================\n";
echo "           MIGRASI & REKONSILIASI DATABASE - DAY 2               \n";
echo "=================================================================\n";
echo "Tanggal & Waktu : " . date('Y-m-d H:i:s') . "\n";
echo "Database Target : " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";

$conn->begin_transaction();

try {
    // -------------------------------------------------------------
    // 1. TAMBAH KOLOM OUTLET_ID PADA TABEL TRANSAKSI (JIKA BELUM ADA)
    // -------------------------------------------------------------
    echo "[1/6] Memeriksa struktur tabel transaksi...\n";
    $col_check = $conn->query("SHOW COLUMNS FROM `transaksi` LIKE 'outlet_id'");
    if ($col_check->num_rows === 0) {
        $conn->query("ALTER TABLE `transaksi` ADD COLUMN `outlet_id` INT(11) NULL AFTER `petugas`");
        $conn->query("ALTER TABLE `transaksi` ADD INDEX `idx_outlet_id` (`outlet_id`)");
        echo "  -> Kolom 'outlet_id' berhasil ditambahkan ke tabel transaksi.\n";
    } else {
        echo "  -> Kolom 'outlet_id' sudah tersedia di tabel transaksi.\n";
    }

    // Update outlet_id untuk transaksi historis berdasarkan pos_aktif
    $conn->query("UPDATE `transaksi` SET `outlet_id` = 2 WHERE (`pos_aktif` = 'POS A' OR `pos_aktif` = 'POS Terminal A') AND (`outlet_id` IS NULL OR `outlet_id` = 0)");
    $conn->query("UPDATE `transaksi` SET `outlet_id` = 3 WHERE (`pos_aktif` = 'POS B' OR `pos_aktif` = 'POS Terminal B') AND (`outlet_id` IS NULL OR `outlet_id` = 0)");
    echo "  -> Outlet ID pada data transaksi historis berhasil disinkronkan.\n";


    // -------------------------------------------------------------
    // 2. VERIFIKASI & SEEDING 11 LOKASI TARGET
    // -------------------------------------------------------------
    echo "\n[2/6] Memverifikasi 11 lokasi target di tabel locations...\n";
    $target_locations = [
        ['code' => 'WH-CENTRAL', 'name' => 'Gudang Pusat Borobudur', 'type' => 'warehouse', 'status' => 'active'],
        ['code' => 'OUT-MUSEUM', 'name' => 'Outlet Museum Samudra Raksa', 'type' => 'outlet', 'status' => 'active'],
        ['code' => 'OUT-BARAT', 'name' => 'Outlet Refreshment Barat', 'type' => 'outlet', 'status' => 'active'],
        ['code' => 'VM-01', 'name' => 'Vending Machine 1', 'type' => 'vm', 'status' => 'active'],
        ['code' => 'VM-02', 'name' => 'Vending Machine 2', 'type' => 'vm', 'status' => 'active'],
        ['code' => 'VM-03', 'name' => 'Vending Machine 3', 'type' => 'vm', 'status' => 'active'],
        ['code' => 'VM-04', 'name' => 'Vending Machine 4', 'type' => 'vm', 'status' => 'active'],
        ['code' => 'VM-05', 'name' => 'Vending Machine 5', 'type' => 'vm', 'status' => 'active'],
        ['code' => 'VM-06', 'name' => 'Vending Machine 6', 'type' => 'vm', 'status' => 'active'],
        ['code' => 'VM-07', 'name' => 'Vending Machine 7', 'type' => 'vm', 'status' => 'active'],
        ['code' => 'VM-08', 'name' => 'Vending Machine 8', 'type' => 'vm', 'status' => 'active'],
        ['code' => 'VM-09', 'name' => 'Vending Machine 9', 'type' => 'vm', 'status' => 'active']
    ];

    $loc_map = []; // Map 'code' => id
    foreach ($target_locations as $loc) {
        $stmt = $conn->prepare("SELECT id FROM `locations` WHERE `code` = ? LIMIT 1");
        $stmt->bind_param("s", $loc['code']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $loc_map[$loc['code']] = intval($row['id']);
            echo "  [OK] Lokasi {$loc['code']} ({$loc['name']}) -> ID: {$row['id']}\n";
        } else {
            $stmt_ins = $conn->prepare("INSERT INTO `locations` (`code`, `name`, `type`, `status`) VALUES (?, ?, ?, ?)");
            $stmt_ins->bind_param("ssss", $loc['code'], $loc['name'], $loc['type'], $loc['status']);
            $stmt_ins->execute();
            $new_id = $stmt_ins->insert_id;
            $loc_map[$loc['code']] = $new_id;
            echo "  [INSERT] Lokasi baru ditambahkan: {$loc['code']} -> ID: {$new_id}\n";
        }
    }


    // -------------------------------------------------------------
    // 3. MAPPING & MIGRATION STOK DARI PRODUK KE STOK_LOKASI
    // -------------------------------------------------------------
    echo "\n[3/6] Menyelaraskan stok produk ke stok_lokasi...\n";
    $wh_id = $loc_map['WH-CENTRAL'] ?? 1;
    $out_museum_id = $loc_map['OUT-MUSEUM'] ?? 2;
    $out_barat_id = $loc_map['OUT-BARAT'] ?? 3;

    $prods = $conn->query("SELECT id, nama, stok_gudang, stok_pos_a, stok_pos_b FROM produk ORDER BY id ASC");
    $total_synced = 0;

    while ($p = $prods->fetch_assoc()) {
        $p_id = intval($p['id']);
        $stok_gudang = intval($p['stok_gudang']);
        $stok_pos_a = intval($p['stok_pos_a']);
        $stok_pos_b = intval($p['stok_pos_b']);

        // 1. Gudang Pusat
        $conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`, `stock_damaged`, `stock_expired`) 
                      VALUES ($p_id, $wh_id, $stok_gudang, 0, 0) 
                      ON DUPLICATE KEY UPDATE `quantity` = IF(`quantity` = 0, $stok_gudang, `quantity`)");

        // 2. Outlet Museum Samudra Raksa (POS A)
        $conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`, `stock_damaged`, `stock_expired`) 
                      VALUES ($p_id, $out_museum_id, $stok_pos_a, 0, 0) 
                      ON DUPLICATE KEY UPDATE `quantity` = IF(`quantity` = 0, $stok_pos_a, `quantity`)");

        // 3. Outlet Refreshment Barat (POS B)
        $conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`, `stock_damaged`, `stock_expired`) 
                      VALUES ($p_id, $out_barat_id, $stok_pos_b, 0, 0) 
                      ON DUPLICATE KEY UPDATE `quantity` = IF(`quantity` = 0, $stok_pos_b, `quantity`)");

        // 4. Inisialisasi slot untuk 9 VM (VM-01 s/d VM-09) jika belum ada
        for ($v = 1; $v <= 9; $v++) {
            $vm_code = sprintf("VM-%02d", $v);
            if (isset($loc_map[$vm_code])) {
                $vm_id = $loc_map[$vm_code];
                $conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`, `stock_damaged`, `stock_expired`) 
                              VALUES ($p_id, $vm_id, 0, 0, 0) 
                              ON DUPLICATE KEY UPDATE `id` = `id`");
            }
        }

        $total_synced++;
    }
    echo "  -> Berhasil menyinkronkan stok untuk $total_synced produk ke Gudang, Outlet Museum, Outlet Barat, dan 9 VM.\n";


    // -------------------------------------------------------------
    // 4. INISIALISASI PRODUCT_OUTLETS (MANY-TO-MANY VISIBILITY)
    // -------------------------------------------------------------
    echo "\n[4/6] Menginisialisasi ketersediaan produk di product_outlets...\n";
    $active_outlets = $conn->query("SELECT id FROM locations WHERE type IN ('outlet', 'vm') AND status = 'active'");
    $outlet_ids = [];
    while ($o = $active_outlets->fetch_assoc()) {
        $outlet_ids[] = intval($o['id']);
    }

    $all_products = $conn->query("SELECT id, nama FROM produk");
    $po_count = 0;
    while ($p = $all_products->fetch_assoc()) {
        $p_id = intval($p['id']);
        foreach ($outlet_ids as $o_id) {
            $cek = $conn->query("SELECT id FROM `product_outlets` WHERE `product_id` = $p_id AND `outlet_id` = $o_id");
            if ($cek->num_rows === 0) {
                $conn->query("INSERT INTO `product_outlets` (`product_id`, `outlet_id`) VALUES ($p_id, $o_id)");
                $po_count++;
            }
        }
    }
    echo "  -> Berhasil mendaftarkan $po_count relasi produk-outlet baru (Produk aktif di semua lokasi target).\n";


    // -------------------------------------------------------------
    // 5. PARSING TRANSAKSI DETAIL HISTORIS DARI ITEM_SINGKAT
    // -------------------------------------------------------------
    echo "\n[5/6] Memeriksa & mem-parsing detail transaksi historis...\n";
    $txs = $conn->query("SELECT id, item_singkat FROM transaksi WHERE item_singkat IS NOT NULL AND item_singkat != ''");
    $parsed_items = 0;

    // Cache daftar produk [nama => [id, harga]]
    $prod_cache = [];
    $p_res = $conn->query("SELECT id, nama, harga FROM produk");
    while ($pr = $p_res->fetch_assoc()) {
        $prod_cache[strtolower(trim($pr['nama']))] = [
            'id' => intval($pr['id']),
            'harga' => intval($pr['harga'])
        ];
    }

    while ($tx = $txs->fetch_assoc()) {
        $tx_id = intval($tx['id']);
        // Cek apakah sudah punya detail
        $det_cek = $conn->query("SELECT COUNT(*) FROM `transaksi_detail` WHERE `transaksi_id` = $tx_id");
        if ($det_cek->fetch_row()[0] == 0) {
            // Parse item_singkat, misal: "4x Aqua Botol (Dingin)", "1x Fanta, 1x Frestea Apple 500ml"
            $parts = explode(',', $tx['item_singkat']);
            foreach ($parts as $part) {
                $part = trim($part);
                if (preg_match('/^(\d+)x\s+(.+)$/i', $part, $matches)) {
                    $qty = intval($matches[1]);
                    $raw_name = trim($matches[2]);
                    // Hilangkan suffix opsi suhu jika ada, misal: "(Dingin)", "(Normal)"
                    $clean_name = preg_replace('/\s*\(.*?\)\s*/', '', $raw_name);
                    $clean_key = strtolower(trim($clean_name));

                    $found_id = null;
                    $found_harga = 0;

                    // Pencarian nama eksak / parsial
                    if (isset($prod_cache[$clean_key])) {
                        $found_id = $prod_cache[$clean_key]['id'];
                        $found_harga = $prod_cache[$clean_key]['harga'];
                    } else {
                        foreach ($prod_cache as $name_key => $p_info) {
                            if (strpos($clean_key, $name_key) !== false || strpos($name_key, $clean_key) !== false) {
                                $found_id = $p_info['id'];
                                $found_harga = $p_info['harga'];
                                break;
                            }
                        }
                    }

                    if ($found_id) {
                        $subtotal = $qty * $found_harga;
                        $conn->query("INSERT INTO `transaksi_detail` (`transaksi_id`, `product_id`, `qty`, `harga_satuan`, `subtotal`) 
                                      VALUES ($tx_id, $found_id, $qty, $found_harga, $subtotal)");
                        $parsed_items++;
                    }
                }
            }
        }
    }
    echo "  -> Berhasil mengekstrak $parsed_items item ke transaksi_detail dari nota historis.\n";


    // -------------------------------------------------------------
    // 6. MEMBUAT VIEW KOMPATIBILITAS (SEPERTI YANG DIREKOMENDASIKAN)
    // -------------------------------------------------------------
    echo "\n[6/6] Menyiapkan compatibility views untuk interoperabilitas query...\n";
    $conn->query("CREATE OR REPLACE VIEW `outlets` AS SELECT * FROM `locations`");
    $conn->query("CREATE OR REPLACE VIEW `stok_outlet` AS SELECT `id`, `product_id` AS `id_produk`, `location_id` AS `id_outlet`, `quantity` AS `stok`, `stock_damaged`, `stock_expired` FROM `stok_lokasi`");
    $conn->query("CREATE OR REPLACE VIEW `produk_outlet` AS SELECT `id`, `product_id` AS `id_produk`, `outlet_id` AS `id_outlet` FROM `product_outlets`");
    $conn->query("CREATE OR REPLACE VIEW `delivery_order` AS SELECT * FROM `delivery_orders`");
    $conn->query("CREATE OR REPLACE VIEW `delivery_order_detail` AS SELECT * FROM `delivery_order_items`");
    $conn->query("CREATE OR REPLACE VIEW `retur` AS SELECT * FROM `returns`");
    $conn->query("CREATE OR REPLACE VIEW `retur_detail` AS SELECT * FROM `return_items`");
    $conn->query("CREATE OR REPLACE VIEW `export_log` AS SELECT * FROM `export_logs`");
    echo "  -> 8 Compatibility Views berhasil dibuat (`outlets`, `stok_outlet`, `produk_outlet`, `delivery_order`, `delivery_order_detail`, `retur`, `retur_detail`, `export_log`).\n";

    $conn->commit();

    echo "\n=================================================================\n";
    echo "MIGRASI & REKONSILIASI DAY 2 BERHASIL 100% (ZERO ERROR)\n";
    echo "=================================================================\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "\n[ERROR] Migrasi dibatalkan karena kesalahan: " . $e->getMessage() . "\n";
    exit(1);
}
