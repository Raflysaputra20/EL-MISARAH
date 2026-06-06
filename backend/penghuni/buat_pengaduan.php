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

// Auto-detect nomor kamar
$noKamarAuto = null;
try {
    $stmtKmr = $conn->prepare("
        SELECT k.nomor_kamar as no_kamar FROM booking b
        JOIN kamar k ON b.kamar_id = k.id
        WHERE b.user_id = ? AND b.status IN ('disetujui','aktif')
        ORDER BY b.id DESC LIMIT 1
    ");
    $stmtKmr->execute([$userId]);
    $kamarRow = $stmtKmr->fetch(PDO::FETCH_ASSOC);
    if ($kamarRow && !empty($kamarRow['no_kamar'])) {
        $noKamarAuto = 'Kamar ' . $kamarRow['no_kamar'];
    }
    if (!$noKamarAuto) {
        $stmtPay = $conn->prepare("
            SELECT k.nomor_kamar as no_kamar FROM pembayaran p
            JOIN booking b ON p.booking_id = b.id
            JOIN kamar k ON b.kamar_id = k.id
            WHERE p.user_id = ? ORDER BY p.id DESC LIMIT 1
        ");
        $stmtPay->execute([$userId]);
        $payRow = $stmtPay->fetch(PDO::FETCH_ASSOC);
        if ($payRow && !empty($payRow['no_kamar'])) {
            $noKamarAuto = 'Kamar ' . $payRow['no_kamar'];
        }
    }
} catch (Exception $e) {}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $judul     = trim($_POST["judul"]    ?? "");
    $isi       = trim($_POST["isi"]      ?? "");
    $no_kamar  = $noKamarAuto ?? trim($_POST["no_kamar"] ?? "");

    if (empty($judul) || empty($isi)) {
        $message = "Judul dan deskripsi wajib diisi.";
        $msgType = "error";
    } else {
        $foto_bukti = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . "/../../uploads/pengaduan/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fileName = 'pengaduan_' . $userId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $fileName)) {
                $foto_bukti = $fileName;
            }
        }
        try {
            $stmt = $conn->prepare("
                INSERT INTO pengaduan (user_id, judul, isi, no_kamar, prioritas, foto_bukti, status, created_at)
                VALUES (?, ?, ?, ?, 'sedang', ?, 'baru', NOW())
            ");
            $stmt->execute([$userId, $judul, $isi, $no_kamar, $foto_bukti]);
            $message = "Laporan berhasil dikirim! Admin akan segera menanggapi.";
            $msgType = "success";
        } catch (Exception $e) {
            $message = "Gagal mengirim laporan.";
            $msgType = "error";
        }
    }
}

