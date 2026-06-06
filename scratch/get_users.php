<?php
require_once __DIR__ . '/../backend/config/database.php';
$stmt = $conn->query("SELECT id, nama, email, no_hp, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
