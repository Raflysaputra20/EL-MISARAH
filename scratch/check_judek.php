<?php
require_once __DIR__ . '/../backend/config/database.php';
$s = $conn->query("SELECT id, nama, foto FROM users WHERE nama LIKE '%judek%'");
print_r($s->fetchAll(PDO::FETCH_ASSOC));
