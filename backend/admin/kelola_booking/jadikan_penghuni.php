<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id = $_GET["id"] ?? null;

if (!$id) {
    header("Location: list_booking.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM booking WHERE id = ? AND status = 'disetujui'");
$stmt->execute([$id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: list_booking.php?error=" . urlencode("Booking tidak ditemukan atau belum disetujui."));
    exit;
}

// ═══ ANTI DOUBLE TENANT: Cek apakah kamar sudah ditempati ═══
$stmtCekKamar = $conn->prepare("SELECT status FROM kamar WHERE id = ?");
$stmtCekKamar->execute([$booking["kamar_id"]]);
$kamarStatus = $stmtCekKamar->fetchColumn();

if ($kamarStatus === 'terisi') {
    // Kamar sudah ada penghuninya — tolak!
    header("Location: list_booking.php?error=" . urlencode("GAGAL: Kamar ini sudah ditempati penghuni lain. Tidak bisa dijadikan penghuni ganda."));
    exit;
}

// ═══ CEK apakah user sudah jadi penghuni aktif di kamar lain ═══
$stmtCekUser = $conn->prepare("SELECT b.id, k.nomor_kamar FROM booking b JOIN kamar k ON b.kamar_id = k.id WHERE b.user_id = ? AND b.status = 'aktif' LIMIT 1");
$stmtCekUser->execute([$booking["user_id"]]);
$existingActive = $stmtCekUser->fetch(PDO::FETCH_ASSOC);

if ($existingActive) {
    header("Location: list_booking.php?error=" . urlencode("GAGAL: User ini sudah menjadi penghuni aktif di kamar " . $existingActive['nomor_kamar'] . ". Satu user hanya boleh menghuni satu kamar."));
    exit;
}

try {
    $conn->beginTransaction();

    // 1. User resmi jadi penghuni
    $updateUser = $conn->prepare("UPDATE users SET role = 'penghuni' WHERE id = ?");
    $updateUser->execute([$booking["user_id"]]);

    // 2. Kamar resmi terisi
    if (!empty($booking["kamar_id"])) {
        $updateKamar = $conn->prepare("UPDATE kamar SET status = 'terisi' WHERE id = ?");
        $updateKamar->execute([$booking["kamar_id"]]);
    }

    // 3. Booking resmi aktif (penghuni sedang tinggal)
    $updateBooking = $conn->prepare("UPDATE booking SET status = 'aktif' WHERE id = ?");
    $updateBooking->execute([$id]);

    // 4. ═══ ISSUE #6: Insert ke tabel penghuni ═══
    try {
        // Cek apakah sudah ada record penghuni aktif untuk user ini
        $stmtCekPenghuni = $conn->prepare("SELECT id FROM penghuni WHERE user_id = ? AND status = 'aktif' LIMIT 1");
        $stmtCekPenghuni->execute([$booking["user_id"]]);
        $existingPenghuni = $stmtCekPenghuni->fetch();
        
        if (!$existingPenghuni) {
            $stmtInsertPenghuni = $conn->prepare("INSERT INTO penghuni (user_id, kamar_id, booking_id, tanggal_masuk, status) VALUES (?, ?, ?, ?, 'aktif')");
            $stmtInsertPenghuni->execute([
                $booking["user_id"],
                $booking["kamar_id"],
                $id,
                $booking["tanggal_masuk"] ?? date('Y-m-d')
            ]);
        }
    } catch (Exception $e) {
        // Tabel penghuni mungkin belum ada, lanjutkan saja
        error_log("Insert penghuni error: " . $e->getMessage());
    }

    // ═══ AUTO-REJECT: Tolak semua booking lain untuk kamar yang sama ═══
    $stmtRejectSameRoom = $conn->prepare("
        UPDATE booking 
        SET status = 'ditolak', 
            catatan = CONCAT(IFNULL(catatan,''), '\n[Otomatis ditolak: kamar sudah diisi penghuni lain]')
        WHERE kamar_id = ? 
          AND id != ? 
          AND status IN ('pending', 'disetujui', 'menunggu_dp')
    ");
    $stmtRejectSameRoom->execute([$booking["kamar_id"], $id]);

    // ═══ AUTO-REJECT: Tolak semua booking lain dari user yang sama (1 user = 1 kamar) ═══
    $stmtRejectSameUser = $conn->prepare("
        UPDATE booking 
        SET status = 'ditolak',
            catatan = CONCAT(IFNULL(catatan,''), '\n[Otomatis ditolak: user sudah menjadi penghuni kamar lain]')
        WHERE user_id = ? 
          AND id != ? 
          AND status IN ('pending', 'disetujui', 'menunggu_dp')
    ");
    $stmtRejectSameUser->execute([$booking["user_id"], $id]);

    $conn->commit();

    header("Location: list_booking.php?success=" . urlencode("Berhasil! User telah dijadikan penghuni. Booking lain yang bentrok otomatis ditolak."));
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    header("Location: list_booking.php?error=" . urlencode("Gagal memproses: " . $e->getMessage()));
    exit;
}
