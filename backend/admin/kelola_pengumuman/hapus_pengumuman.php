<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

if (isset($_GET["id"])) {
    $id = $_GET["id"];
    
    try {
        $stmt = $conn->prepare("DELETE FROM informasi WHERE id = :id");
        $stmt->execute([":id" => $id]);
        header("Location: list_pengumuman.php?success=deleted");
    } catch (PDOException $e) {
        header("Location: list_pengumuman.php?error=db_error");
    }
} else {
    header("Location: list_pengumuman.php");
}
exit;
