<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

// Auto-create galeri table if not exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS galeri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kategori VARCHAR(50) NOT NULL,
        tipe_file ENUM('foto', 'video') NOT NULL DEFAULT 'foto',
        file_path VARCHAR(255) NOT NULL,
        caption VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$uploadDir = __DIR__ . "/../../../frontend/assets/image/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];
    if ($action === "add") {
        $kategori = $_POST["kategori"] ?? "";
        $caption = trim($_POST["caption"] ?? "");
        
        if (isset($_FILES["file"]) && $_FILES["file"]["error"] === UPLOAD_ERR_OK) {
            $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["file"]["name"]));
            $targetPath = $uploadDir . $fileName;
            
            // Determine file type
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $tipe_file = in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', '3gp']) ? 'video' : 'foto';
            
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
                $stmt = $conn->prepare("INSERT INTO galeri (kategori, tipe_file, file_path, caption) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kategori, $tipe_file, $fileName, $caption]);
                $_SESSION['success_msg'] = "Media berhasil diunggah ke galeri!";
            } else {
                $_SESSION['error_msg'] = "Gagal memindahkan file ke direktori tujuan.";
            }
        } else {
            $_SESSION['error_msg'] = "Gagal mengunggah file. Silakan coba lagi.";
        }
    } elseif ($action === "delete") {
        $id = $_POST["id"] ?? 0;
        $stmt = $conn->prepare("SELECT file_path FROM galeri WHERE id = ?");
        $stmt->execute([$id]);
        $filePath = $stmt->fetchColumn();
        
        if ($filePath) {
            // Delete file if it's in the uploads folder (doesn't contain path separators)
            if (strpos($filePath, '/') === false) {
                $diskPath = $uploadDir . $filePath;
                if (file_exists($diskPath)) {
                    unlink($diskPath);
                }
            }
            $stmtDel = $conn->prepare("DELETE FROM galeri WHERE id = ?");
            $stmtDel->execute([$id]);
            $_SESSION['success_msg'] = "Media berhasil dihapus!";
        }
    }
    header("Location: list_galeri.php");
    exit;
}

