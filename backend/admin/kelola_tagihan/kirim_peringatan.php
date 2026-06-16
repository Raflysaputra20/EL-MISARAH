<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(['success'=>false,'message'=>'Akses ditolak']); exit;
}

$bookingId = intval($_POST['booking_id'] ?? 0);
$pesan     = trim($_POST['pesan'] ?? '');
$userId    = intval($_POST['user_id'] ?? 0);

if (!$bookingId || !$pesan) {
    echo json_encode(['success'=>false,'message'=>'Data tidak lengkap']); exit;
}

try {
    // Dapatkan user_id & nama dari booking
    $stmtChk = $conn->prepare("SELECT b.user_id, u.nama FROM booking b JOIN users u ON b.user_id=u.id WHERE b.id=?");
    $stmtChk->execute([$bookingId]);
    $booking = $stmtChk->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success'=>false,'message'=>'Booking tidak ditemukan']); exit;
    }

    $targetUserId = $userId ?: $booking['user_id'];
    $namaUser     = $booking['nama'];

    // Simpan langsung ke tabel notifikasi (personal, bukan pengumuman umum)
    $stmtNotif = $conn->prepare("
        INSERT INTO notifikasi (user_id, judul, isi, tipe, is_read, created_at)
        VALUES (?, ?, ?, 'warning', 0, NOW())
    ");
    $judulNotif = "Peringatan Tagihan";
    $stmtNotif->execute([$targetUserId, $judulNotif, $pesan]);

    echo json_encode([
        'success' => true,
        'message' => "Peringatan berhasil dikirim ke {$namaUser}"
    ]);

} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