// Fetch foto user
try {
    $stmtFoto = $conn->prepare("SELECT foto FROM users WHERE id = ?");
    $stmtFoto->execute([$userId]);
    $userFoto = $stmtFoto->fetchColumn();
} catch (Exception $e) { $userFoto = null; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaduan Kost - Kost Elmi Sarah</title>
    <meta name="description" content="Buat laporan pengaduan kost ke admin Kost Elmi Sarah.">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css">
    <style>
        :root { --green:#11a654; --green-light:#e8f7f0; --bg:#f4f6f8; --dark:#1f2937; --gray:#6b7280; --border:#e5e7eb; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; background:var(--bg); color:var(--dark); overflow-x:hidden; }

        /* SIDEBAR */
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

        /* MAIN */
        .main { margin-left:240px; min-height:100vh; display:flex; flex-direction:column; }
        .topbar { height:68px; background:white; display:flex; align-items:center; justify-content:space-between; padding:0 30px; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; }
        .topbar-left h2 { font-size:18px; font-weight:700; line-height:1.2; }
        .topbar-left p  { font-size:12px; color:var(--gray); }
        .topbar-right { display:flex; align-items:center; gap:12px; }
        .avatar { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,#9ca3af,#6b7280); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; color:white; flex-shrink:0; overflow:hidden; }
        .avatar img { width:100%; height:100%; object-fit:cover; }
        .user-name { font-weight:600; font-size:14px; line-height:1.2; }
        .user-role { font-size:11.5px; color:var(--gray); }
        .content { padding:24px 28px; flex-grow:1; }

        /* GREETING CARD */
        .greeting-card {
            background:white; border-radius:16px;
            box-shadow:0 2px 12px rgba(0,0,0,.05);
            padding:22px 28px; margin-bottom:22px;
            display:flex; align-items:center; gap:20px;
        }
        .greeting-icon {
            width:52px; height:52px; border-radius:14px;
            background:var(--green-light);
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .greeting-title { font-size:16px; font-weight:700; color:var(--dark); margin-bottom:4px; }
        .greeting-sub { font-size:13px; color:var(--gray); line-height:1.5; }

        /* FORM CARD diperkecil */
        .form-card {
            background:white; border-radius:16px;
            box-shadow:0 2px 12px rgba(0,0,0,.05);
            padding:20px 24px;
        }
        .form-title { font-size:15px; font-weight:700; color:var(--dark); margin-bottom:4px; }
        .form-sub { font-size:12px; color:var(--gray); margin-bottom:18px; }

        .field-group { margin-bottom:16px; }
        .field-label { font-size:12.5px; font-weight:600; color:var(--dark); margin-bottom:6px; display:block; }
        .field-label span { color:#ef4444; }
        .field-input {
            width:100%; border:1.5px solid var(--border); border-radius:8px;
            padding:8px 12px; font-size:12.5px; font-family:'Poppins',sans-serif;
            color:var(--dark); outline:none; transition:border-color .2s;
            background:white;
        }
        .field-input:focus { border-color:var(--green); }
        .field-input::placeholder { color:#c0c4cc; }
        .field-textarea {
            width:100%; border:1.5px solid var(--border); border-radius:8px;
            padding:8px 12px; font-size:12.5px; font-family:'Poppins',sans-serif;
            color:var(--dark); outline:none; resize:vertical; min-height:100px;
            transition:border-color .2s;
        }
        .field-textarea:focus { border-color:var(--green); }
        .field-textarea::placeholder { color:#c0c4cc; }

        /* Upload Area */
        .upload-area {
            width:100%; border:1.5px dashed var(--border); border-radius:8px;
            padding:20px 15px; text-align:center;
            cursor:pointer; transition:all .2s; background:#fafbfc;
        }
        .upload-area:hover { border-color:var(--green); background:var(--green-light); }
        .upload-area-label { font-size:12.5px; font-weight:600; color:var(--gray); margin-top:6px; }
        .upload-area-sub { font-size:11px; color:#9ca3af; margin-top:2px; }
        .upload-preview { font-size:11.5px; color:var(--green); font-weight:600; margin-top:6px; }

        /* Footer button */
        .form-footer { display:flex; justify-content:flex-end; margin-top:16px; }
        .btn-kirim {
            background:var(--green); color:white; border:none;
            border-radius:8px; padding:10px 24px;
            font-family:'Poppins',sans-serif; font-size:13px; font-weight:600;
            cursor:pointer; transition:background .2s;
            display:inline-flex; align-items:center; gap:6px;
        }
        .btn-kirim:hover { background:#0d8e47; }

        /* Alert */
        .alert-success { background:var(--green-light); color:var(--green); border-radius:10px; padding:10px 14px; font-size:12.5px; margin-bottom:14px; display:flex; align-items:center; gap:6px; border-left:3px solid var(--green); }
        .alert-error { background:#fee2e2; color:#ef4444; border-radius:10px; padding:10px 14px; font-size:12.5px; margin-bottom:14px; display:flex; align-items:center; gap:6px; border-left:3px solid #ef4444; }

        /* Mobile / Tablet Responsiveness */
        @media (max-width: 1024px) {
            .main { margin-left: 0; }
            .topbar { padding: 0 16px; height: 60px; }
            .content { padding: 16px; }
            .greeting-card, .form-card { padding: 16px; }
        }

        @media (max-width: 768px) {
            .greeting-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding: 15px;
            }
            .topbar-left h2 { font-size: 16px; }
            .topbar-right .user-name,
            .topbar-right .user-role { display: none; }
            .form-footer { justify-content: stretch; }
            .btn-kirim { width: 100%; justify-content: center; }
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
        <li class="sidebar-item"><a href="riwayat_pengaduan.php" class="sidebar-link active"><i data-lucide="wrench" class="sidebar-icon"></i> Pengaduan Kost</a></li>
        <li class="sidebar-item"><a href="pengumuman.php" class="sidebar-link"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
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
        <div class="topbar-left" style="display:flex; align-items:center; gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px; height:24px;"></i></button>
            <div>
                <h2 style="margin:0; font-size:18px; font-weight:700;">Form Pengaduan</h2>
                <p style="margin:0; font-size:12px; color:var(--gray);">Penghuni Elmi Sarah</p>
            </div>
        </div>
        <div class="topbar-right">
            <a href="profil.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px;">
                <div class="topbar-user-info" style="text-align:right;">
                    <div class="user-name"><?= htmlspecialchars($namaUser) ?></div>
                    <div class="user-role">Penghuni</div>
                </div>
                <div class="avatar">
                    <?php if ($userFoto): ?>
                        <img src="../uploads/profil/<?= htmlspecialchars(basename($userFoto)) ?>" alt="Profil">
                    <?php else: ?>
                        <?= strtoupper(substr($namaUser, 0, 1)) ?>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    </header>

    <main class="content">

        <!-- Greeting Card -->
        <div class="greeting-card">
            <div class="greeting-icon">
                <i data-lucide="message-square-warning" style="width:26px;height:26px;color:var(--green);"></i>
            </div>
            <div>
                <div class="greeting-title">Halo <?= htmlspecialchars(explode(' ', $namaUser)[0]) ?></div>
                <div class="greeting-sub">Setiap Laporan Anda Penting Bagi Kami. Sampaikan keluhan dengan jelas dan sertakan bukti foto agar lebih cepat ditangani</div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="form-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <div class="form-title">Buat Pengaduan</div>
                <a href="riwayat_pengaduan.php" style="font-size:12.5px; color:var(--green); text-decoration:none; font-weight:600; display:flex; align-items:center; gap:4px;">
                    <i data-lucide="history" style="width:14px; height:14px;"></i> Lihat Riwayat
                </a>
            </div>
            <div class="form-sub">Isi Form dibawah ini dengan detail. Foto bukti sangat membantu proses verifikasi</div>

            <?php if ($message): ?>
            <div class="<?= $msgType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <i data-lucide="<?= $msgType === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width:15px;height:15px;flex-shrink:0;"></i>
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="field-group">
                    <label class="field-label">Judul Pengaduan <span>*</span></label>
                    <input type="text" name="judul" class="field-input" placeholder="Tulis judul singkat pengaduan..." required>
                </div>
                <div class="field-group">
                    <label class="field-label">Deskrisi Pengaduan <span>*</span></label>
                    <textarea name="isi" class="field-textarea" placeholder="Jelaskan secara detail masalah yang terjadi..." required></textarea>
                </div>
                <div class="field-group">
                    <label class="field-label">Bukti Foto (maks 5, Ukuran 1GB) <span>*</span></label>
                    <div class="upload-area" onclick="document.getElementById('fotoInput').click()">
                        <i data-lucide="upload" style="width:28px;height:28px;color:#9ca3af;display:block;margin:0 auto;"></i>
                        <div class="upload-area-label">Ketuk Untuk Mengunggah Foto</div>
                        <div class="upload-area-sub">JPG, JPEG, PNG</div>
                        <div class="upload-preview" id="uploadLabel"></div>
                    </div>
                    <input type="file" id="fotoInput" name="foto" accept="image/*" style="display:none;"
                        onchange="document.getElementById('uploadLabel').textContent = this.files[0]?.name || ''">
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn-kirim">
                        <i data-lucide="send" style="width:16px;height:16px;"></i>
                        Kirim Pengaduan
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>