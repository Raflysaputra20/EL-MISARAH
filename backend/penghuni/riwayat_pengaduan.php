<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . '/init.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penghuni") {
    header("Location: ../api/auth/login.php"); exit;
}
$userId   = $_SESSION["user_id"];
$namaUser = $_SESSION["nama"] ?? "Penghuni";

$pengaduan = [];
try {
    $stmt = $conn->prepare("SELECT * FROM pengaduan WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$userId]);
    $pengaduan = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$userFoto = getUserFoto($conn, $userId);

$statusLabel = ['baru'=>'Baru','diproses'=>'Diproses','selesai'=>'Selesai','ditolak'=>'Ditolak'];
$statusClass = ['baru'=>'badge-baru','diproses'=>'badge-proses','selesai'=>'badge-selesai','ditolak'=>'badge-tolak'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Riwayat Pengaduan - Kost Elmi Sarah</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard-responsive.css">
<style>
:root{--g:#11a654;--gl:#e8f7f0;--bg:#f4f6f8;--dk:#1f2937;--gr:#6b7280;--bd:#e5e7eb}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--dk);overflow-x:hidden}
.sb{width:240px;height:100vh;background:var(--g);position:fixed;top:0;left:0;display:flex;flex-direction:column;border-top-right-radius:20px;border-bottom-right-radius:20px;box-shadow:4px 0 20px rgba(0,0,0,.1);z-index:1000}
.sb-brand{padding:28px 22px 22px;display:flex;align-items:center;justify-content:space-between}
.sb-name{font-size:22px;font-weight:800;color:#fff}
.sb-menu{list-style:none;padding:0 14px;flex-grow:1}
.sb-item{margin-bottom:4px}
.sb-link{display:flex;align-items:center;gap:12px;padding:11px 16px;color:rgba(255,255,255,.85);text-decoration:none;font-size:14px;font-weight:500;border-radius:12px;transition:all .2s}
.sb-link:hover{background:rgba(255,255,255,.15);color:#fff}
.sb-link.on{background:#fff;color:var(--g);font-weight:700}
.sb-ic{width:18px;height:18px;flex-shrink:0}
.sb-foot{padding:16px 14px 24px}
.btn-out{display:inline-flex;align-items:center;gap:8px;background:#fff;color:var(--dk);text-decoration:none;padding:10px 22px;border-radius:30px;font-weight:700;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.main{margin-left:240px;min-height:100vh;display:flex;flex-direction:column}
.topbar{height:68px;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 30px;border-bottom:1px solid var(--bd);position:sticky;top:0;z-index:100}
.tp-title{font-size:20px;font-weight:700}
.av{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#9ca3af,#6b7280);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;color:#fff;flex-shrink:0;overflow:hidden}
.av img{width:100%;height:100%;object-fit:cover}
.u-name{font-weight:600;font-size:14px;line-height:1.2}
.u-role{font-size:11.5px;color:var(--gr)}
.content{padding:24px 28px;flex-grow:1}
.top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.btn-buat{display:inline-flex;align-items:center;gap:8px;background:var(--g);color:#fff;text-decoration:none;padding:10px 22px;border-radius:12px;font-size:13.5px;font-weight:600;transition:.2s}
.btn-buat:hover{background:#0d8e47;color:#fff}
.aduan-card{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.04);padding:20px 24px;margin-bottom:14px;display:flex;align-items:flex-start;gap:18px;animation:fadeUp .3s ease forwards;opacity:0}
.aduan-card:nth-child(1){animation-delay:.05s}
.aduan-card:nth-child(2){animation-delay:.10s}
.aduan-card:nth-child(3){animation-delay:.15s}
@keyframes fadeUp{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.aduan-icon{width:48px;height:48px;border-radius:12px;background:var(--gl);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.aduan-body{flex:1}
.aduan-title-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px}
.aduan-title{font-size:14px;font-weight:700;color:var(--dk)}
.aduan-desc{font-size:12.5px;color:var(--gr);line-height:1.55;margin-bottom:8px}
.aduan-meta{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.aduan-date{font-size:11.5px;color:#9ca3af;display:flex;align-items:center;gap:4px}
.badge-baru{background:#dbeafe;color:#2563eb;border-radius:20px;padding:4px 12px;font-size:11.5px;font-weight:600}
.badge-proses{background:#fef3c7;color:#d97706;border-radius:20px;padding:4px 12px;font-size:11.5px;font-weight:600}
.badge-selesai{background:var(--gl);color:var(--g);border-radius:20px;padding:4px 12px;font-size:11.5px;font-weight:600}
.badge-tolak{background:#fee2e2;color:#ef4444;border-radius:20px;padding:4px 12px;font-size:11.5px;font-weight:600}
.empty-state{text-align:center;padding:60px 0;color:var(--gr);font-size:13px}
@media (max-width: 1024px) {
  .main{margin-left:0}
  .topbar{padding:0 16px;height:60px}
  .content{padding:16px}
  .top-bar{flex-direction:column;align-items:flex-start;gap:10px}
  .btn-buat{width:100%;justify-content:center}
}
@media (max-width: 768px) {
  .aduan-card{padding:16px;gap:12px}
  .aduan-title-row{flex-direction:column;align-items:flex-start}
  .aduan-meta{flex-direction:column;align-items:flex-start;gap:8px}
  .bukti-timeline{flex-direction:column;gap:10px}
  .bukti-line{display:none}
  .bukti-step{align-items:flex-start;width:100%}
  .bukti-dot{margin-bottom:0}
  .bukti-label{text-align:left}
}
/* Bukti Timeline */
.bukti-timeline{display:flex;align-items:flex-start;gap:0;margin-top:16px;padding:8px 0}
.bukti-step{display:flex;flex-direction:column;align-items:center;flex:1;min-width:0;position:relative}
.bukti-dot{width:36px;height:36px;border-radius:50%;border:2px solid var(--bd);background:#fafbfc;display:flex;align-items:center;justify-content:center;transition:all .3s ease;margin-bottom:8px}
.bukti-step.active .bukti-dot{transform:scale(1.1);box-shadow:0 3px 12px rgba(0,0,0,.08)}
.bukti-step:hover .bukti-dot{transform:scale(1.15)}
.bukti-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;transition:color .2s}
.bukti-link{font-size:11px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:3px;transition:opacity .2s}
.bukti-link:hover{opacity:.8}
.bukti-line{width:100%;max-width:60px;height:2px;background:var(--bd);align-self:center;margin-top:18px;margin-left:-8px;margin-right:-8px;transition:background .3s;border-radius:2px;flex-shrink:1}
.bukti-line.done{background:var(--g)}
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
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="btn-toggle-sidebar" onclick="openMobileSidebar()"><i data-lucide="menu" style="width:24px;height:24px;"></i></button>
      <h2 class="tp-title">Riwayat Pengaduan</h2>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <div class="av"><?php if($userFoto):?><img src="../uploads/profil/<?=htmlspecialchars(basename($userFoto))?>" alt=""><?php else:?><?=strtoupper(substr($namaUser,0,1))?><?php endif;?></div>
      <div><div class="u-name"><?=htmlspecialchars($namaUser)?></div><div class="u-role">Penghuni</div></div>
    </div>
  </header>
  <main class="content">
    <div class="top-bar">
      <div style="font-size:13px;color:var(--gr)"><?=count($pengaduan)?> laporan ditemukan</div>
      <a href="buat_pengaduan.php" class="btn-buat"><i data-lucide="plus" style="width:16px;height:16px"></i> Buat Pengaduan</a>
    </div>

    <?php if (empty($pengaduan)): ?>
    <div class="empty-state">
      <i data-lucide="inbox" style="width:44px;height:44px;display:block;margin:0 auto 12px;color:#d1d5db"></i>
      Belum ada pengaduan yang dikirim.<br>
      <a href="buat_pengaduan.php" style="color:var(--g);font-weight:600;font-size:13px;text-decoration:none;margin-top:8px;display:inline-block">+ Buat pengaduan pertama</a>
    </div>
    <?php else: ?>
      <?php foreach ($pengaduan as $p):
        $st  = $p['status'] ?? 'baru';
        $lbl = $statusLabel[$st] ?? ucfirst($st);
        $cls = $statusClass[$st] ?? 'badge-baru';
        $tgl = date('j M Y', strtotime($p['created_at']));
      ?>
      <div class="aduan-card">
        <div class="aduan-icon"><i data-lucide="wrench" style="width:22px;height:22px;color:var(--g)"></i></div>
        <div class="aduan-body">
          <div class="aduan-title-row">
            <span class="aduan-title"><?=htmlspecialchars($p['judul'])?></span>
            <span class="<?=$cls?>"><?=$lbl?></span>
          </div>
          <div class="aduan-desc"><?=htmlspecialchars(mb_strimwidth($p['isi'],0,120,'...'))?></div>
          <div class="aduan-meta">
            <div class="aduan-date"><i data-lucide="calendar" style="width:12px;height:12px"></i> <?=$tgl?></div>
            <?php if (!empty($p['no_kamar'])): ?>
            <div class="aduan-date"><i data-lucide="door-open" style="width:12px;height:12px"></i> <?=htmlspecialchars($p['no_kamar'])?></div>
            <?php endif; ?>
          </div>
          <!-- Timeline Bukti Smooth -->
          <div class="bukti-timeline">
            <?php
              $stages = [
                ['key'=>'foto_bukti','label'=>'Bukti Masuk','icon'=>'upload','color'=>'var(--g)','bg'=>'var(--gl)'],
                ['key'=>'foto_proses','label'=>'Bukti Proses','icon'=>'loader','color'=>'#d97706','bg'=>'#fffbeb'],
                ['key'=>'foto_selesai','label'=>'Bukti Selesai','icon'=>'check-circle','color'=>'var(--g)','bg'=>'var(--gl)'],
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
                <a href="../../uploads/pengaduan/<?=htmlspecialchars($p[$s['key']])?>" target="_blank" class="bukti-link" style="color:<?= $s['color'] ?>">
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
      <?php endforeach; ?>
    <?php endif; ?>
  </main>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>