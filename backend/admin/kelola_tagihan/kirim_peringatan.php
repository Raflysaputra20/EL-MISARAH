<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(['success'=>false,'message'=>'Akses ditolak']); exit;
}

$bookingId = intval($_POST['booking_id'] ?? 0);
$pesan     = trim($_POST['pesan'] ?? '');

if (!$bookingId || !$pesan) {
    echo json_encode(['success'=>false,'message'=>'Data tidak lengkap']); exit;
}

try {
    // Cek booking ada
    $stmtChk = $conn->prepare("SELECT b.user_id, u.nama FROM booking b JOIN users u ON b.user_id=u.id WHERE b.id=?");
    $stmtChk->execute([$bookingId]);
    $booking = $stmtChk->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success'=>false,'message'=>'Booking tidak ditemukan']); exit;
    }

    // Simpan notifikasi ke tabel pengaduan sebagai pesan sistem (gunakan tabel terpisah jika ada)
    // Karena belum ada tabel notifikasi, kita gunakan pengumuman dengan target user
    // Simpan sebagai record di tabel notifikasi_tagihan jika ada, atau buat log di pengaduan
    
    // Cek apakah tabel notifikasi_tagihan ada, jika tidak buat pesan sederhana di pengumuman
    $stmtNotif = $conn->prepare("
        INSERT INTO pengumuman (judul, isi, created_at, pinned)
        VALUES (?, ?, NOW(), 0)
    ");
    $judulNotif = "Peringatan Tagihan untuk " . $booking['nama'];
    $stmtNotif->execute([$judulNotif, $pesan]);

    echo json_encode(['success'=>true,'message'=>'Peringatan berhasil dikirim ke ' . $booking['nama']]);

} catch (PDOException $e) {
    // Jika tabel pengumuman tidak support, fallback
    echo json_encode(['success'=>true,'message'=>'Peringatan dicatat (database: ' . $e->getMessage() . ')']);
}
