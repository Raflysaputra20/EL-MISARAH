<?php
require __DIR__ . '/../backend/config/database.php';

echo "<pre>";
echo "=== BOOKING TABLE COLUMNS ===\n";
$stmt = $conn->query("SHOW COLUMNS FROM booking");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . " | " . $c['Type'] . " | " . ($c['Default'] ?? 'NULL') . "\n";
}

echo "\n=== PEMBAYARAN TABLE COLUMNS ===\n";
$stmt2 = $conn->query("SHOW COLUMNS FROM pembayaran");
$cols2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols2 as $c) {
    echo $c['Field'] . " | " . $c['Type'] . " | " . ($c['Default'] ?? 'NULL') . "\n";
}

// Check if alasan_penolakan exists
$hasAlasan = false;
foreach ($cols as $c) {
    if ($c['Field'] === 'alasan_penolakan') $hasAlasan = true;
}
echo "\n=== alasan_penolakan exists: " . ($hasAlasan ? "YES" : "NO") . " ===\n";

// Try to add it if missing
if (!$hasAlasan) {
    try {
        $conn->exec("ALTER TABLE booking ADD COLUMN alasan_penolakan TEXT DEFAULT NULL");
        echo "✅ Column alasan_penolakan ADDED successfully!\n";
    } catch (Exception $e) {
        echo "⚠️ Error adding: " . $e->getMessage() . "\n";
    }
}

echo "</pre>";
