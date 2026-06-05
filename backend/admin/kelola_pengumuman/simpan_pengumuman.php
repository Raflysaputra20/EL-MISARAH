<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = isset($_POST["id"]) ? trim($_POST["id"]) : "";
    $judul = isset($_POST["judul"]) ? trim($_POST["judul"]) : "";
    $isi = isset($_POST["isi"]) ? trim($_POST["isi"]) : "";
    $tanggal = isset($_POST["tanggal"]) && !empty($_POST["tanggal"]) ? trim($_POST["tanggal"]) : date('Y-m-d');
    $pinned = isset($_POST["pinned"]) ? 1 : 0;

    if (empty($judul) || empty($isi)) {
        header("Location: list_pengumuman.php?error=empty_fields");
        exit;
    }

    try {
        if (!empty($id)) {
            // Update existing in 'informasi' table
            $stmt = $conn->prepare("UPDATE informasi SET judul = :judul, isi = :isi WHERE id = :id");
            $stmt->execute([
                ":judul" => $judul,
                ":isi" => $isi,
                ":id" => $id
            ]);
        } else {
            // Create new in 'informasi' table
            $stmt = $conn->prepare("INSERT INTO informasi (judul, isi, created_at) VALUES (:judul, :isi, NOW())");
            $stmt->execute([
                ":judul" => $judul,
                ":isi" => $isi
            ]);
        }
        header("Location: list_pengumuman.php?success=1");
    } catch (PDOException $e) {
        header("Location: list_pengumuman.php?error=db_error");
    }
    exit;
} else {
    header("Location: list_pengumuman.php");
    exit;
}
