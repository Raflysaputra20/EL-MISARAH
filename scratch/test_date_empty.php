<?php
require_once __DIR__ . '/../backend/config/database.php';

try {
    $stmt = $conn->prepare("UPDATE users SET tanggal_lahir = ? WHERE id = 37");
    $stmt->execute(['']);
    echo "✅ Success! Date empty string was accepted.\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}
