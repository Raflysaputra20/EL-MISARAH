<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id = $_GET["id"] ?? null;

if (!$id) {
    header("Location: list_penghuni.php");
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Ambil data booking terakhir untuk membebaskan kamarnya
    $stmtB = $conn->prepare("SELECT id, kamar_id FROM booking WHERE user_id = ? AND status IN ('aktif', 'disetujui') ORDER BY id DESC LIMIT 1");
    $stmtB->execute([$id]);
    $booking = $stmtB->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        // 2. Bebaskan kamar
        if (!empty($booking['kamar_id'])) {
            $stmtK = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id = ?");
            $stmtK->execute([$booking['kamar_id']]);
        }
        
        // 3. Tandai booking ini sebagai 'selesai'
        $stmtUpdBook = $conn->prepare("UPDATE booking SET status = 'selesai' WHERE user_id = ? AND status != 'dibatalkan'");
        $stmtUpdBook->execute([$id]);
    }

    // 4. Ubah role user kembali ke 'user', tapi akun tetap aktif (tidak dihapus)
    $stmtU = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
    $stmtU->execute([$id]);

    $conn->commit();
    header("Location: list_penghuni.php?success=checkout");
    exit;
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    die("Gagal memproses checkout (Error): " . $e->getMessage());
}
