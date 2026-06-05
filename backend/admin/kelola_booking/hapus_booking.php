<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id = $_GET["id"] ?? null;
if ($id) {
    // get booking to free the room if needed
    $stmt = $conn->prepare("SELECT * FROM booking WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        $status = strtolower($booking['status']);
        // Jika statusnya belum selesai/aktif dan ada kamar yg di-assign, bebaskan kamar
        if (!empty($booking['kamar_id']) && in_array($status, ['pending', 'menunggu_dp', 'disetujui', 'dijadwalkan', 'dibooking'])) {
             $updateKamar = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id = ?");
             $updateKamar->execute([$booking['kamar_id']]);
        }
        
        $del = $conn->prepare("DELETE FROM booking WHERE id = ?");
        $del->execute([$id]);
    }
}
header("Location: list_booking.php?success=hapus");
exit;
