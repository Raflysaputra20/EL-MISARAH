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
$adminWA  = "6289634566662";

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

// 1. Info Booking & Kamar
try {
    $stmtBooking = $conn->prepare("
        SELECT b.id as booking_id, b.tanggal_masuk, b.durasi_bulan, b.status as status_sewa,
               k.nomor_kamar as no_kamar, k.tipe, k.harga
        FROM booking b
        JOIN kamar k ON b.kamar_id = k.id
        WHERE b.user_id = ? AND b.status NOT IN ('ditolak','dibatalkan')
        ORDER BY FIELD(b.status, 'aktif', 'disetujui', 'selesai', 'pending') ASC, b.id DESC LIMIT 1
    ");
    $stmtBooking->execute([$userId]);
    $bookingInfo = $stmtBooking->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $bookingInfo = null; }

// Blokir jika belum ada booking yang disetujui
$bookingApproved = false;
if ($bookingInfo && in_array($bookingInfo['status_sewa'], ['disetujui', 'selesai', 'aktif'])) {
    $bookingApproved = true;
} elseif ($bookingInfo && $bookingInfo['status_sewa'] === 'pending') {
    // Booking masih pending, belum bisa bayar
} elseif ($bookingInfo && $bookingInfo['status_sewa'] === 'ditolak') {
    // Booking ditolak
}

$sisaHari = 0; $tglHabis = '-'; $isWarning = false;
if ($bookingInfo && !empty($bookingInfo['tanggal_masuk'])) {
    $tglMasuk = new DateTime($bookingInfo['tanggal_masuk']);
    $durasi   = (int)$bookingInfo['durasi_bulan'];
    $tglHabisObj = clone $tglMasuk;
    $tglHabisObj->modify("+$durasi month");
    $tglHabis = $tglHabisObj->format('d F Y');
    $today = new DateTime();
    if ($tglHabisObj < $today) { 
        $sisaHari = 0; 
        $isWarning = true; 
    } else {
        if ($today < $tglMasuk) {
            $interval = $tglMasuk->diff($tglHabisObj);
            $sisaHari = $interval->days;
        } else {
            $interval = $today->diff($tglHabisObj);
            $sisaHari = $interval->days;
            if ($sisaHari <= 5) $isWarning = true;
        }
    }
}

$hargaSewa  = $bookingInfo ? (int)$bookingInfo['harga'] : 0;
$nomorKamar = $bookingInfo ? ($bookingInfo['no_kamar'] ?? '-') : '-';
$bookingId  = $bookingInfo ? $bookingInfo['booking_id'] : null;

// TIDAK ada auto-create tagihan oleh penghuni —
// Tagihan hanya dibuat oleh Admin melalui halaman kelola_tagihan.
// Di sini hanya ambil tagihan terbaru yang masih belum_bayar / menunggu_verifikasi

// 2. Tagihan Aktif (belum_bayar atau menunggu_verifikasi, bukan perpanjangan)
// PENTING: tagihan dari admin memiliki metode = NULL
// kondisi NOT (NULL LIKE '%Perpanjangan%') = NULL = false → row hilang!
// Fix: gunakan (metode IS NULL OR metode NOT LIKE '%Perpanjangan%')
$pembayaranBulanIni = null;
try {
    $stmtBulanIni = $conn->prepare("
        SELECT p.id, p.jumlah, p.status, p.metode, p.tanggal_bayar,
               MONTH(p.tanggal_bayar) as bln, YEAR(p.tanggal_bayar) as thn
        FROM pembayaran p
        JOIN booking b ON p.booking_id = b.id
        WHERE b.user_id = ?
          AND p.status IN ('belum_bayar','menunggu_verifikasi')
          AND (p.metode IS NULL OR p.metode NOT LIKE '%Perpanjangan%')
        ORDER BY p.id DESC LIMIT 1
    ");
    $stmtBulanIni->execute([$userId]);
    $pembayaranBulanIni = $stmtBulanIni->fetch(PDO::FETCH_ASSOC);

    // Jika tidak ada tagihan aktif, ambil tagihan reguler terakhir (untuk ditampilkan)
    if (!$pembayaranBulanIni) {
        $stmtLast = $conn->prepare("
            SELECT p.id, p.jumlah, p.status, p.metode, p.tanggal_bayar,
                   MONTH(p.tanggal_bayar) as bln, YEAR(p.tanggal_bayar) as thn
            FROM pembayaran p
            JOIN booking b ON p.booking_id = b.id
            WHERE b.user_id = ?
              AND (p.metode IS NULL OR p.metode NOT LIKE '%Perpanjangan%')
            ORDER BY p.id DESC LIMIT 1
        ");
        $stmtLast->execute([$userId]);
        $pembayaranBulanIni = $stmtLast->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}


$tagihanBulanIni = ($pembayaranBulanIni && (int)$pembayaranBulanIni['jumlah'] > 0) ? (int)$pembayaranBulanIni['jumlah'] : $hargaSewa;
$statusBulanIni  = $pembayaranBulanIni ? $pembayaranBulanIni['status'] : null;
$pembayaranId    = $pembayaranBulanIni ? (int)$pembayaranBulanIni['id'] : 0;

// 3. Riwayat
$riwayatBayar = [];
try {
    $stmtRiwayat = $conn->prepare("
        SELECT p.jumlah, p.status, p.tanggal_bayar, p.durasi_bulan,
               MONTHNAME(p.tanggal_bayar) as bulan, YEAR(p.tanggal_bayar) as tahun 
        FROM pembayaran p
        JOIN booking b ON p.booking_id = b.id
        WHERE b.user_id = ?
        ORDER BY p.id DESC LIMIT 10
    ");
    $stmtRiwayat->execute([$userId]);
    $riwayatBayar = $stmtRiwayat->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// 4. Foto
try {
    $stmtFoto = $conn->prepare("SELECT foto FROM users WHERE id = ?");
    $stmtFoto->execute([$userId]);
    $userFoto = $stmtFoto->fetchColumn();
} catch (Exception $e) { $userFoto = null; }

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css?v=1.2">
    <style>
        /* Notification bell reset */
        .notification-btn, .notif-btn { background:none !important; border:none !important; outline:none !important; box-shadow:none !important; cursor:pointer; padding:6px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#1f2937; transition:background .15s; }
        .notification-btn:hover, .notif-btn:hover { background:rgba(0,0,0,0.06) !important; }

        :root { --green:#11a654; --green-light:#e8f7f0; --bg:#f4f6f8; --dark:#1f2937; --gray:#6b7280; --border:#e5e7eb; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; background:var(--bg); color:var(--dark); overflow-x:hidden; }
        .sidebar { width:240px; height:100vh; background:var(--green); position:fixed; top:0; left:0; display:flex; flex-direction:column; border-top-right-radius:20px; border-bottom-right-radius:20px; box-shadow:4px 0 20px rgba(0,0,0,.1); z-index:1000; }
        .sidebar-brand { padding:28px 22px 22px; display:flex; align-items:center; justify-content:space-between; }
        .sidebar-brand-name { font-size:22px; font-weight:800; color:white; }
        .sidebar-menu { list-style:none; padding:0 14px; flex-grow:1; }
        .sidebar-item { margin-bottom:4px; }
        .sidebar-link { display:flex; align-items:center; gap:12px; padding:11px 16px; color:rgba(255,255,255,.85); text-decoration:none; font-size:14px; font-weight:500; border-radius:12px; transition:all .2s; }
        .sidebar-link:hover { background:rgba(255,255,255,.15); color:white; }
        .sidebar-link.active { background:white; color:var(--green); font-weight:700; }
        .sidebar-icon { width:18px; height:18px; flex-shrink:0; }
        .sidebar-footer { padding:16px 14px 24px; }
        .btn-keluar { display:inline-flex; align-items:center; gap:8px; background:white; color:var(--dark); text-decoration:none; padding:10px 22px; border-radius:30px; font-weight:700; font-size:13px; box-shadow:0 2px 8px rgba(0,0,0,.1); }
        .main { margin-left:240px; min-height:100vh; display:flex; flex-direction:column; }
        .topbar { height:68px; background:white; display:flex; align-items:center; justify-content:space-between; padding:0 30px; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; }
        .topbar-title { font-size:20px; font-weight:700; }
        .topbar-right { display:flex; align-items:center; gap:16px; }
        .user-profile { display:flex; align-items:center; gap:12px; }
        .user-info { display:flex; flex-direction:column; }
        .avatar { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,#9ca3af,#6b7280); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; color:white; flex-shrink:0; overflow:hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-name { font-weight:600; font-size:14px; line-height:1.2; }
        .user-role { font-size:11.5px; color:var(--gray); }
        .content { padding:24px 28px; flex-grow:1; }
        .glass-card { background:white; border-radius:14px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,.04); border:none; margin-bottom:20px; }
        .amount-big { font-size:36px; font-weight:800; margin:5px 0; color:var(--dark); }
        .status-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 14px; border-radius:50px; font-size:11.5px; font-weight:700; }
        .pill-belum { background:#fee2e2; color:#ef4444; }
        .pill-lunas { background:#dcfce7; color:var(--green); }
        .pill-verif { background:#fef3c7; color:#d97706; }
        .timer-circle { width:100px; height:100px; border-radius:50%; border:6px solid #f1f5f9; display:flex; flex-direction:column; align-items:center; justify-content:center; margin-bottom:15px; position:relative; }
        .timer-circle.warning { border-color:#fee2e2; animation:pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow:0 0 0 0 rgba(239,68,68,.3); } 70% { box-shadow:0 0 0 15px rgba(239,68,68,0); } 100% { box-shadow:0 0 0 0 rgba(239,68,68,0); } }
        .btn-action { width:100%; display:flex; align-items:center; justify-content:center; gap:8px; padding:13px; border-radius:12px; font-weight:700; border:none; cursor:pointer; text-decoration:none; margin-top:15px; transition:.2s; font-size:13.5px; }
        .btn-pay { background:var(--green); color:white; }
        .btn-extend { background:#3b82f6; color:white; }
        
        /* MODAL STYLES */
        .modal-pesanan .modal-content { background:#2c3440; border-radius:20px; color:white; border:none; }
        .modal-pesanan .modal-header { border:none; padding:25px 25px 0; }
        .modal-pesanan .modal-title { font-weight:800; font-size:17px; }
        .modal-pesanan .modal-body { padding:25px; }
        .ringkasan-row { display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px; color:#9ca3af; }
        .ringkasan-val { color:white; font-weight:600; }
        .total-box { margin-top:15px; border-top:1px solid #4b5563; padding-top:15px; }
        .total-val { font-size:28px; font-weight:800; color:white; }
        .bank-box { background:white; border-radius:14px; padding:20px; text-align:center; color:#1f2937; margin:20px 0; }
        .bank-acc { font-size:24px; font-weight:800; letter-spacing:1px; }
        .upload-dashed { border:2px dashed #4b5563; border-radius:14px; padding:25px; text-align:center; margin-bottom:20px; background:rgba(255,255,255,0.02); }
        .btn-confirm-wa { background:#11a654; color:white; border:none; width:100%; padding:15px; border-radius:14px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:10px; transition:.2s; }
    </style>
</head>
<body>

<aside class="sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-brand">
        <span class="sidebar-brand-name">Elmi Sarah</span>
    </div>
        <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="dashboard.php" class="sidebar-link "><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="notifikasi.php" class="sidebar-link "><i data-lucide="bell" class="sidebar-icon"></i> Notifikasi</a></li>
        <li class="sidebar-item"><a href="pembayaran.php" class="sidebar-link active"><i data-lucide="credit-card" class="sidebar-icon"></i> Pembayaran</a></li>
        <li class="sidebar-item"><a href="riwayat_pengaduan.php" class="sidebar-link "><i data-lucide="wrench" class="sidebar-icon"></i> Pengaduan Kost</a></li>
        <li class="sidebar-item"><a href="pengumuman.php" class="sidebar-link "><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="riwayat_sewa.php" class="sidebar-link "><i data-lucide="history" class="sidebar-icon"></i> Riwayat Sewa</a></li>
        <li class="sidebar-item"><a href="informasi_kost.php" class="sidebar-link "><i data-lucide="info" class="sidebar-icon"></i> Informasi Kost</a></li>
        <li class="sidebar-item"><a href="ulasan.php" class="sidebar-link "><i data-lucide="star" class="sidebar-icon"></i> Ulasan</a></li>
        <li class="sidebar-item"><a href="profil.php" class="sidebar-link "><i data-lucide="user" class="sidebar-icon"></i> Profil Saya</a></li>
        <li class="sidebar-item"><a href="pengaturan.php" class="sidebar-link "><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-keluar"><i data-lucide="log-out" style="width:16px;height:16px;"></i> Keluar</a>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <div style="display:flex; align-items:center; gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px; height:24px;"></i></button>
            <h2 class="topbar-title">Tagihan & Sewa</h2>
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
        <?php if (!$bookingApproved): ?>
        <div class="glass-card" style="text-align:center; padding:50px 30px;">
            <i data-lucide="lock" style="width:48px;height:48px;color:#d1d5db;margin-bottom:16px;"></i>
            <h4 style="font-weight:800; margin-bottom:10px;">Pembayaran Belum Tersedia</h4>
            <?php if (!$bookingInfo): ?>
                <p style="color:var(--gray); font-size:13px;">Anda belum memiliki booking. Silakan buat booking terlebih dahulu.</p>
            <?php elseif ($bookingInfo['status_sewa'] === 'pending'): ?>
                <p style="color:var(--gray); font-size:13px;">Booking Anda masih menunggu persetujuan admin. Pembayaran akan tersedia setelah booking disetujui.</p>
            <?php elseif ($bookingInfo['status_sewa'] === 'ditolak'): ?>
                <p style="color:#ef4444; font-size:13px;">Booking Anda ditolak oleh admin. Silakan ajukan booking baru.</p>
            <?php else: ?>
                <p style="color:var(--gray); font-size:13px;">Pembayaran belum bisa dilakukan saat ini.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <div class="col-md-7">
                <div class="glass-card">
                    <?php if ($statusBulanIni === null): ?>
                        <!-- Belum ada tagihan dari admin -->
                        <span style="font-size:12px; color:var(--gray); font-weight:700; letter-spacing:1px; text-transform:uppercase;">Status Tagihan</span>
                        <div class="amount-big" style="font-size:22px; color:var(--gray); margin:10px 0;">Belum Ada Tagihan</div>
                        <div class="status-pill pill-belum" style="background:#f3f4f6; color:#6b7280;">
                            <i data-lucide="clock" style="width:14px;"></i> Menunggu Admin
                        </div>
                        <p style="font-size:12px; color:var(--gray); margin-top:12px;">Tagihan bulan ini belum dibuat oleh admin. Silakan tunggu notifikasi tagihan dari admin.</p>
                    <?php else: ?>
                        <span style="font-size:12px; color:var(--gray); font-weight:700; letter-spacing:1px; text-transform:uppercase;">
                            Tagihan
                            <?php if (!empty($pembayaranBulanIni['tanggal_bayar'])): ?>
                                <?= date('F Y', strtotime($pembayaranBulanIni['tanggal_bayar'])) ?>
                            <?php endif; ?>
                        </span>
                        <div class="amount-big">Rp <?= number_format($tagihanBulanIni, 0, ',', '.') ?></div>
                        <div class="status-pill <?= $statusBulanIni === 'valid' ? 'pill-lunas' : ($statusBulanIni === 'menunggu_verifikasi' ? 'pill-verif' : 'pill-belum') ?>">
                            <i data-lucide="<?= $statusBulanIni === 'valid' ? 'check-circle' : 'clock' ?>" style="width:14px;"></i>
                            <?= $statusBulanIni === 'valid' ? 'Sudah Lunas' : ($statusBulanIni === 'menunggu_verifikasi' ? 'Menunggu Verifikasi Admin' : 'Belum Bayar') ?>
                        </div>
                        <?php if ($statusBulanIni === 'menunggu_verifikasi'): ?>
                            <p style="font-size:12px; color:#d97706; margin-top:12px;">
                                <i data-lucide="info" style="width:13px; display:inline;"></i>
                                Bukti transfer Anda sedang diverifikasi admin. Harap tunggu konfirmasi.
                            </p>
                        <?php elseif ($statusBulanIni === 'belum_bayar' && $pembayaranId > 0): ?>
                            <button class="btn-action btn-pay" data-bs-toggle="modal" data-bs-target="#modalBayar">
                                <i data-lucide="wallet" style="width:18px;"></i> Bayar Sekarang
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-5">
                <div class="glass-card" style="text-align:center; display:flex; flex-direction:column; align-items:center;">
                    <div class="timer-circle <?= $isWarning ? 'warning' : '' ?>">
                        <div style="font-size:36px; font-weight:800;"><?= $sisaHari ?></div>
                        <div style="font-size:11px; color:var(--gray); font-weight:700;">Hari Lagi</div>
                    </div>
                    <div style="font-size:12px; color:var(--gray);">Berakhir: <strong style="color:var(--dark);"><?= $tglHabis ?></strong></div>
                    <button class="btn-action btn-extend" data-bs-toggle="modal" data-bs-target="#modalPerpanjang">
                        <i data-lucide="plus-circle" style="width:18px;"></i> Perpanjang Sewa
                    </button>
                </div>
            </div>
        </div>

        <h5 style="margin:25px 0 15px; font-weight:800; font-size:16px;">Riwayat Pembayaran</h5>
        <div class="glass-card p-0 table-responsive">
            <?php if (empty($riwayatBayar)): ?>
                <div style="text-align:center; padding:30px; color:var(--gray); font-size:13px;">
                    <i data-lucide="inbox" style="width:32px; height:32px; margin-bottom:8px; display:block; margin:0 auto 8px;"></i>
                    Belum ada riwayat pembayaran.
                </div>
            <?php else: ?>
            <table class="table table-hover mb-0" style="font-size:13.5px;">
                <thead class="table-light"><tr><th class="ps-4">Periode</th><th>Durasi</th><th>Nominal</th><th>Status</th><th class="text-end pe-4">Tanggal Bayar</th></tr></thead>
                <tbody>
                <?php foreach ($riwayatBayar as $r): ?>
                    <tr>
                        <td class="ps-4" style="font-weight:700;"><?= $r['bulan'] ?> <?= $r['tahun'] ?></td>
                        <td><?= isset($r['durasi_bulan']) && $r['durasi_bulan'] > 0 ? $r['durasi_bulan'] . ' Bulan' : '1 Bulan' ?></td>
                        <td style="font-weight:800; color:var(--green);">Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></td>
                        <td>
                            <?php 
                            $statusLabel = match($r['status']) {
                                'valid' => ['bg-success-subtle text-success', 'Lunas'],
                                'menunggu_verifikasi' => ['bg-warning-subtle text-warning', 'Menunggu Verifikasi'],
                                default => ['bg-danger-subtle text-danger', 'Belum Bayar']
                            };
                            ?>
                            <span class="badge <?= $statusLabel[0] ?> rounded-pill px-3 py-2"><?= $statusLabel[1] ?></span>
                        </td>
                        <td class="text-end pe-4 text-muted"><?= !empty($r['tanggal_bayar']) ? date('d M Y', strtotime($r['tanggal_bayar'])) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- MODAL BAYAR -->
<div class="modal fade modal-pesanan" id="modalBayar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">RINGKASAN</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="ringkasan-box">
                    <div class="ringkasan-row"><span>Harga Sewa / Bulan</span><span class="ringkasan-val">Rp <?= number_format($hargaSewa, 0, ',', '.') ?></span></div>
                    <div class="ringkasan-row"><span>Jenis</span><span class="ringkasan-val">Penuh</span></div>
                    <div class="total-box"><div style="color:#9ca3af;font-size:12px;margin-bottom:5px;">Total Bayar</div><div class="total-val">Rp <?= number_format($tagihanBulanIni, 0, ',', '.') ?></div></div>
                </div>
                <div class="bank-box" style="text-align: left; padding: 16px;">
                    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 10px;">
                        <div style="font-size:11px;color:#6b7280;margin-bottom:4px;text-transform:uppercase;font-weight:600;">Transfer Bank BRI</div>
                        <div class="bank-acc" style="font-size:20px;color:#1f2937;margin-bottom:2px;">152401000931531</div>
                        <div style="font-size:12px;color:#6b7280;">a/n ABD KHOLIK</div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:#6b7280;margin-bottom:4px;text-transform:uppercase;font-weight:600;">E-Wallet DANA</div>
                        <div class="bank-acc" style="font-size:20px;color:#1f2937;margin-bottom:2px;">0896-3456-6662</div>
                        <div style="font-size:12px;color:#6b7280;">a/n ABD KHOLIK</div>
                    </div>
                </div>
                <div class="upload-dashed"><div style="font-size:12px;color:#9ca3af;margin-bottom:15px;">Silakan unggah bukti transfer Anda</div><input type="file" id="buktiI" class="d-none" onchange="document.getElementById('pv').innerText='✓ '+this.files[0].name"><button type="button" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" onclick="document.getElementById('buktiI').click()">Unggah Bukti</button><div id="pv" style="font-size:11px;color:#11a654;margin-top:8px;"></div></div>
                <button onclick="kirimBayarWA()" class="btn-confirm-wa"><i data-lucide="message-circle"></i> Konfirmasi via WhatsApp</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PERPANJANG -->
<div class="modal fade modal-pesanan" id="modalPerpanjang" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">PERPANJANG SEWA</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-4"><label style="font-size:12px;color:#9ca3af;margin-bottom:8px;display:block;">Pilih Durasi</label><select id="durI" class="form-select bg-dark text-white border-secondary rounded-3" onchange="uT()"><option value="1">1 Bulan</option><option value="3">3 Bulan</option><option value="6">6 Bulan</option><option value="12">12 Bulan</option></select></div>
                <div class="total-box" style="border:none;padding:0;margin-bottom:20px;"><div style="color:#9ca3af;font-size:12px;margin-bottom:5px;">Total Biaya Baru</div><div class="total-val" id="tV">Rp <?= number_format($hargaSewa, 0, ',', '.') ?></div></div>
                <div class="bank-box" style="text-align: left; padding: 16px;">
                    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 10px;">
                        <div style="font-size:11px;color:#6b7280;margin-bottom:4px;text-transform:uppercase;font-weight:600;">Transfer Bank BRI</div>
                        <div class="bank-acc" style="font-size:20px;color:#1f2937;margin-bottom:2px;">152401000931531</div>
                        <div style="font-size:12px;color:#6b7280;">a/n ABD KHOLIK</div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:#6b7280;margin-bottom:4px;text-transform:uppercase;font-weight:600;">E-Wallet DANA</div>
                        <div class="bank-acc" style="font-size:20px;color:#1f2937;margin-bottom:2px;">0896-3456-6662</div>
                        <div style="font-size:12px;color:#6b7280;">a/n ABD KHOLIK</div>
                    </div>
                </div>
                <div class="upload-dashed"><div style="font-size:12px;color:#9ca3af;margin-bottom:15px;">Silakan unggah bukti transfer perpanjangan</div><input type="file" id="buktiExt" class="d-none" onchange="document.getElementById('pvExt').innerText='✓ '+this.files[0].name"><button type="button" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" onclick="document.getElementById('buktiExt').click()">Unggah Bukti</button><div id="pvExt" style="font-size:11px;color:#11a654;margin-top:8px;"></div></div>
                <button onclick="prosesPerpanjang()" id="btnSubmitExt" class="btn-confirm-wa"><i data-lucide="send"></i> Ajukan & Konfirmasi</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();
    const hs = <?= $hargaSewa ?>;
    function uT() { const b = document.getElementById('durI').value; document.getElementById('tV').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(hs * b); }
    
    function kirimBayarWA() {
        const fileInput = document.getElementById('buktiI');
        if (!fileInput.files[0]) { alert("Upload bukti transfer terlebih dahulu!"); return; }
        
        const btn = document.querySelector('.btn-confirm-wa');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses Upload...';

        const formData = new FormData();
        formData.append('pembayaran_id', '<?= $pembayaranId ?>');
        formData.append('bukti', fileInput.files[0]);

        fetch('../api/pembayaran/upload_bukti.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert("Bukti pembayaran berhasil diunggah! Melanjutkan ke WhatsApp untuk konfirmasi...");
                const m = encodeURIComponent(`Halo Admin, saya *<?= addslashes($namaUser) ?>* (Kamar <?= $nomorKamar ?>) konfirmasi pembayaran tagihan bulanan *Rp <?= number_format($tagihanBulanIni,0,',','.') ?>*. Bukti transfer sudah saya upload di aplikasi.`);
                window.open(`https://wa.me/<?= $adminWA ?>?text=${m}`, '_blank');
                location.reload();
            } else {
                alert("Gagal mengupload bukti: " + d.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(err => {
            console.error(err);
            alert("Terjadi kesalahan sistem saat mengunggah bukti.");
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    function prosesPerpanjang() {
        const fileInput = document.getElementById('buktiExt');
        if (!fileInput.files[0]) { alert("Upload bukti bayar dulu!"); return; }
        
        const bulan = document.getElementById('durI').value;
        const total = hs * bulan;
        const btn = document.getElementById('btnSubmitExt');
        btn.disabled = true;
        btn.innerText = "Memproses...";

        const formData = new FormData();
        formData.append('booking_id', '<?= $bookingId ?>');
        formData.append('bulan', bulan);
        formData.append('jumlah', total);
        formData.append('bukti', fileInput.files[0]);

        fetch('../api/penghuni/proses_perpanjang.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert(d.message);
                const m = encodeURIComponent(d.wa_msg);
                window.open(`https://wa.me/<?= $adminWA ?>?text=${m}`, '_blank');
                location.reload();
            } else {
                alert("Gagal: " + d.message);
                btn.disabled = false;
                btn.innerText = "Ajukan & Konfirmasi";
            }
        })
        .catch(err => {
            alert("Terjadi kesalahan sistem.");
            btn.disabled = false;
        });
    }
</script>
<script src="../assets/js/notifikasi.js"></script>
</body>
</html>
