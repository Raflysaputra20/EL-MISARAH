<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penghuni") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId    = $_SESSION["user_id"];
$bookingId = $_POST['booking_id'] ?? null;
$bulan     = intval($_POST['bulan'] ?? 1);
$jumlah    = floatval($_POST['jumlah'] ?? 0);

if (!$bookingId || $jumlah <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

// Proses Upload Foto (Bukti Bayar)
$buktiNama = null;
if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === 0) {
    $ext = pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION);
    $buktiNama = "perpanjang_" . time() . "_" . $userId . "." . $ext;
    $target = __DIR__ . "/../../../frontend/assets/image/bukti/" . $buktiNama;
    
    // Pastikan folder ada
    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0777, true);
    }
    
    move_uploaded_file($_FILES['bukti']['tmp_name'], $target);
}

try {
    // Simpan ke tabel pembayaran dengan catatan durasi di kolom metode/catatan
    // Kita gunakan kolom 'metode' untuk menandai ini adalah 'Perpanjangan'
    // Simpan ke tabel pembayaran dengan catatan durasi
    $stmt = $conn->prepare("
        INSERT INTO pembayaran (booking_id, jumlah, status, tanggal_bayar, bukti_bayar, metode, durasi_bulan)
        VALUES (?, ?, 'menunggu_verifikasi', NOW(), ?, ?, ?)
    ");
    $metodeKet = "Perpanjangan";
    $stmt->execute([$bookingId, $jumlah, $buktiNama, $metodeKet, $bulan]);

    echo json_encode([
        'success' => true, 
        'message' => 'Pengajuan perpanjangan berhasil disimpan. Silakan konfirmasi via WhatsApp.',
        'wa_msg' => "Halo Admin, saya ingin konfirmasi PERPANJANGAN SEWA selama $bulan Bulan. Bukti bayar sudah saya upload di sistem."
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
