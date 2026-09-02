<?php
/**
 * Konfigurasi Database Terpusat - Veloce POS
 * Single Source of Truth untuk koneksi MySQL/MariaDB
 */

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "veloce_pos";

// Inisialisasi koneksi MySQLi
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Validasi koneksi
if ($conn->connect_error) {
    if (php_sapi_name() !== 'cli' && (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Koneksi database gagal: " . $conn->connect_error]);
        exit();
    }
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

// Set karakter encoding ke utf8mb4
$conn->set_charset("utf8mb4");
