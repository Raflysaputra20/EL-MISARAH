<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

$id = $_GET["id"] ?? null;

if (!$id) {
    die("ID penghuni tidak ditemukan");
}

$stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE id = ? AND role != 'admin'
");
$stmt->execute([$id]);
$penghuni = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$penghuni) {
    die("Data penghuni/user tidak ditemukan");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $no_hp = trim($_POST["no_hp"] ?? "");
    $alamat = trim($_POST["alamat"] ?? "");
    $status = $_POST["status"] ?? "aktif";

    if ($nama === "" || $email === "") {
        $message = "Nama dan email wajib diisi";
    } else {
        $update = $conn->prepare("
            UPDATE users 
            SET nama = ?, email = ?, no_hp = ?, alamat = ?, status = ?
            WHERE id = ? AND role != 'admin'
        ");

        $success = $update->execute([
            $nama,
            $email,
            $no_hp,
            $alamat,
            $status,
            $id
        ]);

        if ($success) {
            if ($penghuni['role'] === 'user') {
                header("Location: ../kelola_user/list_user.php?success=edit");
            } else {
                header("Location: list_penghuni.php?success=edit");
            }
            exit;
        } else {
            $message = "Gagal mengupdate profil";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Admin Kost Elmi Sarah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard-responsive.css">
    <style>
        :root { --admin-green: #11a654; --admin-bg: #f4f6f8; --admin-text: #1f2937; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--admin-bg); color: var(--admin-text); padding: 40px 20px; }
        .edit-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .header-flex { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; }
        .btn-back { display: flex; align-items: center; gap: 8px; color: #6b7280; text-decoration: none; font-size: 14px; font-weight: 500; }
        .btn-back:hover { color: var(--admin-text); }
        .form-label { font-weight: 600; font-size: 13px; color: #374151; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 15px; font-size: 14px; }
        .form-control:focus { border-color: var(--admin-green); box-shadow: 0 0 0 3px rgba(17, 166, 84, 0.1); }
        .btn-save { background-color: var(--admin-green); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; width: 100%; margin-top: 20px; transition: 0.3s; }
        .btn-save:hover { background-color: #0d8a45; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="edit-container">
    <div class="header-flex">
        <h4 style="margin:0; font-weight:700;">Edit Profil</h4>
        <a href="<?php echo $penghuni['role'] === 'user' ? '../kelola_user/list_user.php' : 'list_penghuni.php'; ?>" class="btn-back">
            <i data-lucide="arrow-left" style="width:16px; height:16px;"></i> Kembali
        </a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-danger" style="font-size:13px;"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control" name="nama" value="<?php echo htmlspecialchars($penghuni["nama"]); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($penghuni["email"]); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">No Handphone</label>
            <input type="text" class="form-control" name="no_hp" value="<?php echo htmlspecialchars($penghuni["no_hp"] ?? ""); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea class="form-control" name="alamat" rows="3"><?php echo htmlspecialchars($penghuni["alamat"] ?? ""); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Status Akun</label>
            <select class="form-select" name="status">
                <option value="aktif" <?php if ($penghuni["status"] === "aktif") echo "selected"; ?>>Aktif</option>
                <option value="nonaktif" <?php if ($penghuni["status"] === "nonaktif") echo "selected"; ?>>Nonaktif</option>
            </select>
        </div>

        <button type="submit" class="btn-save">Simpan Perubahan</button>
    </form>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../../assets/js/sidebar-toggle.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
