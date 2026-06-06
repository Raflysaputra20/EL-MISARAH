<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . '/init.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penghuni") {
    header("Location: ../api/auth/login.php");
    exit;
}
$namaUser = $_SESSION["nama"] ?? "Penghuni";

try {
    $stmt = $conn->query("SELECT * FROM informasi ORDER BY pinned DESC, id DESC");
    $pengumumanList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $pengumumanList = []; }

try {
    $stmtFoto = $conn->prepare("SELECT foto FROM users WHERE id = ?");
    $stmtFoto->execute([$_SESSION["user_id"]]);
    $userFoto = $stmtFoto->fetchColumn();
} catch (Exception $e) { $userFoto = null; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - Kost Elmi Sarah</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css">
    <style>
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
        .topbar-right { display:flex; align-items:center; gap:12px; }
        .avatar { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,#9ca3af,#6b7280); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; color:white; flex-shrink:0; overflow:hidden; }
        .avatar img { width:100%; height:100%; object-fit:cover; }
        .user-name { font-weight:600; font-size:14px; line-height:1.2; }
        .user-role { font-size:11.5px; color:var(--gray); }
        .content { padding:24px 28px; flex-grow:1; }

        /* Announcement Cards */
        .ann-card {
            background:white; border-radius:14px;
            box-shadow:0 2px 10px rgba(0,0,0,.04);
            padding:20px 24px; margin-bottom:14px;
            display:flex; align-items:flex-start; gap:18px;
            transition:box-shadow .2s;
            animation:fadeUp .35s ease forwards; opacity:0;
        }
        .ann-card:hover { box-shadow:0 4px 18px rgba(0,0,0,.08); }
        .ann-card:nth-child(1) { animation-delay:.05s; }
        .ann-card:nth-child(2) { animation-delay:.10s; }
        .ann-card:nth-child(3) { animation-delay:.15s; }
        .ann-card:nth-child(4) { animation-delay:.20s; }
        .ann-card:nth-child(5) { animation-delay:.25s; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

        .ann-icon {
            width:48px; height:48px; border-radius:50%;
            background:var(--green-light);
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0;
        }
        .ann-body { flex:1; }
        .ann-title-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px; }
        .ann-title { font-size:14px; font-weight:700; color:var(--dark); }
        .badge-pinned {
            display:inline-flex; align-items:center; gap:4px;
            background:var(--green-light); color:var(--green);
            border-radius:20px; padding:3px 10px;
            font-size:11px; font-weight:600;
        }
        .ann-desc { font-size:12.5px; color:var(--gray); line-height:1.55; }
        .ann-date { font-size:11.5px; color:#9ca3af; white-space:nowrap; flex-shrink:0; padding-top:2px; }
        .empty-state { text-align:center; padding:60px 0; color:var(--gray); font-size:13px; }
    </style>
</head>
<body>
<aside class="sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-brand">
        <span class="sidebar-brand-name">Elmi Sarah</span>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="dashboard.php" class="sidebar-link"><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="pembayaran.php" class="sidebar-link"><i data-lucide="credit-card" class="sidebar-icon"></i> Pembayaran</a></li>
        <li class="sidebar-item"><a href="riwayat_pengaduan.php" class="sidebar-link"><i data-lucide="wrench" class="sidebar-icon"></i> Pengaduan Kost</a></li>
        <li class="sidebar-item"><a href="pengumuman.php" class="sidebar-link active"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="riwayat_sewa.php" class="sidebar-link"><i data-lucide="history" class="sidebar-icon"></i> Riwayat Sewa</a></li>
        <li class="sidebar-item"><a href="informasi_kost.php" class="sidebar-link"><i data-lucide="info" class="sidebar-icon"></i> Informasi Kost</a></li>
        <li class="sidebar-item"><a href="ulasan.php" class="sidebar-link"><i data-lucide="star" class="sidebar-icon"></i> Ulasan</a></li>
        <li class="sidebar-item"><a href="profil.php" class="sidebar-link"><i data-lucide="user" class="sidebar-icon"></i> Profil Saya</a></li>
        <li class="sidebar-item"><a href="pengaturan.php" class="sidebar-link"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-keluar"><i data-lucide="log-out" style="width:16px;height:16px;"></i> Keluar</a>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 class="topbar-title">Pengumuman</h2>
        </div>
        <a href="profil.php" class="topbar-right" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar">
                    <?php if ($userFoto): ?><img src="../uploads/profil/<?= htmlspecialchars(basename($userFoto)) ?>" alt="Profil"><?php else: ?><?= strtoupper(substr($namaUser,0,1)) ?><?php endif; ?>
                </div>
                <div class="topbar-user-info"><div class="user-name"><?= htmlspecialchars($namaUser) ?></div><div class="user-role">Penghuni</div></div>
            </div>
        </a>
    </header>
    <main class="content">
        <?php if (empty($pengumumanList)): ?>
            <div class="empty-state"><i data-lucide="megaphone" style="width:40px;height:40px;display:block;margin:0 auto 12px;color:#d1d5db;"></i>Belum ada pengumuman.</div>
        <?php else: ?>
            <?php foreach ($pengumumanList as $p):
                $isPinned = !empty($p['pinned']);
                $tgl = date('j M Y', strtotime($p['created_at']));
            ?>
            <div class="ann-card">
                <div class="ann-icon">
                    <i data-lucide="megaphone" style="width:22px;height:22px;color:var(--green);"></i>
                </div>
                <div class="ann-body">
                    <div class="ann-title-row">
                        <span class="ann-title"><?= htmlspecialchars($p['judul']) ?></span>
                        <?php if ($isPinned): ?>
                            <span class="badge-pinned"><i data-lucide="pin" style="width:10px;height:10px;"></i> disematkan</span>
                        <?php endif; ?>
                    </div>
                    <div class="ann-desc"><?= htmlspecialchars(mb_strimwidth($p['isi'], 0, 120, '...')) ?></div>
                </div>
                <div class="ann-date"><?= $tgl ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
