<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$tab = $_GET['tab'] ?? 'aktif';
if (!in_array($tab, ['aktif', 'riwayat', 'batal'])) {
    $tab = 'aktif';
}

$statusFilter = "";
if ($tab === 'aktif') {
    $statusFilter = "AND booking.status IN ('pending', 'menunggu_dp', 'disetujui', 'aktif')";
} elseif ($tab === 'riwayat') {
    $statusFilter = "AND booking.status = 'selesai'";
} elseif ($tab === 'batal') {
    $statusFilter = "AND booking.status IN ('dibatalkan', 'ditolak')";
}

// Fetch booking data from DB with full user info & Payment Proof
try {
    $stmtB = $conn->prepare("
        SELECT booking.*, 
               users.nama, users.no_hp, users.email, users.alamat, users.foto_ktp, users.no_ktp,
               kamar.tipe, kamar.nomor_kamar as nomor,
               (SELECT p.bukti_bayar FROM pembayaran p WHERE p.booking_id = booking.id ORDER BY p.id DESC LIMIT 1) as bukti_bayar,
               (SELECT p.metode FROM pembayaran p WHERE p.booking_id = booking.id ORDER BY p.id DESC LIMIT 1) as metode_bayar,
               (SELECT p.jumlah FROM pembayaran p WHERE p.booking_id = booking.id ORDER BY p.id DESC LIMIT 1) as jumlah_bayar
        FROM booking
        JOIN users ON booking.user_id = users.id
        JOIN kamar ON booking.kamar_id = kamar.id
        WHERE 1=1 $statusFilter
        ORDER BY booking.id DESC
    ");
    $stmtB->execute();
    $bookings = $stmtB->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $bookings = [];
}

function getStatusBadge($status, $buktiBayar = null) {
    $s = strtolower($status);
    if ($s === 'menunggu_dp' && !empty($buktiBayar)) {
        return ['label'=>'Menunggu Verifikasi', 'color'=>'#0284c7', 'bg'=>'#e0f2fe', 'icon'=>false];
    }
    return match(true) {
        $s === 'lunas'          => ['label'=>'Lunas',           'color'=>'#11a654', 'bg'=>'#e8f7f0', 'icon'=>true],
        $s === 'menunggu_dp'    => ['label'=>'Menunggu DP',     'color'=>'#d97706', 'bg'=>'#fef3c7', 'icon'=>false],
        $s === 'dijadwalkan'    => ['label'=>'Dijadwalkan',     'color'=>'#6b7280', 'bg'=>'#f3f4f6', 'icon'=>false],
        $s === 'pending'        => ['label'=>'Pending',         'color'=>'#d97706', 'bg'=>'#fef3c7', 'icon'=>false],
        $s === 'disetujui'      => ['label'=>'Disetujui',       'color'=>'#11a654', 'bg'=>'#e8f7f0', 'icon'=>true],
        $s === 'ditolak'        => ['label'=>'Ditolak',         'color'=>'#ef4444', 'bg'=>'#fee2e2', 'icon'=>false],
        $s === 'dibatalkan'     => ['label'=>'Dibatalkan',      'color'=>'#ef4444', 'bg'=>'#fee2e2', 'icon'=>false],
        $s === 'menunggu_batal' => ['label'=>'Menunggu Batal',  'color'=>'#d97706', 'bg'=>'#fef3c7', 'icon'=>false],
        $s === 'selesai'        => ['label'=>'Selesai',         'color'=>'#11a654', 'bg'=>'#e8f7f0', 'icon'=>true],
        default                 => ['label'=>ucfirst($status),  'color'=>'#6b7280', 'bg'=>'#f3f4f6', 'icon'=>false],
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --admin-green: #11a654; --admin-bg: #f4f6f8; --admin-text-dark: #1f2937; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--admin-bg); margin: 0; overflow-x: hidden; color: var(--admin-text-dark); }
        .admin-sidebar { width: 240px; height: 100vh; background-color: var(--admin-green); position: fixed; top: 0; left: 0; display: flex; flex-direction: column; color: white; z-index: 1000; border-top-right-radius: 15px; border-bottom-right-radius: 15px; box-shadow: 4px 0 10px rgba(0,0,0,0.03); }
        .sidebar-header { padding: 25px 20px; display: flex; align-items: center; justify-content: space-between; }
        .sidebar-brand { font-size: 22px; font-weight: 700; color: white; text-decoration: none; margin: 0; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .sidebar-item { padding-left: 15px; margin-bottom: 5px; }
        .sidebar-link { display: flex; align-items: center; padding: 10px 20px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 13.5px; font-weight: 500; border-top-left-radius: 25px; border-bottom-left-radius: 25px; transition: all 0.2s ease; }
        .sidebar-link:hover { color: white; background-color: rgba(255,255,255,0.1); }
        .sidebar-link.active { background-color: var(--admin-bg); color: var(--admin-green); font-weight: 600; }
        .sidebar-icon { width: 18px; height: 18px; margin-right: 12px; }
        .sidebar-footer { padding: 20px 15px; margin-bottom: 15px; }
        .btn-keluar { display: inline-flex; align-items: center; background-color: white; color: var(--admin-text-dark); text-decoration: none; padding: 8px 20px; border-radius: 25px; font-weight: 600; font-size: 13px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .admin-main { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar { height: 70px; background-color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; border-bottom: 1px solid #e5e7eb; }
        .page-title { font-size: 20px; font-weight: 600; color: var(--admin-text-dark); margin: 0; }
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .notification-btn { background: none; border: none; color: var(--admin-text-dark); }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 38px; height: 38px; background-color: #d1d5db; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; }
        .user-name { font-weight: 600; font-size: 13.5px; color: var(--admin-text-dark); line-height: 1.2; }
        .user-role { font-size: 11px; color: #9ca3af; font-weight: 500; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }
        .section-title { font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px; margin-top: 8px; }
        .bv-table-wrapper { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 36px; }
        .bv-table { width: 100%; border-collapse: collapse; }
        .bv-table thead tr { background-color: var(--admin-green); color: white; }
        .bv-table thead th { padding: 13px 18px; font-size: 13px; font-weight: 500; text-align: left; }
        .bv-table thead th:first-child { border-top-left-radius: 12px; }
        .bv-table thead th:last-child { border-top-right-radius: 12px; }
        .bv-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
        .bv-table tbody tr:hover { background-color: #fafafa; }
        .bv-table tbody td { padding: 13px 18px; font-size: 13px; color: #374151; vertical-align: middle; }
        .badge-room { display: inline-flex; align-items: center; justify-content: center; background-color: #f3f4f6; color: #374151; border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 500; }
        .badge-status { display: inline-flex; align-items: center; gap: 5px; border-radius: 20px; padding: 5px 14px; font-size: 11.5px; font-weight: 600; }
        .action-icons { display: flex; align-items: center; gap: 10px; }
        .action-btn { background: none; border: none; padding: 0; color: #9ca3af; cursor: pointer; transition: color 0.2s; text-decoration: none; }
        .action-btn:hover { color: #374151; }
        .td-phone { color: #9ca3af; }
        .td-date { font-weight: 600; }

        /* Modal Styles */
        .modal-header-custom { background-color: var(--admin-green); color: white; border-top-left-radius: 12px; border-top-right-radius: 12px; padding: 20px 25px; }
        .modal-content { border-radius: 12px; border: none; }
        .detail-label { font-size: 11px; text-transform: uppercase; color: #9ca3af; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 2px; }
        .detail-value { font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 15px; }
        .detail-section-title { font-size: 13px; font-weight: 700; color: var(--admin-green); margin: 20px 0 15px; border-bottom: 1px solid #f3f4f6; padding-bottom: 5px; }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-header"><h1 class="sidebar-brand">Elmi Sarah</h1></div>
    <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="../dashboard.php" class="sidebar-link"><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="../kelola_penghuni/list_penghuni.php" class="sidebar-link"><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li class="sidebar-item"><a href="../kelola_user/list_user.php" class="sidebar-link"><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="../kelola_kamar/list_kamar.php" class="sidebar-link"><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li class="sidebar-item"><a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link"><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
        <li class="sidebar-item"><a href="../kelola_pengaduan/list_pengaduan.php" class="sidebar-link"><i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan</a></li>
        <li class="sidebar-item"><a href="list_booking.php" class="sidebar-link active"><i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking</a></li>
        <li class="sidebar-item"><a href="../kelola_pengumuman/list_pengumuman.php" class="sidebar-link"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item">
            <a href="../kelola_ulasan/list_ulasan.php" class="sidebar-link">
                <i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan
            </a>
        </li>
        <li class="sidebar-item"><a href="../pengaturan.php" class="sidebar-link"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer"><a href="../../logout.php" class="btn-keluar"><i data-lucide="log-out" class="sidebar-icon" style="color:#1f2937; margin-right:8px;"></i> Keluar</a></div>
</aside>

<div class="admin-main">
    <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 class="page-title">Kelola Booking</h2>
        </div>
        <div class="topbar-right">
            <button class="notification-btn"><i data-lucide="bell" style="width:20px; height:20px;"></i></button>
            <div class="user-profile"><div class="avatar"></div><div><div class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div><div class="user-role">Admin</div></div></div>
        </div>
    </header>

    <main class="admin-content">
        <?php if (isset($_GET["success"])): ?>
            <div class="alert alert-success mb-3" style="font-size:13px; border-radius:10px; border-left:4px solid #11a654;">
                ✓ <?= htmlspecialchars($_GET["success"]) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET["error"])): ?>
            <div class="alert alert-danger mb-3" style="font-size:13px; border-radius:10px; border-left:4px solid #ef4444;">
                ✗ <?= htmlspecialchars($_GET["error"]) ?>
            </div>
        <?php endif; ?>
        <h3 class="section-title">Daftar Booking</h3>
        <div class="mb-4 d-flex gap-2">
            <a href="?tab=aktif" class="btn btn-sm <?= $tab === 'aktif' ? 'text-white' : 'btn-outline-secondary' ?>" style="border-radius:20px; font-weight:600; padding:8px 20px; <?= $tab === 'aktif' ? 'background-color: var(--admin-green); border-color: var(--admin-green);' : '' ?>">Aktif (Sedang Berjalan)</a>
            <a href="?tab=riwayat" class="btn btn-sm <?= $tab === 'riwayat' ? 'text-white' : 'btn-outline-secondary' ?>" style="border-radius:20px; font-weight:600; padding:8px 20px; <?= $tab === 'riwayat' ? 'background-color: var(--admin-green); border-color: var(--admin-green);' : '' ?>">Riwayat (Selesai)</a>
            <a href="?tab=batal" class="btn btn-sm <?= $tab === 'batal' ? 'text-white' : 'btn-outline-secondary' ?>" style="border-radius:20px; font-weight:600; padding:8px 20px; <?= $tab === 'batal' ? 'background-color: var(--admin-green); border-color: var(--admin-green);' : '' ?>">Batal / Ditolak</a>
        </div>
        <div class="bv-table-wrapper">
            <table class="bv-table">
                <thead><tr><th>Nama</th><th>No Hp</th><th>Tanggal Booking / Masuk</th><th>Kamar</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="6" class="text-center py-4" style="color:#9ca3af;">Belum ada data booking</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b):
                        $badge = getStatusBadge($b['status'], $b['bukti_bayar']);
                        $tglBook = isset($b['created_at']) ? date('j M Y', strtotime($b['created_at'])) : '-';
                        $tglMasuk = isset($b['tanggal_masuk']) ? date('j M Y', strtotime($b['tanggal_masuk'])) : '-';
                        
                        // KTP Path logic
                        $pathKtp = "../../uploads/profil/" . $b['foto_ktp'];
                        if (!file_exists(__DIR__ . "/../../uploads/profil/" . $b['foto_ktp'])) {
                            $pathKtp = "../../../frontend/assets/image/" . $b['foto_ktp'];
                        }
                        $pathBukti = "../../../frontend/assets/image/bukti/" . $b['bukti_bayar'];
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($b['nama']) ?></div>
                            <div class="mt-1"><button class="btn p-0 text-primary" style="font-size:11px;"
                                data-booking="<?= htmlspecialchars(json_encode($b, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG), ENT_QUOTES) ?>"
                                data-ktp="<?= htmlspecialchars($pathKtp, ENT_QUOTES) ?>"
                                data-bukti="<?= htmlspecialchars($pathBukti, ENT_QUOTES) ?>"
                                onclick="showDetail(this)"><i data-lucide="eye" style="width:12px; height:12px;"></i> Lihat Detail</button></div>
                        </td>
                        <td class="td-phone"><?= htmlspecialchars($b['no_hp'] ?? '-') ?></td>
                        <td><div class="td-date" style="font-size:12px;">Book: <?= $tglBook ?></div><div class="td-date" style="font-size:12px; color:#11a654;">Masuk: <?= $tglMasuk ?></div></td>
                        <td><div style="font-size:11px; color:#6b7280; font-weight:500; margin-bottom:2px;"><?= htmlspecialchars($b['tipe']) ?></div><span class="badge-room">No. <?= htmlspecialchars($b['nomor'] ?? '-') ?></span></td>
                        <td><span class="badge-status" style="background-color:<?= $badge['bg'] ?>; color:<?= $badge['color'] ?>;"><?php if ($badge['icon']): ?><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?php endif; ?> <?= $badge['label'] ?></span></td>
                        <td>
                            <div class="action-icons">
                                <a href="edit_booking.php?id=<?= $b['id'] ?>" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:15px; height:15px;"></i></a>
                                <div class="dropdown">
                                    <button class="action-btn" data-bs-toggle="dropdown"><i data-lucide="more-vertical" style="width:15px; height:15px;"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:13px;">
                                        <?php 
                                        $statusLow = strtolower($b['status']);
                                        $hasBukti = !empty($b['bukti_bayar']);
                                        if ($statusLow === 'pending'): ?>
                                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault();confirmAction('proses_booking.php?id=<?= $b['id'] ?>&aksi=setujui','Setujui booking ini?','success')">✔ Setujui</a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault();showRejectModal(<?= $b['id'] ?>)">✕ Tolak</a></li>
                                        <?php elseif ($statusLow === 'menunggu_dp' && $hasBukti): ?>
                                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault();confirmAction('proses_booking.php?id=<?= $b['id'] ?>&aksi=setujui','Setujui booking ini?','success')">✔ Setujui</a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault();showRejectModal(<?= $b['id'] ?>)">✕ Tolak</a></li>
                                        <?php elseif ($statusLow === 'menunggu_dp' && !$hasBukti): ?>
                                            <li><span class="dropdown-item text-muted" style="font-size:11px; cursor:default;">⏳ Menunggu bukti bayar</span></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault();showRejectModal(<?= $b['id'] ?>)">✕ Tolak</a></li>
                                        <?php elseif ($statusLow === 'disetujui'): ?>
                                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault();confirmAction('jadikan_penghuni.php?id=<?= $b['id'] ?>','Jadikan user ini sebagai penghuni?','success')">👤 Jadikan Penghuni</a></li>
                                        <?php elseif ($statusLow === 'aktif'): ?>
                                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault();confirmAction('proses_booking.php?id=<?= $b['id'] ?>&aksi=selesai','Tandai booking ini sebagai selesai?','success')">✅ Tandai Selesai</a></li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault();confirmAction('hapus_booking.php?id=<?= $b['id'] ?>','Hapus booking ini permanen? Tidak bisa dibatalkan.','danger')">🗑 Hapus</a></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- MODAL DETAIL BOOKING -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" style="font-weight:700;">Detail Lengkap Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-section-title">Informasi Penyewa</div>
                        <div class="detail-label">Nama Lengkap</div><div class="detail-value" id="det-nama"></div>
                        <div class="detail-label">Email</div><div class="detail-value" id="det-email"></div>
                        <div class="detail-label">No. Telepon / WA</div><div class="detail-value" id="det-hp"></div>
                        <div class="detail-label">No. KTP</div><div class="detail-value" id="det-ktp"></div>
                        <div class="detail-label">Alamat Asal</div><div class="detail-value" id="det-alamat"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-section-title">Informasi Kamar & Sewa</div>
                        <div class="detail-label">Tipe Kamar</div><div class="detail-value" id="det-tipe"></div>
                        <div class="detail-label">Nomor Kamar</div><div class="detail-value" id="det-no"></div>
                        <div class="detail-label">Tanggal Masuk</div><div class="detail-value" id="det-masuk"></div>
                        <div class="detail-label">Durasi Sewa</div><div class="detail-value" id="det-durasi"></div>
                        <div class="detail-label">Catatan Tambahan</div><div class="detail-value" id="det-catatan"></div>
                    </div>
                </div>
                <div class="row" id="det-reason-wrapper" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-section-title text-danger">Alasan Penolakan</div>
                        <div class="detail-value p-3 border border-danger-subtle bg-danger-subtle text-danger rounded mb-3" id="det-alasan" style="font-style: italic;"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="detail-section-title">Dokumen & Pembayaran</div>
                        <div class="d-flex flex-wrap gap-4">
                            <div><div class="detail-label">Metode Bayar</div><div class="detail-value" id="det-metode"></div></div>
                            <div><div class="detail-label">Jumlah Bayar</div><div class="detail-value text-success" id="det-jumlah"></div></div>
                        </div>
                        <div class="d-flex gap-3 mt-2">
                            <a id="btn-view-ktp" href="#" target="_blank" class="btn btn-outline-success btn-sm"><i data-lucide="user-square-2" style="width:14px; height:14px;"></i> Lihat Foto KTP</a>
                            <a id="btn-view-bukti" href="#" target="_blank" class="btn btn-outline-primary btn-sm"><i data-lucide="image" style="width:14px; height:14px;"></i> Lihat Bukti Bayar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PENOLAKAN BOOKING -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-custom" style="background-color: #ef4444;">
                <h5 class="modal-title" style="font-weight:700;">Tolak Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="proses_booking.php" method="POST">
                <input type="hidden" name="id" id="reject-booking-id">
                <input type="hidden" name="aksi" value="tolak">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="alasan_penolakan" class="form-label" style="font-weight: 600; font-size: 14px;">Alasan Penolakan</label>
                        <textarea class="form-control" name="alasan" id="alasan_penolakan" rows="4" placeholder="Masukkan alasan mengapa booking ini ditolak..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: none;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-danger" style="border-radius: 8px; background-color: #ef4444; border-color: #ef4444;">Kirim & Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI AKSI -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border:none; border-radius:16px; overflow:hidden;">
            <div class="modal-body text-center p-4">
                <div id="confirm-icon-wrapper" style="width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i data-lucide="help-circle" id="confirm-icon" style="width:32px;height:32px;"></i>
                </div>
                <h5 style="font-weight:700;font-size:16px;margin-bottom:8px;color:#1f2937;">Konfirmasi Aksi</h5>
                <p id="confirm-msg" style="font-size:13.5px;color:#6b7280;margin-bottom:24px;"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:10px;padding:10px 28px;font-weight:600;font-size:13px;">Batal</button>
                    <button type="button" id="confirm-yes-btn" class="btn" style="border-radius:10px;padding:10px 28px;font-weight:600;font-size:13px;color:white;" onclick="doConfirmAction()">Ya, Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();

    // === CONFIRM ACTION MODAL ===
    var pendingActionUrl = '';

    function confirmAction(url, msg, color) {
        pendingActionUrl = url;
        document.getElementById('confirm-msg').textContent = msg;

        var iconWrap = document.getElementById('confirm-icon-wrapper');
        var yesBtn   = document.getElementById('confirm-yes-btn');
        var icon     = iconWrap.firstElementChild;

        if (color === 'danger') {
            iconWrap.style.background = '#fee2e2';
            if (icon) icon.style.color = '#ef4444';
            yesBtn.style.backgroundColor = '#ef4444';
            yesBtn.style.borderColor = '#ef4444';
        } else {
            iconWrap.style.background = '#e8f7f0';
            if (icon) icon.style.color = '#11a654';
            yesBtn.style.backgroundColor = '#11a654';
            yesBtn.style.borderColor = '#11a654';
        }

        var confirmModal = new bootstrap.Modal(document.getElementById('confirmActionModal'));
        confirmModal.show();
    }

    function doConfirmAction() {
        if (pendingActionUrl) {
            window.location.href = pendingActionUrl;
        }
    }

    // === REJECT MODAL ===
    function showRejectModal(id) {
        document.getElementById('reject-booking-id').value = id;
        document.getElementById('alasan_penolakan').value = '';
        var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        rejectModal.show();
    }

    // === DETAIL MODAL ===
    function showDetail(btn) {
        let data, pathKtp, pathBukti;
        try {
            data      = JSON.parse(btn.dataset.booking);
            pathKtp   = btn.dataset.ktp;
            pathBukti = btn.dataset.bukti;
        } catch(e) {
            alert('Gagal memuat detail booking.');
            return;
        }

        document.getElementById('det-nama').innerText    = data.nama    || '-';
        document.getElementById('det-email').innerText   = data.email   || '-';
        document.getElementById('det-hp').innerText      = data.no_hp   || '-';
        document.getElementById('det-ktp').innerText     = data.no_ktp  || '-';
        document.getElementById('det-alamat').innerText  = data.alamat  || '-';
        document.getElementById('det-tipe').innerText    = data.tipe    || '-';
        document.getElementById('det-no').innerText      = data.nomor   || '-';
        document.getElementById('det-masuk').innerText   = data.tanggal_masuk || '-';
        document.getElementById('det-durasi').innerText  = (data.durasi_bulan || 0) + ' Bulan';
        document.getElementById('det-catatan').innerText = data.catatan || '-';
        document.getElementById('det-metode').innerText  = (data.metode_bayar || 'QRIS').toUpperCase();
        document.getElementById('det-jumlah').innerText  = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.jumlah_bayar || 0);

        const reasonWrap = document.getElementById('det-reason-wrapper');
        if (data.status === 'ditolak') {
            reasonWrap.style.display = 'block';
            document.getElementById('det-alasan').innerText = data.alasan_penolakan || 'Tidak ada alasan spesifik.';
        } else {
            reasonWrap.style.display = 'none';
        }

        const btnKtp   = document.getElementById('btn-view-ktp');
        const btnBukti = document.getElementById('btn-view-bukti');
        btnKtp.href    = pathKtp;
        btnBukti.href  = pathBukti;
        btnBukti.style.display = data.bukti_bayar ? 'inline-flex' : 'none';

        new bootstrap.Modal(document.getElementById('detailModal')).show();
        setTimeout(() => lucide.createIcons(), 200);
    }
</script>
</body>
</html>
