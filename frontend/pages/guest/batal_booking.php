<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='index.php?login_modal=1';</script>";
    exit;
}

$bookingId = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($bookingId && ($action === 'batal' || $action === 'edit')) {
    if ($action === 'edit') {
        echo "<script>window.location.href='index.php?page=booking';</script>";
        exit;
    }

    try {
        // Cek dulu apakah booking ini milik user dan statusnya valid untuk dibatalkan
        $stmt = $conn->prepare("SELECT status, kamar_id FROM booking WHERE id = ? AND user_id = ?");
        $stmt->execute([$bookingId, $_SESSION['user_id']]);
        $booking = $stmt->fetch();

        if ($booking) {
            $status = strtolower($booking['status']);
            // Hanya bisa dibatalkan jika belum bayar lunas (pending / disetujui / menunggu_dp)
            if (in_array($status, ['pending', 'disetujui', 'menunggu_dp'])) {
                // Update status booking
                $upd = $conn->prepare("UPDATE booking SET status = 'dibatalkan' WHERE id = ?");
                $upd->execute([$bookingId]);
                
                // Kamar kembali tersedia jika tadinya sudah disetujui (dibooking)
                if (!empty($booking['kamar_id'])) {
                    $updKamar = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id = ?");
                    $updKamar->execute([$booking['kamar_id']]);
                }
                
                echo "<script>alert('Pemesanan berhasil dibatalkan.'); window.location.href='index.php?page=riwayat_booking';</script>";
                exit;
            } else {
                echo "<script>alert('Pemesanan ini tidak dapat dibatalkan.'); window.location.href='index.php?page=riwayat_booking';</script>";
                exit;
            }
        }
    } catch (PDOException $e) {
        echo "<script>alert('Terjadi kesalahan sistem.'); window.location.href='index.php?page=riwayat_booking';</script>";
        exit;
    }
}
echo "<script>window.location.href='index.php?page=riwayat_booking';</script>";
exit;
