<?php
require_once __DIR__ . '/../config/database.php';
$res = $conn->query("DESCRIBE kasir");
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . " | " . $r['Type'] . "\n";
}
echo "\n--- DATA KASIR ---\n";
$data = $conn->query("SELECT * FROM kasir");
while ($row = $data->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Nama: " . $row['nama'] . " | Role: " . ($row['role'] ?? 'none') . "\n";
}
