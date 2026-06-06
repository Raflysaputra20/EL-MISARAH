<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

// Auto-create missing columns in kamar table if not exists
try {
    $existingKamarCols = [];
    $colKResult = $conn->query("SHOW COLUMNS FROM kamar");
    foreach ($colKResult->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $existingKamarCols[] = $col['Field'];
    }
    if (!in_array('harga_3_bulan', $existingKamarCols)) {
        $conn->exec("ALTER TABLE kamar ADD COLUMN harga_3_bulan INT DEFAULT NULL");
    }
    if (!in_array('harga_6_bulan', $existingKamarCols)) {
        $conn->exec("ALTER TABLE kamar ADD COLUMN harga_6_bulan INT DEFAULT NULL");
    }
    if (!in_array('harga_tahun', $existingKamarCols)) {
        $conn->exec("ALTER TABLE kamar ADD COLUMN harga_tahun INT DEFAULT NULL");
    }
} catch (Exception $e) {}

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

// ═══ AUTO-SYNC: Sinkronisasi status kamar dengan data penghuni aktif ═══
try {
    // 1. Fix booking yang user-nya masih penghuni aktif tapi statusnya 'selesai' -> 'aktif'
    $conn->exec("
        UPDATE booking b
        JOIN users u ON b.user_id = u.id
        SET b.status = 'aktif'
        WHERE b.status = 'selesai'
          AND u.role = 'penghuni'
          AND u.status = 'aktif'
          AND b.kamar_id IS NOT NULL
          AND b.id = (SELECT MAX(b2.id) FROM (SELECT id, user_id FROM booking) b2 WHERE b2.user_id = b.user_id)
    ");

    // 2. Kamar yang harusnya 'terisi' (ada penghuni aktif) tapi masih 'tersedia' -> 'terisi'
    $conn->exec("
        UPDATE kamar k
        JOIN booking b ON b.kamar_id = k.id
        JOIN users u ON b.user_id = u.id
        SET k.status = 'terisi'
        WHERE b.status = 'aktif'
          AND u.role = 'penghuni'
          AND u.status = 'aktif'
          AND k.status = 'tersedia'
    ");

    // 3. Kamar yang 'terisi' tapi tidak punya penghuni aktif -> 'tersedia'
    $conn->exec("
        UPDATE kamar 
        SET status = 'tersedia' 
        WHERE status = 'terisi' 
          AND id NOT IN (
              SELECT b.kamar_id 
              FROM booking b 
              JOIN users u ON b.user_id = u.id 
              WHERE b.status IN ('aktif', 'disetujui') 
                AND u.role = 'penghuni' 
                AND u.status = 'aktif'
                AND b.kamar_id IS NOT NULL
          )
    ");
} catch (Exception $e) {}

// Fetch grouped kamar data (By Tipe)
$stmt = $conn->query("
    SELECT 
        tipe, 
        harga, 
        fasilitas, 
        foto,
        COUNT(*) as total_kamar,
        SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as tersedia,
        MAX(id) as last_id
    FROM kamar 
    GROUP BY tipe, harga, fasilitas, foto
    ORDER BY last_id DESC
");
$kamarGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Global Stats
$stmtTotal = $conn->query("SELECT status FROM kamar");
$allRooms = $stmtTotal->fetchAll(PDO::FETCH_ASSOC);
$totalKamar = count($allRooms);
$totalTerisi = count(array_filter($allRooms, fn($r) => strtolower($r['status'] ?? '') === 'terisi'));
$totalBooking = count(array_filter($allRooms, fn($r) => strtolower($r['status'] ?? '') === 'dibooking'));
$totalKosong = count(array_filter($allRooms, fn($r) => strtolower($r['status'] ?? '') === 'tersedia'));

// Parse fasilitas helper
function parseFasilitas($str) {
    if (empty($str)) return [];
    return array_map('trim', explode(',', $str));
}

// Fasilitas icons mapping
$fasilitasIcons = [
    'single bed'       => 'bed-single',
    'double bed'       => 'bed-double',
    'ac'               => 'air-vent',
    'kamar mandi dalam'=> 'shower-head',
    'kamar mandi luar' => 'shower-head',
    'meja belajar'     => 'book-open',
    'lemari'           => 'package',
    'wifi'             => 'wifi',
    'tv'               => 'tv',
    'kulkas'           => 'thermometer',
    'parkir'           => 'car',
];

function getIcon($fasilitas, $iconMap) {
    $key = strtolower(trim($fasilitas));
    return $iconMap[$key] ?? 'check';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kamar - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-green: #11a654;
            --admin-bg: #f4f6f8;
            --admin-text-dark: #1f2937;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--admin-bg);
            margin: 0; overflow-x: hidden;
            color: var(--admin-text-dark);
        }

        /* Sidebar */
        .admin-sidebar {
            width: 240px; height: 100vh;
            background-color: var(--admin-green);
            position: fixed; top: 0; left: 0;
            display: flex; flex-direction: column;
            color: white; z-index: 1000;
            border-top-right-radius: 15px;
            border-bottom-right-radius: 15px;
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
            width: 38px; height: 38px; background-color: #d1d5db;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; color: white; font-weight: bold; font-size: 14px;
        }
        .user-name { font-weight: 600; font-size: 13.5px; color: var(--admin-text-dark); line-height: 1.2; }
        .user-role { font-size: 11px; color: #9ca3af; font-weight: 500; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }

        /* Stat Cards */
        .stat-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            padding: 22px 28px; height: 100%;
        }
        .stat-card-title { font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 12px; }
        .stat-card-value { font-size: 36px; font-weight: 700; color: #1f2937; line-height: 1; }

        /* Room Cards */
        .room-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .room-card-img {
            width: 100%; height: 200px;
            background-color: #e5e7eb;
            overflow: hidden;
            position: relative;
        }
        .room-card-img img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .room-card-img .img-placeholder {
            width: 100%; height: 100%;
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            display: flex; align-items: center; justify-content: center;
            color: #9ca3af;
        }

        .room-card-body { padding: 18px 20px 0; }
        .room-brand { font-size: 12px; color: #6b7280; margin-bottom: 4px; }

        .room-title-row {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 6px;
        }
        .room-title { font-size: 18px; font-weight: 700; color: #1f2937; margin: 0; }

        .badge-tersedia {
            background-color: transparent; color: var(--admin-green);
            border: 1px solid var(--admin-green);
            border-radius: 20px; padding: 4px 16px;
            font-size: 11.5px; font-weight: 500;
            white-space: nowrap;
        }
        .badge-terisi {
            background-color: transparent; color: #ef4444;
            border: 1px solid #ef4444;
            border-radius: 20px; padding: 4px 16px;
            font-size: 11.5px; font-weight: 500;
            white-space: nowrap;
        }

        .room-price {
            font-size: 15px; font-weight: 700;
            color: var(--admin-green); margin-bottom: 14px;
        }
        .room-price span { font-size: 12px; font-weight: 400; color: #9ca3af; }

        .room-facilities {
            display: flex; flex-wrap: wrap; gap: 14px;
            margin-bottom: 16px;
        }
        .facility-item {
            display: flex; align-items: center; gap: 5px;
            font-size: 11.5px; color: #6b7280;
        }
        .facility-item svg { width: 14px; height: 14px; color: #6b7280; }

        .room-card-footer {
            border-top: 1px solid #f3f4f6;
            padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .btn-ubah-foto {
            display: inline-flex; align-items: center; gap: 6px;
            background-color: #f0faf5; color: var(--admin-green);
            border: 1px solid #d1fae5;
            border-radius: 8px; padding: 7px 16px;
            font-size: 12px; font-weight: 500;
            text-decoration: none; cursor: pointer;
            transition: all 0.2s;
        }
        .btn-ubah-foto:hover { background-color: #e0f7ec; color: var(--admin-green); }
        .btn-edit {
            display: inline-flex; align-items: center; gap: 6px;
            background-color: #f0faf5; color: var(--admin-green);
            border: 1px solid #d1fae5;
            border-radius: 8px; padding: 7px 16px;
            font-size: 12px; font-weight: 500;
            text-decoration: none; cursor: pointer;
            transition: all 0.2s;
        }
        .btn-edit:hover { background-color: #e0f7ec; color: var(--admin-green); }
        .btn-more {
            background: none; border: none;
            color: #9ca3af; padding: 4px;
            cursor: pointer; margin-left: auto;
        }
        .btn-more:hover { color: #374151; }

        /* Add Room Button */
        .btn-add-room {
            background-color: var(--admin-green);
            color: white; border: none;
            border-radius: 10px; padding: 10px 22px;
            font-size: 13px; font-weight: 600;
            font-family: 'Poppins', sans-serif;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; cursor: pointer;
            transition: background 0.2s;
        }
        .btn-add-room:hover { background-color: #0e9148; color: white; }
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
        <li class="sidebar-item"><a href="../dashboard.php" class="sidebar-link "><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="../kelola_penghuni/list_penghuni.php" class="sidebar-link "><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li class="sidebar-item"><a href="../kelola_user/list_user.php" class="sidebar-link "><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="../kelola_kamar/list_kamar.php" class="sidebar-link active"><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
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

<!-- Main -->
<div class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 class="page-title">Menejemen Kamar</h2>
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
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i data-lucide="alert-circle" style="width:18px; height:18px; margin-right:8px;"></i>
                <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i data-lucide="check-circle" style="width:18px; height:18px; margin-right:8px;"></i>
                Operasi berhasil dilakukan!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-title">Total Kamar</div>
                    <div class="stat-card-value"><?= $totalKamar ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-title">Kamar Terisi</div>
                    <div class="stat-card-value"><?= $totalTerisi ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-title">Dibooking</div>
                    <div class="stat-card-value"><?= $totalBooking ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-title">Kamar Kosong</div>
                    <div class="stat-card-value"><?= $totalKosong ?></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button class="btn-add-room" data-bs-toggle="modal" data-bs-target="#modalTambahKamar">
                <i data-lucide="plus" style="width:16px; height:16px;"></i>
                Tambah Tipe Kamar
            </button>
        </div>

        <!-- Room Cards Grid -->
        <?php if (empty($kamarGroups)): ?>
            <div class="text-center py-5" style="color:#9ca3af;">
                <i data-lucide="bed" style="width:48px; height:48px; margin-bottom:12px; color:#d1d5db;"></i>
                <p>Belum ada data tipe kamar. Klik "+ Tambah Tipe Kamar" untuk menambah.</p>
            </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($kamarGroups as $kg):
                $fasilitas = parseFasilitas($kg['fasilitas'] ?? '');
                $tersediaCount = (int)$kg['tersedia'];
                $totalCount = (int)$kg['total_kamar'];
                
                $imgPath = '../../../frontend/assets/image/kost.png'; // default image
                if (!empty($kg['foto'])) {
                    $imgPath = '../../../frontend/assets/image/' . $kg['foto'];
                }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="room-card">
                    <!-- Image -->
                    <div class="room-card-img">
                        <?php if (!empty($kg['foto']) && file_exists(__DIR__ . '/../../../frontend/assets/image/' . $kg['foto'])): ?>
                            <img src="<?= htmlspecialchars($imgPath) ?>" alt="Foto Kamar">
                        <?php else: ?>
                            <div class="img-placeholder">
                                <i data-lucide="image" style="width:40px; height:40px;"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Body -->
                    <div class="room-card-body">
                        <div class="room-brand">Elmi Sarah</div>
                        <div class="room-title-row">
                            <h3 class="room-title"><?= htmlspecialchars($kg['tipe']) ?></h3>
                            <span class="<?= $tersediaCount === 0 ? 'badge-terisi' : 'badge-tersedia' ?>">
                                <?= $tersediaCount ?>/<?= $totalCount ?> Tersedia
                            </span>
                        </div>

                        <div class="room-price">
                            Rp <?= number_format($kg['harga'] ?? 0, 0, ',', '.') ?><span>/bln</span>
                        </div>

                        <?php if (!empty($fasilitas)): ?>
                        <div class="room-facilities">
                            <?php foreach ($fasilitas as $f): ?>
                            <div class="facility-item">
                                <i data-lucide="<?= getIcon($f, $fasilitasIcons) ?>" style="width:14px; height:14px;"></i>
                                <?= htmlspecialchars(trim($f)) ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Footer Actions -->
                    <div class="room-card-footer">
                        <a href="edit_kamar.php?tipe=<?= urlencode($kg['tipe']) ?>" class="btn-edit">
                            <i data-lucide="settings" style="width:13px; height:13px;"></i>
                            Kelola Tipe & Kamar
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </main>
</div>

<div class="modal fade" id="modalTambahKamar" tabindex="-1" aria-labelledby="modalTambahKamarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="tambah_kamar.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTambahKamarLabel">Tambah Tipe Kamar Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Nama Tipe (Misal: Kamar Tipe 1)</label>
            <input type="text" name="tipe" class="form-control" placeholder="Contoh: Kamar Tipe 1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nomor Kamar Pertama</label>
            <input type="text" name="nomor_kamar" class="form-control" placeholder="Contoh: 101" required>
            <div class="form-text">Anda bisa menambah nomor kamar lainnya setelah tipe ini dibuat.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Harga per Bulan</label>
            <input type="number" name="harga" class="form-control" placeholder="Contoh: 1300000" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Fasilitas</label>
            <textarea name="fasilitas" class="form-control" rows="2" placeholder="Contoh: Single bed, Lemari, Wifi"></textarea>
            <div class="form-text">Pisahkan dengan koma (,)</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Masukkan deskripsi singkat tipe kamar..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" style="background-color: var(--admin-green); border:none;">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id) {
    if (confirm('Yakin ingin menghapus kamar ini?')) {
        window.location.href = 'hapus_kamar.php?id=' + id;
    }
}

</script>
</body>
</html>
