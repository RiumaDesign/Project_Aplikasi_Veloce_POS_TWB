<?php
require_once __DIR__ . '/../config/database.php';
echo "PHP Time: " . date('Y-m-d H:i:s T') . "\n";
$res = $conn->query("SELECT NOW()")->fetch_row()[0];
echo "MySQL Time: " . $res . "\n";
