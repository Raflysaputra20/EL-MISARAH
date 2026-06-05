<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipe = trim($_POST["tipe"] ?? "");
    $harga = trim($_POST["harga"] ?? "");
    $fasilitas = trim($_POST["fasilitas"] ?? "");
    $deskripsi = trim($_POST["deskripsi"] ?? "");
    $nomor_kamar = trim($_POST["nomor_kamar"] ?? "");

    if ($tipe !== "" && $harga !== "" && $nomor_kamar !== "") {
        try {
            $stmt = $conn->prepare("INSERT INTO kamar (nomor_kamar, tipe, harga, fasilitas, deskripsi) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nomor_kamar, $tipe, $harga, $fasilitas, $deskripsi]);
            header("Location: list_kamar.php?success=tambah");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $_SESSION['error_msg'] = "Gagal menambah tipe kamar: Nomor kamar '$nomor_kamar' sudah digunakan oleh tipe lain!";
                header("Location: list_kamar.php");
            } else {
                $_SESSION['error_msg'] = "Terjadi kesalahan database.";
                header("Location: list_kamar.php");
            }
        }
        exit;
    }
}

header("Location: list_kamar.php");
exit;
