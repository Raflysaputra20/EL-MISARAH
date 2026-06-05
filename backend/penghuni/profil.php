<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . '/init.php';
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penghuni") { header("Location: ../api/auth/login.php"); exit; }
$userId = $_SESSION["user_id"];
$namaUser = $_SESSION["nama"] ?? "Penghuni";
$success = $error = '';
$activeTab = $_GET['tab'] ?? 'view';

$user = [];
try { $s=$conn->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$userId]); $user=$s->fetch(PDO::FETCH_ASSOC); } catch(Exception $e){}

$kamarInfo = null;
try { $s=$conn->prepare("SELECT k.nomor_kamar,b.tanggal_masuk,b.status FROM booking b JOIN kamar k ON b.kamar_id=k.id WHERE b.user_id=? AND b.status IN('disetujui','aktif') ORDER BY b.id DESC LIMIT 1"); $s->execute([$userId]); $kamarInfo=$s->fetch(PDO::FETCH_ASSOC); } catch(Exception $e){}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['tab'])) {
    if ($_POST['tab']==='info') {
        $nd=trim($_POST['nama_depan']??''); $nb=trim($_POST['nama_belakang']??'');
        $hp=trim($_POST['no_hp']??''); $al=trim($_POST['alamat']??'');
        $tl=trim($_POST['tanggal_lahir']??'');
        $nama=trim("$nd $nb");
        $foto=$user['foto']??null;
        if (!empty($_FILES['foto']['name'])) {
            $ext=strtolower(pathinfo($_FILES['foto']['name'],PATHINFO_EXTENSION));
            if (in_array($ext,['jpg','jpeg','png','webp'])) {
                $dir=__DIR__.'/../../uploads/profil/';
                if(!is_dir($dir)) mkdir($dir,0755,true);
                $fn='user_'.$userId.'_'.time().'.'.$ext;
                if(move_uploaded_file($_FILES['foto']['tmp_name'],$dir.$fn)) $foto=$fn;
            }
        }
        $pekerjaan = trim($_POST['pekerjaan'] ?? '');
        try {
            $upd=$conn->prepare("UPDATE users SET nama=?,no_hp=?,alamat=?,foto=?,tanggal_lahir=?,pekerjaan=? WHERE id=?");
            $upd->execute([$nama,$hp,$al,$foto,$tl,$pekerjaan,$userId]);
            $_SESSION['nama']=$nama; $namaUser=$nama;
            $user=array_merge($user,['nama'=>$nama,'no_hp'=>$hp,'alamat'=>$al,'foto'=>$foto,'tanggal_lahir'=>$tl,'pekerjaan'=>$pekerjaan]);
            $success='Profil berhasil diperbarui!'; $activeTab='info';
        } catch(Exception $e){ $error='Gagal: '.$e->getMessage(); $activeTab='info'; }
    } elseif ($_POST['tab']==='password') {
        $old=$_POST['old_password']??''; $new=$_POST['new_password']??''; $conf=$_POST['confirm_password']??'';
        if (!password_verify($old,$user['password']??'')) $error='Kata sandi lama salah.';
        elseif (strlen($new)<8) $error='Min 8 karakter.';
        elseif ($new!==$conf) $error='Konfirmasi tidak cocok.';
        else { try { $conn->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$userId]); $success='Kata sandi diperbarui!'; } catch(Exception $e){ $error='Gagal.'; } }
        $activeTab='password';
    }
}

