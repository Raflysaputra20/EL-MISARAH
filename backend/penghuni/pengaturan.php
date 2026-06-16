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
$userFoto = $_SESSION["foto"] ?? null;
$success  = '';
$error    = '';

// Ambil foto terbaru dari DB
try {
    $stmtFoto = $conn->prepare("SELECT foto FROM users WHERE id = ?");
    $stmtFoto->execute([$userId]);
    $userFoto = $stmtFoto->fetchColumn() ?: null;
} catch (Exception $e) {}

// Buat tabel pengaturan jika belum ada
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS pengaturan_penghuni (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            user_id      INT NOT NULL UNIQUE,
            notif_email  TINYINT(1) DEFAULT 1,
            notif_tagihan TINYINT(1) DEFAULT 1,
            notif_pengumuman TINYINT(1) DEFAULT 1,
            notif_pengaduan  TINYINT(1) DEFAULT 1,
            privasi_profil   TINYINT(1) DEFAULT 0,
            sesi_aktif_notif TINYINT(1) DEFAULT 1,
            updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Exception $e) {}

// Ambil pengaturan user
$setting = [];
try {
    $stmt = $conn->prepare("SELECT * FROM pengaturan_penghuni WHERE user_id = ?");
    $stmt->execute([$userId]);
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$setting) {
        $conn->prepare("INSERT INTO pengaturan_penghuni (user_id) VALUES (?)")->execute([$userId]);
        $setting = ['notif_email'=>1,'notif_tagihan'=>1,'notif_pengumuman'=>1,'notif_pengaduan'=>1,'privasi_profil'=>0,'sesi_aktif_notif'=>1];
    }
} catch (Exception $e) {
    $setting = ['notif_email'=>1,'notif_tagihan'=>1,'notif_pengumuman'=>1,'notif_pengaduan'=>1,'privasi_profil'=>0,'sesi_aktif_notif'=>1];
}

// Handle POST simpan pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? 'settings';

    if ($action === 'change_password') {
        $oldPw  = $_POST['old_password'] ?? '';
        $newPw  = $_POST['new_password'] ?? '';
        $confPw = $_POST['confirm_password'] ?? '';
        if (!$oldPw || !$newPw || !$confPw) {
            $error = 'Semua field password wajib diisi.';
        } elseif (strlen($newPw) < 8) {
            $error = 'Password baru minimal 8 karakter.';
        } elseif ($newPw !== $confPw) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            try {
                $stmtPw = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmtPw->execute([$userId]);
                $hash = $stmtPw->fetchColumn();
                if (!password_verify($oldPw, $hash)) {
                    $error = 'Password lama salah.';
                } else {
                    $newHash = password_hash($newPw, PASSWORD_DEFAULT);
                    $conn->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $userId]);
                    $success = 'Password berhasil diubah!';
                }
            } catch (Exception $e) {
                $error = 'Gagal mengubah password.';
            }
        }
    } else {
        $data = [
            'notif_email'       => isset($_POST['notif_email'])       ? 1 : 0,
            'notif_tagihan'     => isset($_POST['notif_tagihan'])     ? 1 : 0,
            'notif_pengumuman'  => isset($_POST['notif_pengumuman'])  ? 1 : 0,
            'notif_pengaduan'   => isset($_POST['notif_pengaduan'])   ? 1 : 0,
            'privasi_profil'    => isset($_POST['privasi_profil'])    ? 1 : 0,
            'sesi_aktif_notif'  => isset($_POST['sesi_aktif_notif'])  ? 1 : 0,
        ];
        try {
            $upd = $conn->prepare("
                UPDATE pengaturan_penghuni
                SET notif_email=?, notif_tagihan=?, notif_pengumuman=?, notif_pengaduan=?, privasi_profil=?, sesi_aktif_notif=?
                WHERE user_id=?
            ");
            $upd->execute(array_merge(array_values($data), [$userId]));
            $setting  = array_merge($setting, $data);
            $success  = 'Pengaturan berhasil disimpan!';
        } catch (Exception $e) {
            $error = 'Gagal menyimpan pengaturan.';
        }
    }
}

