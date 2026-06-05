<?php
session_start();

// FIX PATH DATABASE
require_once __DIR__ . "/../config/database.php";

// Proteksi login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../api/auth/login.php");
    exit;
}

// Proteksi role admin
if ($_SESSION["role"] !== "admin") {
    echo "Akses ditolak. Halaman ini khusus admin.";
    exit;
}

// Hitung data ringkasan
$totalPenghuni = 0;
$totalKamarTersedia = 0;
$totalPengaduanAktif = 0;
$kamarTerisi = 0;
$kamarDibooking = 0;
$kamarTersedia = 0;
$recentActivities = [];

// ═══ AUTO-SYNC: Sinkronisasi status kamar dengan data penghuni aktif ═══
try {
    $conn->exec("
        UPDATE booking b
        JOIN users u ON b.user_id = u.id
        SET b.status = 'aktif'
        WHERE b.status = 'selesai'
          AND u.role = 'penghuni'
          AND u.status = 'aktif'
          AND b.kamar_id IS NOT NULL
          AND b.id = (SELECT MAX(b2.id) FROM (SELECT id, user_id FROM booking) b2 WHERE b2.user_id = b.user_id)
    ");
    $conn->exec("
        UPDATE kamar k
        JOIN booking b ON b.kamar_id = k.id
        JOIN users u ON b.user_id = u.id
        SET k.status = 'terisi'
        WHERE b.status = 'aktif'
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
              WHERE b.status IN ('aktif', 'disetujui') 
                AND u.role = 'penghuni' 
                AND u.status = 'aktif'
                AND b.kamar_id IS NOT NULL
          )
    ");
} catch (Exception $e) {}

try {
    $stmtPenghuni = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'penghuni'");
    $totalPenghuni = $stmtPenghuni->fetch()["total"] ?? 0;

    $stmtKamarTersedia = $conn->query("SELECT COUNT(*) as total FROM kamar WHERE status = 'tersedia'");
    $totalKamarTersedia = $stmtKamarTersedia->fetch()["total"] ?? 0;

    $stmtPengaduanAktif = $conn->query("SELECT COUNT(*) as total FROM pengaduan WHERE status != 'selesai'");
    $totalPengaduanAktif = $stmtPengaduanAktif->fetch()["total"] ?? 0;

    $stmtKamarStat = $conn->query("SELECT status, COUNT(*) as count FROM kamar GROUP BY status");
    $kamarStats = $stmtKamarStat->fetchAll(PDO::FETCH_KEY_PAIR);
    $kamarTersedia = $kamarStats['tersedia'] ?? 0;
    $kamarTerisi = $kamarStats['terisi'] ?? 0;
    $kamarDibooking = $kamarStats['dibooking'] ?? 0;

    $stmtRecent = $conn->query("
            SELECT 'Booking' as tipe, b.id, u.nama, CONCAT(k.tipe, ' No.', COALESCE(k.nomor_kamar, '-')) as nomor_kamar, b.created_at as tgl, b.tanggal_booking as tgl_acara
            FROM booking b
            JOIN users u ON b.user_id = u.id
            JOIN kamar k ON b.kamar_id = k.id
        ORDER BY tgl DESC
        LIMIT 5
    ");
    $recentActivities = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // aman walau tabel belum ada
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Kost Elmi Sarah</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css">
    <!-- Google Fonts: Poppins for that specific clean look -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --admin-green: #11a654; /* Exact green from design */
            --admin-bg: #f4f6f8; /* Very light grey */
            --admin-text-dark: #1f2937;
            --admin-text-muted: #9ca3af;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--admin-bg);
            margin: 0;
            overflow-x: hidden;
            color: var(--admin-text-dark);
        }

        /* Sidebar Styling */
        .admin-sidebar {
            width: 240px;
            height: 100vh;
            background-color: var(--admin-green);
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            color: white;
            z-index: 1000;
            border-top-right-radius: 15px;
            border-bottom-right-radius: 15px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.03);
        }

        .sidebar-header {
            padding: 25px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-brand {
            font-size: 22px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }

        .sidebar-item {
            padding-left: 15px;
            margin-bottom: 5px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-top-left-radius: 25px;
            border-bottom-left-radius: 25px;
            transition: all 0.2s ease;
        }

        .sidebar-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-link.active {
            background-color: var(--admin-bg);
            color: var(--admin-green);
            font-weight: 600;
            box-shadow: -3px 0 8px rgba(0,0,0,0.02);
        }

        .sidebar-icon {
            width: 18px;
            height: 18px;
            margin-right: 12px;
        }

        .sidebar-footer {
            padding: 20px 15px;
            margin-bottom: 15px;
        }

        .btn-keluar {
            display: inline-flex;
            align-items: center;
            background-color: white;
            color: var(--admin-text-dark);
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }

        .btn-keluar:hover {
            background-color: #f3f4f6;
            color: var(--admin-text-dark);
        }

        /* Topbar & Main Content */
        .admin-main {
            margin-left: 240px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-topbar {
            height: 70px;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--admin-text-dark);
            margin: 0;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-btn {
            background: none;
            border: none;
            color: var(--admin-text-dark);
            position: relative;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            background-color: #d1d5db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 13.5px;
            color: var(--admin-text-dark);
            line-height: 1.2;
        }

        .user-role {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 500;
        }

        .admin-content {
            padding: 25px 30px;
            flex-grow: 1;
        }

        /* Dashboard specific */
        .dashboard-card {
            background: white;
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            padding: 20px;
            height: 100%;
            position: relative;
        }

        .stat-title { font-size: 13.5px; font-weight: 600; color: #6b7280; margin-bottom: 12px; }
        .stat-value { font-size: 32px; font-weight: 700; color: #1f2937; margin-bottom: 6px; line-height: 1; }
        .stat-subtitle { font-size: 11px; font-weight: 600; color: var(--admin-green); }
        .stat-icon-wrapper {
            position: absolute; top: 20px; right: 20px; width: 44px; height: 44px;
            background-color: #eaf8f1; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--admin-green);
        }

        .booking-item {
            background-color: #f6fdf9; border: 1px solid #e1f5e8; border-radius: 10px; padding: 12px 16px;
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;
        }
        .booking-item:last-child { margin-bottom: 0; }
        .booking-info { display: flex; align-items: center; gap: 12px; }
        .booking-icon-wrapper {
            width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: #9ca3af;
        }
        .booking-details { display: flex; flex-direction: column; }
        .booking-name { font-weight: 600; font-size: 13px; color: #374151; margin-bottom: 2px; }
        .booking-room { font-size: 11px; color: #9ca3af; font-weight: 500;}
        
        .badge-booking { background-color: transparent; color: var(--admin-green); border: 1px solid var(--admin-green); border-radius: 20px; padding: 4px 16px; font-size: 11px; font-weight: 500; }
    </style>
</head>
<body>

    <aside class="admin-sidebar">
        <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
        <div class="sidebar-header">
            <h1 class="sidebar-brand">Elmi Sarah</h1>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="dashboard.php" class="sidebar-link active">
                    <i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard
                </a>
            </li>
            <li class="sidebar-item">
                <a href="kelola_penghuni/list_penghuni.php" class="sidebar-link">
                    <i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost
                </a>
            </li>
            <li class="sidebar-item">
                <a href="kelola_user/list_user.php" class="sidebar-link">
                    <i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User
                </a>
            </li>
            <li class="sidebar-item">
                <a href="kelola_kamar/list_kamar.php" class="sidebar-link">
                    <i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar
                </a>
            </li>
            <li class="sidebar-item">
            <a href="kelola_tagihan/list_tagihan.php" class="sidebar-link">
                <i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran
            </a>
        </li>
            
            <li class="sidebar-item">
                <a href="kelola_pengaduan/list_pengaduan.php" class="sidebar-link">
                    <i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan
                </a>
            </li>
            <li class="sidebar-item">
                <a href="kelola_booking/list_booking.php" class="sidebar-link">
                    <i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking
                </a>
            </li>
            <li class="sidebar-item">
                <a href="kelola_pengumuman/list_pengumuman.php" class="sidebar-link">
                    <i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman
                </a>
            </li>
        <li class="sidebar-item">
            <a href="kelola_ulasan/list_ulasan.php" class="sidebar-link">
                <i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan
            </a>
        </li>
        <li class="sidebar-item">
                <a href="pengaturan.php" class="sidebar-link">
                    <i data-lucide="settings" class="sidebar-icon"></i> Pengaturan
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="../logout.php" class="btn-keluar">
                <i data-lucide="log-out" class="sidebar-icon" style="color:var(--admin-text-dark); margin-right: 10px;"></i>
                Keluar
            </a>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="admin-main">
        
        <header class="admin-topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
                <h2 class="page-title">Dashboard</h2>
            </div>
            
            <div class="topbar-right">
                <button class="notification-btn">
                    <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
                </button>
                <div class="user-profile">
                    <div class="avatar"></div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
                        <span class="user-role">Admin</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Area -->
        <main class="admin-content">
            
            <!-- TOP STATS ROW -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="stat-title">Total Penghuni</div>
                        <div class="stat-value"><?= $totalPenghuni ?></div> 
                        <div class="stat-subtitle">Semua Penghuni Aktif</div>
                        <div class="stat-icon-wrapper">
                            <i data-lucide="users" style="width:20px; height:20px;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="stat-title">Total Kamar Tersedia</div>
                        <div class="stat-value"><?= $totalKamarTersedia ?></div> 
                        <div class="stat-icon-wrapper">
                            <i data-lucide="bed-single" style="width:20px; height:20px;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="stat-title">Pengaduan Aktif</div>
                        <div class="stat-value"><?= $totalPengaduanAktif ?></div> 
                        <div class="stat-subtitle">Perlu Ditangani</div>
                        <div class="stat-icon-wrapper">
                            <i data-lucide="message-square-warning" style="width:20px; height:20px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTTOM ROW -->
            <div class="row g-3">
                
                <!-- DONUT CHART -->
                <div class="col-md-5">
                    <div class="dashboard-card d-flex flex-column">
                        <h5 class="fw-bold mb-3" style="font-size: 14.5px; color:#1f2937;">Status Kamar</h5>
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative pb-3">
                            <canvas id="roomStatusChart" style="max-height: 200px; max-width: 200px;"></canvas>
                        </div>
                        
                        <!-- Custom Legend -->
                        <div class="d-flex justify-content-center gap-3 mt-auto">
                            <div class="d-flex align-items-center gap-2">
                                <span style="width:10px; height:10px; border-radius:50%; background-color:#11a654;"></span>
                                <span style="font-size:11px; color:#6b7280; font-weight:500;">Terisi</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span style="width:10px; height:10px; border-radius:50%; background-color:#f59e0b;"></span>
                                <span style="font-size:11px; color:#6b7280; font-weight:500;">Dibooking</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span style="width:10px; height:10px; border-radius:50%; background-color:#e5e7eb;"></span>
                                <span style="font-size:11px; color:#6b7280; font-weight:500;">Tersedia</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOOKING LIST -->
                <div class="col-md-7">
                    <div class="dashboard-card">
                        <h5 class="fw-bold mb-3" style="font-size: 14.5px; color:#1f2937;">Booking</h5>
                        
                        <div class="booking-list">
                            <?php if(empty($recentActivities)): ?>
                                <p class="text-muted text-center py-4" style="font-size:13px;">Belum ada aktivitas terbaru.</p>
                            <?php else: ?>
                                <?php foreach($recentActivities as $act): ?>
                                <div class="booking-item">
                                    <div class="booking-info">
                                        <div class="booking-icon-wrapper">
                                            <i data-lucide="calendar-check-2" style="width:18px; height:18px;"></i>
                                        </div>
                                        <div class="booking-details">
                                            <span class="booking-name"><?= htmlspecialchars($act['nama']) ?></span>
                                            <span class="booking-room">Kamar <?= htmlspecialchars($act['nomor_kamar']) ?>, <?= date('d M Y', strtotime($act['tgl_acara'])) ?></span>
                                        </div>
                                    </div>
                                    <span class="badge-booking"><?= $act['tipe'] ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>


    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/sidebar-toggle.js"></script>
    <script>
        lucide.createIcons();
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('roomStatusChart').getContext('2d');
        const data = {
            datasets: [{
                data: [<?= $kamarTerisi ?>, <?= $kamarDibooking ?>, <?= $kamarTersedia ?>], 
                backgroundColor: ['#11a654', '#f59e0b', '#e5e7eb'],
                borderWidth: 6,
                borderColor: '#ffffff',
                cutout: '75%'
            }]
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                animation: { animateScale: true, animateRotate: true },
                layout: { padding: 10 }
            }
        });
    });
    </script>
</body>
</html>