$namaParts=explode(' ',$user['nama']??$namaUser,2);
$namaDepan=$namaParts[0]??''; $namaBelakang=$namaParts[1]??'';
$foto=$user['foto']??null;
$tglLahir=$user['tanggal_lahir']??null;
$tglLahirFmt=$tglLahir?date('j F Y',strtotime($tglLahir)):'—';
?><!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profil Saya - Kost Elmi Sarah</title>
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
/* VIEW MODE */
.prof-card{background:#fff;border-radius:18px;box-shadow:0 2px 16px rgba(0,0,0,.06);padding:30px 36px;margin-bottom:20px;display:flex;align-items:center;gap:28px;position:relative}
.prof-photo{width:120px;height:120px;border-radius:50%;flex-shrink:0;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.1)}
.prof-photo img{width:100%;height:100%;object-fit:cover}
.prof-info{flex:1}
.prof-name{font-size:22px;font-weight:800;color:var(--dk);margin-bottom:4px;letter-spacing:-.3px}
.prof-dob{font-size:13px;color:var(--gr);margin-bottom:16px}
.prof-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px 32px}
.prof-field{display:flex;align-items:center;gap:9px;font-size:13.5px;color:var(--dk)}
.btn-edit{position:absolute;top:24px;right:28px;display:inline-flex;align-items:center;gap:7px;border:1.5px solid var(--bd);border-radius:10px;padding:8px 18px;font-size:13px;font-weight:600;color:var(--dk);background:#fff;text-decoration:none;transition:all .2s;cursor:pointer}
.btn-edit:hover{border-color:var(--g);color:var(--g);background:var(--gl)}
.bottom-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.info-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);padding:22px 24px}
.ic-header{display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--bd)}
.ic-icon{width:44px;height:44px;border-radius:12px;background:var(--gl);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ic-title{font-size:15px;font-weight:700;color:var(--dk)}
.ic-sub{font-size:12px;color:var(--gr);margin-top:2px}
.kontak-name{font-size:14px;font-weight:700;color:var(--dk);margin-bottom:4px}
.kontak-rel{font-size:12px;color:var(--gr);margin-bottom:10px}
.kontak-hp{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--dk)}
.doc-chip{display:flex;align-items:center;justify-content:space-between;border:1px solid var(--bd);border-radius:10px;padding:10px 14px}
.doc-chip-name{font-size:13px;font-weight:600;color:var(--dk)}
.doc-chip-status{font-size:11px;color:var(--g);margin-top:2px}
/* EDIT MODE */
.edit-grid{display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start}

@media (max-width: 1024px) {
  .main{margin-left:0}
  .topbar{padding:0 16px;height:60px}
  .content{padding:16px}
  .prof-card{padding:22px}
  .bottom-grid,.edit-grid{grid-template-columns:1fr}
}