// Info sesi aktif
$sesiInfo = [
    ['device' => 'Chrome – Windows',  'lokasi' => 'Makassar, ID', 'waktu' => 'Aktif sekarang', 'current' => true],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Kost Elmi Sarah</title>
    <meta name="description" content="Kelola pengaturan akun dan notifikasi Kost Elmi Sarah.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css?v=1.2">
    <style>
        :root { --green:#11a654; --green-light:#e8f7f0; --bg:#f4f6f8; --dark:#1f2937; --gray:#6b7280; --border:#e5e7eb; }
        * { box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:var(--bg); margin:0; overflow-x:hidden; color:var(--dark); }

        /* SIDEBAR — inherits from dashboard-responsive.css?v=1.2 */
        .sidebar { width:240px; height:100vh; background:var(--green); position:fixed; top:0; left:0; display:flex; flex-direction:column; z-index:1000; }
        .sidebar-menu { list-style:none; padding:0; margin:0; flex-grow:1; }
        .sidebar-icon { width:18px; height:18px; flex-shrink:0; }

        /* MAIN */
        .main { margin-left:240px; min-height:100vh; display:flex; flex-direction:column; }

        /* Password field */
        .pw-field { width:100%; border:1.5px solid var(--border); border-radius:10px; padding:10px 14px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; transition:border-color .2s; background:#fff; }
        .pw-field:focus { border-color:var(--green); }
        .pw-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; padding:16px 22px; }
        @media(max-width:768px) { .pw-grid { grid-template-columns:1fr; } }
        .pw-label { font-size:12px; font-weight:600; color:var(--dark); margin-bottom:6px; display:block; }
        .btn-pw { background:var(--green); color:white; border:none; border-radius:10px; padding:9px 22px; font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:6px; }
        .btn-pw:hover { background:#0d8e47; }
        .topbar { height:68px; background:white; display:flex; align-items:center; justify-content:space-between; padding:0 28px; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; }
        .topbar-title { font-size:19px; font-weight:600; margin:0; }
        .topbar-right { display:flex; align-items:center; gap:16px; }
        .notif-btn, .notification-btn { background:none; border:none; outline:none; color:var(--dark); cursor:pointer; padding:6px; border-radius:8px; display:flex; align-items:center; justify-content:center; transition:background .15s; }
        .notif-btn:hover, .notification-btn:hover { background:rgba(0,0,0,.06); }
        .user-profile { display:flex; align-items:center; gap:10px; }
        .avatar { width:38px; height:38px; background:#d1d5db; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:14px; overflow:hidden; }
        .user-info { display:flex; flex-direction:column; }
        .user-name { font-weight:600; font-size:13px; line-height:1.2; }
        .user-role { font-size:11px; color:#9ca3af; }
        .content { padding:24px 28px; flex-grow:1; }

        /* SETTING GRID */
        .setting-grid { display:flex; flex-direction:column; gap:18px; }

        /* SECTION CARD */
        .setting-card { background:white; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.04); overflow:hidden; }
        .setting-card-header { display:flex; align-items:center; gap:10px; padding:18px 22px 14px; border-bottom:1px solid var(--border); }
        .setting-card-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .setting-card-title { font-size:14px; font-weight:700; color:var(--dark); margin:0; }
        .setting-card-desc  { font-size:12px; color:var(--gray); margin:2px 0 0; }

        /* SETTING ROW */
        .setting-row { display:flex; align-items:center; justify-content:space-between; padding:16px 22px; border-bottom:1px solid #f9fafb; transition:background .15s; }
        .setting-row:last-child { border-bottom:none; }
        .setting-row:hover { background:#fafbfc; }
        .setting-row-info { flex:1; margin-right:16px; }
        .setting-row-label { font-size:13px; font-weight:600; color:var(--dark); margin-bottom:3px; }
        .setting-row-sub   { font-size:12px; color:var(--gray); }

        /* TOGGLE SWITCH */
        .toggle-switch { position:relative; width:44px; height:24px; flex-shrink:0; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .toggle-slider {
            position:absolute; inset:0; background:#d1d5db;
            border-radius:24px; cursor:pointer; transition:.25s;
        }
        .toggle-slider::before {
            content:''; position:absolute;
            width:18px; height:18px; left:3px; bottom:3px;
            background:white; border-radius:50%; transition:.25s;
            box-shadow:0 1px 3px rgba(0,0,0,.2);
        }
        .toggle-switch input:checked + .toggle-slider { background:var(--green); }
        .toggle-switch input:checked + .toggle-slider::before { transform:translateX(20px); }

        /* SESSION ITEM */
        .session-item { display:flex; align-items:center; gap:14px; padding:16px 22px; border-bottom:1px solid #f9fafb; }
        .session-item:last-child { border-bottom:none; }
        .session-icon { width:40px; height:40px; border-radius:10px; background:var(--green-light); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .session-device { font-size:13px; font-weight:600; color:var(--dark); margin-bottom:3px; }
        .session-meta { font-size:12px; color:var(--gray); }
        .badge-current { background:var(--green-light); color:var(--green); font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; margin-left:8px; }

        /* ABOUT ROWS */
        .about-row { display:flex; justify-content:space-between; align-items:center; padding:14px 22px; border-bottom:1px solid #f9fafb; font-size:13px; }
        .about-row:last-child { border-bottom:none; }
        .about-row-label { color:var(--gray); font-weight:500; }
        .about-row-value { color:var(--dark); font-weight:600; }

        /* DANGER ZONE */
        .danger-row { display:flex; align-items:center; justify-content:space-between; padding:16px 22px; }
        .danger-label { font-size:13px; font-weight:600; color:#ef4444; margin-bottom:3px; }
        .danger-sub   { font-size:12px; color:var(--gray); }
        .btn-danger { background:#fee2e2; color:#ef4444; border:none; border-radius:10px; padding:8px 20px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; transition:all .2s; white-space:nowrap; }
        .btn-danger:hover { background:#ef4444; color:white; }

        /* SAVE BAR */
        .save-bar { position:sticky; bottom:0; background:white; border-top:1px solid var(--border); padding:14px 28px; display:flex; align-items:center; justify-content:space-between; z-index:50; }
        .alert-success-inline { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--green); font-weight:500; }
        .btn-save { background:var(--green); color:white; border:none; border-radius:10px; padding:10px 28px; font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; display:flex; align-items:center; gap:8px; }
        .btn-save:hover { background:#0d8e47; }

        /* Animation */
        .setting-card { animation:fadeUp .3s ease forwards; opacity:0; }
        .setting-card:nth-child(1) { animation-delay:.05s; }
        .setting-card:nth-child(2) { animation-delay:.10s; }
        .setting-card:nth-child(3) { animation-delay:.15s; }
        .setting-card:nth-child(4) { animation-delay:.20s; }
        .setting-card:nth-child(5) { animation-delay:.25s; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-brand">
        <span class="sidebar-brand-name">Elmi Sarah</span>
    </div>
        <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="dashboard.php" class="sidebar-link "><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="notifikasi.php" class="sidebar-link "><i data-lucide="bell" class="sidebar-icon"></i> Notifikasi</a></li>
        <li class="sidebar-item"><a href="pembayaran.php" class="sidebar-link "><i data-lucide="credit-card" class="sidebar-icon"></i> Pembayaran</a></li>
        <li class="sidebar-item"><a href="riwayat_pengaduan.php" class="sidebar-link "><i data-lucide="wrench" class="sidebar-icon"></i> Pengaduan Kost</a></li>
        <li class="sidebar-item"><a href="pengumuman.php" class="sidebar-link "><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="riwayat_sewa.php" class="sidebar-link "><i data-lucide="history" class="sidebar-icon"></i> Riwayat Sewa</a></li>
        <li class="sidebar-item"><a href="informasi_kost.php" class="sidebar-link "><i data-lucide="info" class="sidebar-icon"></i> Informasi Kost</a></li>
        <li class="sidebar-item"><a href="ulasan.php" class="sidebar-link "><i data-lucide="star" class="sidebar-icon"></i> Ulasan</a></li>
        <li class="sidebar-item"><a href="profil.php" class="sidebar-link "><i data-lucide="user" class="sidebar-icon"></i> Profil Saya</a></li>
        <li class="sidebar-item"><a href="pengaturan.php" class="sidebar-link active"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-keluar"><i data-lucide="log-out" style="width:16px;height:16px;"></i> Keluar</a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">
    <header class="topbar">
        <div style="display:flex; align-items:center; gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px; height:24px;"></i></button>
            <h2 class="topbar-title">Pengaturan</h2>
        </div>
        <div class="topbar-right">
            <div id="notifWrapper" style="position:relative;display:inline-block;">
                    <button id="notifBell" class="notification-btn" onclick="toggleNotif(event)" aria-label="Notifikasi" style="position:relative;">
                        <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
                        <span id="notifBadge" style="display:none;position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:17px;height:17px;border-radius:999px;align-items:center;justify-content:center;padding:0 3px;line-height:17px;text-align:center;">0</span>
                    </button>
                    <!-- DROPDOWN NOTIFIKASI -->
                    <div id="notifDropdown" style="display:none;position:absolute;right:0;top:52px;width:330px;background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,0.14);z-index:9999;overflow:hidden;">
                        <div style="padding:14px 18px 10px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-weight:700;font-size:14px;color:#111;">🔔 Notifikasi</span>
                            <span id="notifCount" style="font-size:11px;color:#888;">Memuat...</span>
                        </div>
                        <div id="notifList" style="max-height:300px;overflow-y:auto;">
                            <div style="padding:20px;text-align:center;color:#aaa;font-size:13px;">Memuat notifikasi...</div>
                        </div>
                    </div>
                </div>
            <div class="user-profile">
                <a href="profil.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:12px;">
                    <div class="avatar">
                        <?php if (isset($userFoto) && $userFoto): ?>
                            <img src="../uploads/profil/<?= htmlspecialchars(basename($userFoto)) ?>" alt="Profil">
                        <?php elseif (isset($foto) && $foto): ?>
                            <img src="../uploads/profil/<?= htmlspecialchars(basename($foto)) ?>" alt="Profil">
                        <?php else: ?>
                            <?= strtoupper(substr($namaUser ?? 'P', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaUser) ?></span>
                        <span class="user-role">Penghuni Kos</span>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <main class="content">
        <?php if ($success): ?>
        <div style="background:#e8f7f0;color:#11a654;padding:12px 18px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i> <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div style="background:#fee2e2;color:#ef4444;padding:12px 18px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <form method="POST" id="settingForm">
        <input type="hidden" name="form_action" value="settings">
        <div class="setting-grid">

            <!-- 1. NOTIFIKASI -->
            <div class="setting-card">
                <div class="setting-card-header">
                    <div class="setting-card-icon" style="background:var(--green-light);">
                        <i data-lucide="bell" style="width:18px;height:18px;color:var(--green);"></i>
                    </div>
                    <div>
                        <div class="setting-card-title">Notifikasi</div>
                        <div class="setting-card-desc">Atur jenis notifikasi yang ingin kamu terima</div>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-row-info">
                        <div class="setting-row-label">Notifikasi Email</div>
                        <div class="setting-row-sub">Terima ringkasan dan pengumuman via email</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notif_email" id="notif_email" <?= $setting['notif_email'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-row-info">
                        <div class="setting-row-label">Pengingat Tagihan</div>
                        <div class="setting-row-sub">Ingatkan saya ketika tagihan mendekati jatuh tempo</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notif_tagihan" id="notif_tagihan" <?= $setting['notif_tagihan'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-row-info">
                        <div class="setting-row-label">Pengumuman Kost</div>
                        <div class="setting-row-sub">Terima notifikasi saat ada pengumuman baru dari admin</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notif_pengumuman" id="notif_pengumuman" <?= $setting['notif_pengumuman'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-row-info">
                        <div class="setting-row-label">Update Status Pengaduan</div>
                        <div class="setting-row-sub">Beritahu saya ketika pengaduan sudah diproses admin</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notif_pengaduan" id="notif_pengaduan" <?= $setting['notif_pengaduan'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- 2. PRIVASI -->
            <div class="setting-card">
                <div class="setting-card-header">
                    <div class="setting-card-icon" style="background:#eff6ff;">
                        <i data-lucide="shield" style="width:18px;height:18px;color:#3b82f6;"></i>
                    </div>
                    <div>
                        <div class="setting-card-title">Privasi</div>
                        <div class="setting-card-desc">Kelola visibilitas data profilmu</div>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-row-info">
                        <div class="setting-row-label">Sembunyikan Profil</div>
                        <div class="setting-row-sub">Profil kamu tidak akan terlihat oleh penghuni lain</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="privasi_profil" id="privasi_profil" <?= $setting['privasi_profil'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- 2b. UBAH PASSWORD -->
            <div class="setting-card">
                <div class="setting-card-header">
                    <div class="setting-card-icon" style="background:#fef3c7;">
                        <i data-lucide="key-round" style="width:18px;height:18px;color:#d97706;"></i>
                    </div>
                    <div>
                        <div class="setting-card-title">Ubah Password</div>
                        <div class="setting-card-desc">Ganti kata sandi akun kamu</div>
                    </div>
                </div>
                <div class="pw-grid">
                    <div>
                        <label class="pw-label">Password Lama</label>
                        <input type="password" name="old_password" form="pwForm" class="pw-field" placeholder="••••••••" required>
                    </div>
                    <div>
                        <label class="pw-label">Password Baru</label>
                        <input type="password" name="new_password" form="pwForm" class="pw-field" placeholder="Min. 8 karakter" required minlength="8">
                    </div>
                    <div>
                        <label class="pw-label">Konfirmasi</label>
                        <input type="password" name="confirm_password" form="pwForm" class="pw-field" placeholder="Ulangi password" required>
                    </div>
                </div>
                <div style="padding:0 22px 18px;">
                    <button type="submit" form="pwForm" class="btn-pw">
                        <i data-lucide="lock" style="width:14px;height:14px;"></i> Ubah Password
                    </button>
                </div>
            </div>

            <!-- 3. KEAMANAN & SESI -->
            <div class="setting-card">
                <div class="setting-card-header">
                    <div class="setting-card-icon" style="background:#fff7ed;">
                        <i data-lucide="lock" style="width:18px;height:18px;color:#f97316;"></i>
                    </div>
                    <div>
                        <div class="setting-card-title">Keamanan</div>
                        <div class="setting-card-desc">Sesi aktif dan pengaturan keamanan akun</div>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-row-info">
                        <div class="setting-row-label">Notifikasi Login Baru</div>
                        <div class="setting-row-sub">Ingatkan saya jika ada login dari perangkat baru</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="sesi_aktif_notif" id="sesi_aktif_notif" <?= $setting['sesi_aktif_notif'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- Active sessions -->
                <?php foreach ($sesiInfo as $sesi): ?>
                <div class="session-item">
                    <div class="session-icon">
                        <i data-lucide="monitor" style="width:18px;height:18px;color:var(--green);"></i>
                    </div>
                    <div style="flex:1;">
                        <div class="session-device">
                            <?= htmlspecialchars($sesi['device']) ?>
                            <?php if ($sesi['current']): ?>
                                <span class="badge-current">Sesi ini</span>
                            <?php endif; ?>
                        </div>
                        <div class="session-meta"><?= htmlspecialchars($sesi['lokasi']) ?> · <?= htmlspecialchars($sesi['waktu']) ?></div>
                    </div>
                    <?php if (!$sesi['current']): ?>
                    <button type="button" class="btn-danger" style="padding:6px 14px;font-size:12px;">Keluarkan</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 4. TENTANG APLIKASI -->
            <div class="setting-card">
                <div class="setting-card-header">
                    <div class="setting-card-icon" style="background:var(--green-light);">
                        <i data-lucide="info" style="width:18px;height:18px;color:var(--green);"></i>
                    </div>
                    <div>
                        <div class="setting-card-title">Tentang Aplikasi</div>
                        <div class="setting-card-desc">Informasi versi dan developer</div>
                    </div>
                </div>
                <div class="about-row">
                    <span class="about-row-label">Nama Aplikasi</span>
                    <span class="about-row-value">Kost Elmi Sarah</span>
                </div>
                <div class="about-row">
                    <span class="about-row-label">Versi</span>
                    <span class="about-row-value">1.0.0</span>
                </div>
                <div class="about-row">
                    <span class="about-row-label">Terakhir Diperbarui</span>
                    <span class="about-row-value">Mei 2026</span>
                </div>
                <div class="about-row">
                    <span class="about-row-label">Platform</span>
                    <span class="about-row-value">Web App · PHP &amp; MySQL</span>
                </div>
            </div>

            <!-- 5. DANGER ZONE -->
            <div class="setting-card">
                <div class="setting-card-header">
                    <div class="setting-card-icon" style="background:#fee2e2;">
                        <i data-lucide="alert-triangle" style="width:18px;height:18px;color:#ef4444;"></i>
                    </div>
                    <div>
                        <div class="setting-card-title" style="color:#ef4444;">Zona Berbahaya</div>
                        <div class="setting-card-desc">Tindakan ini tidak dapat dibatalkan</div>
                    </div>
                </div>
                <div class="danger-row">
                    <div>
                        <div class="danger-label">Keluar dari Semua Perangkat</div>
                        <div class="danger-sub">Akhiri semua sesi aktif di seluruh perangkat</div>
                    </div>
                    <a href="../logout.php" class="btn-danger">
                        <i data-lucide="log-out" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:4px;"></i>
                        Keluar Semua
                    </a>
                </div>
            </div>

        </div><!-- end setting-grid -->

        <!-- SAVE BAR -->
        <div class="save-bar">
            <div>
                <span id="saveStatus" style="font-size:12.5px;color:var(--gray);">Perubahan belum disimpan</span>
            </div>
            <button type="submit" class="btn-save">
                <i data-lucide="save" style="width:15px;height:15px;"></i>
                Simpan Pengaturan
            </button>
        </div>

        </form>
    </main>
</div>

<!-- Separate form for changing password -->
<form id="pwForm" method="POST" style="display:none;">
    <input type="hidden" name="form_action" value="change_password">
</form>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Tandai perubahan belum disimpan
    const saveStatus = document.getElementById('saveStatus');
    document.querySelectorAll('.toggle-switch input').forEach(toggle => {
        toggle.addEventListener('change', () => {
            if (saveStatus) {
                saveStatus.textContent = '● Ada perubahan belum disimpan';
                saveStatus.style.color = '#f59e0b';
                saveStatus.style.fontWeight = '600';
            }
        });
    });

    // Password confirm validation
    const pwForm = document.getElementById('pwForm');
    if (pwForm) {
        pwForm.addEventListener('submit', function(e) {
            const np = document.querySelector('[name=new_password]').value;
            const cp = document.querySelector('[name=confirm_password]').value;
            if (np !== cp) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
            }
        });
    }
</script>
<script src="../assets/js/notifikasi.js"></script>
</body>
</html>
