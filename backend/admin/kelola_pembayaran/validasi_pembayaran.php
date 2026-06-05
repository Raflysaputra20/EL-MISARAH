<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id     = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['valid', 'tidak_valid'])) {
    try {
        $conn->beginTransaction();

        // Update status pembayaran
        $stmtPay = $conn->prepare("UPDATE pembayaran SET status = ? WHERE id = ?");
        $stmtPay->execute([$action, $id]);

        // Ambil info pembayaran untuk update user status
        $stmtGet = $conn->prepare("SELECT booking_id FROM pembayaran WHERE id = ?");
        $stmtGet->execute([$id]);
        $pay = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if ($action === 'valid') {
            // Ambil info pembayaran lengkap
            $stmtCheck = $conn->prepare("SELECT metode, booking_id, durasi_bulan FROM pembayaran WHERE id = ?");
            $stmtCheck->execute([$id]);
            $payInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            $isPerpanjangan = ($payInfo && strpos($payInfo['metode'], 'Perpanjangan') !== false);

            // Update users.status = 'aktif'
            $targetUserId = null;
            if (!empty($payInfo['booking_id'])) {
                $stmtUid = $conn->prepare("SELECT user_id FROM booking WHERE id = ?");
                $stmtUid->execute([$payInfo['booking_id']]);
                $row = $stmtUid->fetch(PDO::FETCH_ASSOC);
                $targetUserId = $row['user_id'] ?? null;

                // Update status booking HANYA jika ini bukan perpanjangan
                // Jika perpanjangan, booking biasanya sudah 'selesai' atau 'aktif'
                if (!$isPerpanjangan) {
                    $stmtBook = $conn->prepare("UPDATE booking SET status = 'disetujui' WHERE id = ?");
                    $stmtBook->execute([$payInfo['booking_id']]);
                }
            }

            if ($targetUserId) {
                $stmtUser = $conn->prepare("UPDATE users SET status = 'aktif' WHERE id = ?");
                $stmtUser->execute([$targetUserId]);

                // AUTO JADIKAN PENGHUNI: set role penghuni & kamar terisi
                if (!$isPerpanjangan) {
                    $stmtRole = $conn->prepare("UPDATE users SET role = 'penghuni' WHERE id = ? AND role = 'user'");
                    $stmtRole->execute([$targetUserId]);

                    // Update booking jadi aktif & kamar jadi terisi
                    $stmtBookDone = $conn->prepare("UPDATE booking SET status = 'aktif' WHERE id = ?");
                    $stmtBookDone->execute([$payInfo['booking_id']]);

                    $stmtKamar = $conn->prepare("SELECT kamar_id FROM booking WHERE id = ?");
                    $stmtKamar->execute([$payInfo['booking_id']]);
                    $kamarRow = $stmtKamar->fetch(PDO::FETCH_ASSOC);
                    if ($kamarRow && !empty($kamarRow['kamar_id'])) {
                        $stmtKamarUpd = $conn->prepare("UPDATE kamar SET status = 'terisi' WHERE id = ?");
                        $stmtKamarUpd->execute([$kamarRow['kamar_id']]);
                    }
                }
            }

            // --- LOGIKA PERPANJANGAN OTOMATIS ---
            if ($isPerpanjangan) {
                $bulanTambah = isset($payInfo['durasi_bulan']) ? intval($payInfo['durasi_bulan']) : 1;

                if ($bulanTambah > 0 && !empty($payInfo['booking_id'])) {
                    $stmtExt = $conn->prepare("UPDATE booking SET durasi_bulan = durasi_bulan + ? WHERE id = ?");
                    $stmtExt->execute([$bulanTambah, $payInfo['booking_id']]);
                }
            }
            // ------------------------------------
        } elseif ($action === 'tidak_valid') {
            // Jika pembayaran nipu / tidak valid, batalkan booking terkait
            if (!empty($pay['booking_id'])) {
                $stmtBookInfo = $conn->prepare("SELECT kamar_id FROM booking WHERE id = ?");
                $stmtBookInfo->execute([$pay['booking_id']]);
                $bookRow = $stmtBookInfo->fetch(PDO::FETCH_ASSOC);

                if ($bookRow) {
                    $stmtUpdateBook = $conn->prepare("UPDATE booking SET status = 'dibatalkan' WHERE id = ?");
                    $stmtUpdateBook->execute([$pay['booking_id']]);

                    // Kosongkan status kamar jika sedang diisi booking ini
                    $stmtFreeKamar = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id = ? AND status != 'dihuni'");
                    $stmtFreeKamar->execute([$bookRow['kamar_id']]);
                }
            }
        }

        $conn->commit();
        if ($targetUserId) {
            header("Location: list_pembayaran.php?user_id=" . $targetUserId . "&success=Pembayaran+berhasil+diproses");
        } else {
            header("Location: list_pembayaran.php?success=Pembayaran+berhasil+diproses");
        }
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        if (isset($targetUserId)) {
            header("Location: list_pembayaran.php?user_id=" . $targetUserId . "&error=" . urlencode($e->getMessage()));
        } else {
            header("Location: list_pembayaran.php?error=" . urlencode($e->getMessage()));
        }
        exit;
    }
} else {
    header("Location: list_pembayaran.php");
    exit;
}
