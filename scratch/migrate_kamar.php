<?php
require_once __DIR__ . "/../backend/config/database.php";

try {
    $columns = [
        "deskripsi" => "TEXT NULL AFTER fasilitas",
        "foto_2" => "VARCHAR(255) NULL AFTER foto",
        "foto_3" => "VARCHAR(255) NULL AFTER foto_2",
        "foto_4" => "VARCHAR(255) NULL AFTER foto_3",
        "foto_5" => "VARCHAR(255) NULL AFTER foto_4",
        "foto_denah" => "VARCHAR(255) NULL AFTER foto_5"
    ];

    foreach ($columns as $col => $def) {
        // Check if column exists
        $check = $conn->query("SHOW COLUMNS FROM kamar LIKE '$col'");
        if ($check->rowCount() == 0) {
            $conn->exec("ALTER TABLE kamar ADD $col $def");
            echo "Column '$col' added successfully.\n";
        } else {
            echo "Column '$col' already exists.\n";
        }
    }
    echo "Migration completed.\n";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
