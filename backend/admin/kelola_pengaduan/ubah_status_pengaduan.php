<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

// Auto-add bukti columns if not exist
try {
    $conn->exec("ALTER TABLE pengaduan ADD COLUMN IF NOT EXISTS foto_proses VARCHAR(255) DEFAULT NULL");
    $conn->exec("ALTER TABLE pengaduan ADD COLUMN IF NOT EXISTS foto_selesai VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {}

// Allow GET for simple status updates without photo, or POST with photo
$id = $_POST["id"] ?? $_GET["id"] ?? null;
$status = $_POST["status"] ?? $_GET["status"] ?? null;

$allowedStatus = ["baru", "masuk", "diproses", "selesai"];

if (!$id || !in_array($status, $allowedStatus)) {
    header("Location: list_pengaduan.php");
    exit;
}

$foto_bukti = null;

// Handle file upload if present
if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = dirname(__DIR__, 3) . "/uploads/pengaduan/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['foto_bukti']['name']));
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['foto_bukti']['tmp_name'], $targetPath)) {
        $foto_bukti = $fileName;
    }
}

// Update status + simpan bukti ke kolom yang sesuai per tahap
try {
    if ($status === 'diproses' && $foto_bukti) {
        $stmt = $conn->prepare("UPDATE pengaduan SET status = ?, foto_proses = ? WHERE id = ?");
        $stmt->execute([$status, $foto_bukti, $id]);
    } elseif ($status === 'selesai' && $foto_bukti) {
        $stmt = $conn->prepare("UPDATE pengaduan SET status = ?, foto_selesai = ? WHERE id = ?");
        $stmt->execute([$status, $foto_bukti, $id]);
    } elseif ($foto_bukti) {
        $stmt = $conn->prepare("UPDATE pengaduan SET status = ?, foto_bukti = ? WHERE id = ?");
        $stmt->execute([$status, $foto_bukti, $id]);
    } else {
        $stmt = $conn->prepare("UPDATE pengaduan SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
} catch (Exception $e) {
    // Fallback if new columns don't exist yet
    if ($foto_bukti) {
        $stmt = $conn->prepare("UPDATE pengaduan SET status = ?, foto_bukti = ? WHERE id = ?");
        $stmt->execute([$status, $foto_bukti, $id]);
    } else {
        $stmt = $conn->prepare("UPDATE pengaduan SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
}

header("Location: list_pengaduan.php?success=1");
exit;
