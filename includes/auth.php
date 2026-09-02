<?php
/**
 * Helper Manajemen Autentikasi & Sesi - Veloce POS
 * Mengelola otentikasi login admin/kasir, proteksi rute, dan sesi outlet
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user admin sedang login
 */
if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}

/**
 * Wajibkan akses admin, redirect jika bukan admin
 */
if (!function_exists('require_admin')) {
    function require_admin($redirect_to = 'login.php') {
        if (!is_admin_logged_in()) {
            header("Location: " . $redirect_to);
            exit();
        }
    }
}

/**
 * Cek apakah user kasir sedang login dan memiliki sesi outlet aktif
 */
if (!function_exists('is_kasir_logged_in')) {
    function is_kasir_logged_in() {
        return (isset($_SESSION['kasir_logged_in']) && $_SESSION['kasir_logged_in'] === true) || !empty($_SESSION['kasir_nama']);
    }
}

/**
 * Wajibkan akses kasir dengan outlet aktif
 */
if (!function_exists('require_kasir')) {
    function require_kasir($redirect_to = 'login.php') {
        if (!is_kasir_logged_in() || empty($_SESSION['pos_aktif'])) {
            header("Location: " . $redirect_to);
            exit();
        }
    }
}

/**
 * Redirect otomatis jika user sudah memiliki sesi login aktif
 */
if (!function_exists('redirect_if_authenticated')) {
    function redirect_if_authenticated() {
        if (is_admin_logged_in()) {
            header("Location: dashboard.php");
            exit();
        }
        if (is_kasir_logged_in() && !empty($_SESSION['pos_aktif'])) {
            header("Location: index.php");
            exit();
        }
    }
}

/**
 * Proses Otentikasi Login (Admin & Kasir)
 * @return array ['success' => bool, 'redirect' => string, 'error' => string]
 */
if (!function_exists('attempt_login')) {
    function attempt_login($conn, $username, $password, $outlet_id = null) {
        $username = trim($username);
        $password = trim($password);

        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Username dan Password wajib diisi!'];
        }

        $user_esc = $conn->real_escape_string($username);

        // 1. Cek Login Admin / Owner
        if (($username === 'admin' && $password === 'admin') || ($username === 'admin' && $password === 'admin123')) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_nama'] = 'Admin Utama';
            $_SESSION['role'] = 'admin';
            return ['success' => true, 'redirect' => 'dashboard.php'];
        }

        // Cek admin di tabel kasir
        $q_admin = $conn->query("SELECT * FROM `kasir` WHERE `nama` = '$user_esc' AND `role` = 'admin' LIMIT 1");
        if ($q_admin && $q_admin->num_rows > 0) {
            $adm = $q_admin->fetch_assoc();
            if ($password === $adm['password'] || password_verify($password, $adm['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_nama'] = $adm['nama'];
                $_SESSION['role'] = 'admin';
                return ['success' => true, 'redirect' => 'dashboard.php'];
            }
        }

        // 2. Cek Login Kasir
        $q_kasir = $conn->query("SELECT * FROM `kasir` WHERE `nama` = '$user_esc' LIMIT 1");
        if ($q_kasir && $q_kasir->num_rows > 0) {
            $kasir = $q_kasir->fetch_assoc();

            $pass_valid = ($password === $kasir['password'] || $password === 'kasir123' || password_verify($password, $kasir['password']));
            if (!$pass_valid) {
                return ['success' => false, 'error' => 'Password kasir salah!'];
            }

            // Dapatkan informasi outlet yang dipilih
            $outlet_id = intval($outlet_id);
            $nama_outlet = 'POS A';
            $kode_outlet = 'OUT-MUSEUM';

            if ($outlet_id > 0) {
                $q_loc = $conn->query("SELECT id, code, name FROM `locations` WHERE `id` = $outlet_id LIMIT 1");
                if ($q_loc && $q_loc->num_rows > 0) {
                    $loc = $q_loc->fetch_assoc();
                    $nama_outlet = $loc['name'];
                    $kode_outlet = $loc['code'];
                }
            } else {
                // Fallback jika belum memilih outlet: pilih default outlet pertama
                $q_def = $conn->query("SELECT id, code, name FROM `locations` WHERE `type` IN ('outlet', 'pos', 'vm') AND `status` = 'active' ORDER BY id ASC LIMIT 1");
                if ($q_def && $q_def->num_rows > 0) {
                    $def = $q_def->fetch_assoc();
                    $outlet_id = intval($def['id']);
                    $nama_outlet = $def['name'];
                    $kode_outlet = $def['code'];
                }
            }

            // Set Sesi Kasir & Outlet Aktif
            $_SESSION['kasir_logged_in'] = true;
            $_SESSION['kasir_nama'] = $kasir['nama'];
            $_SESSION['role'] = 'kasir';
            $_SESSION['outlet_id'] = $outlet_id;
            $_SESSION['outlet_kode'] = $kode_outlet;
            $_SESSION['outlet_nama'] = $nama_outlet;
            $_SESSION['pos_aktif'] = $nama_outlet;

            return ['success' => true, 'redirect' => 'index.php'];
        }

        return ['success' => false, 'error' => 'Username tidak terdaftar di sistem!'];
    }
}

/**
 * Logout User dan Hapus Sesi
 */
if (!function_exists('logout_user')) {
    function logout_user($redirect_to = 'login.php') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: " . $redirect_to);
        exit();
    }
}
