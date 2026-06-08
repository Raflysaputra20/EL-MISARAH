<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../api/auth/login.php");
    exit;
}

// Query Update: Hanya tampilkan orang dengan status 'aktif'
// Dan pastikan tidak ada duplikasi data booking per user
try {
    $stmt = $conn->query("
        SELECT 
            u.id, u.nama, u.email, u.no_hp, u.status, u.created_at,
            k.nomor_kamar as no_kamar, k.tipe,
            b.tanggal_masuk, b.durasi_bulan, b.id as booking_id, b.status as booking_status
        FROM users u
        LEFT JOIN booking b ON u.id = b.user_id 
            AND b.id = (SELECT MAX(id) FROM booking b2 WHERE b2.user_id = u.id)
        LEFT JOIN kamar k ON b.kamar_id = k.id
        WHERE u.role = 'penghuni'
          AND u.status = 'aktif'
        ORDER BY b.id DESC, u.id DESC
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
            if ($today < $tglMasuk) {
                // Belum masuk, sisa hari adalah sewa penuh
                $diff = $tglMasuk->diff($tglHabis);
                $p['sisa_hari'] = $diff->days;
            } else {
                // Sudah masuk, sisa hari berkurang setiap hari
                $diff = $today->diff($tglHabis);
                $p['sisa_hari'] = $diff->days;
                if ($p['sisa_hari'] <= 5) $totalJatuhTempo++;
            }
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
    <title>Penghuni Kost - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --admin-green: #11a654; --admin-bg: #f4f6f8; --admin-text-dark: #1f2937; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--admin-bg); margin: 0; color: var(--admin-text-dark); overflow-x: hidden; }
        .admin-sidebar {
            width: 240px; height: 100vh; background-color: var(--admin-green);
            position: fixed; top: 0; left: 0;
            display: flex; flex-direction: column; color: white; z-index: 1000;
            border-top-right-radius: 15px; border-bottom-right-radius: 15px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.03);
        }
        .sidebar-header { padding: 25px 20px; display: flex; align-items: center; justify-content: space-between; }
        .sidebar-brand { font-size: 22px; font-weight: 700; color: white; text-decoration: none; margin: 0; letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .sidebar-item { padding-left: 15px; margin-bottom: 5px; }
        .sidebar-link {
            display: flex; align-items: center; padding: 10px 20px;
            color: rgba(255,255,255,0.85); text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            border-top-left-radius: 25px; border-bottom-left-radius: 25px;
            transition: all 0.2s ease;
        }
        .sidebar-link:hover { color: white; background-color: rgba(255,255,255,0.1); }
        .sidebar-link.active { background-color: var(--admin-bg); color: var(--admin-green); font-weight: 600; box-shadow: -3px 0 8px rgba(0,0,0,0.02); }
        .sidebar-icon { width: 18px; height: 18px; margin-right: 12px; }
        .sidebar-footer { padding: 20px 15px; margin-bottom: 15px; }
        .btn-keluar {
            display: inline-flex; align-items: center;
            background-color: white; color: var(--admin-text-dark);
            text-decoration: none; padding: 8px 20px;
            border-radius: 25px; font-weight: 600; font-size: 13px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .btn-keluar:hover { background-color: #f3f4f6; color: var(--admin-text-dark); }
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
    <div class="sidebar-header">
        <h1 class="sidebar-brand">Elmi Sarah</h1>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="../dashboard.php" class="sidebar-link "><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="../kelola_penghuni/list_penghuni.php" class="sidebar-link active"><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li class="sidebar-item"><a href="../kelola_user/list_user.php" class="sidebar-link "><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="../kelola_kamar/list_kamar.php" class="sidebar-link "><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li class="sidebar-item"><a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link "><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
        <li class="sidebar-item"><a href="../kelola_pengaduan/list_pengaduan.php" class="sidebar-link "><i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan</a></li>
        <li class="sidebar-item"><a href="../kelola_booking/list_booking.php" class="sidebar-link "><i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking</a></li>
        <li class="sidebar-item"><a href="../kelola_pengumuman/list_pengumuman.php" class="sidebar-link "><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="../kelola_ulasan/list_ulasan.php" class="sidebar-link "><i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan</a></li>
        <li class="sidebar-item"><a href="../kelola_galeri/list_galeri.php" class="sidebar-link "><i data-lucide="image" class="sidebar-icon"></i> Kelola Galeri</a></li>
        <li class="sidebar-item"><a href="../pengaturan.php" class="sidebar-link "><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../../logout.php" class="btn-keluar"><i data-lucide="log-out" class="sidebar-icon" style="color:#1f2937; margin-right:8px;"></i> Keluar</a>
    </div>
</aside>

<div class="admin-main">
    <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 style="font-size: 20px; font-weight: 800; margin: 0;">Kelola Penghuni</h2>
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
            <div class="col-md-4"><div class="stat-card"><div class="stat-card-title">Total Penghuni</div><div class="stat-card-value"><?= $totalPenghuni ?></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="stat-card-title">Penghuni Aktif</div><div class="stat-card-value" style="color:var(--admin-green);"><?= $totalAktif ?></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="stat-card-title">Jatuh Tempo (<= 5 Hari)</div><div class="stat-card-value" style="color:#ef4444;"><?= $totalJatuhTempo ?></div></div></div>
        </div>


        <div class="d-flex justify-content-between align-items-end mb-3">
            <h5 style="font-weight: 700; color: #1f2937; margin: 0;">Data Penghuni Aktif</h5>
            <a href="riwayat_penghuni.php" class="btn btn-sm text-white" style="background-color: var(--admin-green); border-radius: 8px; font-weight: 600; padding: 8px 16px;">
                <i data-lucide="history" style="width: 16px; margin-right: 6px; display: inline-block; vertical-align: text-bottom;"></i> Riwayat Penghuni
            </a>
        </div>
        <div class="p-table-card" style="margin-top: 0;">
            <table class="p-table">
                <thead>
                    <tr>
                        <th>Penghuni</th>
                        <th>Kamar</th>
                        <th>Kontak</th>
                        <th>Sisa Sewa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($penghuni)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data penghuni aktif.</td></tr>
                <?php else: foreach ($penghuni as $p): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700;"><?= htmlspecialchars($p['nama']) ?></div>
                            <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($p['email']) ?></div>
                        </td>
                        <td><span class="badge-room">Kamar <?= htmlspecialchars($p['no_kamar'] ?? '-') ?></span></td>
                        <td style="color:#64748b; font-weight:600; font-size:13px;"><?= htmlspecialchars($p['no_hp'] ?? '-') ?></td>
                        <td>
                            <?php if ($p['sisa_hari'] === '-'): ?>
                                <span style="color:#cbd5e1;">-</span>
                            <?php else: ?>
                                <span class="badge-sisa <?= $p['sisa_hari'] <= 5 ? 'badge-warning' : 'badge-safe' ?>">
                                    <?= $p['sisa_hari'] ?> Hari
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size:11px; font-weight:800; padding:4px 10px; border-radius:50px; display:inline-block; border:1px solid <?= ($p['status'] ?? 'aktif') === 'aktif' ? 'var(--admin-green)' : '#ef4444' ?>; color:<?= ($p['status'] ?? 'aktif') === 'aktif' ? 'var(--admin-green)' : '#ef4444' ?>;">
                                <?= strtoupper($p['status'] ?? 'aktif') ?>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="detail_penghuni.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center" title="Lihat Detail" style="width: 32px; height: 32px; border-radius: 8px;">
                                    <i data-lucide="eye" style="width:16px; height:16px;"></i>
                                </a>
                                <a href="edit_penghuni.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Edit Profil" style="width: 32px; height: 32px; border-radius: 8px;">
                                    <i data-lucide="edit-3" style="width:16px; height:16px;"></i>
                                </a>
                                <a href="hapus_penghuni.php?id=<?= $p['id'] ?>" onclick="return confirm('Proses checkout penghuni ini?')" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" title="Checkout" style="width: 32px; height: 32px; border-radius: 8px;">
                                    <i data-lucide="log-out" style="width:16px; height:16px;"></i>
                                </a>
                            </div>
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
