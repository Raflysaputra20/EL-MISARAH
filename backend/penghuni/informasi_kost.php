<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . '/init.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penghuni") {
    header("Location: ../api/auth/login.php");
    exit;
}

$userId   = $_SESSION["user_id"];
$namaUser = $_SESSION["nama"] ?? "Penghuni";

// Foto user
try {
    $stmtFoto = $conn->prepare("SELECT foto FROM users WHERE id = ?");
    $stmtFoto->execute([$userId]);
    $userFoto = $stmtFoto->fetchColumn();
} catch (Exception $e) { $userFoto = null; }

// Data informasi kost dari DB (jika ada)
$infoList = [];
try {
    $stmt = $conn->query("SELECT * FROM informasi_kost ORDER BY urutan ASC");
    $infoList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $infoList = []; }

// Fallback sesuai desain Figma (4 kartu aturan)
if (empty($infoList)) {
    $infoList = [
        [
            'icon'    => 'users',
            'judul'   => 'Tamu',
            'deskripsi' => "1. Tamu hanya diperbolehkan sampai pukul 23.00 WIB.\n2. Dilarang membawa tamu lawan jenis ke dalam kamar.\n3. Tamu tidak diperbolehkan menginap tanpa izin.\n4. Tamu wajib menjaga sopan santun serta ketertiban selama berada di lingkungan kos.\n5. Tamu tidak diperkenankan mengganggu kenyamanan penghuni lainnya.\n6. Tamu di perkenankan menginap dalam jangka waktu 1 (satu) hari",
        ],
        [
            'icon'    => 'shield-check',
            'judul'   => 'Ketertiban',
            'deskripsi' => "1. Setiap penghuni wajib menjaga ketertiban, kenyamanan, dan keamanan lingkungan kost.\n2. Penghuni diharapkan tidak membuat keributan, terutama pada malam hari, agar tidak mengganggu kenyamanan penghuni lainnya.\n3. Penghuni wajib menjaga serta merawat fasilitas yang tersedia di kamar maupun di area kos. Jika terjadi kerusakan akibat kelalaian penghuni, maka penghuni yang bersangkutan wajib bertanggung jawab.",
        ],
        [
            'icon'    => 'moon',
            'judul'   => 'Jam Malam',
            'deskripsi' => "Jam malam bagi penghuni ditetapkan hingga pukul 23.00 WIB. Apabila penghuni pulang lebih dari jam tersebut, diharapkan untuk tetap menjaga ketenangan lingkungan kos.",
        ],
        [
            'icon'    => 'shield',
            'judul'   => 'Keamanan',
            'deskripsi' => "1. Barang berharga menjadi tanggung jawab masing-masing penghuni.\n2. Jika kehilangan barang, segera laporkan ke pengelola.\n3. Penghuni dapat melaporkan keluhan kepada pengelola atau sistem pengaduan.\n4. Setiap laporan akan ditindaklanjuti secepatnya.",
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Kost - Kost Elmi Sarah</title>
    <meta name="description" content="Informasi dan peraturan Kost Elmi Sarah untuk penghuni.">
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

        /* GRID 2x2 */
        .info-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:18px; }

        /* INFO CARD */
        .info-card {
            background:white; border-radius:16px;
            box-shadow:0 2px 12px rgba(0,0,0,.05);
            padding:24px 26px;
            animation:fadeUp .35s ease forwards; opacity:0;
        }
        .info-card:nth-child(1) { animation-delay:.05s; }
        .info-card:nth-child(2) { animation-delay:.10s; }
        .info-card:nth-child(3) { animation-delay:.15s; }
        .info-card:nth-child(4) { animation-delay:.20s; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

        .info-card-header { display:flex; align-items:center; gap:14px; margin-bottom:16px; }
        .info-card-icon {
            width:44px; height:44px; border-radius:12px;
            background:var(--green-light);
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .info-card-title { font-size:16px; font-weight:700; color:var(--dark); }

        .info-card-body { font-size:13px; color:var(--gray); line-height:1.8; white-space:pre-line; }
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
        <li class="sidebar-item"><a href="pengumuman.php" class="sidebar-link"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="riwayat_sewa.php" class="sidebar-link"><i data-lucide="history" class="sidebar-icon"></i> Riwayat Sewa</a></li>
        <li class="sidebar-item"><a href="informasi_kost.php" class="sidebar-link active"><i data-lucide="info" class="sidebar-icon"></i> Informasi Kost</a></li>
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
            <h2 class="topbar-title">Informasi Kost</h2>
        </div>
        <a href="profil.php" class="topbar-right" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar">
                    <?php if ($userFoto): ?>
                        <img src="../uploads/profil/<?= htmlspecialchars(basename($userFoto)) ?>" alt="Profil">
                    <?php else: ?>
                        <?= strtoupper(substr($namaUser, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div><div class="user-name"><?= htmlspecialchars($namaUser) ?></div><div class="user-role">Penghuni</div></div>
            </div>
        </a>
    </header>
    <main class="content">
        <div class="info-grid">
            <?php foreach ($infoList as $item):
                $icon  = htmlspecialchars($item['icon'] ?? 'info');
                $judul = htmlspecialchars($item['judul'] ?? '');
                $desk  = $item['deskripsi'] ?? '';
            ?>
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i data-lucide="<?= $icon ?>" style="width:22px;height:22px;color:var(--green);"></i>
                    </div>
                    <div class="info-card-title"><?= $judul ?></div>
                </div>
                <div class="info-card-body"><?= htmlspecialchars($desk) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
