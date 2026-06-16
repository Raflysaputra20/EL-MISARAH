<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION["user_id"];
$role = $_SESSION["role"] ?? '';

$notifikasi = [];

if ($role === 'penghuni') {
    try {
        // 1. Status Booking Terbaru
        $stmtB = $conn->prepare("
            SELECT b.id as booking_id, b.status as status_sewa, b.tanggal_masuk, k.nomor_kamar, k.tipe, k.harga
            FROM booking b
            JOIN kamar k ON b.kamar_id = k.id
            WHERE b.user_id = ? AND b.status NOT IN ('dibatalkan')
            ORDER BY b.id DESC LIMIT 1
        ");
        $stmtB->execute([$userId]);
        $kInfo = $stmtB->fetch(PDO::FETCH_ASSOC);

        if ($kInfo) {
            $bookingId = $kInfo['booking_id'];
            $kLabel = 'Kamar ' . ($kInfo['nomor_kamar'] ?? '') . ' (' . ($kInfo['tipe'] ?? '') . ')';
            $tgl = $kInfo['tanggal_masuk'] ? date('d M Y', strtotime($kInfo['tanggal_masuk'])) : '-';

            switch ($kInfo['status_sewa']) {
                case 'disetujui':
                    $notifikasi[] = [
                        'key' => 'booking_approved_' . $bookingId,
                        'isi' => '🎉 Booking ' . $kLabel . ' disetujui! Masuk: ' . $tgl,
                        'waktu' => 'Segera bayar DP',
                        'type' => 'success',
                        'link' => 'penghuni/pembayaran.php'
                    ];
                    break;
                case 'ditolak':
                    $notifikasi[] = [
                        'key' => 'booking_rejected_' . $bookingId,
                        'isi' => '❌ Booking ' . $kLabel . ' ditolak admin.',
                        'waktu' => 'Hubungi admin',
                        'type' => 'warning',
                        'link' => 'penghuni/dashboard.php'
                    ];
                    break;
                case 'selesai':
                    $notifikasi[] = [
                        'key' => 'booking_completed_' . $bookingId,
                        'isi' => '✅ Masa sewa ' . $kLabel . ' telah selesai.',
                        'waktu' => 'Terima kasih',
                        'type' => 'info',
                        'link' => 'penghuni/riwayat_sewa.php'
                    ];
                    break;
                case 'pending':
                    $notifikasi[] = [
                        'key' => 'booking_pending_' . $bookingId,
                        'isi' => '⏳ Booking ' . $kLabel . ' menunggu persetujuan admin.',
                        'waktu' => 'Mohon tunggu',
                        'type' => 'info',
                        'link' => 'penghuni/dashboard.php'
                    ];
                    break;
            }

            // 2. Status Tagihan / Pembayaran
            $hSewa = (int)$kInfo['harga'];
            $statusP = 'Belum Ada';

            // Cek tagihan reguler (non-Perpanjangan) terbaru
            $stmtP = $conn->prepare("
                SELECT id, status FROM pembayaran
                WHERE booking_id = ?
                  AND (metode IS NULL OR metode NOT LIKE '%Perpanjangan%')
                ORDER BY id DESC LIMIT 1
            ");
            $stmtP->execute([$bookingId]);
            $pInfo = $stmtP->fetch(PDO::FETCH_ASSOC);
            if ($pInfo) {
                if ($pInfo['status'] === 'valid') {
                    $statusP = 'Lunas';
                } elseif ($pInfo['status'] === 'menunggu_verifikasi') {
                    $statusP = 'Proses Verifikasi';
                } elseif ($pInfo['status'] === 'belum_bayar') {
                    $statusP = 'Belum Bayar';
                }
            }

            if ($statusP === 'Belum Bayar' && $hSewa > 0) {
                $notifikasi[] = [
                    'key' => 'tagihan_unpaid_' . $bookingId . '_' . ($pInfo['id'] ?? 0),
                    'isi' => '💳 Tagihan kost belum dibayar. Segera lakukan pembayaran!',
                    'waktu' => 'Harap segera lunas',
                    'type' => 'warning',
                    'link' => 'penghuni/pembayaran.php'
                ];
            } elseif ($statusP === 'Belum Ada' && $hSewa > 0) {
                // Belum ada tagihan sama sekali
            } elseif ($statusP === 'Lunas') {
                $notifikasi[] = [
                    'key' => 'tagihan_paid_' . $bookingId . '_' . ($pInfo['id'] ?? 0),
                    'isi' => '✅ Pembayaran terakhir sudah terverifikasi & lunas.',
                    'waktu' => 'Terima kasih',
                    'type' => 'success',
                    'link' => 'penghuni/pembayaran.php'
                ];
            } elseif ($statusP === 'Proses Verifikasi') {
                $notifikasi[] = [
                    'key' => 'tagihan_verifying_' . $bookingId . '_' . ($pInfo['id'] ?? 0),
                    'isi' => '🔄 Bukti bayar Anda sedang dicek oleh Admin.',
                    'waktu' => 'Mohon tunggu',
                    'type' => 'info',
                    'link' => 'penghuni/pembayaran.php'
                ];
            }

            // Cek peringatan tagihan dari admin (tabel notifikasi personal)
            try {
                $stmtWarn = $conn->prepare("
                    SELECT id, judul, isi, created_at FROM notifikasi
                    WHERE user_id = ? AND tipe = 'warning' AND is_read = 0
                    ORDER BY id DESC LIMIT 5
                ");
                $stmtWarn->execute([$userId]);
                $warnings = $stmtWarn->fetchAll(PDO::FETCH_ASSOC);
                foreach ($warnings as $w) {
                    $notifikasi[] = [
                        'key'   => 'notif_personal_' . $w['id'],
                        'isi'   => '🔔 ' . $w['judul'] . ': ' . mb_strimwidth($w['isi'], 0, 80, '…'),
                        'waktu' => date('d M Y H:i', strtotime($w['created_at'])),
                        'type'  => 'warning',
                        'link'  => 'penghuni/pembayaran.php'
                    ];
                }
            } catch (Exception $e) {}
        }


        // 3. Semua pengaduan milik penghuni ini (terbaru, limit 5)
        $stmtA = $conn->prepare("SELECT id, judul, status, created_at FROM pengaduan WHERE user_id = ? ORDER BY id DESC LIMIT 5");
        $stmtA->execute([$userId]);
        $adList = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        foreach ($adList as $ad) {
            $judulPendek = mb_strimwidth($ad['judul'], 0, 40, '…');
            $tglAduan = date('d M Y', strtotime($ad['created_at']));
            switch ($ad['status']) {
                case 'baru':
                case 'masuk':
                    $notifikasi[] = [
                        'key' => 'pengaduan_masuk_' . $ad['id'] . '_' . $ad['status'],
                        'isi' => '📩 Pengaduan "' . $judulPendek . '" diterima, menunggu tindak lanjut.',
                        'waktu' => $tglAduan,
                        'type' => 'info',
                        'link' => 'penghuni/riwayat_pengaduan.php'
                    ];
                    break;
                case 'diproses':
                    $notifikasi[] = [
                        'key' => 'pengaduan_proses_' . $ad['id'] . '_' . $ad['status'],
                        'isi' => '🔧 Pengaduan "' . $judulPendek . '" sedang diproses admin.',
                        'waktu' => $tglAduan,
                        'type' => 'warning',
                        'link' => 'penghuni/riwayat_pengaduan.php'
                    ];
                    break;
                case 'selesai':
                    $notifikasi[] = [
                        'key' => 'pengaduan_selesai_' . $ad['id'] . '_' . $ad['status'],
                        'isi' => '✅ Pengaduan "' . $judulPendek . '" telah diselesaikan.',
                        'waktu' => $tglAduan,
                        'type' => 'success',
                        'link' => 'penghuni/riwayat_pengaduan.php'
                    ];
                    break;
            }
        }

        // 4. Pengumuman kost terbaru (EXCLUDE peringatan tagihan personal)
        $pengumumanList = [];
        try {
            $stmtPengumuman = $conn->query("
                SELECT id, judul, created_at FROM pengumuman 
                WHERE judul NOT LIKE 'PERINGATAN_TAGIHAN:%'
                ORDER BY id DESC LIMIT 3
            ");
            $pengumumanList = $stmtPengumuman->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        if (empty($pengumumanList)) {
            try {
                $stmtPengumuman = $conn->query("SELECT id, judul, created_at FROM informasi ORDER BY id DESC LIMIT 3");
                $pengumumanList = $stmtPengumuman->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        }

        foreach ($pengumumanList as $pm) {
            $notifikasi[] = [
                'key'   => 'pengumuman_' . $pm['id'],
                'isi'   => '📢 Pengumuman: ' . mb_strimwidth($pm['judul'], 0, 50, '…'),
                'waktu' => date('d M Y', strtotime($pm['created_at'])),
                'type'  => 'info',
                'link'  => 'penghuni/pengumuman.php'
            ];
        }

    } catch (Exception $e) {
        // Fallback silently
    }
} else if ($role === 'admin') {
    try {
        // 1. Ambil jumlah pengaduan 'baru'
        $stmtA = $conn->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'baru'");
        $countA = $stmtA->fetchColumn();
        if ($countA > 0) {
            $notifikasi[] = [
                'key' => 'admin_pengaduan_' . $countA,
                'isi' => 'Ada ' . $countA . ' laporan pengaduan baru.',
                'waktu' => 'Segera respon',
                'type' => 'warning',
                'link' => 'admin/kelola_pengaduan/list_pengaduan.php'
            ];
        }

        // 2. Ambil jumlah pembayaran 'menunggu_verifikasi'
        $stmtP = $conn->query("SELECT COUNT(*) FROM pembayaran WHERE status = 'menunggu_verifikasi'");
        $countP = $stmtP->fetchColumn();
        if ($countP > 0) {
            $notifikasi[] = [
                'key' => 'admin_pembayaran_' . $countP,
                'isi' => 'Ada ' . $countP . ' pembayaran menunggu verifikasi.',
                'waktu' => 'Segera cek',
                'type' => 'info',
                'link' => 'admin/kelola_tagihan/list_tagihan.php'
            ];
        }

        // 3. Ambil jumlah booking 'pending'
        $stmtB = $conn->query("SELECT COUNT(*) FROM booking WHERE status = 'pending'");
        $countB = $stmtB->fetchColumn();
        if ($countB > 0) {
            $notifikasi[] = [
                'key' => 'admin_booking_' . $countB,
                'isi' => 'Ada ' . $countB . ' pengajuan booking baru.',
                'waktu' => 'Segera verifikasi',
                'type' => 'warning',
                'link' => 'admin/kelola_booking/list_booking.php'
            ];
        }
    } catch (Exception $e) {}
}

echo json_encode([
    'success' => true,
    'notifikasi' => $notifikasi
]);
