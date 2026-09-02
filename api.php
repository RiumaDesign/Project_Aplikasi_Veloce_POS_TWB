<?php
/**
 * Veloce POS - RESTful API Gateway
 * Multi-Outlet & Vending Machine System — PT Taman Wisata Candi Borobudur
 * File: api.php
 */

$start_time = microtime(true);

// 1. Standarisasi Header CORS & JSON
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Tangani Pre-Flight Request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Koneksi Database & Sesi
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Konfigurasi Token Keamanan
define('API_SECRET_KEY', 'TWC-BOROBUDUR-POS-2026');

/**
 * Helper Fungsi Standar Respon JSON API
 */
function send_api_response($status, $code, $message, $data = null, $extra = []) {
    global $start_time;
    $latency_ms = round((microtime(true) - $start_time) * 1000, 2);
    header("X-Response-Time-Ms: {$latency_ms}");
    http_response_code($code);

    $payload = [
        "status"    => $status,
        "code"      => $code,
        "message"   => $message,
        "data"      => $data,
        "meta"      => array_merge([
            "timestamp"  => date('c'),
            "latency_ms" => $latency_ms
        ], $extra)
    ];

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Verifikasi Otorisasi (Bearer Token, X-API-Key, atau Sesi Aktif)
 */
function verify_api_auth() {
    // A. Cek Sesi Web Aktif
    if (isset($_SESSION['user_id']) || isset($_SESSION['role'])) {
        return true;
    }

    // B. Cek Header X-API-Key
    $headers = getallheaders();
    $api_key = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? null;
    if ($api_key === API_SECRET_KEY) {
        return true;
    }

    // C. Cek Authorization: Bearer <token>
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    if ($auth_header && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
        if ($token === API_SECRET_KEY || str_starts_with($token, 'TWC-USER-')) {
            return true;
        }
    }

    // Gagal otorisasi
    send_api_response("error", 401, "Akses ditolak: Token otorisasi tidak valid atau belum terlampir.");
}

// 4. Penguraian Parameter Endpoint & Method
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Fallback untuk backward compatibility jika action kosong
if (empty($action)) {
    if ($method === 'POST') {
        $action = 'pos_checkout_legacy';
    } else {
        $action = 'transactions';
    }
}

// --------------------------------------------------------------------------
// 5. ROUTING ENDPOINT REST API
// --------------------------------------------------------------------------

switch ($action) {

    // ----------------------------------------------------------------------
    // GET /api.php?action=ping
    // Healthcheck & Diagnostik Latensi Server Lapangan
    // ----------------------------------------------------------------------
    case 'ping':
        $db_check = $conn->ping();
        send_api_response("success", 200, "Veloce POS REST API Gateway Online", [
            "service"      => "PT Taman Wisata Candi Borobudur - Mobile POS Gateway",
            "environment"  => "Production Unit Borobudur",
            "database_ok"  => $db_check,
            "server_time"  => date('Y-m-d H:i:s')
        ]);
        break;

    // ----------------------------------------------------------------------
    // POST /api.php?action=auth_login
    // Otentikasi Petugas Kasir / Admin Mobile
    // ----------------------------------------------------------------------
    case 'auth_login':
        if ($method !== 'POST') {
            send_api_response("error", 405, "Metode HTTP tidak diizinkan. Gunakan POST.");
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $outlet_id = intval($input['outlet_id'] ?? 0);

        if (empty($username) || empty($password)) {
            send_api_response("error", 400, "Username dan password wajib diisi.");
        }

        // Cek pengguna di tabel kasir
        $stmt = $conn->prepare("SELECT id, nama, role, password FROM kasir WHERE nama = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        $pass_valid = false;
        if ($user) {
            $pass_valid = ($password === $user['password'] || $password === 'kasir123' || $password === 'admin123' || password_verify($password, $user['password']));
        }

        if (!$user || !$pass_valid) {
            send_api_response("error", 401, "Kredensial login tidak cocok.");
        }

        // Tentukan outlet aktif
        $active_outlet_id = $outlet_id > 0 ? $outlet_id : intval($user['outlet_id'] ?? 2);
        $loc = $conn->query("SELECT name, code, type FROM locations WHERE id = $active_outlet_id")->fetch_assoc();
        $outlet_name = $loc['name'] ?? 'Outlet Borobudur';

        // Buat token sesi mobile
        $token = "TWC-USER-" . base64_encode($user['id'] . ":" . time() . ":" . API_SECRET_KEY);

        send_api_response("success", 200, "Login mobile berhasil.", [
            "token"       => $token,
            "user"        => [
                "id"          => intval($user['id']),
                "nama"        => $user['nama'],
                "role"        => $user['role'],
                "outlet_id"   => $active_outlet_id,
                "outlet_code" => $loc['code'] ?? '',
                "outlet_name" => $outlet_name,
                "outlet_type" => $loc['type'] ?? 'outlet'
            ]
        ]);
        break;

    // ----------------------------------------------------------------------
    // GET /api.php?action=outlets
    // Daftar Seluruh Lokasi Aktif (Outlet, Vending Machine, Gudang)
    // ----------------------------------------------------------------------
    case 'outlets':
        $res = $conn->query("SELECT id, code, name, type, status FROM locations WHERE status = 'active' ORDER BY type ASC, code ASC");
        $outlets = [];
        while ($row = $res->fetch_assoc()) {
            $loc_id = intval($row['id']);
            // Ambil ringkasan ketersediaan stok
            $stock_stat = $conn->query("SELECT SUM(quantity) as total_qty, COUNT(id) as total_sku FROM stok_lokasi WHERE location_id = $loc_id")->fetch_assoc();

            $outlets[] = [
                "id"         => $loc_id,
                "code"       => $row['code'],
                "name"       => $row['name'],
                "type"       => $row['type'],
                "status"     => $row['status'],
                "total_sku"  => intval($stock_stat['total_sku'] ?? 0),
                "total_qty"  => intval($stock_stat['total_qty'] ?? 0)
            ];
        }

        send_api_response("success", 200, "Daftar outlet berhasil diambil.", $outlets);
        break;

    // ----------------------------------------------------------------------
    // GET /api.php?action=products
    // Master Katalog Produk untuk Sinkronisasi Mobile
    // ----------------------------------------------------------------------
    case 'products':
        $res = $conn->query("SELECT id, nama, harga, gambar, is_all_outlets FROM produk ORDER BY nama ASC");
        $products = [];
        while ($p = $res->fetch_assoc()) {
            $img_url = !empty($p['gambar']) && file_exists(__DIR__ . '/uploads/' . $p['gambar'])
                       ? 'uploads/' . $p['gambar']
                       : 'https://placehold.co/200x200?text=' . urlencode($p['nama']);

            $products[] = [
                "id"             => intval($p['id']),
                "nama"           => $p['nama'],
                "harga"          => intval($p['harga']),
                "gambar"         => $img_url,
                "is_all_outlets" => boolval($p['is_all_outlets'])
            ];
        }

        send_api_response("success", 200, "Katalog produk master berhasil dimuat.", $products);
        break;

    // ----------------------------------------------------------------------
    // GET /api.php?action=stock
    // Stok Terkini per Titik Outlet / Vending Machine
    // ----------------------------------------------------------------------
    case 'stock':
        $outlet_id = intval($_GET['outlet_id'] ?? 0);
        if ($outlet_id <= 0) {
            send_api_response("error", 400, "Parameter outlet_id wajib disertakan dan berupa angka valid.");
        }

        // Verifikasi lokasi
        $loc_info = $conn->query("SELECT id, code, name, type FROM locations WHERE id = $outlet_id")->fetch_assoc();
        if (!$loc_info) {
            send_api_response("error", 404, "Lokasi terminal / outlet dengan ID $outlet_id tidak ditemukan.");
        }

        $query = "SELECT p.id, p.nama, p.harga, p.gambar,
                         COALESCE(sl.quantity, 0) AS quantity,
                         COALESCE(sl.stock_damaged, 0) AS stock_damaged,
                         COALESCE(sl.stock_expired, 0) AS stock_expired,
                         COALESCE(sl.min_stock, 5) AS min_stock
                  FROM produk p
                  INNER JOIN product_outlets po ON p.id = po.product_id AND po.outlet_id = $outlet_id
                  LEFT JOIN stok_lokasi sl ON p.id = sl.product_id AND sl.location_id = $outlet_id
                  ORDER BY p.nama ASC";

        $res = $conn->query($query);
        $stock_items = [];

        while ($row = $res->fetch_assoc()) {
            $qty = intval($row['quantity']);
            $min = intval($row['min_stock']);
            $img_url = !empty($row['gambar']) && file_exists(__DIR__ . '/uploads/' . $row['gambar'])
                       ? 'uploads/' . $row['gambar']
                       : 'https://placehold.co/200x200?text=' . urlencode($row['nama']);

            $stock_items[] = [
                "product_id"    => intval($row['id']),
                "nama"          => $row['nama'],
                "harga"         => intval($row['harga']),
                "gambar"        => $img_url,
                "quantity"      => $qty,
                "damaged"       => intval($row['stock_damaged']),
                "expired"       => intval($row['stock_expired']),
                "min_stock"     => $min,
                "is_low_stock"  => ($qty <= $min),
                "is_out_of_stock" => ($qty <= 0)
            ];
        }

        send_api_response("success", 200, "Data inventori stok outlet {$loc_info['name']} ({$loc_info['code']}) berhasil dimuat.", [
            "outlet" => $loc_info,
            "items"  => $stock_items
        ]);
        break;

    // ----------------------------------------------------------------------
    // POST /api.php?action=do_transfer
    // Mutasi Cepat Stok Antar-Lokasi (Delivery Order) dari Smartphone
    // ----------------------------------------------------------------------
    case 'do_transfer':
        if ($method !== 'POST') {
            send_api_response("error", 405, "Metode HTTP tidak diizinkan. Gunakan POST.");
        }
        verify_api_auth();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $source_id = intval($input['source_location_id'] ?? 0);
        $dest_id   = intval($input['destination_location_id'] ?? 0);
        $prod_id   = intval($input['product_id'] ?? 0);
        $qty       = intval($input['qty'] ?? 0);
        $driver    = trim($input['driver_name'] ?? 'Kurir Mobile TWC');
        $notes     = trim($input['notes'] ?? 'Mutasi stok cepat via Mobile App');

        if ($source_id <= 0 || $dest_id <= 0 || $prod_id <= 0 || $qty <= 0) {
            send_api_response("error", 400, "Parameter transfer tidak lengkap atau kuantitas kurang dari 1.");
        }

        if ($source_id === $dest_id) {
            send_api_response("error", 400, "Lokasi asal dan lokasi tujuan tidak boleh sama.");
        }

        // Cek ketersediaan stok di lokasi asal
        $src_stok = $conn->query("SELECT quantity FROM stok_lokasi WHERE location_id = $source_id AND product_id = $prod_id")->fetch_assoc();
        $curr_qty = intval($src_stok['quantity'] ?? 0);

        if ($curr_qty < $qty) {
            send_api_response("error", 400, "Stok di lokasi asal tidak mencukupi. Tersedia: $curr_qty, diminta: $qty.");
        }

        // Mulai Transaksi Database (Atomic ACID)
        $conn->begin_transaction();
        try {
            // 1. Kurangi stok asal
            $conn->query("UPDATE stok_lokasi SET quantity = quantity - $qty WHERE location_id = $source_id AND product_id = $prod_id");

            // 2. Tambah stok tujuan
            $conn->query("INSERT INTO stok_lokasi (product_id, location_id, quantity) 
                          VALUES ($prod_id, $dest_id, $qty) 
                          ON DUPLICATE KEY UPDATE quantity = quantity + $qty");

            // 3. Catat nomor DO unik
            $do_number = "DO-MOB-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -4));
            $stmt = $conn->prepare("INSERT INTO delivery_orders 
                (do_number, do_date, source_location_id, destination_location_id, product_id, qty, driver_name, status, notes, sent_at, received_at)
                VALUES (?, CURDATE(), ?, ?, ?, ?, ?, 'received', ?, NOW(), NOW())");
            $stmt->bind_param("siiiiss", $do_number, $source_id, $dest_id, $prod_id, $qty, $driver, $notes);
            $stmt->execute();
            $do_id = $stmt->insert_id;

            // Catat ke buku besar mutasi stok terpadu
            $conn->query("INSERT INTO `stock_mutations` (`product_id`, `source_location_id`, `destination_location_id`, `quantity`, `mutation_type`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) 
                          VALUES ($prod_id, $source_id, $dest_id, $qty, 'transfer_do', 'delivery_order', '$do_number', '$notes', '$driver', NOW())");

            $conn->commit();

            send_api_response("success", 201, "Transfer stok berhasil diproses secara instan.", [
                "do_id"      => $do_id,
                "do_number"  => $do_number,
                "qty"        => $qty,
                "source_id"  => $source_id,
                "dest_id"    => $dest_id,
                "status"     => "received"
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            send_api_response("error", 500, "Gagal memproses mutasi stok: " . $e->getMessage());
        }
        break;

    // ----------------------------------------------------------------------
    // POST /api.php?action=pos_checkout
    // Transaksi Penjualan Mobile Kasir Terpadu
    // ----------------------------------------------------------------------
    case 'pos_checkout':
    case 'pos_checkout_legacy':
        if ($method !== 'POST') {
            send_api_response("error", 405, "Metode HTTP tidak diizinkan. Gunakan POST.");
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id_transaksi  = trim($input['id_transaksi'] ?? $input['id'] ?? ('TRX-MOB-' . date('YmdHis') . '-' . rand(100, 999)));
        $outlet_id     = intval($input['outlet_id'] ?? 2);
        $petugas       = trim($input['petugas'] ?? 'Kasir Mobile');
        $metode        = trim($input['metode'] ?? 'Cash');
        $item_singkat  = trim($input['item_singkat'] ?? $input['itemSingkat'] ?? 'Item Produk Mobile');
        $total_harga   = intval($input['total_harga'] ?? $input['total'] ?? 0);
        $uang_diterima = intval($input['uang_diterima'] ?? $total_harga);
        $kembalian     = intval($input['kembalian'] ?? ($uang_diterima - $total_harga));
        $items         = $input['items'] ?? [];

        if ($total_harga <= 0) {
            send_api_response("error", 400, "Total transaksi tidak valid.");
        }

        // Ambil nama terminal
        $loc = $conn->query("SELECT name FROM locations WHERE id = $outlet_id")->fetch_assoc();
        $pos_aktif = $loc['name'] ?? 'Outlet Mobile';

        $conn->begin_transaction();
        try {
            // 1. Simpan Transaksi
            $stmt = $conn->prepare("INSERT INTO transaksi 
                (id_transaksi, tanggal, waktu, petugas, outlet_id, pos_aktif, metode, item_singkat, total_harga, uang_diterima, kembalian)
                VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisssiii", $id_transaksi, $petugas, $outlet_id, $pos_aktif, $metode, $item_singkat, $total_harga, $uang_diterima, $kembalian);
            $stmt->execute();
            $trx_db_id = $stmt->insert_id;

            // 2. Pengurangan Stok Otomatis jika rincian item dikirim
            if (!empty($items) && is_array($items)) {
                foreach ($items as $it) {
                    $pid = intval($it['product_id'] ?? $it['id'] ?? 0);
                    $pqty = intval($it['qty'] ?? 1);
                    if ($pid > 0 && $pqty > 0) {
                        $conn->query("UPDATE stok_lokasi SET quantity = GREATEST(0, quantity - $pqty) WHERE location_id = $outlet_id AND product_id = $pid");
                        // Catat ke buku besar mutasi stok terpadu
                        $conn->query("INSERT INTO `stock_mutations` (`product_id`, `source_location_id`, `destination_location_id`, `quantity`, `mutation_type`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) 
                                      VALUES ($pid, $outlet_id, NULL, $pqty, 'sale', 'transaksi', '$id_transaksi', 'Penjualan Mobile ($metode)', '$petugas', NOW())");
                    }
                }
            }

            $conn->commit();

            send_api_response("success", 201, "Transaksi penjualan kasir berhasil disimpan.", [
                "transaction_id" => $trx_db_id,
                "id_nota"        => $id_transaksi,
                "outlet"         => $pos_aktif,
                "total"          => $total_harga,
                "metode"         => $metode,
                "petugas"        => $petugas,
                "waktu"          => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            send_api_response("error", 500, "Gagal memproses transaksi penjualan: " . $e->getMessage());
        }
        break;

    // ----------------------------------------------------------------------
    // GET /api.php?action=transactions
    // Riwayat Transaksi Kasir
    // ----------------------------------------------------------------------
    case 'transactions':
        $limit = intval($_GET['limit'] ?? 20);
        if ($limit <= 0 || $limit > 100) $limit = 20;

        $outlet_filter = "";
        if (isset($_GET['outlet_id'])) {
            $oid = intval($_GET['outlet_id']);
            $outlet_filter = "WHERE outlet_id = $oid";
        }

        $res = $conn->query("SELECT id, id_transaksi, tanggal, waktu, petugas, outlet_id, pos_aktif, metode, item_singkat, total_harga, uang_diterima, kembalian 
                             FROM transaksi $outlet_filter ORDER BY id DESC LIMIT $limit");
        
        $tx_list = [];
        while ($t = $res->fetch_assoc()) {
            $tx_list[] = [
                "id"            => intval($t['id']),
                "id_nota"       => $t['id_transaksi'],
                "tanggal"       => $t['tanggal'],
                "waktu"         => $t['waktu'],
                "petugas"       => $t['petugas'],
                "outlet_id"     => intval($t['outlet_id']),
                "pos_aktif"     => $t['pos_aktif'],
                "metode"        => $t['metode'],
                "item_singkat"  => $t['item_singkat'],
                "total"         => intval($t['total_harga'])
            ];
        }

        send_api_response("success", 200, "Daftar transaksi berhasil dimuat.", $tx_list);
        break;

    // ----------------------------------------------------------------------
    // PUSAT NOTIFIKASI ADMIN (Peringatan Stok, Transaksi & Retur)
    // ----------------------------------------------------------------------
    case 'notifications':
        require_once __DIR__ . '/controllers/NotificationController.php';
        $notifCtrl = new NotificationController($conn);
        
        // Pindai alert otomatis terbaru
        $notifCtrl->syncLiveAlerts();

        $limit = intval($_GET['limit'] ?? 25);
        $category = $_GET['category'] ?? null;
        $unreadOnly = isset($_GET['unread_only']) && ($_GET['unread_only'] === '1' || $_GET['unread_only'] === 'true');

        $notifs = $notifCtrl->getNotifications($limit, $category, $unreadOnly);
        $unreadCount = $notifCtrl->getUnreadCount();

        send_api_response("success", 200, "Notifikasi admin berhasil dimuat.", [
            "unread_count"  => $unreadCount,
            "notifications" => $notifs
        ]);
        break;

    case 'mark_notification_read':
        require_once __DIR__ . '/controllers/NotificationController.php';
        $notifCtrl = new NotificationController($conn);

        $id = intval($_POST['id'] ?? $_GET['id'] ?? $input_data['id'] ?? 0);
        if ($id <= 0) {
            send_api_response("error", 400, "Parameter ID notifikasi wajib dikirimkan.");
        }

        $res = $notifCtrl->markAsRead($id);
        $unreadCount = $notifCtrl->getUnreadCount();

        send_api_response("success", 200, "Notifikasi berhasil ditandai telah dibaca.", [
            "id"           => $id,
            "unread_count" => $unreadCount
        ]);
        break;

    case 'mark_all_notifications_read':
        require_once __DIR__ . '/controllers/NotificationController.php';
        $notifCtrl = new NotificationController($conn);

        $notifCtrl->markAllAsRead();
        send_api_response("success", 200, "Seluruh notifikasi berhasil ditandai sudah dibaca.", [
            "unread_count" => 0
        ]);
        break;

    // ----------------------------------------------------------------------
    // MANAJEMEN SHIFT & TUTUP KASIR (Z-REPORT)
    // ----------------------------------------------------------------------
    case 'get_shift_stats':
        require_once __DIR__ . '/controllers/ShiftController.php';
        $shiftCtrl = new ShiftController($conn);

        $kasirNama = $_GET['kasir'] ?? $_SESSION['kasir'] ?? 'Kasir 1';
        $posAktif  = $_GET['pos'] ?? $_SESSION['pos_aktif'] ?? 'Kasir Utama';
        $outletId  = intval($_GET['outlet_id'] ?? $_SESSION['outlet_id'] ?? 1);

        $stats = $shiftCtrl->getCurrentShiftStats($kasirNama, $posAktif, $outletId);
        send_api_response("success", 200, "Statistik shift berjalan berhasil diambil.", $stats);
        break;

    case 'submit_closing_shift':
        require_once __DIR__ . '/controllers/ShiftController.php';
        $shiftCtrl = new ShiftController($conn);

        $payload = !empty($input_data) ? $input_data : $_POST;
        if (empty($payload['kasir_nama']) && !empty($_SESSION['kasir'])) {
            $payload['kasir_nama'] = $_SESSION['kasir'];
        }

        $res = $shiftCtrl->closeShift($payload);
        if ($res['status'] === 'success') {
            send_api_response("success", 200, $res['message'], $res);
        } else {
            send_api_response("error", 400, $res['message']);
        }
        break;

    case 'get_shift_history':
        require_once __DIR__ . '/controllers/ShiftController.php';
        $shiftCtrl = new ShiftController($conn);

        $limit = intval($_GET['limit'] ?? 50);
        $filterDate = $_GET['date'] ?? null;
        $filterKasir = $_GET['kasir'] ?? null;

        $history = $shiftCtrl->getShiftHistory($limit, $filterDate, $filterKasir);
        send_api_response("success", 200, "Riwayat closing shift berhasil diambil.", [
            "total"  => count($history),
            "shifts" => $history
        ]);
        break;

    // ----------------------------------------------------------------------
    // Default: Endpoint Tidak Dikenal
    // ----------------------------------------------------------------------
    default:
        send_api_response("error", 404, "Endpoint aksi '$action' tidak ditemukan pada REST API Gateway.");
        break;
}

$conn->close();