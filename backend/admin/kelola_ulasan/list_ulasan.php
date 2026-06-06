<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

// Ensure columns exist
try {
    $chk = $conn->query("SHOW COLUMNS FROM ulasan LIKE 'balasan_admin'");
    if ($chk->rowCount() === 0) {
        $conn->exec("ALTER TABLE ulasan ADD COLUMN balasan_admin TEXT DEFAULT NULL");
    }
    $chk2 = $conn->query("SHOW COLUMNS FROM ulasan LIKE 'balasan_at'");
    if ($chk2->rowCount() === 0) {
        $conn->exec("ALTER TABLE ulasan ADD COLUMN balasan_at DATETIME DEFAULT NULL");
    }
    $chk3 = $conn->query("SHOW COLUMNS FROM ulasan LIKE 'foto_ulasan'");
    if ($chk3->rowCount() === 0) {
        $conn->exec("ALTER TABLE ulasan ADD COLUMN foto_ulasan VARCHAR(255) DEFAULT NULL");
    }
    $chk4 = $conn->query("SHOW COLUMNS FROM ulasan LIKE 'tampilkan'");
    if ($chk4->rowCount() === 0) {
        $conn->exec("ALTER TABLE ulasan ADD COLUMN tampilkan TINYINT DEFAULT 1");
    }
} catch (Exception $e) {}

// Handle toggle tampilkan (GET action)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_tampilkan' && isset($_GET['id'])) {
    $uid = (int)$_GET['id'];
    $status = (int)$_GET['status'];
    try {
        $conn->prepare("UPDATE ulasan SET tampilkan = ? WHERE id = ?")->execute([$status, $uid]);
        header("Location: list_ulasan.php?success=" . urlencode("Status tampilan ulasan berhasil diperbarui."));
        exit;
    } catch (Exception $e) {
        header("Location: list_ulasan.php?error=" . urlencode("Gagal memperbarui status: " . $e->getMessage()));
        exit;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reply' && isset($_POST['ulasan_id'])) {
        $balasan = trim($_POST['balasan'] ?? '');
        $uid = (int)$_POST['ulasan_id'];
        if ($balasan !== '') {
            $conn->prepare("UPDATE ulasan SET balasan_admin = ?, balasan_at = NOW() WHERE id = ?")->execute([$balasan, $uid]);
        } else {
            $conn->prepare("UPDATE ulasan SET balasan_admin = NULL, balasan_at = NULL WHERE id = ?")->execute([$uid]);
        }
        header("Location: list_ulasan.php?success=" . urlencode("Balasan berhasil disimpan."));
        exit;
    }
}

