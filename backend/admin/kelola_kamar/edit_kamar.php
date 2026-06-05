<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$tipe_target = $_GET["tipe"] ?? $_POST["tipe_hidden"] ?? null;
if (!$tipe_target) {
    header("Location: list_kamar.php");
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

// ─── 1. HANDLE ACTION (ADD/DELETE ROOM / DELETE PHOTO) ───
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];
    
    if ($action === "add_room") {
        $new_no = trim($_POST["new_nomor"] ?? "");
        if ($new_no !== "") {
            $stmt = $conn->prepare("SELECT * FROM kamar WHERE tipe = ? LIMIT 1");
            $stmt->execute([$tipe_target]);
            $base = $stmt->fetch();
            if ($base) {
                try {
                    $ins = $conn->prepare("INSERT INTO kamar (nomor_kamar, tipe, harga, fasilitas, deskripsi, foto, foto_2, foto_3, foto_4, foto_5, foto_denah, status) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'tersedia')");
                    $ins->execute([$new_no, $base['tipe'], $base['harga'], $base['fasilitas'], $base['deskripsi'], 
                                   $base['foto'], $base['foto_2'], $base['foto_3'], $base['foto_4'], $base['foto_5'], $base['foto_denah']]);
                } catch (PDOException $e) {
                    if ($e->errorInfo[1] == 1062) $_SESSION['error_msg'] = "Gagal menambah: Nomor kamar '$new_no' sudah ada!";
                }
            }
        }
    } elseif ($action === "delete_room") {
        $stmt = $conn->prepare("DELETE FROM kamar WHERE id = ? AND tipe = ?");
        $stmt->execute([$_POST["room_id"], $tipe_target]);
    } elseif ($action === "update_status") {
        $stmt = $conn->prepare("UPDATE kamar SET status = ? WHERE id = ? AND tipe = ?");
        $stmt->execute([$_POST["new_status"], $_POST["room_id"], $tipe_target]);
    } elseif ($action === "delete_gallery_photo") {
        $stmt = $conn->prepare("DELETE FROM galeri_kamar WHERE id = ? AND tipe = ?");
        $stmt->execute([$_POST["photo_id"], $tipe_target]);
    }
    header("Location: edit_kamar.php?tipe=" . urlencode($tipe_target));
    exit;
}

// ─── 2. HANDLE TYPE EDIT ───
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["action"])) {
    $new_tipe_name = trim($_POST["tipe_name"] ?? "");
    $harga = trim($_POST["harga"] ?? "");
    $fasilitas = trim($_POST["fasilitas"] ?? "");
    $deskripsi = trim($_POST["deskripsi"] ?? "");

    if ($new_tipe_name !== "" && $harga !== "") {
        $fotoQuery = "";
        $params = [$new_tipe_name, $harga, $fasilitas, $deskripsi];
        $uploadDir = __DIR__ . "/../../../frontend/assets/image/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        // Handle 6 Fixed Photo Slots
        $fileFields = [
            'foto' => 'foto', 'foto_2' => 'foto_2', 'foto_3' => 'foto_3', 
            'foto_4' => 'foto_4', 'foto_5' => 'foto_5', 'foto_denah' => 'foto_denah'
        ];
        foreach ($fileFields as $inputName => $dbCol) {
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $fileName = time() . "_" . $dbCol . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES[$inputName]['name']));
                if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadDir . $fileName)) {
                    $fotoQuery .= ", {$dbCol}=?";
                    $params[] = $fileName;
                }
            }
        }
        $params[] = $tipe_target;
        $stmt = $conn->prepare("UPDATE kamar SET tipe=?, harga=?, fasilitas=?, deskripsi=? {$fotoQuery} WHERE tipe=?");
        $stmt->execute($params);

        if ($new_tipe_name !== $tipe_target) {
            $updGal = $conn->prepare("UPDATE galeri_kamar SET tipe = ? WHERE tipe = ?");
            $updGal->execute([$new_tipe_name, $tipe_target]);
        }

        // Handle Additional Dynamic Photos
        if (isset($_FILES['extra_files'])) {
            $files = $_FILES['extra_files'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $fileName = time() . "_extra_" . $i . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($files['name'][$i]));
                    if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $fileName)) {
                        $insGal = $conn->prepare("INSERT INTO galeri_kamar (tipe, foto) VALUES (?, ?)");
                        $insGal->execute([$new_tipe_name, $fileName]);
                    }
                }
            }
        }
        
        $_SESSION['success_msg'] = "Perubahan berhasil disimpan!";
        header("Location: edit_kamar.php?tipe=" . urlencode($new_tipe_name));
        exit;
    }
}

