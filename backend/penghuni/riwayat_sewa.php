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

// Fetch riwayat dari pembayaran + booking
$riwayatSewa = [];
try {
    // Kita ambil semua pembayaran yang sukses/menunggu untuk user ini
    $stmt = $conn->prepare("
        SELECT k.nomor_kamar, k.tipe, k.harga,
               b.tanggal_masuk, b.durasi_bulan, b.status AS status_booking,
               p.status AS status_bayar,
               p.tanggal_bayar AS tgl_bayar,
               p.jumlah,
               p.metode, p.bukti_bayar,
               p.created_at AS tanggal_transaksi
        FROM pembayaran p
        JOIN booking b ON p.booking_id = b.id
        JOIN kamar k ON b.kamar_id = k.id
        WHERE b.user_id = ?
        ORDER BY p.id ASC
    ");
    $stmt->execute([$userId]);
    $riwayatSewaRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung periode per pembayaran secara kronologis
    $riwayatSewa = [];
    $currentStart = null;
    
    foreach ($riwayatSewaRaw as $r) {
        if (!$currentStart && $r['tanggal_masuk']) {
            $currentStart = new DateTime($r['tanggal_masuk']);
        }
        
        if ($currentStart) {
            $harga = (int)$r['harga'];
            $jumlah = (int)$r['jumlah'];
            // Hitung berapa bulan yang dicover oleh pembayaran ini
            $bulanDicover = ($harga > 0) ? round($jumlah / $harga) : 1;
            if ($bulanDicover < 1) $bulanDicover = 1;
            
            $tglMasukPeriode = clone $currentStart;
            $tglKeluarPeriode = clone $currentStart;
            $tglKeluarPeriode->modify("+$bulanDicover month");
            
            $r['tanggal_masuk_periode'] = $tglMasukPeriode->format('Y-m-d');
            $r['tanggal_keluar_periode'] = $tglKeluarPeriode->format('Y-m-d');
            $r['bulan_dicover'] = $bulanDicover;
            
            // Update currentStart untuk pembayaran berikutnya
            $currentStart = clone $tglKeluarPeriode;
        } else {
            $r['tanggal_masuk_periode'] = null;
            $r['tanggal_keluar_periode'] = null;
            $r['bulan_dicover'] = 0;
        }
        $riwayatSewa[] = $r;
    }
    
    // Balik urutan agar yang terbaru di atas untuk tampilan
    $riwayatSewa = array_reverse($riwayatSewa);
    
} catch (Exception $e) {
    // error_log($e->getMessage());
}

// Stats
$totalTransaksi = count($riwayatSewa);
$sewaAktif  = count(array_unique(array_column(array_filter($riwayatSewa, fn($r) => in_array($r['status_booking'], ['disetujui','aktif'])), 'nomor_kamar')));
$sewaSelesai = count(array_unique(array_column(array_filter($riwayatSewa, fn($r) => $r['status_booking'] === 'selesai'), 'nomor_kamar')));
$totalPengeluaran = array_sum(array_column(array_filter($riwayatSewa, fn($r) => $r['status_bayar'] === 'valid'), 'jumlah'));

// Foto user
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
    <title>Riwayat Sewa - Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard-responsive.css">
    <style>
        :root { --green:#11a654; --green-light:#e8f7f0; --bg:#f4f6f8; --dark:#1f2937; --gray:#6b7280; --border:#e5e7eb; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; background:var(--bg); color:var(--dark); overflow-x:hidden; }
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
        .main { margin-left:240px; min-height:100vh; display:flex; flex-direction:column; }
        .topbar { height:68px; background:white; display:flex; align-items:center; justify-content:space-between; padding:0 30px; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; }
        .topbar-title { font-size:20px; font-weight:700; }
        .topbar-right { display:flex; align-items:center; gap:12px; }
        .avatar { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,#9ca3af,#6b7280); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; color:white; flex-shrink:0; overflow:hidden; }
        .avatar img { width:100%; height:100%; object-fit:cover; }
        .user-name { font-weight:600; font-size:14px; line-height:1.2; }
        .user-role { font-size:11.5px; color:var(--gray); }
        .content { padding:24px 28px; flex-grow:1; }

        /* STAT CARDS */
        .stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
        .stat-card { background:white; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.04); padding:18px 20px; display:flex; align-items:center; gap:14px; }
        .stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .stat-label { font-size:12px; color:var(--gray); font-weight:500; margin-bottom:4px; }
        .stat-value { font-size:22px; font-weight:800; color:var(--dark); line-height:1; }
        .stat-value.small { font-size:16px; }

        /* TRANSAKSI CARD */
        .transaksi-section { background:white; border-radius:16px; box-shadow:0 2px 10px rgba(0,0,0,.04); overflow:hidden; }
        .transaksi-header { display:flex; align-items:center; justify-content:space-between; padding:20px 24px 16px; border-bottom:1px solid var(--border); }
        .transaksi-header-left { display:flex; align-items:center; gap:12px; }
        .transaksi-header-icon { width:40px; height:40px; border-radius:10px; background:var(--green-light); display:flex; align-items:center; justify-content:center; }
        .transaksi-title { font-size:15px; font-weight:700; color:var(--dark); }
        .transaksi-sub { font-size:12px; color:var(--gray); margin-top:2px; }
        .search-box { display:flex; align-items:center; gap:8px; background:#f9fafb; border:1px solid var(--border); border-radius:20px; padding:7px 14px; }
        .search-box input { border:none; background:none; font-family:'Poppins',sans-serif; font-size:12.5px; color:var(--dark); outline:none; width:160px; }
        .search-box input::placeholder { color:#9ca3af; }

        /* Filter tabs */
        .filter-tabs { display:flex; gap:8px; padding:16px 24px 0; }
        .filter-tab {
            padding:6px 18px; border-radius:20px; font-size:13px; font-weight:500;
            cursor:pointer; border:none; font-family:'Poppins',sans-serif;
            transition:all .2s; background:none; color:var(--gray);
        }
        .filter-tab.active { background:var(--green); color:white; font-weight:600; }
        .filter-tab:not(.active):hover { background:#f3f4f6; }

        /* Transaction items */
        .transaksi-list { padding:16px 24px 24px; }
        .transaksi-item {
            display:flex; align-items:center; gap:16px;
            border:1px solid var(--border); border-radius:12px;
            padding:16px 20px; margin-bottom:12px;
            transition:box-shadow .15s;
            animation:fadeUp .3s ease forwards; opacity:0;
        }
        .transaksi-item:hover { box-shadow:0 2px 10px rgba(0,0,0,.06); }
        .transaksi-item:nth-child(1) { animation-delay:.05s; }
        .transaksi-item:nth-child(2) { animation-delay:.10s; }
        .transaksi-item:nth-child(3) { animation-delay:.15s; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(5px)} to{opacity:1;transform:translateY(0)} }

        .item-icon { width:44px; height:44px; border-radius:10px; background:var(--green-light); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .item-body { flex:1; }
        .item-title-row { display:flex; align-items:center; gap:10px; margin-bottom:4px; }
        .item-room { font-size:14px; font-weight:700; color:var(--dark); }
        .item-meta { font-size:12px; color:var(--gray); margin-bottom:6px; }
        .item-dates { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
        .item-date-chip { display:flex; align-items:center; gap:5px; font-size:12px; color:var(--gray); }

        .badge-aktif { background:#e8f7f0; color:var(--green); border-radius:20px; padding:3px 12px; font-size:11.5px; font-weight:600; }
        .badge-selesai { background:#f3f4f6; color:var(--gray); border-radius:20px; padding:3px 12px; font-size:11.5px; font-weight:600; }
        .badge-lunas { background:#e8f7f0; color:var(--green); border-radius:20px; padding:3px 12px; font-size:11.5px; font-weight:600; }
        .badge-belum { background:#fee2e2; color:#ef4444; border-radius:20px; padding:3px 12px; font-size:11.5px; font-weight:600; }
        .badge-verif { background:#fef3c7; color:#d97706; border-radius:20px; padding:3px 12px; font-size:11.5px; font-weight:600; }

        .empty-state { text-align:center; padding:48px; color:var(--gray); font-size:13px; }
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
        <li class="sidebar-item"><a href="riwayat_sewa.php" class="sidebar-link active"><i data-lucide="history" class="sidebar-icon"></i> Riwayat Sewa</a></li>
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
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
            <h2 class="topbar-title">Riwayat Sewa</h2>
        </div>
        <a href="profil.php" class="topbar-right" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar">
                    <?php if ($userFoto): ?>
                        <img src="../uploads/profil/<?= htmlspecialchars(basename($userFoto)) ?>" alt="Profil">
                    <?php else: ?>
                        <?= strtoupper(substr($namaUser, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div><div class="user-name"><?= htmlspecialchars($namaUser) ?></div><div class="user-role">Penghuni</div></div>
            </div>
        </a>
    </header>
    <main class="content">

        <!-- Stat Cards -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f7f0;">
                    <i data-lucide="dollar-sign" style="width:22px;height:22px;color:var(--green);"></i>
                </div>
                <div><div class="stat-label">Total Transaksi</div><div class="stat-value"><?= $totalTransaksi ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f7f0;">
                    <i data-lucide="clock" style="width:22px;height:22px;color:var(--green);"></i>
                </div>
                <div><div class="stat-label">Sewa Aktif</div><div class="stat-value"><?= $sewaAktif ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#eff6ff;">
                    <i data-lucide="badge-check" style="width:22px;height:22px;color:#3b82f6;"></i>
                </div>
                <div><div class="stat-label">Sewa Selesai</div><div class="stat-value"><?= $sewaSelesai ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#eff6ff;">
                    <i data-lucide="credit-card" style="width:22px;height:22px;color:#3b82f6;"></i>
                </div>
                <div><div class="stat-label">Total Pengeluaran</div><div class="stat-value small">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></div></div>
            </div>
        </div>

        <!-- Riwayat Transaksi -->
        <div class="transaksi-section">
            <div class="transaksi-header">
                <div class="transaksi-header-left">
                    <div class="transaksi-header-icon">
                        <i data-lucide="history" style="width:20px;height:20px;color:var(--green);"></i>
                    </div>
                    <div>
                        <div class="transaksi-title">Riwayat Transaksi</div>
                        <div class="transaksi-sub">Riwayat Lengkap Pembayaran Sewa Kamar Anda</div>
                    </div>
                </div>
                <div class="search-box">
                    <i data-lucide="search" style="width:14px;height:14px;color:#9ca3af;"></i>
                    <input type="text" id="searchInput" placeholder="Cari Riwayat Sewa" oninput="filterItems()">
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="setFilter('semua',this)">Semua</button>
                <button class="filter-tab" onclick="setFilter('aktif',this)">Aktif</button>
                <button class="filter-tab" onclick="setFilter('selesai',this)">Selesai</button>
            </div>

            <div class="transaksi-list" id="transaksiList">
                <?php if (empty($riwayatSewa)): ?>
                <div class="empty-state">
                    <i data-lucide="inbox" style="width:40px;height:40px;display:block;margin:0 auto 12px;color:#d1d5db;"></i>
                    Belum ada riwayat sewa
                </div>
                <?php else: ?>
                    <?php foreach ($riwayatSewa as $r):
                        $statusBayar = $r['status_bayar'] ?? 'belum_bayar';
                        $isAktif  = in_array($r['status_booking'], ['disetujui','aktif']);
                        $noKamar  = $r['nomor_kamar'] ? 'No. ' . str_pad($r['nomor_kamar'],2,'0',STR_PAD_LEFT) : '-';
                        $tipe     = $r['tipe'] ?? '';
                        
                        $tglMasuk = $r['tanggal_masuk_periode'] ? date('d M Y', strtotime($r['tanggal_masuk_periode'])) : '—';
                        $tglKeluar = $r['tanggal_keluar_periode'] ? date('d M Y', strtotime($r['tanggal_keluar_periode'])) : '—';
                        
                        $tglTransaksi = $r['tanggal_transaksi'] ? date('d M Y', strtotime($r['tanggal_transaksi'])) : '—';
                        $metode   = $r['metode'] ?? '—';
                        
                        // Buat label durasi yang lebih informatif
                        $durasiLabel = $r['bulan_dicover'] . ' Bulan';
                        if (strpos(strtolower($metode), 'perpanjangan') !== false) {
                            $durasiLabel = 'Perpanjangan ' . $durasiLabel;
                        } else {
                            $durasiLabel = 'Sewa ' . $durasiLabel;
                        }

                        // Fix filter tag berdasarkan status aktual
                        if ($r['status_booking'] === 'selesai') {
                            $filterTag = 'selesai';
                        } elseif ($isAktif) {
                            // Cek apakah periode sudah habis
                            $tglKeluarCheck = $r['tanggal_keluar_periode'] ? new DateTime($r['tanggal_keluar_periode']) : null;
                            $todayCheck = new DateTime();
                            $filterTag = ($tglKeluarCheck && $tglKeluarCheck < $todayCheck) ? 'selesai' : 'aktif';
                        } else {
                            $filterTag = 'selesai';
                        }
                    ?>
                    <div class="transaksi-item" data-filter="<?= $filterTag ?>" data-search="<?= htmlspecialchars(strtolower($noKamar . ' ' . $tipe . ' ' . $metode)) ?>">
                        <div class="item-icon">
                            <i data-lucide="bed-double" style="width:22px;height:22px;color:var(--green);"></i>
                        </div>
                        <div class="item-body">
                            <div class="item-title-row">
                                <span class="item-room">Kamar <?= htmlspecialchars($noKamar) ?><?= $tipe ? ' - ' . htmlspecialchars($tipe) : '' ?></span>
                            <?php
                                // ═══ ISSUE #8: Fix status logic berdasarkan database ═══
                                $statusBooking = strtolower($r['status_booking'] ?? '');
                                $tglKeluarDate = $r['tanggal_keluar_periode'] ? new DateTime($r['tanggal_keluar_periode']) : null;
                                $todayDate = new DateTime();
                                
                                if ($statusBooking === 'selesai') {
                                    // Booking sudah selesai/checkout
                                    echo '<span class="badge-selesai">Selesai</span>';
                                } elseif ($statusBayar === 'valid' && ($statusBooking === 'aktif' || $statusBooking === 'disetujui')) {
                                    // Cek apakah periode sudah lewat
                                    if ($tglKeluarDate && $tglKeluarDate < $todayDate) {
                                        echo '<span class="badge-selesai">Selesai</span>';
                                    } else {
                                        echo '<span class="badge-aktif">Aktif</span>';
                                    }
                                } elseif ($statusBayar === 'menunggu_verifikasi') {
                                    echo '<span class="badge-verif">Diverifikasi</span>';
                                } elseif ($statusBooking === 'ditolak' || $statusBooking === 'dibatalkan') {
                                    echo '<span class="badge-belum">Dibatalkan</span>';
                                } else {
                                    echo '<span class="badge-belum">Belum Bayar</span>';
                                }
                            ?>
                            </div>
                            <div class="item-meta">Transaksi pada <?= $tglTransaksi ?> (Rp <?= number_format($r['jumlah'], 0, ',', '.') ?>)</div>
                            <div class="item-dates">
                                <div class="item-date-chip">
                                    <i data-lucide="calendar" style="width:13px;height:13px;color:var(--green);"></i>
                                    <?= $tglMasuk ?> - <?= $tglKeluar ?>
                                </div>
                                <div class="item-date-chip">
                                    <i data-lucide="credit-card" style="width:13px;height:13px;color:#6b7280;"></i>
                                    <?= htmlspecialchars($durasiLabel) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>
lucide.createIcons();
let currentFilter = 'semua';

function setFilter(f, btn) {
    currentFilter = f;
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterItems();
}

function filterItems() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.transaksi-item').forEach(item => {
        const matchFilter = currentFilter === 'semua' || item.dataset.filter === currentFilter;
        const matchSearch = !q || item.dataset.search.includes(q);
        item.style.display = (matchFilter && matchSearch) ? '' : 'none';
    });
}
</script>
</body>
</html>
