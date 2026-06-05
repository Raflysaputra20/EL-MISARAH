<?php
require_once __DIR__ . '/../backend/config/database.php';
$r = $conn->query("SELECT id, nama, email, role, status FROM users");
foreach($r as $row) {
    echo "ID:{$row['id']} Nama:{$row['nama']} Email:{$row['email']} Role:{$row['role']} Status:{$row['status']}\n";
}
