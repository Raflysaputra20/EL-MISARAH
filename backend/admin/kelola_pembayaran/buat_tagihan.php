<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('n'));
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));

// Semua penghuni dengan booking aktif
try {
    $stmtPenghuni = $conn->query("
        SELECT b.id as booking_id, b.user_id, b.tanggal_masuk,
               u.nama, u.no_hp,
               k.tipe, k.harga,
               k.nomor_kamar as no_kamar
        FROM booking b
        JOIN users u ON b.user_id = u.id
        JOIN kamar k ON b.kamar_id = k.id
        WHERE b.status IN ('disetujui', 'aktif', 'selesai')
        ORDER BY k.nomor_kamar ASC
    ");
    $penghuniList = $stmtPenghuni->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $penghuniList = [];
}

// Cek apakah tiap penghuni sudah punya tagihan di bulan/tahun terpilih
$existingTagihan = [];
try {
    $stmtExist = $conn->prepare("
        SELECT p.booking_id
        FROM pembayaran p
        WHERE MONTH(p.tanggal_bayar) = ? AND YEAR(p.tanggal_bayar) = ?
    ");
    $stmtExist->execute([$bulan, $tahun]);
    foreach ($stmtExist->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existingTagihan[$row['booking_id']] = true;
    }
} catch (Exception $e) {}

$sudahAdaTagihan = 0;
$belumAdaTagihan = 0;
foreach ($penghuniList as $p) {
    if (isset($existingTagihan[$p['booking_id']])) $sudahAdaTagihan++;
    else $belumAdaTagihan++;
}

$namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
              7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tagihan - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <style>
        :root { --green: #11a654; --bg: #f4f6f8; --dark: #1f2937; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; overflow-x: hidden; color: var(--dark); }

        .admin-sidebar {
            width: 240px; height: 100vh; background-color: var(--green);
            position: fixed; top: 0; left: 0; display: flex; flex-direction: column;
            color: white; z-index: 1000;
            border-top-right-radius: 15px; border-bottom-right-radius: 15px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.03);
        }
        .sidebar-header { padding: 25px 20px; display: flex; align-items: center; }
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
        .sidebar-link.active { background-color: var(--bg); color: var(--green); font-weight: 600; }
        .sidebar-icon { width: 18px; height: 18px; margin-right: 12px; }
        .sidebar-footer { padding: 20px 15px; margin-bottom: 15px; }
        .btn-keluar {
            display: inline-flex; align-items: center;
            background-color: white; color: var(--dark);
            text-decoration: none; padding: 8px 20px;
            border-radius: 25px; font-weight: 600; font-size: 13px;
        }

        .admin-main { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar {
            height: 70px; background: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 30px; border-bottom: 1px solid #e5e7eb;
        }
        .page-title { font-size: 20px; font-weight: 600; margin: 0; }
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .notification-btn { background: none; border: none; color: var(--dark); }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 38px; height: 38px; background-color: #d1d5db; border-radius: 50%; }
        .user-name { font-weight: 600; font-size: 13.5px; line-height: 1.2; }
        .user-role { font-size: 11px; color: #9ca3af; }
        .admin-content { padding: 25px 30px; flex-grow: 1; }

        /* Stat cards */
        .stat-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); padding: 22px 24px; }
        .stat-card-title { font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 8px; }
        .stat-card-value { font-size: 28px; font-weight: 700; line-height: 1; }
        .val-green { color: var(--green); }
        .val-red   { color: #ef4444; }
        .val-dark  { color: var(--dark); }

        /* Filter bar */
        .filter-bar {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            padding: 18px 22px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .filter-bar label { font-size: 13px; font-weight: 600; color: #374151; margin: 0; }
        .filter-bar select {
            border: 1px solid #e5e7eb; border-radius: 8px;
            padding: 7px 14px; font-size: 13px; font-family: 'Poppins', sans-serif;
            color: #374151; outline: none; background: #f9fafb;
        }
        .btn-filter {
            background: var(--green); color: white; border: none;
            border-radius: 8px; padding: 8px 18px;
            font-size: 13px; font-weight: 600; font-family: 'Poppins', sans-serif;
            cursor: pointer; transition: background 0.2s;
        }
        .btn-filter:hover { background: #0e9148; }
        .btn-generate-all {
            background: #1d4ed8; color: white; border: none;
            border-radius: 8px; padding: 8px 18px;
            font-size: 13px; font-weight: 600; font-family: 'Poppins', sans-serif;
            cursor: pointer; transition: background 0.2s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-generate-all:hover { background: #1e40af; }

        /* Table */
        .table-wrapper { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden; }
        .tagihan-table { width: 100%; border-collapse: collapse; }
        .tagihan-table thead tr { background-color: var(--green); color: white; }
        .tagihan-table thead th { padding: 13px 18px; font-size: 13px; font-weight: 500; text-align: left; }
        .tagihan-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
        .tagihan-table tbody tr:hover { background: #fafafa; }
        .tagihan-table tbody tr:last-child { border-bottom: none; }
        .tagihan-table tbody td { padding: 13px 18px; font-size: 13px; color: #374151; vertical-align: middle; }

        .badge-room { display: inline-flex; align-items: center; justify-content: center; background: #f3f4f6; color: #374151; border-radius: 20px; padding: 4px 14px; font-size: 12px; font-weight: 500; }
        .badge-sudah { background: #e8f7f0; color: var(--green); border-radius: 20px; padding: 5px 14px; font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .badge-belum { background: #fee2e2; color: #ef4444; border-radius: 20px; padding: 5px 14px; font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .badge-verif { background: #fef3c7; color: #d97706; border-radius: 20px; padding: 5px 14px; font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }

        .btn-buat {
            background: var(--green); color: white; border: none;
            border-radius: 8px; padding: 6px 14px;
            font-size: 12px; font-weight: 600; font-family: 'Poppins', sans-serif;
            cursor: pointer; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-buat:hover { background: #0e9148; }
        .btn-buat:disabled { opacity: 0.45; cursor: not-allowed; }

        /* Alert box */
        .alert-success-custom { background: #e8f7f0; border: 1px solid #a7f3d0; color: #065f46; border-radius: 10px; padding: 12px 18px; font-size: 13px; font-weight: 500; margin-bottom: 18px; }
        .alert-error-custom   { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 10px; padding: 12px 18px; font-size: 13px; font-weight: 500; margin-bottom: 18px; }

        .empty-state { text-align: center; padding: 40px 0; color: #9ca3af; font-size: 13px; }
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
        <li class="sidebar-item"><a href="../kelola_galeri/list_galeri.php" class="sidebar-link "><i data-lucide="image" class="sidebar-icon"></i> Kelola Galeri</a></li>
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
            <h2 class="page-title">Kelola Tagihan</h2>
        </div>
        <div class="topbar-right">
            <button class="notification-btn"><i data-lucide="bell" style="width:20px;height:20px;"></i></button>
            <div class="user-profile">
                <div class="avatar"></div>
                <div>
                    <div class=\"user-name\"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
        </div>
    </header>

    <main class="admin-content">

        <?php if (isset($_GET['success'])): ?>
        <div class="alert-success-custom">✓ <?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
        <div class="alert-error-custom">✗ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-card-title">Total Penghuni Aktif</div>
                    <div class="stat-card-value val-dark"><?= count($penghuniList) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-card-title">Sudah Ada Tagihan (<?= $namaBulan[$bulan] . ' ' . $tahun ?>)</div>
                    <div class="stat-card-value val-green"><?= $sudahAdaTagihan ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-card-title">Belum Ada Tagihan</div>
                    <div class="stat-card-value val-red"><?= $belumAdaTagihan ?></div>
                </div>
            </div>
        </div>

        <!-- Filter + Generate All -->
        <div class="filter-bar">
            <form method="GET" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <label>Periode:</label>
                <select name="bulan">
                    <?php foreach ($namaBulan as $nb => $label): ?>
                    <option value="<?= $nb ?>" <?= $nb == $bulan ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-filter">
                    <i data-lucide="filter" style="width:14px;height:14px;display:inline;"></i> Tampilkan
                </button>
            </form>
            <?php if ($belumAdaTagihan > 0): ?>
            <button class="btn-generate-all" onclick="generateSemua()">
                <i data-lucide="zap" style="width:14px;height:14px;"></i>
                Generate Semua Tagihan (<?= $belumAdaTagihan ?> penghuni)
            </button>
            <?php endif; ?>
            <a href="list_pembayaran.php" style="margin-left:auto; font-size:13px; color:#6b7280; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
                <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Kembali ke List Pembayaran
            </a>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <table class="tagihan-table">
                <thead>
                    <tr>
                        <th>Penghuni</th>
                        <th>No Kamar</th>
                        <th>Tipe</th>
                        <th>Tagihan/Bulan</th>
                        <th>Status <?= $namaBulan[$bulan] . ' ' . $tahun ?></th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($penghuniList)): ?>
                    <tr><td colspan="6"><div class="empty-state">Belum ada penghuni aktif</div></td></tr>
                <?php else: ?>
                    <?php foreach ($penghuniList as $ph):
                        $sudah = isset($existingTagihan[$ph['booking_id']]);
                        // Cek status lebih detail
                        $statusDetail = null;
                        try {
                            $stmtSt = $conn->prepare("SELECT status FROM pembayaran WHERE booking_id = ? AND MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=? ORDER BY id DESC LIMIT 1");
                            $stmtSt->execute([$ph['booking_id'], $bulan, $tahun]);
                            $statusDetail = $stmtSt->fetchColumn();
                        } catch (Exception $e) {}
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($ph['nama']) ?></div>
                            <div style="font-size:11px;color:#9ca3af;"><?= htmlspecialchars($ph['no_hp'] ?? '-') ?></div>
                        </td>
                        <td><span class="badge-room"><?= htmlspecialchars($ph['no_kamar'] ?? '-') ?></span></td>
                        <td style="color:#6b7280;"><?= htmlspecialchars($ph['tipe']) ?></td>
                        <td style="font-weight:700;">Rp <?= number_format($ph['harga'], 0, ',', '.') ?></td>
                        <td>
                            <?php if (!$sudah): ?>
                                <span class="badge-belum">✗ Belum Ada Tagihan</span>
                            <?php elseif ($statusDetail === 'valid'): ?>
                                <span class="badge-sudah">✓ Lunas</span>
                            <?php elseif ($statusDetail === 'menunggu_verifikasi'): ?>
                                <span class="badge-verif">⏳ Menunggu Verifikasi</span>
                            <?php else: ?>
                                <span class="badge-verif">📋 Tagihan Terkirim</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$sudah): ?>
                                <button class="btn-buat"
                                    onclick="buatTagihan(<?= $ph['booking_id'] ?>, <?= $ph['harga'] ?>, <?= $bulan ?>, <?= $tahun ?>, '<?= htmlspecialchars(addslashes($ph['nama'])) ?>')">
                                    <i data-lucide="plus-circle" style="width:13px;height:13px;"></i>
                                    Buat Tagihan
                                </button>
                            <?php else: ?>
                                <button class="btn-buat" disabled>
                                    <i data-lucide="check" style="width:13px;height:13px;"></i>
                                    Sudah Ada
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function buatTagihan(bookingId, harga, bulan, tahun, nama) {
    if (!confirm('Buat tagihan Rp ' + harga.toLocaleString('id-ID') + ' untuk ' + nama + '?')) return;
    kirimTagihan([{ booking_id: bookingId, harga: harga }], bulan, tahun);
}

function generateSemua() {
    if (!confirm('Generate tagihan untuk semua penghuni yang belum ada tagihan bulan ini?')) return;
    // Kumpulkan semua yang belum ada tagihan
    const rows = document.querySelectorAll('[data-booking-id]');
    // Gunakan form submit ke proses_tagihan.php dengan semua booking_id
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'proses_tagihan.php';

    const bulanInput = document.createElement('input');
    bulanInput.type = 'hidden'; bulanInput.name = 'bulan'; bulanInput.value = <?= $bulan ?>;
    form.appendChild(bulanInput);

    const tahunInput = document.createElement('input');
    tahunInput.type = 'hidden'; tahunInput.name = 'tahun'; tahunInput.value = <?= $tahun ?>;
    form.appendChild(tahunInput);

    const allInput = document.createElement('input');
    allInput.type = 'hidden'; allInput.name = 'generate_all'; allInput.value = '1';
    form.appendChild(allInput);

    document.body.appendChild(form);
    form.submit();
}

function kirimTagihan(items, bulan, tahun) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'proses_tagihan.php';

    items.forEach(item => {
        const bId = document.createElement('input');
        bId.type = 'hidden'; bId.name = 'booking_ids[]'; bId.value = item.booking_id;
        form.appendChild(bId);
    });

    const bln = document.createElement('input');
    bln.type = 'hidden'; bln.name = 'bulan'; bln.value = bulan;
    form.appendChild(bln);

    const thn = document.createElement('input');
    thn.type = 'hidden'; thn.name = 'tahun'; thn.value = tahun;
    form.appendChild(thn);

    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>
