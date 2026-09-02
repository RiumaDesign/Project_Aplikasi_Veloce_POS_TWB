<?php
/**
 * Helper Fungsi Umum - Veloce POS
 */

if (!function_exists('format_rupiah')) {
    function format_rupiah($angka) {
        return 'Rp ' . number_format(floatval($angka), 0, ',', '.');
    }
}

if (!function_exists('clean_input')) {
    function clean_input($conn, $data) {
        if ($data === null) return '';
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $conn ? $conn->real_escape_string($data) : $data;
    }
}

if (!function_exists('json_response')) {
    function json_response($status_code, $payload) {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload);
        exit();
    }
}
