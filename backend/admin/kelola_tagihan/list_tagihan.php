<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") { header("Location: ../../api/auth/login.php"); exit; }

$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('n'));
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));
$nm = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

// ═══ AUTO-SYNC: Sinkronisasi status kamar dengan data penghuni aktif ═══
try {
    $conn->exec("
        UPDATE kamar k
        JOIN booking b ON b.kamar_id = k.id
        JOIN users u ON b.user_id = u.id
        SET k.status = 'terisi'
        WHERE b.status IN ('aktif', 'selesai')
          AND u.role = 'penghuni'
          AND u.status = 'aktif'
          AND k.status = 'tersedia'
    ");
    $conn->exec("
        UPDATE kamar 
        SET status = 'tersedia' 
        WHERE status = 'terisi' 
          AND id NOT IN (
              SELECT b.kamar_id 
              FROM booking b 
              JOIN users u ON b.user_id = u.id 
              WHERE b.status IN ('aktif', 'disetujui', 'selesai') 
                AND u.role = 'penghuni' 
                AND u.status = 'aktif'
                AND b.kamar_id IS NOT NULL
          )
    ");
} catch (Exception $e) {}

// Tanggal awal & akhir bulan yang dipilih
$tglAwal  = sprintf('%04d-%02d-01', $tahun, $bulan);
$tglAkhir = date('Y-m-t', mktime(0, 0, 0, $bulan, 1, $tahun)); // last day of month

try {
    // Ambil semua penghuni + info kamar dari booking + status pembayaran bulan ini
    // Mendeteksi 2 jenis pembayaran yang berlaku:
    //   (A) Tagihan reguler: tanggal_bayar di bulan yang dipilih, bukan Perpanjangan
    //   (B) Perpanjangan valid yang mencakup bulan ini (tanggal_bayar + durasi_bulan bulan >= bulan ini)
    $stmt = $conn->prepare("
        SELECT 
            u.id as user_id, u.nama, u.no_hp,
            k.nomor_kamar as no_kamar, k.tipe, k.harga,
            b.id      as booking_id,
            p.id      as pay_id, p.status as pay_status,
            p.jumlah  as pay_jumlah, p.tanggal_bayar, p.bukti_bayar,
            p.metode, p.durasi_bulan as pay_durasi,
            perp.id   as perp_id, perp.jumlah as perp_jumlah,
            perp.tanggal_bayar as perp_tgl, perp.durasi_bulan as perp_durasi
        FROM users u
        LEFT JOIN booking b ON b.user_id = u.id 
            AND b.status IN ('disetujui','aktif','selesai')
            AND b.id = (
                SELECT MAX(b3.id) FROM booking b3 
                WHERE b3.user_id = u.id AND b3.status IN ('disetujui','aktif','selesai')
            )
        LEFT JOIN kamar k ON b.kamar_id = k.id
        -- (A) Tagihan reguler bulan ini (bukan Perpanjangan)
        LEFT JOIN pembayaran p ON p.id = (
            SELECT MAX(p2.id) FROM pembayaran p2 
            WHERE p2.booking_id = b.id 
              AND MONTH(p2.tanggal_bayar) = ? AND YEAR(p2.tanggal_bayar) = ?
              AND (p2.metode IS NULL OR p2.metode NOT LIKE '%Perpanjangan%')
        )
        -- (B) Perpanjangan valid yang mencakup bulan ini
        LEFT JOIN pembayaran perp ON perp.id = (
            SELECT MAX(p3.id) FROM pembayaran p3
            WHERE p3.booking_id = b.id
              AND p3.metode LIKE '%Perpanjangan%'
              AND p3.status = 'valid'
              AND p3.tanggal_bayar <= ?
              AND DATE_ADD(p3.tanggal_bayar, INTERVAL p3.durasi_bulan MONTH) > ?
        )
        WHERE u.role = 'penghuni' AND u.status = 'aktif'
        ORDER BY 
            CASE WHEN p.status = 'menunggu_verifikasi' THEN 0
                 WHEN p.id IS NULL AND perp.id IS NULL THEN 1
                 WHEN p.status = 'belum_bayar' THEN 2
                 ELSE 3 END ASC,
            u.id ASC
    ");
    $stmt->execute([$bulan, $tahun, $tglAkhir, $tglAwal]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $list = []; error_log('list_tagihan error: ' . $e->getMessage()); }

$totalBelum = 0; $totalLunas = 0; $totalVerif = 0;
foreach ($list as $r) {
    $coverByPerp = !empty($r['perp_id']); // covered by valid Perpanjangan
    if ($coverByPerp) {
        $totalLunas++; // Perpanjangan valid = sudah lunas untuk bulan ini
    } elseif (!$r['pay_id']) {
        if ($r['booking_id']) $totalBelum++;
    } elseif ($r['pay_status'] === 'valid') $totalLunas++;
    elseif ($r['pay_status'] === 'menunggu_verifikasi') $totalVerif++;
    else $totalBelum++;
}
// Belum generate = tidak ada tagihan reguler DAN tidak di-cover perpanjangan
$belumGenerate = count(array_filter($list, fn($r) => !$r['pay_id'] && empty($r['perp_id']) && !empty($r['booking_id'])));
$tanpaBooking  = count(array_filter($list, fn($r) => empty($r['booking_id'])));

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Kelola Tagihan - Admin Kost Elmi Sarah</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/dashboard-responsive.css?v=1.2">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

:root{--g:#11a654;--bg:#f4f6f8;--dk:#1f2937;}
body{font-family:'Poppins',sans-serif;background:var(--bg);margin:0;color:var(--dk);}
.admin-sidebar{width:240px;height:100vh;background:var(--g);position:fixed;top:0;left:0;display:flex;flex-direction:column;z-index:1000;border-top-right-radius:15px;border-bottom-right-radius:15px;box-shadow:4px 0 10px rgba(0,0,0,.03);}
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
.sidebar-link.active { background-color: var(--bg); color: var(--g); font-weight: 600; box-shadow: -3px 0 8px rgba(0,0,0,0.02); }
.sidebar-icon { width: 18px; height: 18px; margin-right: 12px; }
.sidebar-footer { padding: 20px 15px; margin-bottom: 15px; }
.btn-keluar {
    display: inline-flex; align-items: center;
    background-color: white; color: var(--dk);
    text-decoration: none; padding: 8px 20px;
    border-radius: 25px; font-weight: 600; font-size: 13px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}
.btn-keluar:hover { background-color: #f3f4f6; color: var(--dk); }
.admin-main{margin-left:240px;min-height:100vh;display:flex;flex-direction:column;}
.admin-topbar{height:70px;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 30px;border-bottom:1px solid #e5e7eb;}
.page-title{font-size:20px;font-weight:600;margin:0;}
.topbar-right{display:flex;align-items:center;gap:20px;}
.user-profile{display:flex;align-items:center;gap:12px;}
.user-info{display:flex;flex-direction:column;}
.notif-btn{background:none;border:none;color:var(--dk);}
.avatar{width:38px;height:38px;background:#d1d5db;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:14px;overflow:hidden;}
.user-name{font-weight:600;font-size:13px;line-height:1.2;}
.user-role{font-size:11px;color:#9ca3af;}
.content{padding:25px 30px;flex-grow:1;}

.stat-card{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.03);padding:22px 24px;}
.sc-title{font-size:12px;font-weight:600;color:#6b7280;margin-bottom:8px;}
.sc-val{font-size:28px;font-weight:700;line-height:1;}
.v-green{color:var(--g);}.v-red{color:#ef4444;}.v-yellow{color:#d97706;}.v-dark{color:var(--dk);}

.filter-bar{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.03);padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.filter-bar select{border:1px solid #e5e7eb;border-radius:8px;padding:7px 12px;font-size:13px;font-family:'Poppins',sans-serif;outline:none;background:#f9fafb;}
.btn-go{background:var(--g);color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;}
.btn-genall{background:#1d4ed8;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}

.tw{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.03);overflow:hidden;}
.tt{width:100%;border-collapse:collapse;}
.tt thead tr{background:var(--g);color:#fff;}
.tt thead th{padding:13px 16px;font-size:13px;font-weight:500;text-align:left;}
.tt tbody tr{border-bottom:1px solid #f3f4f6;transition:background .15s;}
.tt tbody tr:hover{background:#fafafa;}
.tt tbody tr:last-child{border-bottom:none;}
.tt tbody td{padding:13px 16px;font-size:13px;color:#374151;vertical-align:middle;}

.bp{display:inline-flex;align-items:center;gap:5px;border-radius:20px;padding:5px 12px;font-size:11.5px;font-weight:600;}
.bp-lunas{background:#e8f7f0;color:var(--g);}
.bp-belum{background:#fee2e2;color:#ef4444;}
.bp-verif{background:#fef3c7;color:#d97706;}
.bp-none{background:#f3f4f6;color:#6b7280;}
.badge-room{display:inline-flex;align-items:center;justify-content:center;background:#f3f4f6;color:#374151;border-radius:20px;padding:3px 12px;font-size:12px;font-weight:500;}

.btn-gen{background:var(--g);color:#fff;border:none;border-radius:7px;padding:5px 12px;font-size:12px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:background .2s;}
.btn-gen:hover{background:#0e9148;}
.btn-gen:disabled{opacity:.4;cursor:not-allowed;}
.btn-warn{background:none;color:#d97706;border:1px solid #d97706;border-radius:7px;padding:5px 12px;font-size:12px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:all .2s;}
.btn-warn:hover{background:#d97706;color:#fff;}

.alert-ok{background:#e8f7f0;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:12px 18px;font-size:13px;font-weight:500;margin-bottom:16px;}
.alert-err{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;border-radius:10px;padding:12px 18px;font-size:13px;font-weight:500;margin-bottom:16px;}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9000;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:16px;padding:28px;width:100%;max-width:460px;box-shadow:0 8px 40px rgba(0,0,0,.15);}
.modal-title{font-size:16px;font-weight:700;margin-bottom:4px;}
.modal-sub{font-size:12px;color:#6b7280;margin-bottom:16px;}
.modal-textarea{width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;font-size:13px;font-family:'Poppins',sans-serif;resize:vertical;outline:none;min-height:80px;}
.modal-actions{display:flex;gap:10px;margin-top:16px;justify-content:flex-end;}
.btn-send{background:var(--g);color:#fff;border:none;border-radius:8px;padding:8px 20px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;}
.btn-cancel{background:none;color:#6b7280;border:1px solid #e5e7eb;border-radius:8px;padding:8px 20px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;}

.toast-wrap{position:fixed;top:20px;right:20px;z-index:9999;display:none;}
.toast-msg{background:#fff;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.1);padding:14px 18px;font-size:13px;font-weight:500;display:flex;align-items:center;gap:10px;min-width:260px;}
.toast-ok{border-left:4px solid var(--g);}
.toast-err{border-left:4px solid #ef4444;}
/* Input Nominal */
.input-nominal {
    width: 100px; border: 1px solid #e5e7eb; border-radius: 6px;
    padding: 3px 8px; font-size: 13px; font-weight: 600; outline: none;
    font-family: 'Poppins', sans-serif;
}
.input-nominal:focus { border-color: var(--g); }
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
        <li class="sidebar-item"><a href="../kelola_user/list_user.php" class="sidebar-link "><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="../kelola_kamar/list_kamar.php" class="sidebar-link "><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li class="sidebar-item"><a href="../kelola_tagihan/list_tagihan.php" class="sidebar-link active"><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
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
            <h2 class="page-title">Kelola Tagihan & Pembayaran</h2>
        </div>
        <div class="topbar-right">
            <button class="notification-btn">
                <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
            </button>
            <div class="user-profile">
                <div class="avatar">
                    <?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
        </div>
    </header>

    <main class="content">

        <?php if (isset($_GET['success'])): ?>
        <div class="alert-ok">✓ <?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
        <div class="alert-err">✗ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="stat-card"><div class="sc-title">Total Penghuni Aktif</div><div class="sc-val v-dark"><?= count($list) ?></div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="sc-title">Lunas <?= $nm[$bulan].' '.$tahun ?></div><div class="sc-val v-green"><?= $totalLunas ?></div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="sc-title">Menunggu Verifikasi</div><div class="sc-val v-yellow"><?= $totalVerif ?></div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="sc-title">Belum Bayar</div><div class="sc-val v-red"><?= $totalBelum ?></div></div></div>
        </div>

        <!-- Filter + Generate All -->
        <div class="filter-bar">
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <label style="font-size:13px;font-weight:600;">Periode:</label>
                <select name="bulan">
                    <?php foreach ($nm as $n => $label): ?>
                    <option value="<?= $n ?>" <?= $n==$bulan?'selected':'' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun">
                    <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
                    <option value="<?= $y ?>" <?= $y==$tahun?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-go">Tampilkan</button>
            </form>
            <?php if ($belumGenerate > 0): ?>
            <form method="POST" action="proses_tagihan.php" onsubmit="return confirm('Generate tagihan untuk <?= $belumGenerate ?> penghuni yang belum ada tagihan?')">
                <input type="hidden" name="bulan" value="<?= $bulan ?>">
                <input type="hidden" name="tahun" value="<?= $tahun ?>">
                <input type="hidden" name="generate_all" value="1">
                <button type="submit" class="btn-genall">
                    <i data-lucide="zap" style="width:14px;height:14px;"></i>
                    Generate Semua (<?= $belumGenerate ?> penghuni)
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Table -->
        <div class="tw">
            <table class="tt">
                <thead>
                    <tr>
                        <th>Penghuni</th>
                        <th>No Kamar</th>
                        <th>Tagihan</th>
                        <th>Perpanjang</th>
                        <th>Status <?= $nm[$bulan].' '.$tahun ?></th>
                        <th>Tgl Bayar</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">Belum ada penghuni aktif</td></tr>
                <?php else: foreach ($list as $r):
                    $sudahGenerate  = !empty($r['pay_id']);
                    $punyaBooking   = !empty($r['booking_id']);
                    $coverByPerp    = !empty($r['perp_id']); // Bulan ini dicakup Perpanjangan valid
                    $status         = $r['pay_status'] ?? null;
                    $hargaTagihan   = $r['pay_jumlah'] ?? $r['harga'];
                ?>
                <tr <?= $coverByPerp ? 'style="background:#f0fdf4;"' : '' ?>>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($r['nama']) ?></div>
                        <div style="font-size:11px;color:#9ca3af;"><?= htmlspecialchars($r['no_hp']??'-') ?></div>
                    </td>
                    <td>
                        <?php if ($punyaBooking): ?>
                            <span class="badge-room"><?= htmlspecialchars($r['no_kamar']??'-') ?></span>
                        <?php else: ?>
                            <span class="bp bp-none" style="font-size:10px;">Belum Booking</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:700;">
                        <?php if ($coverByPerp): ?>
                            <span style="color:#6b7280;font-size:12px;">Rp <?= number_format($r['perp_jumlah'],0,',','.') ?></span>
                        <?php elseif (!$sudahGenerate && $punyaBooking): ?>
                            <div class="d-flex align-items-center gap-1">
                                <span style="font-size:12px; color:#9ca3af;">Rp</span>
                                <input type="number" name="jumlah_custom" 
                                       form="form_gen_<?= $r['user_id'] ?>"
                                       class="input-nominal" 
                                       value="<?= !empty($r['harga']) ? intval($r['harga']) : 1000000 ?>"
                                       placeholder="Nominal...">
                            </div>
                        <?php elseif ($sudahGenerate): ?>
                            Rp <?= number_format($hargaTagihan,0,',','.') ?>
                        <?php else: ?>
                            <span style="color:#d1d5db;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coverByPerp): ?>
                            <span style="font-weight:700;color:var(--g);font-size:12px;"><?= $r['perp_durasi'] ?> Bulan</span>
                        <?php elseif (!$sudahGenerate && $punyaBooking): ?>
                            <input type="number" name="durasi_bulan" 
                                   form="form_gen_<?= $r['user_id'] ?>"
                                   class="input-nominal" style="width: 60px;" 
                                   value="1" min="1"
                                   placeholder="Bln">
                        <?php elseif ($sudahGenerate): ?>
                            <?php if (isset($r['metode']) && strpos($r['metode'], 'Perpanjangan') !== false): ?>
                                <span style="font-weight:700; color:var(--g);"><?= htmlspecialchars($r['pay_durasi'] ?? 1) ?> Bulan</span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coverByPerp): ?>
                            <span class="bp bp-lunas">✓ Lunas (Perpanjangan)</span>
                        <?php elseif (!$punyaBooking): ?>
                            <span class="bp bp-none">— Belum Ada Booking</span>
                        <?php elseif (!$sudahGenerate): ?>
                            <span class="bp bp-none">— Belum Ada Tagihan</span>
                        <?php elseif ($status==='valid'): ?>
                            <span class="bp bp-lunas">✓ Lunas</span>
                        <?php elseif ($status==='menunggu_verifikasi'): ?>
                            <span class="bp bp-verif">⏳ Menunggu Verifikasi</span>
                        <?php else: ?>
                            <span class="bp bp-belum">✗ Belum Bayar</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#9ca3af;font-size:12px;">
                        <?php if ($coverByPerp): ?>
                            <?= date('j M Y', strtotime($r['perp_tgl'])) ?>
                        <?php else: ?>
                            <?= !empty($r['tanggal_bayar']) ? date('j M Y',strtotime($r['tanggal_bayar'])) : '—' ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($r['bukti_bayar'])): ?>
                            <a href="../../../frontend/assets/image/bukti/<?= htmlspecialchars($r['bukti_bayar']) ?>" target="_blank" style="font-size:12px;color:var(--g);text-decoration:none;">
                                <i data-lucide="image" style="width:13px;height:13px;"></i> Lihat
                            </a>
                        <?php else: ?>
                            <span style="color:#d1d5db;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($coverByPerp): ?>
                            <!-- Bulan ini dicakup Perpanjangan, tidak perlu buat tagihan baru -->
                            <?php elseif (!$sudahGenerate && $punyaBooking): ?>
                            <form method="POST" action="proses_tagihan.php" id="form_gen_<?= $r['user_id'] ?>">
                                <input type="hidden" name="bulan" value="<?= $bulan ?>">
                                <input type="hidden" name="tahun" value="<?= $tahun ?>">
                                <input type="hidden" name="user_ids[]" value="<?= $r['user_id'] ?>">
                                <button type="submit" class="btn-gen">
                                    <i data-lucide="plus" style="width:12px;height:12px;"></i>Buat Tagihan
                                </button>
                            </form>
                            <?php elseif ($sudahGenerate): ?>
                            <button class="btn-gen" disabled>
                                <i data-lucide="check" style="width:12px;height:12px;"></i>Tertagih
                            </button>
                            <?php endif; ?>

                            <?php if ($sudahGenerate && $status === 'menunggu_verifikasi'): ?>
                            <a href="../kelola_pembayaran/validasi_pembayaran.php?id=<?= $r['pay_id'] ?>&action=valid" class="btn-gen" style="background:#11a654; text-decoration:none;" onclick="return confirm('Validasi dan setujui pembayaran ini?')">
                                <i data-lucide="check-circle" style="width:12px;height:12px;"></i>Validasi
                            </a>
                            <a href="../kelola_pembayaran/validasi_pembayaran.php?id=<?= $r['pay_id'] ?>&action=tidak_valid" class="btn-warn" style="color:#ef4444; border-color:#ef4444; text-decoration:none;" onclick="return confirm('Tolak bukti ini? Penghuni akan diminta upload ulang.')">
                                <i data-lucide="x-circle" style="width:12px;height:12px;"></i>Tolak
                            </a>
                            <?php elseif ($sudahGenerate && $status !== 'valid'): ?>
                            <button class="btn-warn" onclick="bukaPeringatan(<?= $r['booking_id'] ?>, '<?= htmlspecialchars(addslashes($r['nama'])) ?>', '<?= $nm[$bulan].' '.$tahun ?>', <?= $hargaTagihan ?>, <?= $r['user_id'] ?>)">
                                <i data-lucide="bell" style="width:12px;height:12px;"></i>Ingatkan
                            </button>
                            <?php endif; ?>
                            
                            <a href="../kelola_pembayaran/list_pembayaran.php?user_id=<?= $r['user_id'] ?>" class="btn-gen" style="background:#3b82f6; text-decoration:none;">
                                <i data-lucide="history" style="width:12px;height:12px;"></i>Riwayat Lengkap
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<!-- Modal Peringatan -->
<div class="modal-overlay" id="modalPeringatan">
    <div class="modal-box">
        <div class="modal-title">Kirim Peringatan Tagihan</div>
        <div class="modal-sub" id="modalSub">Kepada: -</div>
        <textarea class="modal-textarea" id="pesanPeringatan" rows="4" placeholder="Tulis pesan peringatan..."></textarea>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="tutupModal()">Batal</button>
            <button class="btn-send" onclick="kirimPeringatan()">
                <i data-lucide="send" style="width:14px;height:14px;display:inline;margin-right:4px;"></i>Kirim
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-wrap" id="toastWrap">
    <div class="toast-msg" id="toastMsg"><span id="toastTxt"></span></div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let activeBookingId = null;
let activeUserId = null;

function bukaPeringatan(bookingId, nama, periode, harga, userId) {
    activeBookingId = bookingId;
    activeUserId    = userId || 0;
    document.getElementById('modalSub').textContent = 'Kepada: ' + nama;
    const rp = 'Rp ' + harga.toLocaleString('id-ID');
    document.getElementById('pesanPeringatan').value =
        'Yth. ' + nama + ',\n\nKami mengingatkan bahwa tagihan kost Anda periode ' +
        periode + ' sebesar ' + rp + ' belum dibayarkan.\n\nMohon segera melakukan pembayaran dan upload bukti transfer melalui aplikasi.\n\nTerima kasih,\nAdmin Kost Elmi Sarah';
    document.getElementById('modalPeringatan').classList.add('show');
}

function tutupModal() {
    document.getElementById('modalPeringatan').classList.remove('show');
    activeBookingId = null;
    activeUserId    = null;
}

function kirimPeringatan() {
    if (!activeBookingId) return;
    const pesan = document.getElementById('pesanPeringatan').value.trim();
    if (!pesan) { showToast('Pesan tidak boleh kosong','err'); return; }

    const fd = new FormData();
    fd.append('booking_id', activeBookingId);
    fd.append('user_id',    activeUserId);
    fd.append('pesan', pesan);

    fetch('kirim_peringatan.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            tutupModal();
            showToast(d.message, d.success ? 'ok' : 'err');
        })
        .catch(() => showToast('Gagal mengirim peringatan','err'));
}

function showToast(msg, type='ok') {
    const wrap = document.getElementById('toastWrap');
    const box  = document.getElementById('toastMsg');
    document.getElementById('toastTxt').textContent = msg;
    box.className = 'toast-msg toast-' + type;
    wrap.style.display = 'block';
    clearTimeout(window._tt);
    window._tt = setTimeout(() => wrap.style.display='none', 3500);
}

document.getElementById('modalPeringatan').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
</script>
</body>
</html>
