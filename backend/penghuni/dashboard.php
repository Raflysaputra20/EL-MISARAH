<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . '/init.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penghuni") {
    header("Location: ../api/auth/login.php");
    exit;
}

$userId   = $_SESSION["user_id"];
$namaUser = $_SESSION["nama"] ?? "Penghuni";

// ═══ AUTO-SYNC: Sinkronisasi status kamar dengan data penghuni aktif ═══
try {
    $conn->exec("
        UPDATE kamar k
        JOIN booking b ON b.kamar_id = k.id
        JOIN users u ON b.user_id = u.id
        SET k.status = 'terisi'
        WHERE b.status IN ('aktif', 'selesai')
          AND u.role = 'penghuni'
          AND u.status = 'aktif'
          AND k.status = 'tersedia'
    ");
    $conn->exec("
        UPDATE kamar 
        SET status = 'tersedia' 
        WHERE status = 'terisi' 
          AND id NOT IN (
              SELECT b.kamar_id 
              FROM booking b 
              JOIN users u ON b.user_id = u.id 
              WHERE b.status IN ('aktif', 'disetujui', 'selesai') 
                AND u.role = 'penghuni' 
                AND u.status = 'aktif'
                AND b.kamar_id IS NOT NULL
          )
    ");
} catch (Exception $e) {}

// 0. Ambil Data User Terbaru
try {
    $stmtUser = $conn->prepare("SELECT status, foto FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $userData = null; }
$userStatus = $userData['status'] ?? 'nonaktif';
$userFoto   = $userData['foto'] ?? null;

// 1. Info Kamar & Booking Aktif
try {
    $stmtKamar = $conn->prepare("
        SELECT b.id as booking_id, b.tanggal_masuk, b.durasi_bulan, b.status as status_sewa,
               k.nomor_kamar as no_kamar, k.tipe, k.harga
        FROM booking b
        JOIN kamar k ON b.kamar_id = k.id
        WHERE b.user_id = ? AND b.status NOT IN ('ditolak','dibatalkan')
        ORDER BY b.id DESC LIMIT 1
    ");
    $stmtKamar->execute([$userId]);
    $kamarInfo = $stmtKamar->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $kamarInfo = null; }

$nomorKamar      = $kamarInfo ? $kamarInfo['no_kamar'] : '-';
$tipeKamar       = $kamarInfo ? $kamarInfo['tipe'] : '';
$nomorKamarLabel = ($nomorKamar !== '-' && $tipeKamar) ? $nomorKamar . ' ' . $tipeKamar : $nomorKamar;
$hargaSewa       = $kamarInfo ? (int)$kamarInfo['harga'] : 0;

// 2. Logika Jatuh Tempo (Tanggal Masuk + Total Durasi Bulan)
$jatuhTempo = '-';
$statusSewaColor = 'red';

if ($kamarInfo) {
    if ($kamarInfo['status_sewa'] === 'selesai') {
        $statusSewa = 'Selesai';
        $statusSewaColor = 'gray';
    } elseif (in_array($kamarInfo['status_sewa'], ['pending', 'menunggu_dp'])) {
        $statusSewa = 'Menunggu Persetujuan';
        $statusSewaColor = 'orange';
    } else {
        if (!empty($kamarInfo['tanggal_masuk'])) {
            $tglMasuk = new DateTime($kamarInfo['tanggal_masuk']);
            $durasi = (int)$kamarInfo['durasi_bulan'];
            $tglHabis = clone $tglMasuk;
            $tglHabis->modify("+$durasi month");
            $jatuhTempo = $tglHabis->format('j F Y');
            
            // Hitung sisa hari untuk status sewa
            $today = new DateTime();
            $statusSewa = ($tglHabis > $today) ? 'Aktif' : 'Habis';
        } else {
            $statusSewa = ($userStatus === 'aktif') ? 'Aktif' : 'Tidak Aktif';
        }
    }
} else {
    $statusSewa = ($userStatus === 'aktif') ? 'Aktif' : 'Tidak Aktif';
}

if ($statusSewa === 'Aktif') $statusSewaColor = 'green';
else if ($statusSewa === 'Selesai') $statusSewaColor = 'gray';
else if ($statusSewa === 'Habis' || $statusSewa === 'Tidak Aktif') $statusSewaColor = 'red';

// 3. Tagihan Bulan Ini (Cek dari tabel pembayaran/tagihan)
$tagihanBulanIni = $hargaSewa;
$statusPembayaran = 'Belum Bayar';

try {
    // Cari pembayaran terbaru bulan ini atau yang statusnya belum lunas
    $stmtPay = $conn->prepare("
        SELECT jumlah, status, metode FROM pembayaran
        WHERE booking_id = ?
        ORDER BY id DESC LIMIT 1
    ");
    $stmtPay->execute([$kamarInfo['booking_id'] ?? 0]);
    $payInfo = $stmtPay->fetch(PDO::FETCH_ASSOC);
    
    if ($payInfo) {
        // Jika ada pembayaran yang valid bulan ini, berarti sudah lunas
        $tagihanBulanIni = (int)$payInfo['jumlah'];
        if ($payInfo['status'] === 'valid') {
            $statusPembayaran = 'Lunas';
        } elseif ($payInfo['status'] === 'menunggu_verifikasi') {
            $statusPembayaran = 'Proses Verifikasi';
        }
    }
} catch (Exception $e) {}

// 3. Pengumuman terbaru — hanya pengumuman asli (bukan peringatan tagihan personal)
$pengumumanList = [];
try {
    $stmtPengumuman = $conn->query("
        SELECT judul, isi, created_at FROM pengumuman 
        WHERE judul NOT LIKE 'PERINGATAN_TAGIHAN:%'
        ORDER BY id DESC LIMIT 3
    ");
    $pengumumanList = $stmtPengumuman->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
if (empty($pengumumanList)) {
    try {
        $stmtPengumuman = $conn->query("SELECT judul, isi, created_at FROM informasi ORDER BY id DESC LIMIT 3");
        $pengumumanList = $stmtPengumuman->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $pengumumanList = []; }
}

// 3b. Notifikasi personal dari tabel notifikasi (peringatan tagihan dari admin)
$notifPersonal = [];
try {
    $stmtNP = $conn->prepare("SELECT judul, isi, tipe, created_at FROM notifikasi WHERE user_id = ? AND is_read = 0 ORDER BY id DESC LIMIT 5");
    $stmtNP->execute([$userId]);
    $notifPersonal = $stmtNP->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}


// ── Notifikasi Widget (server-side) ──────────────────────────────────────────
$notifikasi = [];

// 1. Status Booking terbaru
try {
    $stmtBnotif = $conn->prepare("
        SELECT b.status, b.tanggal_masuk, k.nomor_kamar, k.tipe
        FROM booking b
        LEFT JOIN kamar k ON b.kamar_id = k.id
        WHERE b.user_id = ? ORDER BY b.id DESC LIMIT 1
    ");
    $stmtBnotif->execute([$userId]);
    $bnotif = $stmtBnotif->fetch(PDO::FETCH_ASSOC);
    if ($bnotif) {
        $kLabel = 'Kamar ' . ($bnotif['nomor_kamar'] ?? '') . ' (' . ($bnotif['tipe'] ?? '') . ')';
        switch ($bnotif['status']) {
            case 'disetujui':
                $tgl = $bnotif['tanggal_masuk'] ? date('d M Y', strtotime($bnotif['tanggal_masuk'])) : '-';
                $notifikasi[] = ['isi' => '🎉 Booking ' . $kLabel . ' disetujui! Masuk: ' . $tgl, 'waktu' => 'Segera bayar DP', 'type' => 'success'];
                break;
            case 'ditolak':
                $notifikasi[] = ['isi' => '❌ Booking ' . $kLabel . ' ditolak admin.', 'waktu' => 'Hubungi admin', 'type' => 'warning'];
                break;
            case 'selesai':
                $notifikasi[] = ['isi' => '✅ Masa sewa ' . $kLabel . ' telah selesai.', 'waktu' => 'Terima kasih', 'type' => 'info'];
                break;
            case 'pending':
                $notifikasi[] = ['isi' => '⏳ Booking ' . $kLabel . ' menunggu persetujuan admin.', 'waktu' => 'Mohon tunggu', 'type' => 'info'];
                break;
        }
    }
} catch (Exception $e) {}

// 2. Status Pembayaran
if ($statusPembayaran === 'Belum Bayar' && $hargaSewa > 0) {
    $notifikasi[] = ['isi' => '💳 Tagihan kost bulan ini belum dibayar.', 'waktu' => 'Harap segera lunas', 'type' => 'warning'];
} elseif ($statusPembayaran === 'Lunas') {
    $notifikasi[] = ['isi' => '✅ Pembayaran bulan ini sudah terverifikasi & lunas.', 'waktu' => 'Terima kasih', 'type' => 'success'];
} elseif ($statusPembayaran === 'Proses Verifikasi') {
    $notifikasi[] = ['isi' => '🔄 Bukti bayar Anda sedang dicek oleh Admin.', 'waktu' => 'Mohon tunggu', 'type' => 'info'];
}

// 3. Semua pengaduan milik penghuni ini
try {
    $stmtAduanAll = $conn->prepare("SELECT judul, status, created_at FROM pengaduan WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmtAduanAll->execute([$userId]);
    $aduanList = $stmtAduanAll->fetchAll(PDO::FETCH_ASSOC);
    foreach ($aduanList as $aduan) {
        $judulPendek = mb_strimwidth($aduan['judul'], 0, 40, '…');
        $tglAduan = date('d M Y', strtotime($aduan['created_at']));
        switch ($aduan['status']) {
            case 'masuk':
                $notifikasi[] = ['isi' => '📩 Pengaduan "' . $judulPendek . '" diterima, menunggu tindak lanjut.', 'waktu' => $tglAduan, 'type' => 'info'];
                break;
            case 'diproses':
                $notifikasi[] = ['isi' => '🔧 Pengaduan "' . $judulPendek . '" sedang diproses admin.', 'waktu' => $tglAduan, 'type' => 'warning'];
                break;
            case 'selesai':
                $notifikasi[] = ['isi' => '✅ Pengaduan "' . $judulPendek . '" telah diselesaikan.', 'waktu' => $tglAduan, 'type' => 'success'];
                break;
        }
    }
} catch (Exception $e) {}

// 3c. Notifikasi personal → masuk ke widget notifikasi
foreach ($notifPersonal as $np) {
    $ikoNotif = match($np['tipe']) {
        'warning' => '🔔',
        'success' => '✅',
        'danger'  => '❗',
        default   => 'ℹ️'
    };
    $notifikasi[] = [
        'isi'   => $ikoNotif . ' ' . $np['judul'] . ': ' . mb_strimwidth($np['isi'], 0, 60, '…'),
        'waktu' => date('d M Y H:i', strtotime($np['created_at'])),
        'type'  => $np['tipe']
    ];
}

// 4. Pengumuman terbaru ke notifikasi
foreach ($pengumumanList as $pm) {
    $notifikasi[] = ['isi' => '📢 Pengumuman: ' . mb_strimwidth($pm['judul'], 0, 50, '…'), 'waktu' => date('d M Y', strtotime($pm['created_at'])), 'type' => 'info'];
}

if (empty($notifikasi)) {
    $notifikasi[] = ['isi' => 'Tidak ada notifikasi baru.', 'waktu' => '', 'type' => 'info'];
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penghuni - Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css?v=1.2">
    <style>
        /* Notification bell reset */
        .notification-btn, .notif-btn { background:none !important; border:none !important; outline:none !important; box-shadow:none !important; cursor:pointer; padding:6px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#1f2937; transition:background .15s; }
        .notification-btn:hover, .notif-btn:hover { background:rgba(0,0,0,0.06) !important; }

        :root { --green: #11a654; --bg: #f4f6f8; --dark: #1f2937; --gray: #64748b; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--dark); margin: 0; }
        
        /* SIDEBAR SYNC */
        .sidebar { width: 240px; height: 100vh; background: var(--green); position: fixed; top: 0; left: 0; z-index: 1000; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 10px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
        .sidebar-brand { padding: 30px 25px; font-size: 22px; font-weight: 800; color: white; }
        .sidebar-menu { list-style: none; padding: 0 15px; flex-grow: 1; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 12px 18px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 12px; transition: 0.2s; }
        .sidebar-link:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar-link.active { background: white; color: var(--green); font-weight: 700; }
        .sidebar-icon { width: 18px; height: 18px; }
        .sidebar-footer { padding: 20px 15px 25px; }
        .btn-keluar { display: inline-flex; align-items: center; gap: 8px; background: white; color: var(--dark); text-decoration: none; padding: 10px 22px; border-radius: 30px; font-weight: 700; font-size: 13px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }

        .main { margin-left: 240px; min-height: 100vh; }
        .topbar { height: 68px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 100; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 38px; height: 38px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #475569; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: 700; font-size: 13.5px; color: var(--dark); line-height: 1.2; }
        .user-role { font-size: 11px; color: var(--gray); font-weight: 500; }
        .content { padding: 25px 30px; }

        .info-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 25px; }
        .info-card { background: white; border-radius: 16px; padding: 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 16px; }
        .info-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .info-label { font-size: 12px; font-weight: 600; color: var(--gray); text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 16px; font-weight: 800; color: var(--dark); line-height: 1.2; }

        .grid-container { display: grid; grid-template-columns: 1.8fr 1fr; gap: 20px; }
        .card-box { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); margin-bottom: 20px; }
        .card-title { font-size: 16px; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .notif-item { display: flex; gap: 12px; margin-bottom: 15px; padding: 12px; border-radius: 12px; background: #f8fafc; }
        .notif-dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
        .notif-text { font-size: 13px; font-weight: 600; color: var(--dark); }
        .notif-time { font-size: 11px; color: var(--gray); margin-top: 2px; }

        .pengumuman-item { border-left: 4px solid var(--green); padding-left: 15px; margin-bottom: 20px; }
        .p-judul { font-size: 14px; font-weight: 700; color: var(--dark); }
        .p-isi { font-size: 13px; color: var(--gray); margin-top: 4px; line-height: 1.5; }
        .p-tgl { font-size: 11px; color: #94a3b8; margin-top: 8px; }

        .aturan-box { background: #1e293b; color: white; border-radius: 16px; padding: 25px; }
        .aturan-item { margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        .aturan-item:last-child { border: none; }

        /* Mobile / Tablet Responsiveness */
        @media (max-width: 768px) {
            .info-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .grid-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .info-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-brand">
        <span class="sidebar-brand-name">Elmi Sarah</span>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="dashboard.php" class="sidebar-link active"><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="notifikasi.php" class="sidebar-link"><i data-lucide="bell" class="sidebar-icon"></i> Notifikasi</a></li>
        <li class="sidebar-item"><a href="pembayaran.php" class="sidebar-link"><i data-lucide="credit-card" class="sidebar-icon"></i> Pembayaran</a></li>
        <li class="sidebar-item"><a href="riwayat_pengaduan.php" class="sidebar-link"><i data-lucide="wrench" class="sidebar-icon"></i> Pengaduan Kost</a></li>
        <li class="sidebar-item"><a href="pengumuman.php" class="sidebar-link"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="riwayat_sewa.php" class="sidebar-link"><i data-lucide="history" class="sidebar-icon"></i> Riwayat Sewa</a></li>
        <li class="sidebar-item"><a href="informasi_kost.php" class="sidebar-link"><i data-lucide="info" class="sidebar-icon"></i> Informasi Kost</a></li>
        <li class="sidebar-item"><a href="ulasan.php" class="sidebar-link"><i data-lucide="star" class="sidebar-icon"></i> Ulasan</a></li>
        <li class="sidebar-item"><a href="profil.php" class="sidebar-link"><i data-lucide="user" class="sidebar-icon"></i> Profil Saya</a></li>
        <li class="sidebar-item"><a href="pengaturan.php" class="sidebar-link"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-keluar"><i data-lucide="log-out" style="width:16px;height:16px;"></i> Keluar</a>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <div style="display:flex; align-items:center; gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px; height:24px;"></i></button>
            <h2 style="font-size: 18px; font-weight: 800; margin:0;">Selamat Datang, <?= explode(' ', $namaUser)[0] ?>! 👋</h2>
        </div>
        <div class="topbar-right">
            <div id="notifWrapper" style="position:relative;display:inline-block;">
                    <button id="notifBell" class="notification-btn" onclick="toggleNotif(event)" aria-label="Notifikasi" style="position:relative;">
                        <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
                        <span id="notifBadge" style="display:none;position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:17px;height:17px;border-radius:999px;align-items:center;justify-content:center;padding:0 3px;line-height:17px;text-align:center;">0</span>
                    </button>
                    <!-- DROPDOWN NOTIFIKASI -->
                    <div id="notifDropdown" style="display:none;position:absolute;right:0;top:52px;width:330px;background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,0.14);z-index:9999;overflow:hidden;">
                        <div style="padding:14px 18px 10px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-weight:700;font-size:14px;color:#111;">🔔 Notifikasi</span>
                            <span id="notifCount" style="font-size:11px;color:#888;">Memuat...</span>
                        </div>
                        <div id="notifList" style="max-height:300px;overflow-y:auto;">
                            <div style="padding:20px;text-align:center;color:#aaa;font-size:13px;">Memuat notifikasi...</div>
                        </div>
                    </div>
                </div>
            <div class="user-profile">
                <a href="profil.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:12px;">
                    <div class="avatar">
                        <?php if (isset($userFoto) && $userFoto): ?>
                            <img src="../uploads/profil/<?= htmlspecialchars(basename($userFoto)) ?>" alt="Profil">
                        <?php elseif (isset($foto) && $foto): ?>
                            <img src="../uploads/profil/<?= htmlspecialchars(basename($foto)) ?>" alt="Profil">
                        <?php else: ?>
                            <?= strtoupper(substr($namaUser ?? 'P', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaUser) ?></span>
                        <span class="user-role">Penghuni Kos</span>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <main class="content">
        <div class="info-cards">
            <div class="info-card">
                <div class="info-icon" style="background:#f0fdf4;"><i data-lucide="home" style="color:var(--green); width:24px;"></i></div>
                <div>
                    <div class="info-label">Nomor Kamar</div>
                    <div class="info-value"><?= htmlspecialchars($nomorKamarLabel) ?></div>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon" style="background:<?= $statusSewaColor === 'green' ? '#f0fdf4' : ($statusSewaColor === 'orange' ? '#fff7ed' : '#fef2f2') ?>;">
                    <i data-lucide="shield-check" style="color:<?= $statusSewaColor === 'green' ? 'var(--green)' : ($statusSewaColor === 'orange' ? '#f97316' : '#ef4444') ?>; width:24px;"></i>
                </div>
                <div>
                    <div class="info-label">Status Sewa</div>
                    <div class="info-value" style="color:<?= $statusSewaColor === 'green' ? 'var(--green)' : ($statusSewaColor === 'orange' ? '#f97316' : '#ef4444') ?>; font-size:14px;"><?= $statusSewa ?></div>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon" style="background:#eff6ff;"><i data-lucide="credit-card" style="color:#3b82f6; width:24px;"></i></div>
                <div>
                    <div class="info-label">Tagihan <?= $statusPembayaran === 'Lunas' ? 'Lunas' : 'Bulan Ini' ?></div>
                    <div class="info-value">Rp <?= number_format($tagihanBulanIni, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon" style="background:#fff7ed;"><i data-lucide="calendar" style="color:#f97316; width:24px;"></i></div>
                <div>
                    <div class="info-label">Jatuh Tempo</div>
                    <div class="info-value"><?= $jatuhTempo ?></div>
                </div>
            </div>
        </div>

        <div class="grid-container">
            <div>
                <div class="card-box">
                    <div class="card-title"><i data-lucide="megaphone" style="color:var(--green)"></i> Pengumuman Terbaru</div>
                    <?php if (empty($pengumumanList)): ?>
                        <p style="font-size:13px; color:var(--gray)">Belum ada pengumuman untuk Anda.</p>
                    <?php else: foreach($pengumumanList as $p): ?>
                        <div class="pengumuman-item">
                            <div class="p-judul"><?= htmlspecialchars($p['judul']) ?></div>
                            <div class="p-isi"><?= nl2br(htmlspecialchars($p['isi'])) ?></div>
                            <div class="p-tgl"><?= date('d M Y', strtotime($p['created_at'])) ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="aturan-box">
                    <div class="card-title" style="color:white;"><i data-lucide="scroll-text"></i> Aturan Penting Kost</div>
                    <div class="aturan-item">
                        <div style="font-weight:700; margin-bottom:5px;">🕒 Jam Malam</div>
                        <div style="font-size:13px; opacity:0.8;">Gerbang dikunci pukul 23:00 WIB. Harap konfirmasi jika pulang terlambat.</div>
                    </div>
                    <div class="aturan-item">
                        <div style="font-weight:700; margin-bottom:5px;">🧹 Kebersihan</div>
                        <div style="font-size:13px; opacity:0.8;">Buang sampah pada tempatnya dan jaga kebersihan area bersama.</div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card-box">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="card-title mb-0"><i data-lucide="bell"></i> Notifikasi</div>
                    </div>
                    <?php 
                    $limitedNotif = array_slice($notifikasi, 0, 3);
                    if (empty($limitedNotif) || (count($limitedNotif) === 1 && strpos($limitedNotif[0]['isi'], 'Tidak ada') !== false)): 
                    ?>
                        <p style="font-size:13px; color:var(--gray); text-align:center; padding: 15px 0;">Tidak ada notifikasi baru.</p>
                    <?php else: ?>
                        <?php foreach($limitedNotif as $n): ?>
                            <div class="notif-item">
                                <div class="notif-dot" style="background:<?= $n['type'] === 'warning' ? '#f59e0b' : '#3b82f6' ?>;"></div>
                                <div>
                                    <div class="notif-text"><?= htmlspecialchars($n['isi']) ?></div>
                                    <div class="notif-time"><?= htmlspecialchars($n['waktu']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="notifikasi.php" class="btn btn-sm w-100 text-center mt-2" style="background: rgba(17,166,84,0.1); color: var(--green); font-weight:600; border-radius:10px; padding: 8px;">Lihat Semua Notifikasi</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>
    lucide.createIcons();
</script>
<script src="../assets/js/notifikasi.js"></script>
</body>
</html>