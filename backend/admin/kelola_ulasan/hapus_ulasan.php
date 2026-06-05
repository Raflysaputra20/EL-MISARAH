<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    try {
        // Get photo filename before deleting
        $stmt = $conn->prepare("SELECT foto_ulasan FROM ulasan WHERE id = ?");
        $stmt->execute([$id]);
        $foto = $stmt->fetchColumn();

        // Delete the review
        $del = $conn->prepare("DELETE FROM ulasan WHERE id = ?");
        $del->execute([$id]);

        // Clean up photo file if exists
        if ($foto) {
            $fotoPath = __DIR__ . '/../../../uploads/ulasan/' . $foto;
            if (file_exists($fotoPath)) {
                @unlink($fotoPath);
            }
        }

        header("Location: list_ulasan.php?success=" . urlencode("Ulasan berhasil dihapus."));
        exit;
    } catch (Exception $e) {
        header("Location: list_ulasan.php?error=" . urlencode("Gagal menghapus ulasan: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: list_ulasan.php");
    exit;
}
