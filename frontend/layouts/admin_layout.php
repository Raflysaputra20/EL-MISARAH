<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kost Elmi Sarah</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --admin-green: #1ab35d;
            --admin-bg: #f8f9fa;
            --admin-text-dark: #1f2937;
            --admin-text-muted: #6b7280;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--admin-bg);
            margin: 0;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .admin-sidebar {
            width: 260px;
            height: 100vh;
            background-color: var(--admin-green);
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            color: white;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 30px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-brand {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            margin: 0;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }

        .sidebar-item {
            padding-left: 20px;
            margin-bottom: 5px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 15px;
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
        }

        .sidebar-icon {
            width: 20px;
            height: 20px;
            margin-right: 15px;
        }

        .sidebar-footer {
            padding: 24px;
            margin-bottom: 20px;
        }

        .btn-keluar {
            display: flex;
            align-items: center;
            background-color: white;
            color: var(--admin-text-dark);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        /* Topbar & Main Content */
        .admin-main {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-topbar {
            height: 80px;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--admin-text-dark);
            margin: 0;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 25px;
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
            width: 40px;
            height: 40px;
            background-color: #d1d5db;
            border-radius: 50%;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--admin-text-dark);
            line-height: 1.2;
        }

        .user-role {
            font-size: 12px;
            color: var(--admin-text-muted);
        }

        .admin-content {
            padding: 30px 40px;
            flex-grow: 1;
        }

        /* Utility cards */
        .admin-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #f3f4f6;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            padding: 24px;
        }
    </style>
</head>
<body>

    <?php 
        $current_page = $_GET['page'] ?? 'admin-dashboard'; 
        
        // Define page titles
        $page_titles = [
            'admin-dashboard' => 'Dashboard',
            'admin-penghuni' => 'Penghuni Kost',
            'admin-kamar' => 'Manajemen Kamar',
            'admin-pembayaran' => 'Pembayaran',
            'admin-pengaduan' => 'Pengaduan',
            'admin-booking' => 'Booking dan Visit',
            'admin-pengumuman' => 'Pengumuman',
            'admin-pengaturan' => 'Pengaturan'
        ];
        
        $title = $page_titles[$current_page] ?? 'Dashboard';
    ?>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h1 class="sidebar-brand">Elmi Sarah</h1>
            <i data-lucide="menu" class="text-white d-lg-none" style="cursor:pointer;"></i>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="?page=admin-dashboard" class="sidebar-link <?= $current_page == 'admin-dashboard' ? 'active' : '' ?>">
                    <i data-lucide="layout-dashboard" class="sidebar-icon"></i>
                    Dashboard
                </a>
            </li>
            <li class="sidebar-item">
                <a href="?page=admin-penghuni" class="sidebar-link <?= $current_page == 'admin-penghuni' ? 'active' : '' ?>">
                    <i data-lucide="users" class="sidebar-icon"></i>
                    Penghuni Kost
                </a>
            </li>
            <li class="sidebar-item">
                <a href="?page=admin-kamar" class="sidebar-link <?= $current_page == 'admin-kamar' ? 'active' : '' ?>">
                    <i data-lucide="bed-double" class="sidebar-icon"></i>
                    Manajemen Kamar
                </a>
            </li>
            <li class="sidebar-item">
                <a href="?page=admin-pembayaran" class="sidebar-link <?= $current_page == 'admin-pembayaran' ? 'active' : '' ?>">
                    <i data-lucide="wallet" class="sidebar-icon"></i>
                    Pembayaran
                </a>
            </li>
            <li class="sidebar-item">
                <a href="?page=admin-pengaduan" class="sidebar-link <?= $current_page == 'admin-pengaduan' ? 'active' : '' ?>">
                    <i data-lucide="alert-triangle" class="sidebar-icon"></i>
                    Pengaduan
                </a>
            </li>
            <li class="sidebar-item">
                <a href="?page=admin-booking" class="sidebar-link <?= $current_page == 'admin-booking' ? 'active' : '' ?>">
                    <i data-lucide="calendar-check" class="sidebar-icon"></i>
                    Booking dan Visit
                </a>
            </li>
            <li class="sidebar-item">
                <a href="?page=admin-pengumuman" class="sidebar-link <?= $current_page == 'admin-pengumuman' ? 'active' : '' ?>">
                    <i data-lucide="megaphone" class="sidebar-icon"></i>
                    Pengumuman
                </a>
            </li>
            <li class="sidebar-item">
                <a href="?page=admin-pengaturan" class="sidebar-link <?= $current_page == 'admin-pengaturan' ? 'active' : '' ?>">
                    <i data-lucide="settings" class="sidebar-icon"></i>
                    Pengaturan
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="?page=home" class="btn-keluar">
                <i data-lucide="log-out" class="sidebar-icon" style="color:var(--admin-text-dark); margin-right: 10px;"></i>
                Keluar
            </a>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="admin-main">
        
        <!-- Topbar -->
        <header class="admin-topbar">
            <h2 class="page-title"><?= $title ?></h2>
            
            <div class="topbar-right">
                <button class="notification-btn">
                    <i data-lucide="bell" style="width: 24px; height: 24px;"></i>
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
            <?php 
            if (isset($content) && file_exists($content)) {
                include $content; 
            } else {
                echo "<p>Halaman tidak ditemukan.</p>";
            }
            ?>
        </main>
    </div>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
