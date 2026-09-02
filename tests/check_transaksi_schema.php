<?php
require_once __DIR__ . '/../config/database.php';
$res = $conn->query("DESCRIBE transaksi");
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . " | " . $r['Type'] . "\n";
}
