<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id = $_GET["id"] ?? null;
if (!$id) {
    header("Location: list_penghuni.php");
    exit;
}

// Fetch detail
$stmt = $conn->prepare("
    SELECT u.*, k.nomor_kamar as no_kamar, k.tipe, k.harga
    FROM users u
    LEFT JOIN booking b ON u.id = b.user_id AND b.status IN ('aktif', 'disetujui', 'selesai')
    LEFT JOIN kamar k ON b.kamar_id = k.id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    die("Data tidak ditemukan");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Penghuni - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css?v=1.2">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f6f8; padding: 40px 20px; }
        .detail-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .detail-header { background-color: #11a654; color: white; padding: 30px; position: relative; }
        .back-btn { color: white; text-decoration: none; font-size: 14px; opacity: 0.8; transition: 0.3s; }
        .back-btn:hover { opacity: 1; }
        .detail-body { padding: 40px; }
        .info-group { margin-bottom: 25px; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px; }
        .info-label { font-size: 12px; color: #9ca3af; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
        .info-value { font-size: 16px; font-weight: 500; color: #1f2937; }
        .room-badge { background-color: #e8f7f0; color: #11a654; padding: 5px 15px; border-radius: 20px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>

<div class="detail-container">
    <div class="detail-header">
        <a href="<?php echo $p['role'] === 'user' ? '../kelola_user/list_user.php' : 'list_penghuni.php'; ?>" class="back-btn mb-3 d-inline-block">← Kembali ke Daftar</a>
        <h2 style="margin:0; font-weight:700;"><?= htmlspecialchars($p['nama']) ?></h2>
        <p style="margin:5px 0 0 0; opacity:0.9; font-size:14px;"><?= htmlspecialchars($p['email']) ?></p>
    </div>
    
    <div class="detail-body">
        <div class="row">
            <div class="col-md-6">
                <div class="info-group">
                    <div class="info-label">Nomor Telepon</div>
                    <div class="info-value"><?= htmlspecialchars($p['no_hp'] ?? '-') ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">NIK / KTP</div>
                    <div class="info-value"><?= htmlspecialchars($p['no_ktp'] ?? '-') ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Alamat Asal</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($p['alamat'] ?? '-')) ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-group">
                    <div class="info-label">Kamar Saat Ini</div>
                    <div class="info-value">
                        <?php if ($p['no_kamar']): ?>
                            <span class="room-badge">Kamar <?= htmlspecialchars($p['no_kamar']) ?> (<?= htmlspecialchars($p['tipe']) ?>)</span>
                        <?php else: ?>
                            <span class="text-muted">Belum Menghuni</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">Status Akun</div>
                    <div class="info-value text-capitalize"><?= htmlspecialchars($p['status'] ?? 'aktif') ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Bergabung Sejak</div>
                    <div class="info-value"><?= date('d F Y', strtotime($p['created_at'])) ?></div>
                </div>
            </div>
        </div>

        <?php if ($p['foto_ktp']): 
            $pathKtp = "../../uploads/profil/" . $p['foto_ktp'];
            if (!file_exists(__DIR__ . "/../../uploads/profil/" . $p['foto_ktp'])) {
                $pathKtp = "../../../frontend/assets/image/" . $p['foto_ktp'];
            }
        ?>
            <div class="mt-4">
                <div class="info-label">Foto KTP</div>
                <img src="<?= $pathKtp ?>" class="img-fluid rounded-3 border mt-2" style="max-height: 300px; display: block;">
                <a href="<?= $pathKtp ?>" target="_blank" class="btn btn-sm btn-outline-success mt-2" style="font-size: 11px;">Buka Gambar Penuh</a>
            </div>
        <?php endif; ?>
    </div>
</div>

    <script src="../../assets/js/sidebar-toggle.js"></script>
</body>
</html>
