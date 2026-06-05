<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

// Fetch pengaduan from DB
try {
    $stmt = $conn->query("
        SELECT 
            pengaduan.*,
            users.nama,
            users.email
        FROM pengaduan
        JOIN users ON pengaduan.user_id = users.id
        ORDER BY pengaduan.id DESC
    ");
    $pengaduan = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pengaduan = [];
}

// If DB empty, just use empty array, no dummy data
if (empty($pengaduan)) {
    $pengaduan = [];
}

// Count stats
$countBaru     = count(array_filter($pengaduan, fn($p) => in_array(strtolower($p['status']), ['baru','masuk'])));
$countDiproses = count(array_filter($pengaduan, fn($p) => strtolower($p['status']) === 'diproses'));
$countSelesai  = count(array_filter($pengaduan, fn($p) => strtolower($p['status']) === 'selesai'));

// Helper: status config
function statusConfig(string $s): array {
    return match(strtolower($s)) {
        'baru','masuk' => ['color' => '#ef4444', 'bg' => '#fee2e2', 'dot' => '#ef4444', 'label' => 'Masuk'],
        'diproses'     => ['color' => '#d97706', 'bg' => '#fef3c7', 'dot' => '#f59e0b', 'label' => 'Diproses'],
        'selesai'      => ['color' => '#16a34a', 'bg' => '#dcfce7', 'dot' => '#22c55e', 'label' => 'Selesai'],
        default        => ['color' => '#6b7280', 'bg' => '#f3f4f6', 'dot' => '#9ca3af', 'label' => ucfirst($s)],
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaduan - Admin Kost Elmi Sarah</title>
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
        .page-title-group { display: flex; flex-direction: column; }
        .page-title { font-size: 20px; font-weight: 600; color: var(--admin-text-dark); margin: 0; line-height: 1.2; }
        .page-subtitle { font-size: 12px; color: #9ca3af; font-weight: 400; margin: 0; }
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

        /* Mini Stat Cards */
        .mini-stat-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            padding: 18px 24px; height: 100%;
            display: flex; flex-direction: column; gap: 4px;
        }
        .mini-stat-label { font-size: 12px; color: #9ca3af; font-weight: 500; }
        .mini-stat-row { display: flex; align-items: center; gap: 10px; }
        .mini-stat-icon {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .mini-stat-value { font-size: 28px; font-weight: 700; color: #1f2937; line-height: 1; }

        /* Complaint Cards */
        .complaint-card {
            background: white; border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            padding: 20px 24px;
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            transition: box-shadow 0.2s;
        }
        .complaint-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
        .complaint-card:last-child { margin-bottom: 0; }

        .complaint-left { display: flex; align-items: center; gap: 16px; flex: 1; }

        .complaint-icon-wrap {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .complaint-body { flex: 1; }
        .complaint-title {
            font-size: 14px; font-weight: 600; color: #1f2937;
            margin-bottom: 4px;
        }
        .complaint-meta { font-size: 11.5px; color: #9ca3af; }

        .badge-prioritas {
            display: inline-block;
            border-radius: 20px; padding: 4px 14px;
            font-size: 11.5px; font-weight: 500;
            margin-left: 10px;
        }

        .complaint-right { display: flex; align-items: center; gap: 12px; }

        .badge-status {
            display: inline-flex; align-items: center; gap: 6px;
            border-radius: 20px; padding: 5px 16px;
            font-size: 11.5px; font-weight: 600;
        }
        .status-dot {
            width: 7px; height: 7px;
            border-radius: 50%; display: inline-block; flex-shrink: 0;
        }

        .btn-more-complaint {
            background: none; border: none; color: #9ca3af;
            cursor: pointer; padding: 4px;
            transition: color 0.2s;
        }
        .btn-more-complaint:hover { color: #374151; }

        /* Bukti Timeline Admin */
        .bukti-timeline{display:flex;align-items:flex-start;gap:0;margin-top:16px;padding:8px 0;max-width:450px;}
        .bukti-step{display:flex;flex-direction:column;align-items:center;flex:1;min-width:0;position:relative}
        .bukti-dot{width:36px;height:36px;border-radius:50%;border:2px solid #e5e7eb;background:#fafbfc;display:flex;align-items:center;justify-content:center;transition:all .3s ease;margin-bottom:8px}
        .bukti-step.active .bukti-dot{transform:scale(1.1);box-shadow:0 3px 12px rgba(0,0,0,.08)}
        .bukti-step:hover .bukti-dot{transform:scale(1.15)}
        .bukti-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;transition:color .2s}
        .bukti-link{font-size:11px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:3px;transition:opacity .2s}
        .bukti-link:hover{opacity:.8}
        .bukti-line{width:100%;max-width:50px;height:2px;background:#e5e7eb;align-self:center;margin-top:18px;margin-left:-8px;margin-right:-8px;transition:background .3s;border-radius:2px;flex-shrink:1}
        .bukti-line.done{background:var(--admin-green)}
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
        <li class="sidebar-item">
            <a href="../dashboard.php" class="sidebar-link">
                <i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_penghuni/list_penghuni.php" class="sidebar-link">
                <i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_user/list_user.php" class="sidebar-link">
                <i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_kamar/list_kamar.php" class="sidebar-link">
                <i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link">
                <i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="list_pengaduan.php" class="sidebar-link active">
                <i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_booking/list_booking.php" class="sidebar-link">
                <i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_pengumuman/list_pengumuman.php" class="sidebar-link">
                <i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../kelola_ulasan/list_ulasan.php" class="sidebar-link">
                <i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../pengaturan.php" class="sidebar-link">
                <i data-lucide="settings" class="sidebar-icon"></i> Pengaturan
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="../../logout.php" class="btn-keluar">
            <i data-lucide="log-out" class="sidebar-icon" style="color:#1f2937; margin-right:8px;"></i>
            Keluar
        </a>
    </div>
</aside>

<!-- Main -->
<div class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <div class="page-title-group">
                <h2 class="page-title">Pengaduan</h2>
                <p class="page-subtitle">Tangani pengaduan dan keluahan penghuni</p>
            </div>
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

        <?php if (isset($_GET["success"])): ?>
            <div class="alert alert-success mb-3" style="font-size:13px;">Status pengaduan berhasil diubah.</div>
        <?php endif; ?>

        <!-- Mini Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="mini-stat-card">
                    <div class="mini-stat-label">Masuk</div>
                    <div class="mini-stat-row">
                        <div class="mini-stat-icon" style="background-color:#fee2e2;">
                            <i data-lucide="alert-circle" style="width:18px; height:18px; color:#ef4444;"></i>
                        </div>
                        <div class="mini-stat-value"><?= $countBaru ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mini-stat-card">
                    <div class="mini-stat-label">Diproses</div>
                    <div class="mini-stat-row">
                        <div class="mini-stat-icon" style="background-color:#fef3c7;">
                            <i data-lucide="clock" style="width:18px; height:18px; color:#f59e0b;"></i>
                        </div>
                        <div class="mini-stat-value"><?= $countDiproses ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mini-stat-card">
                    <div class="mini-stat-label">Selesai</div>
                    <div class="mini-stat-row">
                        <div class="mini-stat-icon" style="background-color:#dcfce7;">
                            <i data-lucide="check-circle" style="width:18px; height:18px; color:#22c55e;"></i>
                        </div>
                        <div class="mini-stat-value"><?= $countSelesai ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complaint Cards -->
        <?php if (empty($pengaduan)): ?>
            <div class="text-center py-5" style="color:#9ca3af;">
                <i data-lucide="inbox" style="width:40px; height:40px; margin-bottom:12px; display:block; margin-inline:auto;"></i>
                <p>Belum ada pengaduan masuk.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pengaduan as $p):
                $sConfig = statusConfig($p['status'] ?? 'masuk');
                $tgl = date('j M Y', strtotime($p['created_at']));
            ?>
            <div class="complaint-card">
                <!-- Left: Icon + Content -->
                <div class="complaint-left">
                    <!-- Icon -->
                    <div class="complaint-icon-wrap" style="background-color: #f3f4f6;">
                        <i data-lucide="message-square" style="width:20px; height:20px; color:#6b7280;"></i>
                    </div>

                    <!-- Text -->
                    <div class="complaint-body">
                        <div class="complaint-title">
                            <?= htmlspecialchars($p['judul']) ?>
                        </div>
                        <div class="complaint-meta">
                            <?= htmlspecialchars($p['nama']) ?> – <?= $tgl ?>
                        </div>
                        <div style="font-size:12.5px; color:#4b5563; margin-top:5px;">
                            <?= nl2br(htmlspecialchars($p['isi'])) ?>
                        </div>
                        <!-- Timeline Bukti Smooth (Admin) -->
                        <div class="bukti-timeline">
                            <?php
                            $stages = [
                                ['key'=>'foto_bukti','label'=>'Bukti Masuk','icon'=>'upload','color'=>'var(--admin-green)','bg'=>'#e8f7f0'],
                                ['key'=>'foto_proses','label'=>'Bukti Proses','icon'=>'loader','color'=>'#d97706','bg'=>'#fffbeb'],
                                ['key'=>'foto_selesai','label'=>'Bukti Selesai','icon'=>'check-circle','color'=>'var(--admin-green)','bg'=>'#e8f7f0'],
                            ];
                            foreach ($stages as $idx => $s):
                                $hasFile = !empty($p[$s['key']]);
                                $activeClass = $hasFile ? 'active' : '';
                            ?>
                            <?php if ($idx > 0): ?><div class="bukti-line <?= $hasFile ? 'done' : '' ?>"></div><?php endif; ?>
                            <div class="bukti-step <?= $activeClass ?>">
                                <div class="bukti-dot" style="<?= $hasFile ? "background:{$s['bg']};border-color:{$s['color']};" : '' ?>">
                                    <i data-lucide="<?= $s['icon'] ?>" style="width:14px;height:14px;color:<?= $hasFile ? $s['color'] : '#d1d5db' ?>"></i>
                                </div>
                                <div class="bukti-label" style="color:<?= $hasFile ? $s['color'] : '#9ca3af' ?>"><?= $s['label'] ?></div>
                                <?php if ($hasFile): ?>
                                    <a href="../../../uploads/pengaduan/<?=htmlspecialchars($p[$s['key']])?>" target="_blank" class="bukti-link" style="color:<?= $s['color'] ?>">
                                        <i data-lucide="image" style="width:11px;height:11px"></i> Lihat
                                    </a>
                                <?php else: ?>
                                    <span class="bukti-link" style="color:#d1d5db;">—</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Status + More -->
                <div class="complaint-right">
                    <span class="badge-status" style="background-color:<?= $sConfig['bg'] ?>; color:<?= $sConfig['color'] ?>;">
                        <span class="status-dot" style="background-color:<?= $sConfig['dot'] ?>;"></span>
                        <?= $sConfig['label'] ?>
                    </span>

                            <div class="dropdown">
                                <button class="btn-more-complaint" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i data-lucide="more-vertical" style="width:18px; height:18px;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="font-size:13px; min-width:160px;">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="bukaModalStatus(<?= $p['id'] ?>, 'diproses')">
                                            <i data-lucide="clock" style="width:14px; height:14px; margin-right:6px; color:#f59e0b;"></i>
                                            Tandai Diproses
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="bukaModalStatus(<?= $p['id'] ?>, 'selesai')">
                                            <i data-lucide="check-circle" style="width:14px; height:14px; margin-right:6px; color:#22c55e;"></i>
                                            Tandai Selesai
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="ubah_status_pengaduan.php?id=<?= $p['id'] ?>&status=baru"
                                           style="color:#ef4444;"
                                           onclick="return confirm('Reset ke status Masuk?')">
                                            <i data-lucide="rotate-ccw" style="width:14px; height:14px; margin-right:6px;"></i>
                                            Reset ke Masuk
                                        </a>
                                    </li>
                                </ul>
                            </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>
</div>

<!-- Modal Update Status & Upload Foto -->
<div class="modal fade" id="modalStatus" tabindex="-1" aria-labelledby="modalStatusLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="ubah_status_pengaduan.php" method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalStatusLabel">Update Status Pengaduan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="input_pengaduan_id">
        <input type="hidden" name="status" id="input_pengaduan_status">
        
        <p>Anda akan mengubah status pengaduan menjadi <strong id="text_status_target"></strong>.</p>
        
        <div class="mb-3">
            <label for="foto_bukti" class="form-label">Upload Foto Bukti (Opsional)</label>
            <input class="form-control" type="file" id="foto_bukti" name="foto_bukti" accept="image/*">
            <div class="form-text">Bisa berupa foto pengerjaan atau bukti selesai.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" style="background-color: var(--admin-green); border: none;">Simpan Status</button>
      </div>
    </form>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();
function bukaModalStatus(id, statusTarget) {
    document.getElementById('input_pengaduan_id').value = id;
    document.getElementById('input_pengaduan_status').value = statusTarget;
    document.getElementById('text_status_target').innerText = statusTarget.toUpperCase();
    
    var modal = new bootstrap.Modal(document.getElementById('modalStatus'));
    modal.show();
}
</script>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
