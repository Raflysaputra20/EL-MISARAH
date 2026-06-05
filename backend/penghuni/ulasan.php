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
$message  = "";
$msgType  = "";

// Handle query parameters
if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $msgType = 'success';
} elseif (isset($_GET['error'])) {
    $message = $_GET['error'];
    $msgType = 'error';
}

// Handle delete my ulasan
if (isset($_GET['action']) && $_GET['action'] === 'delete_my') {
    try {
        $stmtFoto = $conn->prepare("SELECT foto_ulasan FROM ulasan WHERE user_id = ?");
        $stmtFoto->execute([$userId]);
        $foto = $stmtFoto->fetchColumn();

        $del = $conn->prepare("DELETE FROM ulasan WHERE user_id = ?");
        $del->execute([$userId]);

        if ($foto) {
            $fotoPath = __DIR__ . '/../../uploads/ulasan/' . $foto;
            if (file_exists($fotoPath)) {
                @unlink($fotoPath);
            }
        }
        header("Location: ulasan.php?success=" . urlencode("Ulasan Anda berhasil dihapus."));
        exit;
    } catch (Exception $e) {
        header("Location: ulasan.php?error=" . urlencode("Gagal menghapus ulasan: " . $e->getMessage()));
        exit;
    }
}

// Auto-create tabel ulasan jika belum ada
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS ulasan (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            rating     TINYINT NOT NULL DEFAULT 5,
            komentar   TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM ulasan LIKE 'foto_ulasan'");
    if ($check->rowCount() === 0) {
        $conn->exec("ALTER TABLE ulasan ADD COLUMN foto_ulasan VARCHAR(255) DEFAULT NULL");
    }
} catch (Exception $e) {}

// Handle form POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rating   = (int)($_POST['rating'] ?? 0);
    $komentar = trim($_POST['komentar'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $message = "Pilih rating antara 1 sampai 5 bintang.";
        $msgType = "error";
    } elseif (empty($komentar)) {
        $message = "Komentar tidak boleh kosong.";
        $msgType = "error";
    } else {
        try {
            $stmtCek = $conn->prepare("SELECT id FROM ulasan WHERE user_id = ?");
            $stmtCek->execute([$userId]);
            $existing = $stmtCek->fetch();

            $foto_ulasan = null;
            if ($existing) {
                $stmtFotoExist = $conn->prepare("SELECT foto_ulasan FROM ulasan WHERE user_id = ?");
                $stmtFotoExist->execute([$userId]);
                $foto_ulasan = $stmtFotoExist->fetchColumn();
            }

            if (isset($_FILES['foto_ulasan']) && $_FILES['foto_ulasan']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . "/../../uploads/ulasan/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $ext = pathinfo($_FILES['foto_ulasan']['name'], PATHINFO_EXTENSION);
                $fileName = 'ulasan_' . $userId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto_ulasan']['tmp_name'], $uploadDir . $fileName)) {
                    if ($foto_ulasan && file_exists($uploadDir . $foto_ulasan)) {
                        @unlink($uploadDir . $foto_ulasan);
                    }
                    $foto_ulasan = $fileName;
                }
            }

            if ($existing) {
                $upd = $conn->prepare("UPDATE ulasan SET rating = ?, komentar = ?, foto_ulasan = ?, updated_at = NOW() WHERE user_id = ?");
                $upd->execute([$rating, $komentar, $foto_ulasan, $userId]);
                $message = "Ulasan berhasil diperbarui!";
            } else {
                $ins = $conn->prepare("INSERT INTO ulasan (user_id, rating, komentar, foto_ulasan, created_at) VALUES (?, ?, ?, ?, NOW())");
                $ins->execute([$userId, $rating, $komentar, $foto_ulasan]);
                $message = "Ulasan berhasil dikirim! Terima kasih.";
            }
            $msgType = "success";
        } catch (Exception $e) {
            $message = "Gagal menyimpan ulasan: " . $e->getMessage();
            $msgType = "error";
        }
    }
}

