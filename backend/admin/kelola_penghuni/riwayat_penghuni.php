<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../api/auth/login.php");
    exit;
}

// Query Update: Tampilkan semua data booking dengan status 'selesai' (mantan penghuni)
try {
    $stmt = $conn->query("
        SELECT 
            u.id, u.nama, u.email, u.no_hp,
            k.nomor_kamar as no_kamar, k.tipe,
            b.tanggal_masuk, b.durasi_bulan, b.id as booking_id
        FROM users u
        JOIN booking b ON u.id = b.user_id 
        JOIN kamar k ON b.kamar_id = k.id
        WHERE b.status = 'selesai'
        ORDER BY b.id DESC
    ");
    $penghuni = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $penghuni = [];
    $error_msg = $e->getMessage();
}

$totalPenghuni = count($penghuni);
$totalAktif = count(array_filter($penghuni, fn($p) => ($p['status'] ?? 'aktif') === 'aktif'));
$totalJatuhTempo = 0;

foreach($penghuni as &$p) {
    $p['sisa_hari'] = '-';
    if (!empty($p['tanggal_masuk']) && !empty($p['durasi_bulan'])) {
        $tglMasuk = new DateTime($p['tanggal_masuk']);
        $durasi = (int)$p['durasi_bulan'];
        $tglHabis = clone $tglMasuk;
        $tglHabis->modify("+$durasi month");
        
        $today = new DateTime();
        if ($tglHabis < $today) {
            $p['sisa_hari'] = 0;
            $totalJatuhTempo++;
        } else {
            $diff = $today->diff($tglHabis);
            $p['sisa_hari'] = $diff->days;
            if ($p['sisa_hari'] <= 5) $totalJatuhTempo++;
        }
    }
}
unset($p);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Penghuni - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <style>
        :root { --admin-green: #11a654; --admin-bg: #f4f6f8; --admin-text-dark: #1f2937; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--admin-bg); margin: 0; color: var(--admin-text-dark); overflow-x: hidden; }
        .admin-sidebar { width: 240px; height: 100vh; background-color: var(--admin-green); position: fixed; top: 0; left: 0; display: flex; flex-direction: column; color: white; z-index: 1000; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 10px rgba(0,0,0,0.03); }
        .sidebar-brand { padding: 30px 25px; font-size: 22px; font-weight: 800; color: white; }
        .sidebar-menu { list-style: none; padding: 0 15px; flex-grow: 1; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 12px 18px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 12px; transition: all 0.2s; }
        .sidebar-link:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar-link.active { background: white; color: var(--admin-green); font-weight: 700; }
        .sidebar-icon { width: 18px; height: 18px; }
        .sidebar-footer { padding: 20px 15px 25px; }
        .btn-exit { display: inline-flex; align-items: center; gap: 8px; background: white; color: var(--admin-text-dark); text-decoration: none; padding: 10px 22px; border-radius: 30px; font-weight: 700; font-size: 13px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .admin-main { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar { height: 68px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 100; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }
        .stat-card { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); height: 100%; border: none; }
        .stat-card-title { font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card-value { font-size: 32px; font-weight: 800; color: var(--admin-text-dark); }
        .p-table-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); margin-top: 25px; overflow: visible; }
        .p-table { width: 100%; border-collapse: collapse; }
        .p-table thead { background: var(--admin-green); color: white; }
        .p-table th { padding: 16px 20px; font-size: 13px; font-weight: 600; text-align: left; }
        .p-table td { padding: 18px 20px; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .p-table tr:last-child td { border-bottom: none; }
        .badge-room { background: #f1f5f9; color: #475569; padding: 5px 14px; border-radius: 50px; font-size: 11.5px; font-weight: 700; }
        .badge-sisa { padding: 5px 14px; border-radius: 50px; font-size: 11.5px; font-weight: 700; }
        .badge-warning { background: #fee2e2; color: #ef4444; }
        .badge-safe { background: #dcfce7; color: #11a654; }
        .dropdown-menu { border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 8px; font-size: 13.5px; z-index: 1050; }
        .dropdown-item { border-radius: 8px; padding: 8px 15px; font-weight: 500; }
        .dropdown-item:hover { background-color: #f8fafc; color: var(--admin-green); }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-brand">Elmi Sarah</div>
    <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="../dashboard.php" class="sidebar-link"><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="list_penghuni.php" class="sidebar-link active"><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li class="sidebar-item"><a href="../kelola_user/list_user.php" class="sidebar-link"><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="../kelola_kamar/list_kamar.php" class="sidebar-link"><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li class="sidebar-item"><a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link"><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
        <li class="sidebar-item"><a href="../kelola_booking/list_booking.php" class="sidebar-link"><i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking</a></li>
        <li class="sidebar-item"><a href="../kelola_pengaduan/list_pengaduan.php" class="sidebar-link"><i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan</a></li>
        <li class="sidebar-item"><a href="../kelola_pengumuman/list_pengumuman.php" class="sidebar-link"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item">
            <a href="../kelola_ulasan/list_ulasan.php" class="sidebar-link">
                <i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan
            </a>
        </li>
        <li class="sidebar-item"><a href="../pengaturan.php" class="sidebar-link"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../../logout.php" class="btn-exit"><i data-lucide="log-out" style="width:16px;height:16px;"></i> Keluar</a>
    </div>
</aside>

<div class="admin-main">
    <header class="admin-topbar">
        <div style="display:flex; align-items:center; gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <a href="list_penghuni.php" style="color:#64748b; text-decoration:none;"><i data-lucide="arrow-left" style="width:20px;"></i></a>
            <h2 style="font-size: 20px; font-weight: 800; margin: 0;">Riwayat Penghuni</h2>
        </div>
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="text-align:right;">
                <div style="font-size:13.5px; font-weight:700;"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
                <div style="font-size:11px; color:#64748b; font-weight:500;">Administrator</div>
            </div>
            <div style="width:38px; height:38px; border-radius:50%; background:#d1d5db; display:flex; align-items:center; justify-content:center; font-weight:800; color:white;">A</div>
        </div>
    </header>

    <main class="admin-content">
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger">Error: <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-4"><div class="stat-card"><div class="stat-card-title">Total Riwayat</div><div class="stat-card-value"><?= $totalPenghuni ?></div></div></div>
        </div>

        <div class="p-table-card">
            <table class="p-table">
                <thead>
                    <tr>
                        <th>Mantan Penghuni</th>
                        <th>Kamar Terakhir</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($penghuni)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada data riwayat penghuni.</td></tr>
                <?php else: foreach ($penghuni as $p): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700;"><?= htmlspecialchars($p['nama']) ?></div>
                            <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($p['email']) ?></div>
                        </td>
                        <td><span class="badge-room">Kamar <?= htmlspecialchars($p['no_kamar'] ?? '-') ?></span></td>
                        <td style="color:#64748b; font-weight:600; font-size:13px;"><?= htmlspecialchars($p['no_hp'] ?? '-') ?></td>
                        <td>
                            <div style="font-size:11px; font-weight:800; padding:4px 10px; border-radius:50px; display:inline-block; border:1px solid #64748b; color:#64748b;">
                                SUDAH KELUAR
                            </div>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-light border fw-bold" style="font-size:12px;" href="detail_penghuni.php?id=<?= $p['id'] ?>">Lihat Detail</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
