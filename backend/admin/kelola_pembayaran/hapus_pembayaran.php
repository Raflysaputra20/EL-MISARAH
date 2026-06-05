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
        // Ambil info file bukti bayar untuk dihapus
        $stmtGet = $conn->prepare("SELECT bukti_bayar FROM pembayaran WHERE id = ?");
        $stmtGet->execute([$id]);
        $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['bukti_bayar'])) {
            $filePath = __DIR__ . "/../../../frontend/assets/image/bukti/" . $row['bukti_bayar'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Hapus record
        $stmtDelete = $conn->prepare("DELETE FROM pembayaran WHERE id = ?");
        $stmtDelete->execute([$id]);

        $userIdParam = $_GET['user_id'] ?? null;
        if ($userIdParam) {
            header("Location: list_pembayaran.php?user_id=" . $userIdParam . "&success=Pembayaran+berhasil+dihapus");
        } else {
            header("Location: list_pembayaran.php?success=Pembayaran+berhasil+dihapus");
        }
        exit;
    } catch (PDOException $e) {
        $userIdParam = $_GET['user_id'] ?? null;
        if ($userIdParam) {
            header("Location: list_pembayaran.php?user_id=" . $userIdParam . "&error=" . urlencode($e->getMessage()));
        } else {
            header("Location: list_pembayaran.php?error=" . urlencode($e->getMessage()));
        }
        exit;
    }
} else {
    $userIdParam = $_GET['user_id'] ?? null;
    if ($userIdParam) {
        header("Location: list_pembayaran.php?user_id=" . $userIdParam);
    } else {
        header("Location: list_pembayaran.php");
    }
    exit;
}