// Ambil ulasan user ini
try {
    $stmtMy = $conn->prepare("SELECT rating, komentar, foto_ulasan, created_at FROM ulasan WHERE user_id = ? LIMIT 1");
    $stmtMy->execute([$userId]);
    $myUlasan = $stmtMy->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $myUlasan = null; }

// Ambil semua ulasan (untuk ditampilkan)
try {
    $stmtAll = $conn->query("
        SELECT u.rating, u.komentar, u.foto_ulasan, u.created_at, us.nama
        FROM ulasan u
        JOIN users us ON u.user_id = us.id
        ORDER BY u.created_at DESC LIMIT 20
    ");
    $allUlasan = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $allUlasan = []; }

// Rata-rata rating
$avgRating = 0;
$totalUlasan = count($allUlasan);
if ($totalUlasan > 0) {
    $avgRating = array_sum(array_column($allUlasan, 'rating')) / $totalUlasan;
}

// Ambil foto user
try {
    $stmtFoto = $conn->prepare("SELECT foto FROM users WHERE id = ?");
    $stmtFoto->execute([$userId]);
    $userFoto = $stmtFoto->fetchColumn();
} catch (Exception $e) { $userFoto = null; }

$selectedRating   = $myUlasan['rating'] ?? 0;
$selectedKomentar = $myUlasan['komentar'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan - Kost Elmi Sarah</title>
    <meta name="description" content="Beri ulasan dan lihat ulasan penghuni Kost Elmi Sarah.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css">
    <style>
        :root {
            --green: #11a654;
            --green-light: #e8f7f0;
            --bg: #f4f6f8;
            --dark: #1f2937;
            --gray: #6b7280;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; color: var(--dark); overflow-x: hidden; }

        /* ====== SIDEBAR ====== */
        .sidebar {
            width: 240px; height: 100vh;
            background: var(--green);
            position: fixed; top: 0; left: 0;
            display: flex; flex-direction: column;
            z-index: 1000;
            border-top-right-radius: 18px;
            border-bottom-right-radius: 18px;
            box-shadow: 4px 0 16px rgba(0,0,0,0.08);
        }
        .sidebar-brand {
            padding: 24px 22px 20px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .sidebar-brand-name { font-size: 22px; font-weight: 800; color: white; letter-spacing: -0.3px; }
        .sidebar-menu-icon { color: white; cursor: pointer; opacity: 0.9; }
        .sidebar-menu { list-style: none; padding: 0 12px; margin: 0; flex-grow: 1; }
        .sidebar-item { margin-bottom: 2px; }
        .sidebar-link {
            display: flex; align-items: center; padding: 11px 16px;
            color: rgba(255,255,255,0.85); text-decoration: none;
            font-size: 14px; font-weight: 500;
            border-radius: 12px;
            transition: all 0.2s ease; gap: 12px;
        }
        .sidebar-link:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar-link.active { background: white; color: var(--green); font-weight: 700; }
        .sidebar-icon { width: 18px; height: 18px; flex-shrink: 0; }
        .sidebar-footer { padding: 16px 12px 20px; }
        .btn-keluar {
            display: inline-flex; align-items: center; gap: 8px;
            background: white; color: var(--dark);
            text-decoration: none; padding: 10px 22px;
            border-radius: 30px; font-weight: 700; font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        /* ====== MAIN ====== */
        .main { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar {
            height: 68px; background: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 30px; border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-title { font-size: 20px; font-weight: 700; margin: 0; }
        .topbar-right { display: flex; align-items: center; gap: 14px; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar {
            width: 42px; height: 42px; border-radius: 50%;
            background: linear-gradient(135deg, #9ca3af, #6b7280);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px; color: white;
            flex-shrink: 0; overflow: hidden;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-name { font-weight: 600; font-size: 14px; line-height: 1.2; }
        .user-role { font-size: 11.5px; color: var(--gray); }
        .content { padding: 24px 28px; flex-grow: 1; }

        /* ====== LAYOUT ====== */
        .ulasan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }

        /* ====== CARD ====== */
        .card-section {
            background: white; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-header-row {
            display: flex; align-items: center; gap: 10px;
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
        }
        .card-title { font-size: 15px; font-weight: 700; color: var(--dark); margin: 0; }
        .card-body { padding: 22px 24px; }

        /* ====== SUMMARY RATING ====== */
        .rating-summary {
            display: flex; align-items: center; gap: 20px;
            background: var(--green-light); border-radius: 12px;
            padding: 16px 20px; margin-bottom: 20px;
        }
        .rating-big { font-size: 44px; font-weight: 800; color: var(--green); line-height: 1; }
        .rating-stars-big { color: #facc15; font-size: 20px; letter-spacing: 2px; }
        .rating-count { font-size: 12px; color: var(--gray); margin-top: 4px; }

        /* ====== STAR RATING INPUT ====== */
        .star-input-wrap { margin-bottom: 18px; }
        .star-input-wrap .field-label { font-size: 13px; font-weight: 600; color: var(--dark); margin-bottom: 10px; }
        .stars-row { display: flex; gap: 6px; direction: rtl; justify-content: flex-end; width: fit-content; }
        .stars-row input[type="radio"] { display: none; }
        .stars-row label {
            font-size: 34px; color: #d1d5db; cursor: pointer;
            transition: color 0.15s; line-height: 1;
        }
        .stars-row label:hover,
        .stars-row label:hover ~ label,
        .stars-row input[type="radio"]:checked ~ label { color: #facc15; }

        /* ====== FORM ====== */
        .field-label { font-size: 13px; font-weight: 600; color: var(--dark); margin-bottom: 8px; }
        .field-textarea {
            width: 100%; border: 1.5px solid var(--border); border-radius: 10px;
            padding: 12px 14px; font-size: 13px; font-family: 'Poppins', sans-serif;
            color: var(--dark); outline: none; resize: vertical; min-height: 110px;
            transition: border-color 0.2s; background: #fafbfc;
        }
        .field-textarea:focus { border-color: var(--green); background: white; }
        .field-textarea::placeholder { color: #c0c4cc; }
        .btn-kirim {
            background: var(--green); color: white; border: none;
            border-radius: 10px; padding: 12px 28px;
            font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: background 0.2s; margin-top: 10px;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-kirim:hover { background: #0d8e47; }

        /* Alert */
        .alert-success {
            background: var(--green-light); color: var(--green);
            border-radius: 10px; padding: 10px 14px; font-size: 13px;
            margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
            border-left: 3px solid var(--green);
        }
        .alert-error {
            background: #fee2e2; color: #ef4444;
            border-radius: 10px; padding: 10px 14px; font-size: 13px;
            margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
            border-left: 3px solid #ef4444;
        }

        /* ====== MY REVIEW PREVIEW ====== */
        .my-review-box {
            background: var(--green-light); border-radius: 10px;
            padding: 14px 16px; margin-bottom: 18px;
            border: 1.5px solid rgba(17,166,84,0.2);
        }
        .my-review-label { font-size: 11px; font-weight: 700; color: var(--green); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .my-review-stars { color: #facc15; font-size: 16px; margin-bottom: 4px; }
        .my-review-text { font-size: 12.5px; color: var(--dark); line-height: 1.5; }

        /* ====== REVIEW LIST ====== */
        .review-list { max-height: 480px; overflow-y: auto; padding-right: 4px; }
        .review-list::-webkit-scrollbar { width: 4px; }
        .review-list::-webkit-scrollbar-track { background: transparent; }
        .review-list::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }

        .review-item {
            background: #fafbfc; border-radius: 12px;
            padding: 14px 16px; margin-bottom: 10px;
            border: 1px solid var(--border);
            transition: box-shadow 0.15s;
        }
        .review-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .review-item:last-child { margin-bottom: 0; }

        .review-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 8px; gap: 10px; }
        .review-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green), #0d8e47);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px; color: white; flex-shrink: 0;
        }
        .review-info { flex: 1; }
        .review-name { font-size: 13px; font-weight: 700; color: var(--dark); margin-bottom: 2px; }
        .review-stars { color: #facc15; font-size: 13px; letter-spacing: 1px; }
        .review-date { font-size: 11px; color: #9ca3af; flex-shrink: 0; margin-top: 2px; }
        .review-text { font-size: 12.5px; color: var(--gray); line-height: 1.55; }

        .empty-state { text-align: center; padding: 40px 20px; color: var(--gray); }
        .empty-state-icon {
            width: 56px; height: 56px; border-radius: 50%; background: var(--green-light);
            display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;
        }
        .empty-state p { font-size: 13px; margin: 0; }

        /* Animation */
        .card-section { animation: fadeUp 0.3s ease forwards; opacity: 0; }
        .card-section:nth-child(1) { animation-delay: 0.05s; }
        .card-section:nth-child(2) { animation-delay: 0.12s; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
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
        <li class="sidebar-item"><a href="informasi_kost.php" class="sidebar-link"><i data-lucide="info" class="sidebar-icon"></i> Informasi Kost</a></li>
        <li class="sidebar-item"><a href="ulasan.php" class="sidebar-link active"><i data-lucide="star" class="sidebar-icon"></i> Ulasan</a></li>
        <li class="sidebar-item"><a href="profil.php" class="sidebar-link"><i data-lucide="user" class="sidebar-icon"></i> Profil Saya</a></li>
        <li class="sidebar-item"><a href="pengaturan.php" class="sidebar-link"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-keluar"><i data-lucide="log-out" style="width:16px;height:16px;"></i> Keluar</a>
    </div>
</aside>

<!-- Main -->
<div class="main">
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 class="topbar-title">Ulasan</h2>
        </div>
        <div class="topbar-right">
            <a href="profil.php" style="text-decoration:none; color:inherit;">
                <div class="user-profile">
                    <div class="avatar">
                        <?php if ($userFoto): ?>
                            <img src="../uploads/profil/<?= htmlspecialchars(basename($userFoto)) ?>" alt="Profil">
                        <?php else: ?>
                            <?= strtoupper(substr($namaUser, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="user-name"><?= htmlspecialchars($namaUser) ?></div>
                        <div class="user-role">Penghuni</div>
                    </div>
                </div>
            </a>
        </div>
    </header>

    <main class="content">
        <div class="ulasan-grid">

            <!-- KIRI: Form Ulasan -->
            <div class="card-section">
                <div class="card-header-row">
                    <i data-lucide="star" style="width:18px;height:18px;color:var(--green);"></i>
                    <h3 class="card-title"><?= $myUlasan ? 'Edit Ulasan Saya' : 'Beri Ulasan' ?></h3>
                </div>
                <div class="card-body">

                    <?php if ($message): ?>
                    <div class="<?= $msgType === 'success' ? 'alert-success' : 'alert-error' ?>">
                        <i data-lucide="<?= $msgType === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width:15px;height:15px;flex-shrink:0;"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Preview ulasan yang sudah ada -->
                    <?php if ($myUlasan && !$message): ?>
                    <div class="my-review-box">
                        <div class="my-review-label">Ulasan Saya Saat Ini</div>
                        <div class="my-review-stars">
                            <?php 
                            for ($s = 1; $s <= 5; $s++) {
                                if ($s <= (int)$myUlasan['rating']) {
                                    echo '<i data-lucide="star" style="width:16px;height:16px;color:#facc15;fill:#facc15;"></i> ';
                                } else {
                                    echo '<i data-lucide="star" style="width:16px;height:16px;color:#d1d5db;"></i> ';
                                }
                            }
                            ?>
                        </div>
                        <div class="my-review-text"><?= htmlspecialchars($myUlasan['komentar']) ?></div>
                        <?php if (!empty($myUlasan['foto_ulasan'])): ?>
                            <div style="margin-top: 10px;">
                                <a href="../../uploads/ulasan/<?= htmlspecialchars($myUlasan['foto_ulasan']) ?>" target="_blank">
                                    <img src="../../uploads/ulasan/<?= htmlspecialchars($myUlasan['foto_ulasan']) ?>" alt="Foto Lampiran" style="max-width: 100px; border-radius: 8px; border: 1px solid var(--border);">
                                </a>
                            </div>
                        <?php endif; ?>
                        <div style="margin-top: 12px; display: flex; gap: 10px;">
                            <a href="ulasan.php?action=delete_my" class="btn btn-sm btn-danger text-white" onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan Anda?')" style="font-size: 12px; border-radius: 8px; font-family: 'Poppins', sans-serif; text-decoration: none;">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px; vertical-align: middle;"></i> Hapus Ulasan Saya
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- Star Rating -->
                        <div class="star-input-wrap">
                            <div class="field-label">Rating Bintang</div>
                            <div class="stars-row" id="starRating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $selectedRating == $i ? 'checked' : '' ?>>
                                <label for="star<?= $i ?>" title="<?= $i ?> bintang">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="field-label">Komentar</div>
                            <textarea name="komentar" class="field-textarea" rows="4"
                                placeholder="Ceritakan pengalaman tinggal di Kost Elmi Sarah..." required><?= htmlspecialchars($selectedKomentar) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="field-label">Unggah Foto Bukti/Suasana Kost (Opsional)</label>
                            <input type="file" name="foto_ulasan" class="form-control" accept="image/*" style="font-size: 13px; border-radius: 8px;">
                            <div style="font-size: 11px; color: var(--gray); margin-top: 4px;">Foto akan ditampilkan di halaman testimoni depan.</div>
                        </div>

                        <button type="submit" class="btn-kirim">
                            <i data-lucide="send" style="width:14px;height:14px;"></i>
                            <?= $myUlasan ? 'Perbarui Ulasan' : 'Kirim Ulasan' ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- KANAN: Daftar Ulasan -->
            <div class="card-section">
                <div class="card-header-row">
                    <i data-lucide="message-square" style="width:18px;height:18px;color:var(--green);"></i>
                    <h3 class="card-title">Ulasan Penghuni</h3>
                </div>
                <div class="card-body">

                    <!-- Summary Rating -->
                    <?php if ($totalUlasan > 0): ?>
                    <div class="rating-summary">
                        <div>
                            <div class="rating-big"><?= number_format($avgRating, 1) ?></div>
                        </div>
                        <div>
                                <?php
                                $full  = floor($avgRating);
                                for ($s = 1; $s <= 5; $s++) {
                                    if ($s <= $full) {
                                        echo '<i data-lucide="star" style="width:20px;height:20px;color:#facc15;fill:#facc15;"></i>';
                                    } else {
                                        echo '<i data-lucide="star" style="width:20px;height:20px;color:#d1d5db;"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="rating-count"><?= $totalUlasan ?> ulasan dari penghuni</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- List Ulasan -->
                    <?php if (empty($allUlasan)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i data-lucide="star" style="width:24px;height:24px;color:var(--green);"></i>
                        </div>
                        <p>Belum ada ulasan dari penghuni.</p>
                        <p style="font-size:12px;margin-top:4px;">Jadilah yang pertama memberi ulasan!</p>
                    </div>
                    <?php else: ?>
                    <div class="review-list">
                        <?php foreach ($allUlasan as $ul): ?>
                        <div class="review-item">
                            <div class="review-top">
                                <div class="review-avatar"><?= strtoupper(substr($ul['nama'], 0, 1)) ?></div>
                                <div class="review-info">
                                    <div class="review-name"><?= htmlspecialchars($ul['nama']) ?></div>
                                    <div class="review-stars">
                                        <?= str_repeat('★', (int)$ul['rating']) ?><?= str_repeat('☆', 5 - (int)$ul['rating']) ?>
                                    </div>
                                </div>
                                <div class="review-date"><?= date('j M Y', strtotime($ul['created_at'])) ?></div>
                            </div>
                            <div class="review-text"><?= htmlspecialchars($ul['komentar']) ?></div>
                            <?php if (!empty($ul['foto_ulasan'])): ?>
                                <div style="margin-top: 10px;">
                                    <a href="../../uploads/ulasan/<?= htmlspecialchars($ul['foto_ulasan']) ?>" target="_blank">
                                        <img src="../../uploads/ulasan/<?= htmlspecialchars($ul['foto_ulasan']) ?>" alt="Foto Bukti" style="max-width: 120px; max-height: 120px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border);">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
