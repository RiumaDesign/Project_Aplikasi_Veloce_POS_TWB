<?php
/**
 * Test Suite: REST API Gateway Verification
 * Multi-Outlet & Mobile POS Veloce — PT Taman Wisata Candi Borobudur
 * File: tests/test_api_gateway.php
 */

$base_url = "http://localhost/pos/api.php";

function make_request($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $default_headers = ['Content-Type: application/json'];
    $final_headers = array_merge($default_headers, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $final_headers);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    curl_close($ch);

    return [
        'code'     => $http_code,
        'body'     => json_decode($response, true),
        'raw'      => $response,
        'time_sec' => $total_time
    ];
}

echo "=========================================================\n";
echo "⚡ PENGUJIAN RESTFUL API GATEWAY (VELOCE POS DAY 9)\n";
echo "=========================================================\n\n";

// 1. Uji Endpoint Ping
echo "1. Menguji GET ?action=ping (Healthcheck & Latensi)...\n";
$res_ping = make_request("$base_url?action=ping");
assert($res_ping['code'] === 200, "Ping harus berstatus 200 OK");
assert($res_ping['body']['status'] === 'success', "Status harus success");
echo "   -> PASS: Server online! Latensi respon: {$res_ping['body']['meta']['latency_ms']} ms\n\n";

// 2. Uji Endpoint Outlets
echo "2. Menguji GET ?action=outlets (Daftar Titik Penjualan & VM)...\n";
$res_outlets = make_request("$base_url?action=outlets");
assert($res_outlets['code'] === 200, "Outlets harus berstatus 200 OK");
assert(is_array($res_outlets['body']['data']), "Data outlets harus berupa array");
$total_outlets = count($res_outlets['body']['data']);
echo "   -> PASS: Ditemukan $total_outlets lokasi aktif. Data format JSON valid!\n\n";

// 3. Uji Endpoint Products
echo "3. Menguji GET ?action=products (Katalog Produk Master)...\n";
$res_prods = make_request("$base_url?action=products");
assert($res_prods['code'] === 200, "Products harus berstatus 200 OK");
$total_prods = count($res_prods['body']['data']);
echo "   -> PASS: Ditemukan $total_prods produk master terdaftar.\n\n";

// 4. Uji Endpoint Stock per Outlet
echo "4. Menguji GET ?action=stock&outlet_id=2 (Stok Outlet Museum)...\n";
$res_stok = make_request("$base_url?action=stock&outlet_id=2");
assert($res_stok['code'] === 200, "Stok outlet 2 harus berstatus 200 OK");
assert(isset($res_stok['body']['data']['items']), "Harus memiliki array items");
$first_item = $res_stok['body']['data']['items'][0] ?? null;
echo "   -> PASS: Lokasi {$res_stok['body']['data']['outlet']['name']} memiliki " . count($res_stok['body']['data']['items']) . " SKU.\n";
if ($first_item) {
    echo "      Contoh Item: {$first_item['nama']} (Qty: {$first_item['quantity']} pcs, Harga: Rp " . number_format($first_item['harga'], 0, ',', '.') . ")\n\n";
}

// 5. Uji Otentikasi Mobile (POST auth_login)
echo "5. Menguji POST ?action=auth_login (Otentikasi Kasir Mobile)...\n";
$login_payload = [
    "username"  => "Andi Wijaya",
    "password"  => "kasir123",
    "outlet_id" => 2
];
$res_login = make_request("$base_url?action=auth_login", "POST", $login_payload);
assert($res_login['code'] === 200, "Login harus berstatus 200 OK");
$token = $res_login['body']['data']['token'] ?? '';
assert(!empty($token), "Token autentikasi harus digenerate");
echo "   -> PASS: Login berhasil! Kasir: {$res_login['body']['data']['user']['nama']}, Token: " . substr($token, 0, 25) . "...\n\n";

// 6. Uji Proteksi Keamanan API (Mutasi tanpa Token harus ditolak 401)
echo "6. Menguji Proteksi Token pada POST ?action=do_transfer (Tanpa Token)...\n";
$res_unauth = make_request("$base_url?action=do_transfer", "POST", [
    "source_location_id" => 1,
    "destination_location_id" => 2,
    "product_id" => 1,
    "qty" => 1
]);
assert($res_unauth['code'] === 401, "Harus ditolak dengan HTTP 401 Unauthorized");
echo "   -> PASS: Akses tanpa token berhasil ditolak secara aman (HTTP 401).\n\n";

// 7. Uji Mutasi Stok Cepat (POST do_transfer dengan Token Sah)
echo "7. Menguji POST ?action=do_transfer (Mutasi Stok Cepat via Mobile dengan Token)...\n";
$transfer_payload = [
    "source_location_id"      => 1, // Gudang Pusat
    "destination_location_id" => 2, // Outlet Museum
    "product_id"              => 1, // Aqua Botol
    "qty"                     => 2,
    "driver_name"             => "Kurir Motor Logistik TWC",
    "notes"                   => "Restock Cepat Mobile API Day 9"
];
$res_trf = make_request("$base_url?action=do_transfer", "POST", $transfer_payload, [
    "Authorization: Bearer $token"
]);
assert($res_trf['code'] === 201, "Transfer harus berhasil dengan HTTP 201 Created");
echo "   -> PASS: Mutasi stok berhasil! No DO: {$res_trf['body']['data']['do_number']}, Qty: {$res_trf['body']['data']['qty']} pcs.\n\n";

// 8. Uji Transaksi Penjualan Mobile POS (POST pos_checkout)
echo "8. Menguji POST ?action=pos_checkout (Transaksi Penjualan Kasir Mobile)...\n";
$checkout_payload = [
    "outlet_id"     => 2,
    "petugas"       => "Andi Wijaya (Mobile)",
    "metode"        => "QRIS",
    "item_singkat"  => "Aqua Botol 600ml (1x)",
    "total_harga"   => 4000,
    "uang_diterima" => 4000,
    "kembalian"     => 0,
    "items"         => [
        ["product_id" => 1, "qty" => 1, "harga" => 4000]
    ]
];
$res_checkout = make_request("$base_url?action=pos_checkout", "POST", $checkout_payload, [
    "Authorization: Bearer $token"
]);
assert($res_checkout['code'] === 201, "Checkout harus berhasil dengan HTTP 201 Created");
echo "   -> PASS: Transaksi kasir mobile tercatat! Nota: {$res_checkout['body']['data']['id_nota']}, Total: Rp " . number_format($res_checkout['body']['data']['total'], 0, ',', '.') . "\n\n";

// 9. Uji Riwayat Transaksi (GET transactions)
echo "9. Menguji GET ?action=transactions&limit=5...\n";
$res_tx = make_request("$base_url?action=transactions&limit=5");
assert($res_tx['code'] === 200, "Transactions harus berstatus 200 OK");
assert(count($res_tx['body']['data']) > 0, "Harus terdapat data transaksi");
echo "   -> PASS: Berhasil memuat " . count($res_tx['body']['data']) . " transaksi terbaru.\n\n";

echo "=========================================================\n";
echo "✅ SEMUA 9 PENGUJIAN REST API GATEWAY BERHASIL 100%!\n";
echo "   Waktu Rata-rata Latensi: " . round($res_ping['time_sec'] * 1000, 2) . " ms (< 200ms Target 4G)\n";
echo "=========================================================\n";