// Fetch all gallery items
$stmt = $conn->query("SELECT * FROM galeri ORDER BY id DESC");
$gallery_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = [
    'suasana_lokasi' => 'Suasana Lokasi',
    'suasana_lantai_1' => 'Suasana Lantai 1',
    'suasana_lantai_2' => 'Suasana Lantai 2',
    'suasana_kamar' => 'Suasana Kamar',
    'parkir' => 'Fasilitas Parkir',
    'dapur' => 'Fasilitas Dapur',
    'lainnya' => 'Fasilitas Lainnya',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Galeri - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css?v=1.2">
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
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar {
            width: 38px; height: 38px; background-color: #d1d5db; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 14px;
        }
        .user-name { font-weight: 600; font-size: 13.5px; color: var(--admin-text-dark); line-height: 1.2; }
        .user-role { font-size: 11px; color: #9ca3af; font-weight: 500; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }

        /* Gallery Grid */
        .gal-card {
            background: white; border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            margin-bottom: 24px; padding: 24px;
        }
        .gal-thumb {
            width: 100%; aspect-ratio: 4/3; height: auto; object-fit: cover;
            border-radius: 10px; background-color: #eee;
            display: block;
        }
        .gal-item-wrapper {
            position: relative; border-radius: 10px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .gal-item-wrapper:hover {
            transform: translateY(-3px);
        }
        .gal-delete-btn {
            position: absolute; top: 10px; right: 10px;
            background: rgba(239, 68, 68, 0.9); color: white;
            border: none; border-radius: 50%; width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; cursor: pointer; transition: background 0.2s;
            z-index: 10;
        }
        .gal-delete-btn:hover { background: #dc2626; }
        .gal-info {
            padding: 8px 12px; background: white;
            border-top: 1px solid #f3f4f6;
        }
        .gal-caption {
            font-size: 11px; font-weight: 600; color: #374151;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            margin: 0;
        }
        .gal-badge {
            position: absolute; bottom: 40px; left: 10px;
            background: rgba(0,0,0,0.6); color: white;
            padding: 2px 8px; font-size: 10px; border-radius: 4px;
            font-weight: 500; text-transform: uppercase;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="admin-sidebar">
    <button class="sidebar-close-btn" onclick="closeMobileSidebar()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
    <div class="sidebar-header"><h1 class="sidebar-brand">Elmi Sarah</h1></div>
    <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="../dashboard.php" class="sidebar-link "><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="../kelola_penghuni/list_penghuni.php" class="sidebar-link "><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li class="sidebar-item"><a href="../kelola_user/list_user.php" class="sidebar-link "><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="../kelola_kamar/list_kamar.php" class="sidebar-link "><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li class="sidebar-item"><a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link "><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
        <li class="sidebar-item"><a href="../kelola_pengaduan/list_pengaduan.php" class="sidebar-link "><i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan</a></li>
        <li class="sidebar-item"><a href="../kelola_booking/list_booking.php" class="sidebar-link "><i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking</a></li>
        <li class="sidebar-item"><a href="../kelola_pengumuman/list_pengumuman.php" class="sidebar-link "><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="../kelola_ulasan/list_ulasan.php" class="sidebar-link "><i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan</a></li>
        <li class="sidebar-item"><a href="../kelola_galeri/list_galeri.php" class="sidebar-link active"><i data-lucide="image" class="sidebar-icon"></i> Kelola Galeri</a></li>
        <li class="sidebar-item"><a href="../pengaturan.php" class="sidebar-link "><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
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
            <h2 class="page-title">Kelola Galeri</h2>
        </div>
        <div class="topbar-right">
                <button class="notification-btn" type="button">
                    <i data-lucide="bell" style="width:20px;height:20px;"></i>
                </button>
            <div class="user-profile">
                <div class="avatar"></div>
                <div><div class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div><div class="user-role">Admin</div></div>
            </div>
        </div>
    </header>

    <main class="admin-content">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px; font-size:13px;">
                ✓ <?= htmlspecialchars($_SESSION['success_msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px; font-size:13px;">
                ✗ <?= htmlspecialchars($_SESSION['error_msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Form Upload -->
            <div class="col-lg-4">
                <div class="gal-card">
                    <h5 class="mb-3" style="font-weight:700; font-size:16px;">Unggah Media Baru</h5>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px; font-weight:600;">Pilih File (Foto / Video)</label>
                            <input type="file" name="file" class="form-control" accept="image/*,video/*" required>
                            <small class="text-muted" style="font-size:11px;">Format: jpg, png, mp4, mov, dll.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px; font-weight:600;">Kategori</label>
                            <select name="kategori" class="form-select" required>
                                <?php foreach ($categories as $val => $lbl): ?>
                                    <option value="<?= $val ?>"><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-size:13px; font-weight:600;">Keterangan / Caption</label>
                            <input type="text" name="caption" class="form-control" placeholder="Contoh: Kamar Tidur Utama" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100" style="background-color: var(--admin-green); border: none; padding: 10px; font-weight: 600; border-radius: 8px;">Mulai Unggah</button>
                    </form>
                </div>
            </div>

            <!-- List Media -->
            <div class="col-lg-8">
                <div class="gal-card">
                    <h5 class="mb-4" style="font-weight:700; font-size:16px;">Daftar Galeri</h5>
                    
                    <?php if (empty($gallery_items)): ?>
                        <div class="text-center py-5 text-muted">
                            <i data-lucide="image" style="width:48px; height:48px; margin-bottom:12px; opacity:0.5;"></i>
                            <p class="mb-0">Belum ada media di galeri.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($gallery_items as $item):
                                $filePath = $item['file_path'];
                                if (strpos($filePath, '/') === false) {
                                    $path = "../../../frontend/assets/image/uploads/" . $filePath;
                                } else {
                                    $path = "../../../frontend/assets/image/" . $filePath;
                                }
                            ?>
                                <div class="col-6 col-md-4">
                                    <div class="gal-item-wrapper">
                                        <!-- Delete Button -->
                                        <form action="" method="POST" onsubmit="return confirm('Hapus media ini dari galeri?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="gal-delete-btn">&times;</button>
                                        </form>

                                        <!-- Preview -->
                                        <?php if ($item['tipe_file'] === 'video'): ?>
                                            <video src="<?= htmlspecialchars($path) ?>" class="gal-thumb" muted></video>
                                        <?php else: ?>
                                            <img src="<?= htmlspecialchars($path) ?>" class="gal-thumb" alt="Preview">
                                        <?php endif; ?>

                                        <!-- Badge Category -->
                                        <span class="gal-badge"><?= $categories[$item['kategori']] ?? $item['kategori'] ?></span>

                                        <!-- Info -->
                                        <div class="gal-info">
                                            <p class="gal-caption" title="<?= htmlspecialchars($item['caption']) ?>"><?= htmlspecialchars($item['caption'] ?: '-') ?></p>
                                        </div>
                                    </div>
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
<script src="../../assets/js/sidebar-toggle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>
