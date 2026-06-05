<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

// Fetch from DB using 'informasi' table (the real table in database)
$pengumuman = [];
try {
    // Check if table 'informasi' exists
    $stmt = $conn->query("SELECT * FROM informasi ORDER BY pinned DESC, id DESC");
    $pengumuman = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If table doesn't exist, pengumuman stays empty
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - Admin Kost Elmi Sarah</title>
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
            margin: 0; overflow-x: hidden; color: var(--admin-text-dark);
        }

        /* Sidebar */
        .admin-sidebar {
            width: 240px; height: 100vh; background-color: var(--admin-green);
            position: fixed; top: 0; left: 0;
            display: flex; flex-direction: column; color: white; z-index: 1000;
            border-top-right-radius: 15px; border-bottom-right-radius: 15px;
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
            width: 38px; height: 38px; background-color: #d1d5db; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 14px;
        }
        .user-name { font-weight: 600; font-size: 13.5px; color: var(--admin-text-dark); line-height: 1.2; }
        .user-role { font-size: 11px; color: #9ca3af; font-weight: 500; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }

        /* Section header */
        .section-header {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .section-label {
            font-size: 16px; font-weight: 700; color: #1f2937;
        }
        .btn-buat {
            display: inline-flex; align-items: center; gap: 8px;
            background-color: var(--admin-green); color: white;
            border: none; border-radius: 12px;
            padding: 11px 22px; font-size: 13px; font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer; text-decoration: none;
            transition: background 0.2s;
        }
        .btn-buat:hover { background-color: #0e9148; color: white; }

        /* Announcement Cards */
        .announcement-card {
            background: white; border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            padding: 20px 22px;
            display: flex; align-items: flex-start;
            gap: 16px; margin-bottom: 14px;
            transition: box-shadow 0.2s;
        }
        .announcement-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
        .announcement-card:last-child { margin-bottom: 0; }

        .ann-icon-wrap {
            width: 44px; height: 44px; border-radius: 50%;
            background-color: #e8f7f0;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .ann-body { flex: 1; }

        .ann-title-row {
            display: flex; align-items: center; gap: 10px; margin-bottom: 6px;
            flex-wrap: wrap;
        }
        .ann-title {
            font-size: 14px; font-weight: 700; color: #1f2937; margin: 0;
        }
        .badge-pinned {
            display: inline-flex; align-items: center; gap: 4px;
            background-color: #e8f7f0; color: var(--admin-green);
            border-radius: 20px; padding: 3px 12px;
            font-size: 11px; font-weight: 600;
        }

        .ann-desc {
            font-size: 12.5px; color: #6b7280;
            line-height: 1.55; margin-bottom: 6px;
        }
        .ann-date { font-size: 11.5px; color: #9ca3af; }

        .ann-more { margin-left: auto; flex-shrink: 0; }
        .btn-more-ann {
            background: none; border: none; color: #9ca3af;
            cursor: pointer; padding: 4px; transition: color 0.2s;
        }
        .btn-more-ann:hover { color: #374151; }

        /* Modal */
        .modal-title-custom { font-size: 16px; font-weight: 700; }
        .form-label-custom { font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .form-control-custom {
            font-size: 13px; font-family: 'Poppins', sans-serif;
            border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 10px 14px; width: 100%; outline: none;
            transition: border-color 0.2s;
        }
        .form-control-custom:focus { border-color: var(--admin-green); box-shadow: 0 0 0 3px rgba(17,166,84,0.1); }
        .btn-simpan {
            background-color: var(--admin-green); color: white; border: none;
            border-radius: 10px; padding: 10px 24px;
            font-size: 13px; font-weight: 600;
            font-family: 'Poppins', sans-serif; cursor: pointer;
        }
        .btn-simpan:hover { background-color: #0e9148; }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="admin-sidebar">
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
            <a href="list_pengumuman.php" class="sidebar-link active">
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
        <h2 class="page-title">Pengumuman</h2>
        <div class="topbar-right">
            <button class="notification-btn">
                <i data-lucide="bell" style="width:20px; height:20px;"></i>
            </button>
            <div class="user-profile">
                <div class="avatar"></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
                    <div class="user-role">Admin</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="admin-content">

        <!-- Section Header -->
        <div class="section-header">
            <span class="section-label">Buat dan Kelola Pengumuman</span>
            <a href="tambah_pengumuman.php" class="btn-buat" style="text-decoration: none;">
                <i data-lucide="plus" style="width:16px; height:16px;"></i>
                Buat pengumuman
            </a>
        </div>

        <!-- Announcement List -->
        <?php if (empty($pengumuman)): ?>
            <div class="text-center py-5" style="color:#9ca3af;">
                <i data-lucide="megaphone" style="width:40px; height:40px; display:block; margin:0 auto 12px;"></i>
                <p>Belum ada pengumuman. Buat pengumuman pertama Anda!</p>
            </div>
        <?php else: ?>
            <?php foreach ($pengumuman as $p):
                $isPinned = !empty($p['pinned']);
                $tgl = !empty($p['tanggal']) ? date('j F Y', strtotime($p['tanggal'])) : date('j F Y', strtotime($p['created_at']));
            ?>
            <div class="announcement-card">
                <!-- Icon -->
                <div class="ann-icon-wrap">
                    <i data-lucide="megaphone" style="width:22px; height:22px; color:var(--admin-green);"></i>
                </div>

                <!-- Body -->
                <div class="ann-body">
                    <div class="ann-title-row">
                        <span class="ann-title"><?= htmlspecialchars($p['judul']) ?></span>
                        <?php if ($isPinned): ?>
                            <span class="badge-pinned">
                                <i data-lucide="pin" style="width:11px; height:11px;"></i>
                                disematkan
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="ann-desc"><?= nl2br(htmlspecialchars($p['isi'])) ?></p>
                    <div class="ann-date"><?= $tgl ?></div>
                </div>

                <!-- More Button -->
                <div class="ann-more dropdown">
                    <button class="btn-more-ann" data-bs-toggle="dropdown" aria-expanded="false">
                        <i data-lucide="more-vertical" style="width:18px; height:18px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:13px; min-width:160px;">
                        <li>
                            <a class="dropdown-item" href="edit_pengumuman.php?id=<?= $p['id'] ?>">
                                <i data-lucide="pencil" style="width:14px; height:14px; margin-right:6px;"></i> Edit
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="pin_pengumuman.php?id=<?= $p['id'] ?>&pin=<?= $isPinned ? 0 : 1 ?>">
                                <i data-lucide="pin" style="width:14px; height:14px; margin-right:6px;"></i>
                                <?= $isPinned ? 'Lepas Sematan' : 'Sematkan' ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="hapus_pengumuman.php?id=<?= $p['id'] ?>" onclick="return confirm('Hapus pengumuman ini?')">
                                <i data-lucide="trash-2" style="width:14px; height:14px; margin-right:6px;"></i> Hapus
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
