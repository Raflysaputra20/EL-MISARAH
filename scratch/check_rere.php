<?php
require_once __DIR__ . '/../backend/config/database.php';
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['rere@gmail.com']);
print_r($stmt->fetch(PDO::FETCH_ASSOC));
