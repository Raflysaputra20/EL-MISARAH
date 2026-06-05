<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penghuni') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak valid']);
    exit;
}

$pembayaranId = intval($_POST['pembayaran_id'] ?? 0);

// Validasi kepemilikan pembayaran (via user_id langsung ATAU via booking)
try {
    $stmtCheck = $conn->prepare("
        SELECT id, status
        FROM pembayaran
        WHERE id = ? AND user_id = ?
    ");
    $stmtCheck->execute([$pembayaranId, $userId]);
    $pay = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$pay) {
        echo json_encode(['success' => false, 'message' => 'Data pembayaran tidak ditemukan']);
        exit;
    }

    if ($pay['status'] === 'valid') {
        echo json_encode(['success' => false, 'message' => 'Pembayaran sudah diverifikasi']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
    exit;
}

// Validasi file
if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File tidak valid atau tidak ditemukan']);
    exit;
}

$file     = $_FILES['bukti'];
$maxSize  = 5 * 1024 * 1024; // 5MB
$allowed  = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'];
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB']);
    exit;
}

if (!in_array($mimeType, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Format file harus JPG, PNG, WEBP, atau PDF']);
    exit;
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'bukti_' . $userId . '_' . $pembayaranId . '_' . time() . '.' . $ext;
$uploadDir = __DIR__ . '/../../../frontend/assets/image/bukti/';
$destPath  = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file']);
    exit;
}

// Update pembayaran
try {
    $stmtUpdate = $conn->prepare("
        UPDATE pembayaran
        SET bukti_bayar = ?, status = 'menunggu_verifikasi', tanggal_bayar = CURDATE()
        WHERE id = ?
    ");
    $stmtUpdate->execute([$filename, $pembayaranId]);

    echo json_encode(['success' => true, 'message' => 'Bukti pembayaran berhasil diupload', 'filename' => $filename]);
} catch (Exception $e) {
    // Hapus file jika DB gagal
    @unlink($destPath);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database']);
}
