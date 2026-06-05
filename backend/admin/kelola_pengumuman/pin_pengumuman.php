<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

// Auto-add pinned column if not exists
try {
    $conn->exec("ALTER TABLE informasi ADD COLUMN IF NOT EXISTS pinned TINYINT(1) DEFAULT 0");
} catch (Exception $e) {
    // Column might already exist
}

if (isset($_GET["id"]) && isset($_GET["pin"])) {
    $id = $_GET["id"];
    $pin = $_GET["pin"] ? 1 : 0;
    
    try {
        $stmt = $conn->prepare("UPDATE informasi SET pinned = :pin WHERE id = :id");
        $stmt->execute([
            ":pin" => $pin,
            ":id" => $id
        ]);
        header("Location: list_pengumuman.php?success=pinned");
    } catch (PDOException $e) {
        header("Location: list_pengumuman.php?error=db_error");
    }
} else {
    header("Location: list_pengumuman.php");
}
exit;