@media (max-width: 768px) {
  .prof-card{flex-direction:column;align-items:flex-start;gap:16px;padding:18px}
  .prof-photo{width:96px;height:96px}
  .prof-fields{grid-template-columns:1fr}
  .btn-edit{position:static;margin-top:8px}
  .edit-grid{grid-template-columns:1fr}
  .edit-left,.edit-right{padding:18px}
  .form-row{grid-template-columns:1fr}
  .topbar h2{font-size:16px}
  .topbar > div:last-child > div:last-child{display:none}
}
.edit-left{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);padding:28px 24px;text-align:center}
.edit-photo-wrap{position:relative;width:100px;height:100px;margin:0 auto 16px}
.edit-photo{width:100px;height:100px;border-radius:50%;object-fit:cover;background:linear-gradient(135deg,var(--g),#0d8e47);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:700;color:#fff;overflow:hidden}
.edit-photo img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.edit-photo-btn{position:absolute;bottom:2px;right:2px;width:28px;height:28px;background:var(--g);border-radius:50%;border:2px solid #fff;display:flex;align-items:center;justify-content:center;cursor:pointer}
.edit-uname{font-size:16px;font-weight:700;color:var(--dk);margin-bottom:2px}
.edit-urole{font-size:12px;color:var(--gr);margin-bottom:20px}
.tab-btn{width:100%;padding:11px 18px;border-radius:10px;border:none;background:none;font-family:'Poppins',sans-serif;font-size:13px;font-weight:500;color:var(--dk);display:flex;align-items:center;gap:10px;cursor:pointer;transition:all .2s;margin-bottom:4px}
.tab-btn:hover{background:var(--gl);color:var(--g)}
.tab-btn.on{background:var(--g);color:#fff;font-weight:600}
.edit-right{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);padding:30px 32px}
.form-title{font-size:16px;font-weight:700;color:var(--dk);margin-bottom:22px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.fg{margin-bottom:16px}
.fl{font-size:13px;font-weight:500;color:var(--gr);margin-bottom:6px;display:block}
.fi{width:100%;background:#f3f5f7;border:none;border-radius:10px;padding:12px 16px;font-size:13.5px;font-family:'Poppins',sans-serif;color:var(--dk);outline:none;transition:.2s}
.fi:focus{background:#eaf7f0;box-shadow:0 0 0 2px rgba(17,166,84,.15)}
.fi:read-only{background:#f9fafb;color:var(--gr);cursor:default}
.fi::placeholder{color:#c0c4cc}
.btn-save{width:100%;background:var(--g);color:#fff;border:none;border-radius:10px;padding:14px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;transition:.2s;margin-top:8px}
.btn-save:hover{background:#0d8e47}
.pw-hint{font-size:11.5px;color:#9ca3af;display:flex;align-items:center;gap:6px;margin-bottom:6px}
.alert{border-radius:10px;padding:11px 16px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.alert.ok{background:var(--gl);color:var(--g);border-left:3px solid var(--g)}
.alert.err{background:#fee2e2;color:#ef4444;border-left:3px solid #ef4444}
</style></head><body>
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
        <li class="sidebar-item"><a href="ulasan.php" class="sidebar-link"><i data-lucide="star" class="sidebar-icon"></i> Ulasan</a></li>
        <li class="sidebar-item"><a href="profil.php" class="sidebar-link active"><i data-lucide="user" class="sidebar-icon"></i> Profil Saya</a></li>
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
      <h2 class="tp-title"><?= $activeTab==='view'?'Profil Saya':'Edit Profil' ?></h2>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <div class="av"><?php if($foto):?><img src="../uploads/profil/<?=htmlspecialchars(basename($foto))?>" alt=""><?php else:?><?=strtoupper(substr($namaUser,0,1))?><?php endif;?></div>
      <div><div class="u-name"><?=htmlspecialchars($namaUser)?></div><div class="u-role">Penghuni</div></div>
    </div>
  </header>
  <main class="content">

<?php if($activeTab==='view'): ?>
  <!-- PROFILE VIEW -->
  <div class="prof-card">
    <div class="prof-photo">
      <?php if($foto):?><img src="../uploads/profil/<?=htmlspecialchars(basename($foto))?>" alt=""><?php else:?>
      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--g),#0d8e47);border-radius:50%;font-size:42px;font-weight:800;color:#fff"><?=strtoupper(substr($namaUser,0,1))?></div>
      <?php endif;?>
    </div>
    <div class="prof-info">
      <div class="prof-name"><?=htmlspecialchars($user['nama']??$namaUser)?></div>
      <?php if($tglLahir): ?><div class="prof-dob"><?=$tglLahirFmt?></div><?php endif; ?>
      <div class="prof-fields">
        <?php if(!empty($user['no_hp'])): ?>
        <div class="prof-field"><i data-lucide="phone" style="width:15px;height:15px;color:var(--gr)"></i> <?=htmlspecialchars($user['no_hp'])?></div>
        <?php endif; ?>
        <?php if(!empty($user['email'])): ?>
        <div class="prof-field"><i data-lucide="mail" style="width:15px;height:15px;color:var(--gr)"></i> <?=htmlspecialchars($user['email'])?></div>
        <?php endif; ?>
        <?php if(!empty($user['alamat'])): ?>
        <div class="prof-field"><i data-lucide="map-pin" style="width:15px;height:15px;color:var(--gr)"></i> <?=htmlspecialchars($user['alamat'])?></div>
        <?php endif; ?>
        <div class="prof-field"><i data-lucide="circle-dashed" style="width:15px;height:15px;color:var(--gr)"></i> <?=htmlspecialchars($user['pekerjaan']??'Penghuni')?></div>
      </div>
    </div>
    <a href="?tab=info" class="btn-edit"><i data-lucide="pen-square" style="width:15px;height:15px"></i> Edit Profil</a>
  </div>

  <div class="bottom-grid">
    <!-- Kontak Darurat Kost Elmi Sarah -->
    <div class="info-card">
      <div class="ic-header">
        <div class="ic-icon"><i data-lucide="phone-call" style="width:22px;height:22px;color:var(--g)"></i></div>
        <div><div class="ic-title">Kontak Darurat</div><div class="ic-sub">Dihubungi saat keadaan darurat</div></div>
      </div>
      <?php
        // Coba ambil kontak admin/pemilik dari DB
        $kontakKost = null;
        try {
            $sk = $conn->query("SELECT nama, no_hp FROM users WHERE role IN ('admin','owner','pemilik') ORDER BY id ASC LIMIT 1");
            $kontakKost = $sk->fetch(PDO::FETCH_ASSOC);
        } catch(Exception $e) {}
      ?>
      <div class="kontak-name"><?= htmlspecialchars($kontakKost['nama'] ?? 'Ibu Elmi Sarah') ?></div>
      <div class="kontak-rel" style="font-size:12px;color:var(--gr);margin-bottom:10px;">Pemilik Kost</div>
      <div class="kontak-hp">
        <i data-lucide="phone" style="width:14px;height:14px;color:var(--gr)"></i>
        <?= htmlspecialchars($kontakKost['no_hp'] ?? '0812-3456-7890') ?>
      </div>
    </div>

    <!-- Dokumen Saya -->
    <div class="info-card">
      <div class="ic-header">
        <div class="ic-icon"><i data-lucide="file-text" style="width:22px;height:22px;color:var(--g)"></i></div>
        <div><div class="ic-title">Dokumen Saya</div><div class="ic-sub">Dokumen penghuni</div></div>
      </div>
      <div class="doc-chip">
        <div><div class="doc-chip-name">KTP</div><div class="doc-chip-status">Terverifikasi</div></div>
        <i data-lucide="download" style="width:16px;height:16px;color:var(--g)"></i>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- EDIT MODE -->
  <?php if($success):?><div class="alert ok"><i data-lucide="check-circle" style="width:15px;height:15px;flex-shrink:0"></i><?=htmlspecialchars($success)?></div><?php endif;?>
  <?php if($error):?><div class="alert err"><i data-lucide="alert-circle" style="width:15px;height:15px;flex-shrink:0"></i><?=htmlspecialchars($error)?></div><?php endif;?>
  <div class="edit-grid">
    <div class="edit-left">
      <form method="POST" enctype="multipart/form-data" id="fotoForm"><input type="hidden" name="tab" value="info">
        <div class="edit-photo-wrap">
          <div class="edit-photo" id="fotoPreview">
            <?php if($foto):?><img src="../uploads/profil/<?=htmlspecialchars(basename($foto))?>" id="fotoImg" alt=""><?php else:?><?=strtoupper(substr($namaUser,0,1))?><?php endif;?>
          </div>
          <label class="edit-photo-btn" for="fotoInput"><i data-lucide="pencil" style="width:13px;height:13px;color:#fff"></i></label>
          <input type="file" id="fotoInput" name="foto" accept="image/*" style="display:none" onchange="previewFoto(this)">
        </div>
      </form>
      <div class="edit-uname"><?=htmlspecialchars($user['nama']??$namaUser)?></div>
      <div class="edit-urole">Penghuni</div>
      <button class="tab-btn <?=$activeTab==='info'?'on':''?>" onclick="location.href='?tab=info'">
        <i data-lucide="user" style="width:15px;height:15px"></i> Informasi Pribadi
      </button>
      <button class="tab-btn <?=$activeTab==='password'?'on':''?>" onclick="location.href='?tab=password'">
        <i data-lucide="lock" style="width:15px;height:15px"></i> Kata Sandi
      </button>
    </div>
    <div class="edit-right">
      <?php if($activeTab==='info'): ?>
        <div class="form-title">Informasi Pribadi</div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="tab" value="info">
          <div class="form-row">
            <div class="fg"><label class="fl">Nama Depan</label><input type="text" name="nama_depan" class="fi" value="<?=htmlspecialchars($namaDepan)?>" placeholder="Nama depan"></div>
            <div class="fg"><label class="fl">Nama Belakang</label><input type="text" name="nama_belakang" class="fi" value="<?=htmlspecialchars($namaBelakang)?>" placeholder="Nama belakang"></div>
          </div>
          <div class="fg"><label class="fl">Email</label><input type="email" class="fi" value="<?=htmlspecialchars($user['email']??'')?>" readonly></div>
          <div class="fg"><label class="fl">No Hp</label><input type="text" name="no_hp" class="fi" value="<?=htmlspecialchars($user['no_hp']??'')?>" placeholder="Contoh: 0812-3456-7890"></div>
          <div class="fg"><label class="fl">Alamat</label><input type="text" name="alamat" class="fi" value="<?=htmlspecialchars($user['alamat']??'')?>" placeholder="Kota, Provinsi"></div>
          <div class="form-row">
            <div class="fg"><label class="fl">Tanggal Lahir</label><input type="date" name="tanggal_lahir" class="fi" value="<?=htmlspecialchars($user['tanggal_lahir']??'')?>"></div>
            <div class="fg"><label class="fl">Pekerjaan</label><input type="text" name="pekerjaan" class="fi" value="<?=htmlspecialchars($user['pekerjaan']??'')?>" placeholder="Mahasiswa, Karyawan..."></div>
          </div>
          <button type="submit" class="btn-save">Simpan</button>
        </form>
      <?php else: ?>
        <div class="form-title">Ubah Kata Sandi</div>
        <form method="POST">
          <input type="hidden" name="tab" value="password">
          <div class="fg"><input type="password" name="old_password" id="op" class="fi" placeholder="Kata Sandi Saat Ini" style="padding-right:44px;position:relative"><span onclick="togglePw('op',this)" style="position:relative;float:right;margin-top:-34px;margin-right:14px;cursor:pointer;color:var(--gr)"><i data-lucide="eye" style="width:16px;height:16px"></i></span></div>
          <div class="fg"><input type="password" name="new_password" id="np" class="fi" placeholder="Kata Sandi Baru"><span onclick="togglePw('np',this)" style="position:relative;float:right;margin-top:-34px;margin-right:14px;cursor:pointer;color:var(--gr)"><i data-lucide="eye" style="width:16px;height:16px"></i></span></div>
          <div class="fg"><input type="password" name="confirm_password" id="cp" class="fi" placeholder="Konfirmasi Kata Sandi Baru"><span onclick="togglePw('cp',this)" style="position:relative;float:right;margin-top:-34px;margin-right:14px;cursor:pointer;color:var(--gr)"><i data-lucide="eye" style="width:16px;height:16px"></i></span></div>
          <div style="margin:12px 0 16px">
            <div class="pw-hint"><i data-lucide="circle" style="width:12px;height:12px"></i> Minimal 8 Karakter</div>
            <div class="pw-hint"><i data-lucide="circle" style="width:12px;height:12px"></i> Mengandung huruf kapital (A-Z)</div>
            <div class="pw-hint"><i data-lucide="circle" style="width:12px;height:12px"></i> Mengandung huruf kecil (a-z)</div>
            <div class="pw-hint"><i data-lucide="circle" style="width:12px;height:12px"></i> Mengandung angka (0-9)</div>
            <div class="pw-hint"><i data-lucide="circle" style="width:12px;height:12px"></i> Mengandung karakter khusus (!@#$%^&*)</div>
          </div>
          <button type="submit" class="btn-save">Simpan</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

  </main>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/sidebar-toggle.js"></script>
<script>
lucide.createIcons();
function previewFoto(input) {
    if (!input.files||!input.files[0]) return;
    const r=new FileReader();
    r.onload=e=>{document.getElementById('fotoPreview').innerHTML=`<img src="${e.target.result}" alt="" id="fotoImg">`;input.closest('form').submit();};
    r.readAsDataURL(input.files[0]);
}
function togglePw(id,btn) {
    const inp=document.getElementById(id);
    inp.type=inp.type==='password'?'text':'password';
    btn.innerHTML=inp.type==='text'?'<i data-lucide="eye-off" style="width:16px;height:16px"></i>':'<i data-lucide="eye" style="width:16px;height:16px"></i>';
    lucide.createIcons();
}
</script>
</body></html>
