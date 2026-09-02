<?php
/**
 * Konfigurasi Database Terpusat - Veloce POS
 * Mendukung Lingkungan Lokal (XAMPP) & Cloud Deployment (Vercel, Railway, cPanel)
 */

// Membaca kredensial dari Environment Variables jika tersedia (Cloud Deployment), atau fallback ke default lokal XAMPP
$db_host = getenv('DB_HOST') ?: (isset($_SERVER['DB_HOST']) ? $_SERVER['DB_HOST'] : "localhost");
$db_user = getenv('DB_USER') ?: (isset($_SERVER['DB_USER']) ? $_SERVER['DB_USER'] : "root");
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (isset($_SERVER['DB_PASS']) ? $_SERVER['DB_PASS'] : ""));
$db_name = getenv('DB_NAME') ?: (isset($_SERVER['DB_NAME']) ? $_SERVER['DB_NAME'] : "veloce_pos");
$db_port = intval(getenv('DB_PORT') ?: (isset($_SERVER['DB_PORT']) ? $_SERVER['DB_PORT'] : 3306));

// Inisialisasi koneksi MySQLi dengan port pendukung
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Validasi koneksi
if ($conn->connect_error) {
    if (php_sapi_name() !== 'cli' && (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Koneksi database gagal: " . $conn->connect_error . " (Host: $db_host:$db_port)"
        ]);
        exit();
    }

    // Tampilan ramah pengguna jika di-deploy ke Cloud (misal Vercel / Railway) tanpa konfigurasi database cloud
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup Database Cloud — Veloce POS</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
    </head>
    <body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
        <div class="max-w-lg w-full bg-slate-900 border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-2xl font-bold">
                    ⚠️
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white leading-tight">Konfigurasi Database Cloud Diperlukan</h1>
                    <p class="text-xs text-slate-400">Veloce POS — PT Taman Wisata Borobudur</p>
                </div>
            </div>

            <div class="bg-slate-950/80 border border-white/5 rounded-2xl p-4 text-xs space-y-2">
                <p class="text-slate-300 font-semibold">Penyebab Pesan Ini:</p>
                <p class="text-slate-400 leading-relaxed">
                    Aplikasi berhasil di-deploy ke cloud (Vercel), namun server cloud tidak dapat menghubungi database lokal XAMPP Anda (<code class="text-amber-300"><?= htmlspecialchars($db_host) ?></code>).
                </p>
                <div class="p-2.5 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-300 font-mono text-[11px]">
                    Error: <?= htmlspecialchars($conn->connect_error) ?>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <h2 class="font-bold text-white flex items-center gap-1.5">
                    <span>🛠️</span> <span>Solusi Menghubungkan Database:</span>
                </h2>
                <ol class="list-decimal list-inside space-y-2 text-slate-300 leading-relaxed">
                    <li>Gunakan layanan database MySQL Cloud gratis seperti <b class="text-blue-400">Aiven</b>, <b class="text-blue-400">Railway</b>, atau <b class="text-blue-400">TiDB Cloud</b>.</li>
                    <li>Import file SQL skema <code class="bg-slate-800 px-1.5 py-0.5 rounded text-slate-200">database/veloce_pos_latest.sql</code> ke database cloud Anda.</li>
                    <li>Masukkan kredensial ke menu <b>Vercel Project Settings ➔ Environment Variables</b>:
                        <ul class="list-disc list-inside mt-1.5 space-y-1 ml-4 text-[11px] font-mono text-emerald-300">
                            <li>DB_HOST (nama host server cloud)</li>
                            <li>DB_USER (nama pengguna database)</li>
                            <li>DB_PASS (kata sandi database)</li>
                            <li>DB_NAME (nama database)</li>
                            <li>DB_PORT (port MySQL, default 3306)</li>
                        </ul>
                    </li>
                </ol>
            </div>

            <div class="pt-2 flex justify-between items-center text-[11px] text-slate-500 border-t border-white/10">
                <span>Veloce POS Engine v2.0</span>
                <a href="https://vercel.com" target="_blank" class="text-blue-400 hover:underline">Vercel Deployment Guide ➔</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Set zona waktu terpusat ke Waktu Indonesia Barat (WIB / UTC+7)
date_default_timezone_set('Asia/Jakarta');

// Set karakter encoding ke utf8mb4 dan zona waktu MySQL
$conn->set_charset("utf8mb4");
@$conn->query("SET time_zone = '+07:00'");


