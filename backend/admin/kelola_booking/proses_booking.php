<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id = $_POST["id"] ?? $_GET["id"] ?? null;
$aksi = $_POST["aksi"] ?? $_GET["aksi"] ?? null;
$alasan = $_POST["alasan"] ?? $_GET["alasan"] ?? null;

if (!$id || !$aksi) {
    header("Location: list_booking.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM booking WHERE id = ?");
$stmt->execute([$id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: list_booking.php");
    exit;
}

if ($aksi === "setujui") {
    // Safety guard: Admin cannot approve without payment proof if in menunggu_dp status
    if (strtolower($booking["status"]) === 'menunggu_dp') {
        $stmtPay = $conn->prepare("SELECT bukti_bayar FROM pembayaran WHERE booking_id = ? ORDER BY id DESC LIMIT 1");
        $stmtPay->execute([$id]);
        $bukti = $stmtPay->fetchColumn();
        if (empty($bukti)) {
            header("Location: list_booking.php?error=Tidak+dapat+menyetujui+karena+bukti+pembayaran+belum+diunggah");
            exit;
        }
    }

    // ═══ ISSUE #1: Cek status kamar sebelum menyetujui ═══
    $stmtKamarStatus = $conn->prepare("SELECT id, status, nomor_kamar FROM kamar WHERE id = ?");
    $stmtKamarStatus->execute([$booking["kamar_id"]]);
    $kamarData = $stmtKamarStatus->fetch(PDO::FETCH_ASSOC);

    if ($kamarData) {
        $kamarStatus = strtolower($kamarData['status']);
        
        // Jika kamar sudah dibooking oleh orang lain atau sudah terisi
        if ($kamarStatus === 'dibooking') {
            // Cek apakah kamar ini dibooking oleh booking ini sendiri
            $stmtCekOwner = $conn->prepare("SELECT id FROM booking WHERE kamar_id = ? AND status IN ('menunggu_dp','disetujui') AND id != ? LIMIT 1");
            $stmtCekOwner->execute([$booking["kamar_id"], $id]);
            $otherBooking = $stmtCekOwner->fetch();
            
            if ($otherBooking) {
                // Kamar sudah dibooking oleh orang lain - otomatis tolak
                $updateBooking = $conn->prepare("UPDATE booking SET status = 'ditolak', alasan_penolakan = ? WHERE id = ?");
                $alasanOtomatis = "Kamar " . $kamarData['nomor_kamar'] . " sudah di-booking oleh penyewa lain. Silakan pilih kamar lain yang tersedia.";
                $updateBooking->execute([$alasanOtomatis, $id]);
                
                header("Location: list_booking.php?error=" . urlencode("DITOLAK OTOMATIS: Kamar " . $kamarData['nomor_kamar'] . " sudah di-booking oleh penyewa lain."));
                exit;
            }
        } elseif ($kamarStatus === 'terisi') {
            // Kamar sudah terisi penghuni - otomatis tolak
            $updateBooking = $conn->prepare("UPDATE booking SET status = 'ditolak', alasan_penolakan = ? WHERE id = ?");
            $alasanOtomatis = "Kamar " . $kamarData['nomor_kamar'] . " sudah terisi penghuni lain. Silakan pilih kamar lain yang tersedia.";
            $updateBooking->execute([$alasanOtomatis, $id]);
            
            header("Location: list_booking.php?error=" . urlencode("DITOLAK OTOMATIS: Kamar " . $kamarData['nomor_kamar'] . " sudah terisi penghuni."));
            exit;
        }
    }

    // Cari kamar yang tersedia dengan tipe yang sama (fallback)
    $stmtTipe = $conn->prepare("SELECT tipe FROM kamar WHERE id = ?");
    $stmtTipe->execute([$booking["kamar_id"]]);
    $tipe = $stmtTipe->fetchColumn();

    $finalKamarId = $booking["kamar_id"];
    
    // Jika kamar yang diminta tidak tersedia, cari alternatif
    if ($kamarData && $kamarData['status'] !== 'tersedia' && $kamarData['status'] !== 'dibooking') {
        $stmtAvailable = $conn->prepare("SELECT id FROM kamar WHERE tipe = ? AND status = 'tersedia' ORDER BY nomor_kamar ASC LIMIT 1");
        $stmtAvailable->execute([$tipe]);
        $availableKamar = $stmtAvailable->fetchColumn();
        if ($availableKamar) {
            $finalKamarId = $availableKamar;
        }
    }

    // Booking disetujui, kamar ditandai dibooking
    $updateBooking = $conn->prepare("UPDATE booking SET status = 'disetujui', kamar_id = ? WHERE id = ?");
    $updateBooking->execute([$finalKamarId, $id]);

    // Kamar ditandai dibooking
    $updateKamar = $conn->prepare("UPDATE kamar SET status = 'dibooking' WHERE id = ?");
    $updateKamar->execute([$finalKamarId]);

    header("Location: list_booking.php?success=Booking+berhasil+disetujui");
    exit;

} elseif ($aksi === "tolak") {
    // Booking ditolak - admin wajib kasih alasan
    if (empty($alasan)) {
        $alasan = "Ditolak oleh admin.";
    }
    // Tambahkan info kontak admin WA
    $alasan .= "\n\nSilahkan konfirmasi ke WA Admin: +62 838-2146-3041";
    
    $updateBooking = $conn->prepare("UPDATE booking SET status = 'ditolak', alasan_penolakan = ? WHERE id = ?");
    $updateBooking->execute([$alasan, $id]);


    // Kamar kembali tersedia (hanya jika kamar ini dibooking oleh booking ini)
    if (!empty($booking["kamar_id"])) {
        // Cek apakah ada booking lain yang masih aktif untuk kamar ini
        $stmtCekLain = $conn->prepare("SELECT id FROM booking WHERE kamar_id = ? AND status IN ('menunggu_dp','disetujui','aktif') AND id != ? LIMIT 1");
        $stmtCekLain->execute([$booking["kamar_id"], $id]);
        $adaLain = $stmtCekLain->fetch();
        
        if (!$adaLain) {
            $updateKamar = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id = ? AND status = 'dibooking'");
            $updateKamar->execute([$booking["kamar_id"]]);
        }
    }
    
    header("Location: list_booking.php?success=Booking+berhasil+ditolak");
    exit;

} elseif ($aksi === "batal" || $aksi === "setujui_batal") {
    // Booking dibatalkan oleh admin, atau admin setujui pembatalan dari user
    $updateBooking = $conn->prepare("UPDATE booking SET status = 'dibatalkan' WHERE id = ?");
    $updateBooking->execute([$id]);

    // Kamar kembali tersedia
    if (!empty($booking["kamar_id"])) {
        $stmtCekLain = $conn->prepare("SELECT id FROM booking WHERE kamar_id = ? AND status IN ('menunggu_dp','disetujui','aktif') AND id != ? LIMIT 1");
        $stmtCekLain->execute([$booking["kamar_id"], $id]);
        $adaLain = $stmtCekLain->fetch();
        
        if (!$adaLain) {
            $updateKamar = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id = ?");
            $updateKamar->execute([$booking["kamar_id"]]);
        }
    }

    header("Location: list_booking.php?success=Booking+berhasil+dibatalkan");
    exit;

} elseif ($aksi === "selesai") {
    // ═══ ISSUE #5: Tandai booking sebagai selesai ═══
    $updateBooking = $conn->prepare("UPDATE booking SET status = 'selesai' WHERE id = ?");
    $updateBooking->execute([$id]);

    // Kamar kembali tersedia
    if (!empty($booking["kamar_id"])) {
        $updateKamar = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id = ?");
        $updateKamar->execute([$booking["kamar_id"]]);
    }

    // Update penghuni record jika ada
    try {
        $stmtPenghuni = $conn->prepare("UPDATE penghuni SET status = 'selesai', tanggal_keluar = CURDATE() WHERE booking_id = ? AND status = 'aktif'");
        $stmtPenghuni->execute([$id]);
    } catch (Exception $e) { /* table may not exist yet */ }

    header("Location: list_booking.php?success=Booking+ditandai+selesai");
    exit;
}

header("Location: list_booking.php");
exit;
