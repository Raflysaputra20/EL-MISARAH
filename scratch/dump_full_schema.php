<?php
require __DIR__ . '/../backend/config/database.php';

try {
    // 1. Get all tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $output = "DATABASE SCHEMA DUMP\n";
    $output .= "====================\n\n";
    
    foreach ($tables as $table) {
        $output .= "--- TABLE: $table ---\n";
        $stmtCreate = $conn->query("SHOW CREATE TABLE `$table`");
        $createRow = $stmtCreate->fetch(PDO::FETCH_ASSOC);
        $output .= $createRow['Create Table'] . ";\n\n";
    }
    
    file_put_contents(__DIR__ . '/full_schema.txt', $output);
    echo "✅ Success! Full schema dumped to scratch/full_schema.txt\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
