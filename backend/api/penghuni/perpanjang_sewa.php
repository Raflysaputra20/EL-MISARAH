<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . "/../../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penghuni") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$bookingId = $_POST['booking_id'] ?? null;
$tambahBulan = isset($_POST['bulan']) ? (int)$_POST['bulan'] : 1;
$userId    = $_SESSION['user_id'];

if (!$bookingId) {
    echo json_encode(['success' => false, 'message' => 'ID Booking tidak ditemukan']);
    exit;
}

try {
    // 1. Pastikan booking ini memang milik user yang sedang login
    $stmtCheck = $conn->prepare("SELECT id, durasi_bulan FROM booking WHERE id = ? AND user_id = ?");
    $stmtCheck->execute([$bookingId, $userId]);
    $booking = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Data booking tidak valid']);
        exit;
    }

    // 2. Tambah durasi sewa sesuai input
    $newDuration = (int)$booking['durasi_bulan'] + $tambahBulan;
    $stmtUpdate = $conn->prepare("UPDATE booking SET durasi_bulan = ? WHERE id = ?");
    $stmtUpdate->execute([$newDuration, $bookingId]);

    echo json_encode(['success' => true, 'message' => "Sewa berhasil diperpanjang $tambahBulan bulan"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
