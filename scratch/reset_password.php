<?php
require_once __DIR__ . '/../backend/config/database.php';

try {
    $password = password_hash('rere123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$password, 'rere@gmail.com']);
    echo "✅ Password for rere@gmail.com successfully updated to 'rere123'!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