// Fetch ulasan
$ulasans = [];
try {
    $stmt = $conn->query("
        SELECT u.id, u.rating, u.komentar, u.foto_ulasan, u.created_at, u.balasan_admin, u.balasan_at, u.tampilkan, usr.nama, usr.foto, usr.email
        FROM ulasan u
        JOIN users usr ON u.user_id = usr.id
        ORDER BY u.created_at DESC
    ");
    $ulasans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Fetch users who don't have a review yet
$usersWithoutReview = [];
try {
    $stmtUsers = $conn->query("
        SELECT id, nama, email 
        FROM users 
        WHERE role IN ('penghuni', 'user') AND id NOT IN (SELECT DISTINCT user_id FROM ulasan)
        ORDER BY nama ASC
    ");
    $usersWithoutReview = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$total = count($ulasans);
$avgRating = $total > 0 ? array_sum(array_column($ulasans, 'rating')) / $total : 0;
$replied = count(array_filter($ulasans, fn($u) => !empty($u['balasan_admin'])));
$dist = [1=>0,2=>0,3=>0,4=>0,5=>0];
foreach ($ulasans as $u) { $r = (int)$u['rating']; if ($r>=1&&$r<=5) $dist[$r]++; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ulasan - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --admin-green: #11a654; --admin-bg: #f4f6f8; --admin-text-dark: #1f2937; }
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--admin-bg); margin: 0; color: var(--admin-text-dark); overflow-x: hidden; }

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
        .btn-exit {
            display: inline-flex; align-items: center;
            background-color: white; color: var(--admin-text-dark);
            text-decoration: none; padding: 8px 20px;
            border-radius: 25px; font-weight: 600; font-size: 13px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .btn-exit:hover { background-color: #f3f4f6; color: var(--admin-text-dark); }

        .admin-main { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar { height: 68px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 100; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }

        .stat-card { background: white; border-radius: 16px; padding: 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); height: 100%; }
        .stat-card-title { font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card-value { font-size: 28px; font-weight: 800; }

        .filter-bar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .search-box { background: #f0f2f5; border: none; border-radius: 30px; padding: 10px 18px 10px 42px; font-size: 13px; font-family: 'Poppins',sans-serif; color: #374151; width: 280px; outline: none; }
        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; top: 50%; left: 16px; transform: translateY(-50%); color: #9ca3af; }
        .filter-btn { background: white; border: 1.5px solid #e5e7eb; border-radius: 30px; padding: 8px 16px; font-size: 12px; font-family: 'Poppins',sans-serif; font-weight: 600; cursor: pointer; color: #64748b; transition: all 0.2s; }
        .filter-btn:hover, .filter-btn.active { border-color: var(--admin-green); color: var(--admin-green); background: #e8f7f0; }

        .review-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); padding: 20px 24px; margin-bottom: 14px; transition: box-shadow 0.2s; animation: fadeIn 0.3s ease; }
        .review-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.07); }
        .review-header { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; }
        .review-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--admin-green), #0d8e47); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; color: white; flex-shrink: 0; overflow: hidden; }
        .review-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .review-meta { flex: 1; }
        .review-name { font-size: 14px; font-weight: 700; }
        .review-email { font-size: 11px; color: #9ca3af; }
        .review-date { font-size: 11px; color: #9ca3af; flex-shrink: 0; }
        .review-stars { color: #facc15; font-size: 15px; letter-spacing: 1px; margin-bottom: 8px; }
        .review-text { font-size: 13px; color: #4b5563; line-height: 1.6; margin-bottom: 10px; }
        .review-photo { max-width: 140px; max-height: 140px; border-radius: 10px; object-fit: cover; border: 1px solid #e5e7eb; cursor: pointer; transition: transform 0.2s; }
        .review-photo:hover { transform: scale(1.05); }

        .reply-box { background: #f0fdf4; border-radius: 10px; padding: 12px 16px; margin-top: 10px; border-left: 3px solid var(--admin-green); }
        .reply-label { font-size: 11px; font-weight: 700; color: var(--admin-green); text-transform: uppercase; margin-bottom: 4px; }
        .reply-text { font-size: 12.5px; color: #374151; line-height: 1.5; }
        .reply-date { font-size: 10px; color: #9ca3af; margin-top: 4px; }

        .review-actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
        .btn-action { border: none; border-radius: 8px; padding: 7px 14px; font-size: 12px; font-family: 'Poppins',sans-serif; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-reply { background: #e8f7f0; color: var(--admin-green); }
        .btn-reply:hover { background: #d1f0e0; }
        .btn-delete { background: #fee2e2; color: #ef4444; }
        .btn-delete:hover { background: #fecaca; }
        .btn-edit-ulasan { background: #e0f2fe; color: #0284c7; }
        .btn-edit-ulasan:hover { background: #bae6fd; }
        .btn-add-ulasan { background: var(--admin-green); color: white; border: none; border-radius: 30px; padding: 10px 20px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer; margin-left: auto; }
        .btn-add-ulasan:hover { background: #0d8e47; color: white; }

        .dist-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .dist-label { font-size: 12px; font-weight: 600; color: #64748b; width: 14px; text-align: center; }
        .dist-bar { flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
        .dist-fill { height: 100%; background: #facc15; border-radius: 4px; transition: width 0.5s ease; }
        .dist-count { font-size: 11px; color: #9ca3af; width: 24px; text-align: right; }

        .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }

        /* Modal */
        .modal-backdrop-custom { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; display: none; align-items: center; justify-content: center; }
        .modal-backdrop-custom.show { display: flex; }
        .modal-box { background: white; border-radius: 20px; width: 520px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2); animation: modalIn 0.25s ease; }
        .modal-box .modal-head { padding: 20px 24px 16px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
        .modal-box .modal-head h4 { margin: 0; font-size: 16px; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #9ca3af; padding: 4px; }
        .modal-body { padding: 20px 24px 24px; }
        .reply-textarea { width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; font-size: 13px; font-family: 'Poppins',sans-serif; outline: none; resize: vertical; min-height: 100px; transition: border-color 0.2s; }
        .reply-textarea:focus { border-color: var(--admin-green); }
        .btn-submit-reply { background: var(--admin-green); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-family: 'Poppins',sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 12px; }
        .btn-submit-reply:hover { background: #0d8e47; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        .alert-bar { border-radius: 12px; padding: 12px 18px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border: none; }
        .alert-bar.success { background: #e8f7f0; color: #11a654; }
        .alert-bar.error { background: #fee2e2; color: #ef4444; }
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
            <a href="list_ulasan.php" class="sidebar-link active">
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
        <a href="../../logout.php" class="btn-exit">
            <i data-lucide="log-out" class="sidebar-icon" style="color:var(--admin-text-dark); margin-right: 10px;"></i>
            Keluar
        </a>
    </div>
</aside>

<div class="admin-main">
    <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 style="font-size:20px; font-weight:800; margin:0;">Kelola Ulasan</h2>
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-bar success"><i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i> <?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert-bar error"><i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;"></i> <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-title">Total Ulasan</div>
                    <div class="stat-card-value"><?= $total ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-title">Rata-rata Rating</div>
                    <div class="stat-card-value" style="color:#f59e0b;">
                        <?= $total > 0 ? number_format($avgRating, 1) : '-' ?>
                        <span style="font-size:16px;">★</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-title">Sudah Dibalas</div>
                    <div class="stat-card-value" style="color:var(--admin-green);"><?= $replied ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-title">Distribusi Rating</div>
                    <div style="margin-top:6px;">
                        <?php for ($s=5; $s>=1; $s--): $pct = $total > 0 ? ($dist[$s]/$total*100) : 0; ?>
                        <div class="dist-row">
                            <div class="dist-label"><?= $s ?></div>
                            <div class="dist-bar"><div class="dist-fill" style="width:<?= $pct ?>%;"></div></div>
                            <div class="dist-count"><?= $dist[$s] ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-bar">
            <div class="search-wrap">
                <i data-lucide="search" style="width:16px;height:16px;"></i>
                <input type="text" class="search-box" id="searchInput" placeholder="Cari nama atau komentar...">
            </div>
            <button class="filter-btn active" data-filter="all">Semua</button>
            <button class="filter-btn" data-filter="5">★5</button>
            <button class="filter-btn" data-filter="4">★4</button>
            <button class="filter-btn" data-filter="3">★3</button>
            <button class="filter-btn" data-filter="2">★2</button>
            <button class="filter-btn" data-filter="1">★1</button>
            <button class="filter-btn" data-filter="norep">Belum Dibalas</button>
        </div>

        <!-- Review List -->
        <?php if (empty($ulasans)): ?>
            <div class="empty-state">
                <i data-lucide="star" style="width:48px; height:48px; color:#d1d5db; display:block; margin:0 auto 14px;"></i>
                <p style="font-size:15px; font-weight:600;">Belum ada ulasan</p>
                <p style="font-size:13px;">Ulasan dari penghuni akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <div id="reviewList">
            <?php foreach ($ulasans as $u): ?>
                <div class="review-card" data-rating="<?= (int)$u['rating'] ?>" data-replied="<?= empty($u['balasan_admin']) ? '0' : '1' ?>">
                    <div class="review-header" style="flex-wrap: wrap;">
                        <div class="review-avatar">
                            <?php if (!empty($u['foto']) && file_exists(__DIR__.'/../../../uploads/profil/'.$u['foto'])): ?>
                                <img src="../../../uploads/profil/<?= htmlspecialchars($u['foto']) ?>" alt="">
                            <?php else: ?>
                                <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="review-meta">
                            <div class="review-name"><?= htmlspecialchars($u['nama']) ?></div>
                            <div class="review-email"><?= htmlspecialchars($u['email']) ?></div>
                        </div>
                        <div style="margin-left: auto; display: flex; align-items: center; gap: 8px;">
                            <?php if ($u['tampilkan'] == 1): ?>
                                <span class="badge bg-success" style="font-size: 11px; padding: 4px 8px; border-radius: 6px; background-color: var(--admin-green) !important; color: white;">Ditampilkan</span>
                            <?php else: ?>
                                <span class="badge bg-secondary" style="font-size: 11px; padding: 4px 8px; border-radius: 6px; background-color: #ef4444 !important; color: white;">Disembunyikan</span>
                            <?php endif; ?>
                            <div class="review-date"><?= date('j M Y, H:i', strtotime($u['created_at'])) ?></div>
                        </div>
                    </div>
                    <div class="review-stars">
                        <?php for ($s=1; $s<=5; $s++) echo ($s <= (int)$u['rating']) ? '★' : '☆'; ?>
                        <span style="font-size:12px; color:#64748b; margin-left:6px;">(<?= $u['rating'] ?>/5)</span>
                    </div>
                    <div class="review-text"><?= nl2br(htmlspecialchars($u['komentar'])) ?></div>

                    <?php if (!empty($u['foto_ulasan'])): ?>
                        <div style="margin-bottom:8px;">
                            <img src="../../../uploads/ulasan/<?= htmlspecialchars($u['foto_ulasan']) ?>" alt="Foto" class="review-photo"
                                 onclick="window.open('../../../uploads/ulasan/<?= htmlspecialchars($u['foto_ulasan']) ?>','_blank')">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($u['balasan_admin'])): ?>
                        <div class="reply-box">
                            <div class="reply-label">Balasan Admin</div>
                            <div class="reply-text"><?= nl2br(htmlspecialchars($u['balasan_admin'])) ?></div>
                            <?php if ($u['balasan_at']): ?>
                                <div class="reply-date"><?= date('j M Y, H:i', strtotime($u['balasan_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="review-actions">
                        <button class="btn-action btn-reply" onclick="openReply(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode($u['nama'])) ?>, <?= htmlspecialchars(json_encode($u['balasan_admin'] ?? '')) ?>)">
                            <i data-lucide="<?= empty($u['balasan_admin']) ? 'message-circle' : 'edit-3' ?>" style="width:14px;height:14px;"></i>
                            <?= empty($u['balasan_admin']) ? 'Balas' : 'Edit Balasan' ?>
                        </button>
                        <a href="list_ulasan.php?action=toggle_tampilkan&id=<?= $u['id'] ?>&status=<?= $u['tampilkan'] ? 0 : 1 ?>" class="btn-action" style="background: <?= $u['tampilkan'] ? '#f3f4f6' : '#e0f2fe' ?>; color: <?= $u['tampilkan'] ? '#374151' : '#0284c7' ?>; text-decoration: none;">
                            <i data-lucide="<?= $u['tampilkan'] ? 'eye-off' : 'eye' ?>" style="width:14px;height:14px;"></i>
                            <?= $u['tampilkan'] ? 'Sembunyikan' : 'Tampilkan' ?>
                        </a>
                        <a href="hapus_ulasan.php?id=<?= $u['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus ulasan dari <?= htmlspecialchars($u['nama']) ?>? Tindakan ini tidak bisa dibatalkan.')">
                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i> Hapus
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Reply Modal -->
<div class="modal-backdrop-custom" id="replyModal">
    <div class="modal-box">
        <div class="modal-head">
            <h4 id="replyTitle">Balas Ulasan</h4>
            <button class="modal-close" onclick="closeReply()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="ulasan_id" id="replyUlasanId">
                <label style="font-size:13px; font-weight:600; margin-bottom:8px; display:block;">Balasan Admin</label>
                <textarea name="balasan" id="replyText" class="reply-textarea" placeholder="Tulis balasan untuk penghuni..."></textarea>
                <div style="font-size:11px; color:#9ca3af; margin-top:6px;">Kosongkan untuk menghapus balasan.</div>
                <div style="display:flex; gap:10px; margin-top:14px;">
                    <button type="submit" class="btn-submit-reply">Simpan Balasan</button>
                    <button type="button" class="btn-action btn-delete" onclick="closeReply()" style="margin-top:0;">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>
lucide.createIcons();

// Search
document.getElementById('searchInput')?.addEventListener('keyup', applyFilters);

// Filter buttons
let activeFilter = 'all';
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.filter;
        applyFilters();
    });
});

function applyFilters() {
    const search = (document.getElementById('searchInput')?.value || '').toLowerCase();
    document.querySelectorAll('.review-card').forEach(card => {
        const text = card.innerText.toLowerCase();
        const rating = card.dataset.rating;
        const replied = card.dataset.replied;
        let show = text.includes(search);
        if (activeFilter === 'norep') show = show && replied === '0';
        else if (activeFilter !== 'all') show = show && rating === activeFilter;
        card.style.display = show ? '' : 'none';
    });
}

function openReply(id, name, existing) {
    document.getElementById('replyUlasanId').value = id;
    document.getElementById('replyTitle').textContent = 'Balas Ulasan - ' + name;
    document.getElementById('replyText').value = existing || '';
    document.getElementById('replyModal').classList.add('show');
    setTimeout(() => document.getElementById('replyText').focus(), 200);
}
function closeReply() {
    document.getElementById('replyModal').classList.remove('show');
}
document.getElementById('replyModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeReply();
});


</script>
</body>
</html>
