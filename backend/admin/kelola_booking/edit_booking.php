<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list_booking.php");
    exit;
}

// Fetch booking data
$stmt = $conn->prepare("
    SELECT b.*, u.nama, k.nomor_kamar 
    FROM booking b
    JOIN users u ON b.user_id = u.id
    JOIN kamar k ON b.kamar_id = k.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: list_booking.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_masuk = trim($_POST['tanggal_masuk']);
    $durasi = trim($_POST['durasi']);
    $kamar_id = trim($_POST['kamar_id']);

    try {
        $conn->beginTransaction();

        // If room changed, release old room and reserve new room
        if ($booking['kamar_id'] != $kamar_id) {
            // Release old room if it's currently booked or occupied by this booking logic
            $stmtRelease = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id = ?");
            $stmtRelease->execute([$booking['kamar_id']]);

            // Reserve new room
            // In a real app we might need to check if the new room is actually available.
            // For now, we trust the admin selection.
            $stmtReserve = $conn->prepare("UPDATE kamar SET status = 'dibooking' WHERE id = ?");
            $stmtReserve->execute([$kamar_id]);
        }

        // Update booking
        $stmtUpdate = $conn->prepare("UPDATE booking SET tanggal_masuk = ?, durasi_bulan = ?, kamar_id = ? WHERE id = ?");
        $stmtUpdate->execute([$tanggal_masuk, $durasi, $kamar_id, $id]);

        $conn->commit();
        header("Location: list_booking.php?success=Booking+berhasil+diupdate");
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Fetch available rooms for the dropdown
$stmtKamar = $conn->query("SELECT id, nomor_kamar, tipe FROM kamar WHERE status = 'tersedia' OR id = " . (int)$booking['kamar_id'] . " ORDER BY nomor_kamar");
$kamars = $stmtKamar->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <style>
        :root {
            --admin-green: #11a654;
            --admin-bg: #f4f6f8;
            --admin-text-dark: #1f2937;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--admin-bg); margin: 0; color: var(--admin-text-dark); }
        .admin-sidebar { width: 240px; height: 100vh; background-color: var(--admin-green); position: fixed; top: 0; left: 0; display: flex; flex-direction: column; color: white; z-index: 1000; border-top-right-radius: 15px; border-bottom-right-radius: 15px; }
        .sidebar-header { padding: 25px 20px; display: flex; align-items: center; justify-content: space-between; }
        .sidebar-brand { font-size: 22px; font-weight: 700; color: white; text-decoration: none; margin: 0; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .sidebar-link { display: flex; align-items: center; padding: 10px 20px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 13.5px; font-weight: 500; border-top-left-radius: 25px; border-bottom-left-radius: 25px; margin-left: 15px; margin-bottom: 5px; }
        .sidebar-link:hover { color: white; background-color: rgba(255,255,255,0.1); }
        .sidebar-link.active { background-color: var(--admin-bg); color: var(--admin-green); font-weight: 600; }
        .sidebar-icon { width: 18px; height: 18px; margin-right: 12px; }
        .sidebar-footer { padding: 20px 15px; margin-bottom: 15px; }
        .btn-keluar { display: inline-flex; align-items: center; background-color: white; color: var(--admin-text-dark); text-decoration: none; padding: 8px 20px; border-radius: 25px; font-weight: 600; font-size: 13px; }
        .admin-main { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar { height: 70px; background-color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; border-bottom: 1px solid #e5e7eb; }
        .page-title { font-size: 20px; font-weight: 600; margin: 0; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 38px; height: 38px; background-color: #d1d5db; border-radius: 50%; }
        .user-name { font-weight: 600; font-size: 13.5px; }
        .user-role { font-size: 11px; color: #9ca3af; font-weight: 500; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }
        
        .form-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); max-width: 600px; }
        .form-label-custom { font-size: 13.5px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .form-control-custom, .form-select-custom { font-size: 14px; padding: 10px 14px; border-radius: 8px; border: 1px solid #e5e7eb; width: 100%; outline: none; }
        .form-control-custom:focus, .form-select-custom:focus { border-color: var(--admin-green); box-shadow: 0 0 0 3px rgba(17,166,84,0.1); }
        .btn-submit { background-color: var(--admin-green); color: white; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background-color: #0e9148; }
        .btn-kembali { display: inline-block; background-color: #e5e7eb; color: #374151; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; margin-right: 10px; }
        .btn-kembali:hover { background-color: #d1d5db; color: #1f2937; }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="admin-sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-header"><h1 class="sidebar-brand">Elmi Sarah</h1></div>
    <ul class="sidebar-menu">
        <li><a href="../dashboard.php" class="sidebar-link"><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li><a href="../kelola_penghuni/list_penghuni.php" class="sidebar-link"><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li><a href="../kelola_kamar/list_kamar.php" class="sidebar-link"><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li><a href="../kelola_pembayaran/list_pembayaran.php" class="sidebar-link"><i data-lucide="wallet" class="sidebar-icon"></i> Pembayaran</a></li>
        <li><a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link"><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
        <li><a href="../kelola_pengaduan/list_pengaduan.php" class="sidebar-link"><i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan</a></li>
        <li><a href="list_booking.php" class="sidebar-link active"><i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking</a></li>
        <li><a href="../kelola_pengumuman/list_pengumuman.php" class="sidebar-link"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li><a href="../pengaturan.php" class="sidebar-link"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
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
            <h2 class="page-title">Edit Booking</h2>
        </div>
        <div class="topbar-right">
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
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" style="font-size:13px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <h4 class="mb-4" style="font-weight:600; font-size:18px;">Informasi Booking: <?= htmlspecialchars($booking['nama']) ?></h4>
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label-custom">Kamar</label>
                    <select name="kamar_id" class="form-select-custom" required>
                        <?php foreach ($kamars as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $k['id'] == $booking['kamar_id'] ? 'selected' : '' ?>>
                                No. <?= htmlspecialchars($k['nomor_kamar']) ?> (<?= htmlspecialchars($k['tipe']) ?>)
                                <?= $k['id'] == $booking['kamar_id'] ? ' - (Kamar Saat Ini)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label-custom">Tanggal Masuk</label>
                    <input type="date" name="tanggal_masuk" class="form-control-custom" value="<?= htmlspecialchars($booking['tanggal_masuk']) ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label-custom">Durasi Sewa (Bulan)</label>
                    <select name="durasi" class="form-select-custom" required>
                        <option value="1" <?= ($booking['durasi_bulan'] ?? 1) == 1 ? 'selected' : '' ?>>1 Bulan</option>
                        <option value="3" <?= ($booking['durasi_bulan'] ?? 1) == 3 ? 'selected' : '' ?>>3 Bulan</option>
                        <option value="6" <?= ($booking['durasi_bulan'] ?? 1) == 6 ? 'selected' : '' ?>>6 Bulan</option>
                        <option value="12" <?= ($booking['durasi_bulan'] ?? 1) == 12 ? 'selected' : '' ?>>1 Tahun</option>
                        <option value="24" <?= ($booking['durasi_bulan'] ?? 1) == 24 ? 'selected' : '' ?>>2 Tahun</option>
                        <option value="36" <?= ($booking['durasi_bulan'] ?? 1) == 36 ? 'selected' : '' ?>>3 Tahun</option>
                        <option value="48" <?= ($booking['durasi_bulan'] ?? 1) == 48 ? 'selected' : '' ?>>4 Tahun</option>
                        <option value="60" <?= ($booking['durasi_bulan'] ?? 1) == 60 ? 'selected' : '' ?>>5 Tahun</option>
                    </select>
                </div>

                <div class="mt-4 pt-3" style="border-top:1px solid #f3f4f6;">
                    <a href="list_booking.php" class="btn-kembali">Batal</a>
                    <button type="submit" class="btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