// Fetch data
$stmt = $conn->prepare("SELECT * FROM kamar WHERE tipe = ? LIMIT 1");
$stmt->execute([$tipe_target]);
$tipe_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tipe_info) {
    $_SESSION['error_msg'] = "Tipe kamar tidak ditemukan!";
    header("Location: list_kamar.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, nomor_kamar, status FROM kamar WHERE tipe = ? ORDER BY CAST(nomor_kamar AS UNSIGNED) ASC, nomor_kamar ASC");
$stmt->execute([$tipe_target]);
$rooms_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM galeri_kamar WHERE tipe = ?");
$stmt->execute([$tipe_target]);
$gallery_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('renderImagePreview')) {
    function renderImagePreview(?string $fileName, string $label, string $inputName): string {
        $imgSrc = !empty($fileName) ? "../../../frontend/assets/image/{$fileName}" : "../../../frontend/assets/image/placeholder.jpg";
        return '
        <div class="col-md-4 mb-3">
            <label class="form-label">'.$label.'</label>
            <div style="border: 1px dashed #d1d5db; border-radius: 8px; padding: 10px; text-align: center; background-color: #f9fafb;">
                <img src="'.$imgSrc.'" alt="Preview" style="max-width: 100%; max-height: 90px; border-radius: 6px; margin-bottom: 8px; object-fit: cover;">
                <input type="file" name="'.$inputName.'" class="form-control form-control-sm" accept="image/*">
            </div>
        </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tipe & Kamar - Admin Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f6f8; }
        .edit-card { background: white; border-radius: 12px; padding: 30px; margin: 20px auto; max-width: 1000px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .form-label { font-weight: 500; color: #374151; font-size: 13px; }
        .btn-save { background-color: #11a654; color: white; border: none; border-radius: 8px; padding: 12px 20px; font-weight: 600; }
        .room-item { background: #f9fafb; border-radius: 10px; padding: 10px 15px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; border: 1px solid #e5e7eb; }
        .status-badge { font-size: 10px; padding: 3px 8px; border-radius: 20px; font-weight: 600; }
        .status-tersedia { background: #dcfce7; color: #166534; }
        .status-terisi { background: #fee2e2; color: #991b1b; }
        .gal-item { position: relative; width: 100%; aspect-ratio: 1; border-radius: 6px; overflow: hidden; border: 1px solid #ddd; }
        .gal-item img { width: 100%; height: 100%; object-fit: cover; }
        .gal-del { position: absolute; top: 2px; right: 2px; background: red; color: white; border: none; border-radius: 3px; font-size: 12px; padding: 0 5px; }
    </style>
</head>
<body>
    <div class="container py-4">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3"><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3"><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="d-flex align-items-center gap-3 mb-3">
            <a href="list_kamar.php" class="btn btn-sm btn-outline-secondary"><i data-lucide="arrow-left" style="width:16px;"></i> Kembali</a>
            <h4 class="mb-0">Kelola Tipe: <?= htmlspecialchars($tipe_info['tipe']) ?></h4>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="edit-card m-0">
                    <form action="edit_kamar.php?tipe=<?= urlencode($tipe_target) ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="tipe_hidden" value="<?= htmlspecialchars($tipe_info['tipe']) ?>">
                        
                        <h5 class="mb-3" style="font-weight:600; border-bottom:2px solid #eee; padding-bottom:8px;">Informasi Dasar</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="form-label">Nama Tipe</label><input type="text" name="tipe_name" class="form-control" value="<?= htmlspecialchars($tipe_info['tipe']) ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Harga (Rp)</label><input type="number" name="harga" class="form-control" value="<?= $tipe_info['harga'] ?>" required></div>
                            <div class="col-md-12"><label class="form-label">Fasilitas</label><input type="text" name="fasilitas" class="form-control" value="<?= htmlspecialchars($tipe_info['fasilitas']) ?>"></div>
                            <div class="col-md-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2"><?= htmlspecialchars($tipe_info['deskripsi'] ?? '') ?></textarea></div>
                        </div>

                        <h5 class="mb-3" style="font-weight:600; border-bottom:2px solid #eee; padding-bottom:8px;">Galeri Utama (6 Slot)</h5>
                        <div class="row g-2 mb-4">
                            <?= renderImagePreview($tipe_info['foto'] ?? '', 'Foto Utama', 'foto') ?>
                            <?= renderImagePreview($tipe_info['foto_2'] ?? '', 'Foto 2', 'foto_2') ?>
                            <?= renderImagePreview($tipe_info['foto_3'] ?? '', 'Foto 3', 'foto_3') ?>
                            <?= renderImagePreview($tipe_info['foto_4'] ?? '', 'Foto 4', 'foto_4') ?>
                            <?= renderImagePreview($tipe_info['foto_5'] ?? '', 'Foto 5', 'foto_5') ?>
                            <?= renderImagePreview($tipe_info['foto_denah'] ?? '', 'Denah', 'foto_denah') ?>
                        </div>

                        <h5 class="mb-3" style="font-weight:600; border-bottom:2px solid #eee; padding-bottom:8px;">Foto Tambahan (Opsional)</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label">Pilih Foto Tambahan (Bisa upload banyak sekaligus)</label>
                                <input type="file" name="extra_files[]" multiple class="form-control" accept="image/*">
                            </div>
                        </div>

                        <button type="submit" class="btn-save w-100 mb-4">Simpan Semua Perubahan</button>
                    </form>

                    <!-- List Foto Tambahan -->
                    <?php if (!empty($gallery_list)): ?>
                    <h6 class="mb-2">Daftar Foto Tambahan:</h6>
                    <div class="row g-2">
                        <?php foreach ($gallery_list as $gal): ?>
                        <div class="col-2">
                            <div class="gal-item">
                                <img src="../../../frontend/assets/image/<?= $gal['foto'] ?>">
                                <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_gallery_photo"><input type="hidden" name="photo_id" value="<?= $gal['id'] ?>"><input type="hidden" name="tipe_hidden" value="<?= htmlspecialchars($tipe_info['tipe']) ?>"><button type="submit" class="gal-del">&times;</button></form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="edit-card m-0">
                    <h5 class="mb-3" style="font-weight:600; border-bottom:2px solid #eee; padding-bottom:8px;">Manajemen Kamar</h5>
                    <form method="POST" class="mb-3"><input type="hidden" name="action" value="add_room"><input type="hidden" name="tipe_hidden" value="<?= htmlspecialchars($tipe_info['tipe']) ?>"><div class="input-group"><input type="text" name="new_nomor" class="form-control" placeholder="No. Baru" required><button type="submit" class="btn btn-primary btn-sm">Tambah</button></div></form>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($rooms_list as $r): ?>
                        <div class="room-item">
                            <div><span style="font-weight:700;">No. <?= htmlspecialchars($r['nomor_kamar']) ?></span> <span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></div>
                            <div class="d-flex align-items-center gap-3">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="tipe_hidden" value="<?= htmlspecialchars($tipe_info['tipe']) ?>">
                                    <input type="hidden" name="new_status" value="<?= $r['status'] === 'tersedia' ? 'terisi' : 'tersedia' ?>">
                                    <div class="form-check form-switch m-0" title="Ubah status Tersedia/Terisi" style="display: flex; align-items: center; padding-left: 2.5em;">
                                        <input class="form-check-input" type="checkbox" onchange="this.form.submit()" <?= strtolower($r['status']) === 'terisi' ? 'checked' : '' ?> style="cursor: pointer; width: 35px; height: 18px; margin-left: -2.5em; margin-top: 0;">
                                    </div>
                                </form>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete_room">
                                    <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="tipe_hidden" value="<?= htmlspecialchars($tipe_info['tipe']) ?>">
                                    <button type="submit" class="btn btn-sm text-danger p-0 m-0" onclick="return confirm('Yakin hapus kamar ini?')" title="Hapus">
                                        <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
    <script>lucide.createIcons();</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
