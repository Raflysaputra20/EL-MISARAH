<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

// Fetch actual data - support both booking-based AND user_id-based payments
$userIdParam = $_GET['user_id'] ?? null;
$query = "
    SELECT p.*,
           b.kamar_id as kamar_ref,
           u_book.nama as nama,
           k.nomor_kamar as no_kamar,
           MONTHNAME(p.tanggal_bayar) as bulan_bayar,
           YEAR(p.tanggal_bayar) as tahun_bayar
    FROM pembayaran p
    LEFT JOIN booking b ON p.booking_id = b.id
    LEFT JOIN users u_book ON b.user_id = u_book.id
    LEFT JOIN kamar k ON b.kamar_id = k.id
";

$params = [];
if ($userIdParam) {
    $query .= " WHERE b.user_id = ? ";
    $params[] = $userIdParam;
}

$query .= " ORDER BY p.tanggal_bayar DESC, p.id DESC ";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kalkulasi
$totalBulanIni = 0;
$sudahBayar = 0;
$totalPenghuni = count($pembayaran);
$jumlahLunas = 0;
$jumlahBelumBayar = 0;

$currentMonth = date('n');
$currentYear = date('Y');

foreach ($pembayaran as $p) {
    // Total bulan ini (semua pembayaran di bulan ini)
    if (date('n', strtotime($p['tanggal_bayar'])) == $currentMonth && date('Y', strtotime($p['tanggal_bayar'])) == $currentYear) {
        $totalBulanIni += $p['jumlah'];
        if ($p['status'] === 'valid') {
            $sudahBayar += $p['jumlah'];
        }
    }
    
    if ($p['status'] === 'valid') {
        $jumlahLunas++;
    } else {
        $jumlahBelumBayar++;
    }
}
$belumBayar = $totalBulanIni - $sudahBayar;
$progressPersen = $totalBulanIni > 0 ? round(($sudahBayar / $totalBulanIni) * 100) : 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-green: #11a654;
            --admin-bg: #f4f6f8;
            --admin-text-dark: #1f2937;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--admin-bg);
            margin: 0; overflow-x: hidden;
            color: var(--admin-text-dark);
        }

        /* Sidebar */
        .admin-sidebar {
            width: 240px; height: 100vh;
            background-color: var(--admin-green);
            position: fixed; top: 0; left: 0;
            display: flex; flex-direction: column;
            color: white; z-index: 1000;
            border-top-right-radius: 15px;
            border-bottom-right-radius: 15px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.03);
        }
        .sidebar-header { padding: 25px 20px; display: flex; align-items: center; justify-content: space-between; }
        .sidebar-brand { font-size: 22px; font-weight: 700; color: white; text-decoration: none; margin: 0; }
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
        .sidebar-link.active { background-color: var(--admin-bg); color: var(--admin-green); font-weight: 600; }
        .sidebar-icon { width: 18px; height: 18px; margin-right: 12px; }
        .sidebar-footer { padding: 20px 15px; margin-bottom: 15px; }
        .btn-keluar {
            display: inline-flex; align-items: center;
            background-color: white; color: var(--admin-text-dark);
            text-decoration: none; padding: 8px 20px;
            border-radius: 25px; font-weight: 600; font-size: 13px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Main */
        .admin-main { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar {
            height: 70px; background-color: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 30px; border-bottom: 1px solid #e5e7eb;
        }
        .page-title { font-size: 20px; font-weight: 600; color: var(--admin-text-dark); margin: 0; }
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .notification-btn { background: none; border: none; color: var(--admin-text-dark); }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar {
            width: 38px; height: 38px; background-color: #d1d5db;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; color: white; font-weight: bold; font-size: 14px;
        }
        .user-name { font-weight: 600; font-size: 13.5px; color: var(--admin-text-dark); line-height: 1.2; }
        .user-role { font-size: 11px; color: #9ca3af; font-weight: 500; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }

        /* Stat Cards */
        .stat-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            padding: 22px 24px; height: 100%;
        }
        .stat-card-title { font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 10px; }
        .stat-card-value { font-size: 26px; font-weight: 700; line-height: 1; margin-bottom: 6px; }
        .stat-card-value.green { color: var(--admin-green); }
        .stat-card-value.red { color: #ef4444; }
        .stat-card-value.dark { color: #1f2937; }
        .stat-card-sub { font-size: 11.5px; color: #9ca3af; margin-top: 4px; }

        /* Progress Bar */
        .progress-custom {
            height: 6px; background-color: #e5e7eb;
            border-radius: 99px; overflow: hidden; margin-top: 12px;
        }
        .progress-fill {
            height: 100%; background-color: var(--admin-green);
            border-radius: 99px; transition: width 0.4s ease;
        }

        /* Search */
        .search-wrapper { position: relative; max-width: 340px; }
        .search-wrapper input {
            background-color: #f0f2f5; border: none;
            border-radius: 30px; padding: 10px 18px 10px 42px;
            font-size: 13px; font-family: 'Poppins', sans-serif;
            color: #374151; width: 100%; outline: none;
        }
        .search-wrapper input::placeholder { color: #9ca3af; }
        .search-icon {
            position: absolute; top: 50%; left: 16px;
            transform: translateY(-50%); color: #9ca3af;
            width: 16px; height: 16px;
        }

        /* Table */
        .pembayaran-table-wrapper {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden;
        }
        .pembayaran-table { width: 100%; border-collapse: collapse; }
        .pembayaran-table thead tr { background-color: var(--admin-green); color: white; }
        .pembayaran-table thead th {
            padding: 14px 18px; font-size: 13px; font-weight: 500; text-align: left;
        }
        .pembayaran-table tbody tr {
            border-bottom: 1px solid #f3f4f6; transition: background 0.15s;
        }
        .pembayaran-table tbody tr:hover { background-color: #fafafa; }
        .pembayaran-table tbody tr:last-child { border-bottom: none; }
        .pembayaran-table tbody td {
            padding: 14px 18px; font-size: 13px; color: #374151; vertical-align: middle;
        }

        /* Badges */
        .badge-room {
            display: inline-flex; align-items: center; justify-content: center;
            background-color: #f3f4f6; color: #374151;
            border-radius: 20px; padding: 4px 14px;
            font-size: 12px; font-weight: 500;
        }
        .badge-lunas {
            display: inline-flex; align-items: center; gap: 5px;
            background-color: #e8f7f0; color: var(--admin-green);
            border-radius: 20px; padding: 5px 14px;
            font-size: 11.5px; font-weight: 600;
        }
        .badge-jatuh-tempo {
            display: inline-flex; align-items: center; gap: 5px;
            background-color: #fff4e5; color: #d97706;
            border-radius: 20px; padding: 5px 14px;
            font-size: 11.5px; font-weight: 600;
        }
        .badge-verifikasi {
            display: inline-flex; align-items: center; gap: 5px;
            background-color: #e0f2fe; color: #0284c7;
            border-radius: 20px; padding: 5px 14px;
            font-size: 11.5px; font-weight: 600;
        }

        /* Action Icons */
        .action-icons { display: flex; align-items: center; gap: 12px; }
        .action-btn {
            background: none; border: none; padding: 0;
            color: #9ca3af; cursor: pointer; transition: color 0.2s;
            text-decoration: none;
        }
        .action-btn:hover { color: #374151; }

        /* Jumlah bold */
        .td-jumlah { font-weight: 700; color: #1f2937; }
        .td-periode { color: #9ca3af; }
        .td-tgl { color: #9ca3af; }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="admin-sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-header">
        <h1 class="sidebar-brand">Elmi Sarah</h1>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="../dashboard.php" class="sidebar-link">
                <i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_penghuni/list_penghuni.php" class="sidebar-link">
                <i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_user/list_user.php" class="sidebar-link">
                <i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_kamar/list_kamar.php" class="sidebar-link">
                <i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar
            </a>
        </li>

        <li class="sidebar-item">
            <a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link">
                <i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../kelola_pengaduan/list_pengaduan.php" class="sidebar-link">
                <i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_booking/list_booking.php" class="sidebar-link">
                <i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_pengumuman/list_pengumuman.php" class="sidebar-link">
                <i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_ulasan/list_ulasan.php" class="sidebar-link">
                <i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../pengaturan.php" class="sidebar-link">
                <i data-lucide="settings" class="sidebar-icon"></i> Pengaturan
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="../../logout.php" class="btn-keluar">
            <i data-lucide="log-out" class="sidebar-icon" style="color:#1f2937; margin-right:8px;"></i>
            Keluar
        </a>
    </div>
</aside>

<!-- Main -->
<div class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <?php if ($userIdParam): ?>
                <a href="../kelola_tagihan/list_tagihan.php" class="btn btn-sm btn-outline-secondary" style="border-radius:20px; font-weight:600;"><i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Kembali</a>
            <?php endif; ?>
            <h2 class="page-title"><?= $userIdParam ? 'Riwayat Pembayaran' : 'Kelola Semua Pembayaran' ?></h2>
        </div>
        <div class="topbar-right">
            <button class="notification-btn">
                <i data-lucide="bell" style="width:20px; height:20px;"></i>
            </button>
            <div class="user-profile">
                <div class="avatar"></div>
                <div>
                    <div class=\"user-name\"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
                    <div class="user-role">Admin</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="admin-content">

        <?php if (!$userIdParam): ?>
        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Bulan Ini -->
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-card-title">Total Bulan Ini</div>
                    <div class="stat-card-value dark">Rp <?= number_format($totalBulanIni, 0, ',', '.') ?></div>
                    <div class="progress-custom">
                        <div class="progress-fill" style="width: <?= $progressPersen ?>%;"></div>
                    </div>
                </div>
            </div>
            <!-- Sudah di Bayar -->
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-card-title">Sudah di Bayar</div>
                    <div class="stat-card-value green">Rp <?= number_format($sudahBayar, 0, ',', '.') ?></div>
                    <div class="stat-card-sub"><?= $jumlahLunas ?> dari <?= $totalPenghuni ?> penghuni</div>
                </div>
            </div>
            <!-- Belum di Bayar -->
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-card-title">Belum di Bayar</div>
                    <div class="stat-card-value red">Rp <?= number_format($belumBayar, 0, ',', '.') ?></div>
                    <div class="stat-card-sub"><?= $jumlahBelumBayar ?> Penghuni belum Bayar</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
        <div style="background:#e8f7f0;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:12px 18px;font-size:13px;font-weight:500;margin-bottom:16px;">
            ✓ <?= htmlspecialchars($_GET['success']) ?>
        </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
        <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;border-radius:10px;padding:12px 18px;font-size:13px;font-weight:500;margin-bottom:16px;">
            ✗ <?= htmlspecialchars($_GET['error']) ?>
        </div>
        <?php endif; ?>

        <!-- Search -->
        <div class="mb-3">
            <div class="search-wrapper">
                <i data-lucide="search" class="search-icon"></i>
                <input type="text" id="searchInput" placeholder="Cari nama atau kamar" onkeyup="filterTable()">
            </div>
        </div>

        <!-- Table -->
        <div class="pembayaran-table-wrapper">
            <table class="pembayaran-table" id="pembayaran-table">
                <thead>
                    <tr>
                        <th>Penghuni</th>
                        <th>No Kamar</th>
                        <th>Periode</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th>Tgl Bayar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pembayaran as $p): 
                    $periode = ($p['bulan_bayar'] ?? '') . ' ' . ($p['tahun_bayar'] ?? '');
                ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($p['nama'] ?? '') ?></td>
                        <td>
                            <span class="badge-room"><?= htmlspecialchars($p['no_kamar'] ?? '-') ?></span>
                        </td>
                        <td class="td-periode"><?= htmlspecialchars($periode) ?></td>
                        <td class="td-jumlah">Rp <?= number_format($p['jumlah'] ?? 0, 0, ',', '.') ?></td>
                        <td>
                            <?php if (strpos($p['metode'] ?? '', 'Perpanjangan') !== false): ?>
                                <span class="badge bg-info text-white" style="font-size: 11px;"><?= htmlspecialchars($p['metode']) ?></span>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($p['metode'] ?? '-') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($p['status'] ?? '') === 'valid'): ?>
                                <span class="badge-lunas">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    Lunas
                                </span>
                            <?php elseif (($p['status'] ?? '') === 'menunggu_verifikasi'): ?>
                                <span class="badge-verifikasi">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Verifikasi
                                </span>
                            <?php else: ?>
                                <span class="badge-jatuh-tempo">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    Tidak Valid
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="td-tgl"><?= !empty($p['tanggal_bayar']) ? date('j F Y', strtotime($p['tanggal_bayar'])) : '-' ?></td>
                        <td>
                            <div class="action-icons">
                                <?php if (!$userIdParam && ($p['status'] ?? '') === 'menunggu_verifikasi'): ?>
                                    <a href="validasi_pembayaran.php?id=<?= $p['id'] ?? '' ?>&action=valid" class="action-btn text-success" title="Setujui" onclick="return confirm('Setujui pembayaran ini?')">
                                        <i data-lucide="check-circle" style="width:16px; height:16px;"></i>
                                    </a>
                                    <a href="validasi_pembayaran.php?id=<?= $p['id'] ?? '' ?>&action=tidak_valid" class="action-btn text-danger" title="Tolak" onclick="return confirm('Tolak pembayaran ini?')">
                                        <i data-lucide="x-circle" style="width:16px; height:16px;"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($p['bukti_bayar'])): ?>
                                    <a href="../../../frontend/assets/image/bukti/<?= htmlspecialchars($p['bukti_bayar']) ?>" target="_blank" class="action-btn text-primary" title="Lihat Bukti">
                                        <i data-lucide="image" style="width:16px; height:16px;"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="hapus_pembayaran.php?id=<?= $p['id'] ?? '' ?><?= $userIdParam ? '&user_id=' . $userIdParam : '' ?>" class="action-btn text-danger" title="Hapus" onclick="return confirm('Hapus data pembayaran ini?')" style="margin-left: 5px;">
                                    <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterTable() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#pembayaran-table tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none';
    });
}
</script>
</body>
</html>
