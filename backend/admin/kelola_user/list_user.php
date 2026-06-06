<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $uid = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'activate') {
        $conn->prepare("UPDATE users SET status = 'aktif' WHERE id = ?")->execute([$uid]);
    } elseif ($action === 'deactivate') {
        $conn->prepare("UPDATE users SET status = 'nonaktif' WHERE id = ?")->execute([$uid]);
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$uid]);
    } elseif ($action === 'reset_password') {
        $newPass = password_hash('123456', PASSWORD_DEFAULT);
        $conn->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newPass, $uid]);
        $_SESSION['success_msg'] = "Password user berhasil di-reset ke '123456'";
    }
    header("Location: list_user.php");
    exit;
}

// Fetch all users (non-admin)
$stmt = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalUser = count($users);
$totalAktif = count(array_filter($users, fn($u) => $u['status'] === 'aktif'));
$totalNonaktif = $totalUser - $totalAktif;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --admin-green: #11a654; --admin-bg: #f4f6f8; --admin-text-dark: #1f2937; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--admin-bg); margin: 0; color: var(--admin-text-dark); overflow-x: hidden; }
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
        .btn-keluar {
            display: inline-flex; align-items: center;
            background-color: white; color: var(--admin-text-dark);
            text-decoration: none; padding: 8px 20px;
            border-radius: 25px; font-weight: 600; font-size: 13px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .btn-keluar:hover { background-color: #f3f4f6; color: var(--admin-text-dark); }
        .admin-main { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar { height: 68px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 100; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }
        .stat-card { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); height: 100%; }
        .stat-card-title { font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card-value { font-size: 32px; font-weight: 800; }
        .p-table-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); margin-top: 25px; overflow: visible; }
        .p-table { width: 100%; border-collapse: collapse; }
        .p-table thead { background: var(--admin-green); color: white; }
        .p-table th { padding: 16px 20px; font-size: 13px; font-weight: 600; text-align: left; }
        .p-table td { padding: 18px 20px; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .p-table tr:last-child td { border-bottom: none; }
        .badge-role { padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .role-user { background: #dbeafe; color: #2563eb; }
        .role-penghuni { background: #dcfce7; color: #16a34a; }
        .badge-status { padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 800; display: inline-block; border: 1px solid; }
        .status-aktif { border-color: var(--admin-green); color: var(--admin-green); }
        .status-nonaktif { border-color: #ef4444; color: #ef4444; }
        .dropdown-menu { border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 8px; font-size: 13.5px; z-index: 1050; }
        .dropdown-item { border-radius: 8px; padding: 8px 15px; font-weight: 500; }
        .dropdown-item:hover { background-color: #f8fafc; color: var(--admin-green); }
        .search-box { background: #f0f2f5; border: none; border-radius: 30px; padding: 10px 18px 10px 42px; font-size: 13px; font-family: 'Poppins',sans-serif; color: #374151; width: 300px; outline: none; }
        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; top: 50%; left: 16px; transform: translateY(-50%); color: #9ca3af; }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-header">
        <h1 class="sidebar-brand">Elmi Sarah</h1>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="../dashboard.php" class="sidebar-link "><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="../kelola_penghuni/list_penghuni.php" class="sidebar-link "><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li class="sidebar-item"><a href="../kelola_user/list_user.php" class="sidebar-link active"><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="../kelola_kamar/list_kamar.php" class="sidebar-link "><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li class="sidebar-item"><a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link "><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
        <li class="sidebar-item"><a href="../kelola_pengaduan/list_pengaduan.php" class="sidebar-link "><i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan</a></li>
        <li class="sidebar-item"><a href="../kelola_booking/list_booking.php" class="sidebar-link "><i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking</a></li>
        <li class="sidebar-item"><a href="../kelola_pengumuman/list_pengumuman.php" class="sidebar-link "><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="../kelola_ulasan/list_ulasan.php" class="sidebar-link "><i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan</a></li>
        <li class="sidebar-item"><a href="../kelola_galeri/list_galeri.php" class="sidebar-link "><i data-lucide="image" class="sidebar-icon"></i> Kelola Galeri</a></li>
        <li class="sidebar-item"><a href="../pengaturan.php" class="sidebar-link "><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../../logout.php" class="btn-keluar"><i data-lucide="log-out" class="sidebar-icon" style="color:#1f2937; margin-right:8px;"></i> Keluar</a>
    </div>
</aside>

<div class="admin-main">
    <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 style="font-size: 20px; font-weight: 800; margin: 0;">Manajemen Akun User</h2>
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
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success mb-3" style="font-size:13px;"><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="stat-card"><div class="stat-card-title">Total User</div><div class="stat-card-value"><?= $totalUser ?></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="stat-card-title">Aktif</div><div class="stat-card-value" style="color:var(--admin-green);"><?= $totalAktif ?></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="stat-card-title">Nonaktif</div><div class="stat-card-value" style="color:#ef4444;"><?= $totalNonaktif ?></div></div></div>
        </div>

        <div class="mb-3">
            <div class="search-wrap">
                <i data-lucide="search" style="width:16px;height:16px;"></i>
                <input type="text" class="search-box" id="searchInput" placeholder="Cari nama atau email..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="p-table-card">
            <table class="p-table" id="userTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Kontak</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data user.</td></tr>
                <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700;"><?= htmlspecialchars($u['nama']) ?></div>
                            <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($u['email']) ?></div>
                        </td>
                        <td style="color:#64748b; font-weight:600; font-size:13px;"><?= htmlspecialchars($u['no_hp'] ?? '-') ?></td>
                        <td><span class="badge-role <?= $u['role'] === 'penghuni' ? 'role-penghuni' : 'role-user' ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td><span class="badge-status <?= $u['status'] === 'aktif' ? 'status-aktif' : 'status-nonaktif' ?>"><?= strtoupper($u['status']) ?></span></td>
                        <td style="font-size:12px; color:#9ca3af;"><?= date('j M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle fw-bold" data-bs-toggle="dropdown" style="font-size:12px;">Opsi</button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../kelola_penghuni/detail_penghuni.php?id=<?= $u['id'] ?>"><i data-lucide="eye" style="width:14px; margin-right:8px;"></i> Lihat Detail</a></li>
                                    <li><a class="dropdown-item" href="../kelola_penghuni/edit_penghuni.php?id=<?= $u['id'] ?>"><i data-lucide="edit-3" style="width:14px; margin-right:8px;"></i> Edit Profil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php if ($u['status'] === 'aktif'): ?>
                                        <li><a class="dropdown-item text-warning" href="list_user.php?action=deactivate&id=<?= $u['id'] ?>" onclick="return confirm('Nonaktifkan akun ini?')"><i data-lucide="user-x" style="width:14px; margin-right:8px;"></i> Nonaktifkan</a></li>
                                    <?php else: ?>
                                        <li><a class="dropdown-item text-success" href="list_user.php?action=activate&id=<?= $u['id'] ?>" onclick="return confirm('Aktifkan kembali akun ini?')"><i data-lucide="user-check" style="width:14px; margin-right:8px;"></i> Aktifkan</a></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item text-info" href="list_user.php?action=reset_password&id=<?= $u['id'] ?>" onclick="return confirm('Reset password user ini ke 123456?')"><i data-lucide="key" style="width:14px; margin-right:8px;"></i> Reset Password</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="list_user.php?action=delete&id=<?= $u['id'] ?>" onclick="return confirm('Hapus permanen akun ini? Data tidak bisa dikembalikan!')"><i data-lucide="trash-2" style="width:14px; margin-right:8px;"></i> Hapus Akun</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
lucide.createIcons();
function filterTable() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#userTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none';
    });
}
</script>
</body>
</html>
