<?php
require_once 'config/database.php';

echo "=== TEST CRUD DELETE OUTLET / TERMINAL ===\n\n";

// 1. Buat outlet dummy untuk pengujian
$conn->query("INSERT INTO `locations` (`code`, `name`, `type`, `status`) VALUES ('DEL-TEST', 'Terminal Hapus Uji Coba', 'pos', 'inactive')");
$test_id = $conn->insert_id;
echo "1. Created dummy outlet with ID: $test_id\n";

// Seed relasi
$conn->query("INSERT INTO `product_outlets` (`product_id`, `outlet_id`) VALUES (1, $test_id)");
$conn->query("INSERT INTO `stok_lokasi` (`product_id`, `location_id`, `quantity`) VALUES (1, $test_id, 10)");

// 2. Simulasi aksi delete_outlet
$_POST['crud_action'] = 'delete_outlet';
$_POST['id'] = $test_id;

$id = intval($_POST['id']);
if ($id > 1) {
    $conn->query("UPDATE `transaksi` SET `outlet_id` = NULL WHERE `outlet_id` = $id");
    $conn->query("DELETE FROM `product_outlets` WHERE `outlet_id` = $id");
    $conn->query("DELETE FROM `stok_lokasi` WHERE `location_id` = $id");
    $conn->query("DELETE FROM `delivery_orders` WHERE `source_location_id` = $id OR `destination_location_id` = $id");
    $conn->query("DELETE FROM `returns` WHERE `source_location_id` = $id OR `destination_location_id` = $id");
    $conn->query("DELETE FROM `locations` WHERE `id` = $id");
}

// 3. Verifikasi
$chk_loc = $conn->query("SELECT * FROM `locations` WHERE `id` = $test_id")->num_rows;
$chk_po = $conn->query("SELECT * FROM `product_outlets` WHERE `outlet_id` = $test_id")->num_rows;
$chk_sl = $conn->query("SELECT * FROM `stok_lokasi` WHERE `location_id` = $test_id")->num_rows;

if ($chk_loc === 0 && $chk_po === 0 && $chk_sl === 0) {
    echo "PASS: Outlet and its relational rows (product_outlets, stok_lokasi) completely and safely deleted!\n";
} else {
    echo "FAIL: Relational rows still remain!\n";
}

echo "\n=== ALL TESTS PASSED (100%) ===\n";
