<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../api/auth/login.php");
    exit;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kata_sandi_lama  = $_POST['kata_sandi_lama']  ?? '';
    $kata_sandi_baru  = $_POST['kata_sandi_baru']  ?? '';
    $konfirmasi       = $_POST['konfirmasi']        ?? '';

    // Fetch current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($kata_sandi_lama, $user['password'])) {
        $error = 'Kata sandi lama tidak sesuai.';
    } elseif (strlen($kata_sandi_baru) < 8) {
        $error = 'Kata sandi baru minimal 8 karakter.';
    } elseif (!preg_match('/[A-Z]/', $kata_sandi_baru) || !preg_match('/[a-z]/', $kata_sandi_baru) || !preg_match('/[0-9]/', $kata_sandi_baru) || !preg_match('/[^A-Za-z0-9]/', $kata_sandi_baru)) {
        $error = 'Kata sandi baru harus mengandung kombinasi huruf besar, huruf kecil, angka, dan simbol.';
    } elseif ($kata_sandi_baru !== $konfirmasi) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        $hashed = password_hash($kata_sandi_baru, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$hashed, $_SESSION['user_id']]);
        $success = 'Kata sandi berhasil diubah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css?v=1.2">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Topbar layout */
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info { display: flex; flex-direction: column; }
        .avatar { width: 38px; height: 38px; background: #d1d5db; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; overflow: hidden; }
        .user-name { font-weight: 600; font-size: 13.5px; line-height: 1.2; }
        .user-role { font-size: 11px; color: #9ca3af; font-weight: 500; }
        .notification-btn { background: none; border: none; outline: none; cursor: pointer; padding: 6px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #1f2937; transition: background 0.15s; }
        .notification-btn:hover { background: rgba(0,0,0,0.06); }

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

        /* Settings Card */
        .settings-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            padding: 36px 40px;
            max-width: 700px;
        }
        .settings-card-title {
            font-size: 20px; font-weight: 700;
            color: #1f2937; margin-bottom: 28px;
        }

        /* Form Fields */
        .field-label {
            font-size: 13px; font-weight: 500;
            color: #374151; margin-bottom: 8px;
        }
        .field-input-wrap { position: relative; margin-bottom: 22px; }
        .field-input {
            width: 100%;
            background-color: #f3f5f7;
            border: none;
            border-radius: 12px;
            padding: 14px 50px 14px 20px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #374151;
            outline: none;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .field-input:focus {
            background-color: #eaf7f0;
            box-shadow: 0 0 0 2px rgba(17,166,84,0.2);
        }
        .field-input::placeholder { font-weight: 400; color: #9ca3af; }
        .toggle-pw {
            position: absolute; right: 16px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #9ca3af; padding: 0;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: #374151; }

        /* Save Button */
        .btn-simpan-row {
            display: flex; justify-content: flex-end; margin-top: 8px;
        }
        .btn-simpan-perubahan {
            background-color: var(--admin-green);
            color: white; border: none;
            border-radius: 12px; padding: 13px 36px;
            font-size: 14px; font-weight: 600;
            font-family: 'Poppins', sans-serif; cursor: pointer;
            transition: background 0.2s;
            min-width: 200px;
        }
        .btn-simpan-perubahan:hover { background-color: #0e9148; }

        /* Alert */
        .alert-custom {
            border-radius: 10px; padding: 12px 18px;
            font-size: 13px; margin-bottom: 20px;
        }
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
        <li class="sidebar-item"><a href="dashboard.php" class="sidebar-link "><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="kelola_penghuni/list_penghuni.php" class="sidebar-link "><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li class="sidebar-item"><a href="kelola_user/list_user.php" class="sidebar-link "><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="kelola_kamar/list_kamar.php" class="sidebar-link "><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li class="sidebar-item"><a href="kelola_tagihan/list_tagihan.php" class="sidebar-link "><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
        <li class="sidebar-item"><a href="kelola_pengaduan/list_pengaduan.php" class="sidebar-link "><i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan</a></li>
        <li class="sidebar-item"><a href="kelola_booking/list_booking.php" class="sidebar-link "><i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking</a></li>
        <li class="sidebar-item"><a href="kelola_pengumuman/list_pengumuman.php" class="sidebar-link "><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="kelola_ulasan/list_ulasan.php" class="sidebar-link "><i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan</a></li>
        <li class="sidebar-item"><a href="kelola_galeri/list_galeri.php" class="sidebar-link "><i data-lucide="image" class="sidebar-icon"></i> Kelola Galeri</a></li>
        <li class="sidebar-item"><a href="pengaturan.php" class="sidebar-link active"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-keluar"><i data-lucide="log-out" class="sidebar-icon" style="color:#1f2937; margin-right:8px;"></i> Keluar</a>
    </div>
</aside>

<!-- Main -->
<div class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 class="page-title">Pengaturan</h2>
        </div>
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
        <div class="settings-card">
            <h3 class="settings-card-title">Ubah Kata Sandi</h3>

            <?php if ($success): ?>
                <div class="alert-custom" style="background-color:#dcfce7; color:#166534;">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-custom" style="background-color:#fee2e2; color:#991b1b;">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="pengaturan.php">
                <!-- Kata Sandi Lama -->
                <div>
                    <div class="field-label">Kata Sandi Lama</div>
                    <div class="field-input-wrap">
                        <input type="password" name="kata_sandi_lama" id="pw_lama"
                               class="field-input" placeholder="Masukkan kata sandi lama" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw_lama', this)">
                            <i data-lucide="eye" style="width:18px; height:18px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Kata Sandi Baru -->
                <div>
                    <div class="field-label">Kata Sandi Baru</div>
                    <div class="field-input-wrap">
                        <input type="password" name="kata_sandi_baru" id="pw_baru"
                               class="field-input" placeholder="Min. 8 karakter (huruf besar, kecil, angka, simbol)" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw_baru', this)">
                            <i data-lucide="eye" style="width:18px; height:18px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Konfirmasi -->
                <div>
                    <div class="field-label">Konfirmasi Kata Sandi</div>
                    <div class="field-input-wrap">
                        <input type="password" name="konfirmasi" id="pw_konfirmasi"
                               class="field-input" placeholder="Ulangi kata sandi baru" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw_konfirmasi', this)">
                            <i data-lucide="eye" style="width:18px; height:18px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="btn-simpan-row">
                    <button type="submit" class="btn-simpan-perubahan">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    // swap icon
    btn.innerHTML = isHidden
        ? '<i data-lucide="eye-off" style="width:18px; height:18px;"></i>'
        : '<i data-lucide="eye" style="width:18px; height:18px;"></i>';
    lucide.createIcons();
}
</script>
</body>
</html>
