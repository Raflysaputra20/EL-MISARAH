<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list_pengumuman.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM informasi WHERE id = ?");
$stmt->execute([$id]);
$pengumuman = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pengumuman) {
    header("Location: list_pengumuman.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengumuman - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <style>
        :root {
            --admin-green: #11a654;
            --admin-bg: #f4f6f8;
            --admin-text-dark: #1f2937;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--admin-bg);
            margin: 0; overflow-x: hidden; color: var(--admin-text-dark);
        }

        /* Sidebar & Topbar (Same as list) */
        .admin-sidebar {
            width: 240px; height: 100vh; background-color: var(--admin-green);
            position: fixed; top: 0; left: 0;
            display: flex; flex-direction: column; color: white; z-index: 1000;
            border-top-right-radius: 15px; border-bottom-right-radius: 15px;
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
        }
        .admin-main { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar {
            height: 70px; background-color: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 30px; border-bottom: 1px solid #e5e7eb;
        }
        .page-title { font-size: 20px; font-weight: 600; margin: 0; }
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .notification-btn { background: none; border: none; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 38px; height: 38px; background-color: #d1d5db; border-radius: 50%; }
        .user-name { font-weight: 600; font-size: 13.5px; }
        .user-role { font-size: 11px; color: #9ca3af; font-weight: 500; }
        
        .admin-content { padding: 25px 30px; flex-grow: 1; }

        /* Form Custom */
        .btn-kembali {
            display: inline-block; background-color: var(--admin-green); color: white;
            padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 500;
            text-decoration: none; margin-bottom: 20px;
        }
        .btn-kembali:hover { background-color: #0e9148; color: white; }

        .form-card {
            background: white; border-radius: 16px; padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            max-width: 100%;
        }

        .form-header {
            display: flex; align-items: center; gap: 16px; margin-bottom: 30px;
        }
        .form-icon-wrap {
            width: 48px; height: 48px; border-radius: 50%;
            background-color: var(--admin-green); display: flex; align-items: center; justify-content: center;
        }
        .form-title { font-size: 18px; font-weight: 600; color: #1f2937; margin: 0; }
        .form-subtitle { font-size: 13px; color: #6b7280; margin: 2px 0 0; }

        .form-label-custom { font-size: 13.5px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .form-control-custom {
            font-size: 14px; padding: 12px 16px; border-radius: 8px;
            border: 1px solid #e5e7eb; width: 100%; outline: none; transition: 0.2s;
        }
        .form-control-custom:focus { border-color: var(--admin-green); box-shadow: 0 0 0 3px rgba(17,166,84,0.1); }

        .pin-box {
            background-color: #f0fdf4; border: 1px solid #dcfce7;
            border-radius: 8px; padding: 16px 20px;
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 25px; margin-bottom: 25px;
        }
        .pin-left { display: flex; align-items: center; gap: 12px; }
        .pin-title { font-size: 14px; font-weight: 600; color: #166534; margin: 0; }
        .pin-subtitle { font-size: 12px; color: #166534; opacity: 0.8; margin: 0; }

        .form-check-input.custom-switch {
            width: 40px; height: 20px;
        }
        .form-check-input.custom-switch:checked {
            background-color: var(--admin-green); border-color: var(--admin-green);
        }

        .btn-submit {
            display: inline-flex; align-items: center; gap: 8px;
            background-color: var(--admin-green); color: white;
            padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500;
            border: none; cursor: pointer; float: right; transition: 0.2s;
        }
        .btn-submit:hover { background-color: #0e9148; }
        .clearfix::after { content: ""; clear: both; display: table; }

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
        <li class="sidebar-item"><a href="../dashboard.php" class="sidebar-link "><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="../kelola_penghuni/list_penghuni.php" class="sidebar-link "><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li class="sidebar-item"><a href="../kelola_user/list_user.php" class="sidebar-link "><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="../kelola_kamar/list_kamar.php" class="sidebar-link "><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li class="sidebar-item"><a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link "><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
        <li class="sidebar-item"><a href="../kelola_pengaduan/list_pengaduan.php" class="sidebar-link "><i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan</a></li>
        <li class="sidebar-item"><a href="../kelola_booking/list_booking.php" class="sidebar-link "><i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking</a></li>
        <li class="sidebar-item"><a href="../kelola_pengumuman/list_pengumuman.php" class="sidebar-link active"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="../kelola_ulasan/list_ulasan.php" class="sidebar-link "><i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan</a></li>
        <li class="sidebar-item"><a href="../kelola_galeri/list_galeri.php" class="sidebar-link "><i data-lucide="image" class="sidebar-icon"></i> Kelola Galeri</a></li>
        <li class="sidebar-item"><a href="../pengaturan.php" class="sidebar-link "><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../../logout.php" class="btn-keluar"><i data-lucide="log-out" class="sidebar-icon" style="color:#1f2937; margin-right:8px;"></i> Keluar</a>
    </div>
</aside>

<!-- Main -->
<div class="admin-main">
    <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 class="page-title">Pengumuman</h2>
        </div>
        <div class="topbar-right">
            <button class="notification-btn"><i data-lucide="bell" style="width:20px; height:20px;"></i></button>
            <div class="user-profile">
                <div class="avatar"></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
                    <div class="user-role">Admin</div>
                </div>
            </div>
        </div>
    </header>

    <main class="admin-content">
        <a href="list_pengumuman.php" class="btn-kembali">Kembali</a>

        <div class="form-card clearfix">
            <div class="form-header">
                <div class="form-icon-wrap" style="background-color: var(--admin-green);">
                    <i data-lucide="pencil" style="width:24px; height:24px; color:white;"></i>
                </div>
                <div>
                    <h3 class="form-title">Edit Pengumuman</h3>
                    <p class="form-subtitle">Ubah detail pengumuman yang sudah ada</p>
                </div>
            </div>

            <form action="simpan_pengumuman.php" method="POST">
                <input type="hidden" name="id" value="<?= htmlspecialchars($pengumuman['id']) ?>">
                
                <div class="mb-3">
                    <label class="form-label-custom">Judul Pengumuman</label>
                    <input type="text" name="judul" class="form-control-custom" value="<?= htmlspecialchars($pengumuman['judul']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control-custom" value="<?= htmlspecialchars($pengumuman['tanggal'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label-custom">Isi Pengumuman</label>
                    <textarea name="isi" class="form-control-custom" rows="5" required><?= htmlspecialchars($pengumuman['isi']) ?></textarea>
                </div>



                <button type="submit" class="btn-submit">
                    <i data-lucide="send" style="width:16px; height:16px;"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
