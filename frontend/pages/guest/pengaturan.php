<?php
if (!isset($_SESSION['user_id'])) {
    echo "header('Location: index.php?login_modal=1&msg=auth_required'); exit;";
    exit;
}

$userId = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'profil';
$message = "";
$messageType = "";

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profil'])) {
        $nama = trim($_POST['nama'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $email = $user['email']; // Email readonly based on previous logic

        // Foto upload logic
        $fotoProfil = $user['foto'] ?? null;
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../backend/uploads/profil/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
            $fileName = time() . "_profil_" . $userId . "." . $ext;
            if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $uploadDir . $fileName)) {
                // Delete old foto if exists
                if ($fotoProfil && file_exists($uploadDir . basename($fotoProfil))) {
                    @unlink($uploadDir . basename($fotoProfil));
                }
                $fotoProfil = $fileName;
            }
        }

        // Update basic info (address and ktp are omitted here to match the design simplicity, or we can just not touch them in DB)
        $updateStmt = $conn->prepare("UPDATE users SET nama = ?, no_hp = ?, foto = ? WHERE id = ?");
        if ($updateStmt->execute([$nama, $no_hp, $fotoProfil, $userId])) {
            $_SESSION['nama'] = $nama;
            $_SESSION['foto'] = $fotoProfil;
            $message = "Profil berhasil diperbarui!";
            $messageType = "success";
            
            // Refresh
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "Gagal memperbarui profil.";
            $messageType = "danger";
        }

    } elseif (isset($_POST['update_password'])) {
        $tab = 'password'; // Keep on password tab
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $user['password'])) {
            $message = "Kata sandi saat ini salah.";
            $messageType = "danger";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Konfirmasi kata sandi baru tidak cocok.";
            $messageType = "danger";
        } elseif (strlen($newPassword) < 8) {
            $message = "Kata sandi baru minimal 8 karakter.";
            $messageType = "danger";
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updatePass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($updatePass->execute([$hash, $userId])) {
                $message = "Kata sandi berhasil diperbarui!";
                $messageType = "success";
            } else {
                $message = "Gagal memperbarui kata sandi.";
                $messageType = "danger";
            }
        }
    }
}
?>

<style>
    .app-navbar { position: relative !important; background: #EEEADF !important; }
    .navbar-logo, .navbar-menu a, .login-link, .register-btn, .auth-separator { color: #1f2937 !important; }
    .nav-arrow { stroke: #1f2937 !important; }
    .mobile-toggle svg { stroke: #1f2937 !important; }
    .dropdown-menu { border: 1px solid #e5e7eb; }

    .page-container {
        max-width: 1000px;
        margin: 40px auto 80px;
        padding: 0 20px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 30px;
    }

    .settings-layout {
        display: flex;
        gap: 30px;
        align-items: flex-start;
    }

    .settings-sidebar {
        width: 250px;
        flex-shrink: 0;
    }

    .sidebar-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 10px 0;
        overflow: hidden;
    }

    .sidebar-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        color: #6b7280;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        border-left: 3px solid transparent;
        transition: all 0.2s;
    }

    .sidebar-item:hover {
        background: #f9fafb;
        color: #1f2937;
    }

    .sidebar-item.active {
        color: #1f2937;
        font-weight: 700;
        background: #f9fafb;
        border-left-color: #EEEADF; /* using the cream color for indicator */
    }

    .settings-content {
        flex: 1;
        min-width: 0;
    }

    .content-title {
        font-size: 18px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 24px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        height: 50px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        padding: 0 16px;
        font-family: inherit;
        font-size: 14px;
        color: #1f2937;
        outline: none;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: #9ca3af;
    }

    .form-control[readonly] {
        background: #f9fafb;
        color: #9ca3af;
    }

    .password-input-wrap {
        position: relative;
    }

    .password-input-wrap input {
        padding-right: 45px;
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-submit {
        background: #374151;
        color: white;
        height: 50px;
        border: none;
        border-radius: 10px;
        padding: 0 30px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        width: 100%;
        transition: background 0.2s;
    }

    .btn-submit:hover {
        background: #1f2937;
    }

    .avatar-edit-wrapper {
        position: relative;
        width: 120px;
        height: 120px;
        margin-bottom: 30px;
    }

    .avatar-img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #374151;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        font-weight: 700;
    }

    .avatar-edit-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 32px;
        height: 32px;
        background: #374151;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        border: 2px solid white;
    }

    .avatar-file-input {
        display: none;
    }

    .password-rules {
        margin-top: 30px;
        margin-bottom: 40px;
    }

    .rule-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #4b5563;
        margin-bottom: 10px;
    }

    .rule-item i {
        color: #d1d5db; /* Default gray check */
    }

    /* Simple alert */
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
    }
    .alert-success { background: #dcfce7; color: #166534; }
    .alert-danger { background: #fee2e2; color: #991b1b; }

    @media (max-width: 768px) {
        .settings-layout {
            flex-direction: column;
        }
        .settings-sidebar {
            width: 100%;
        }
    }
</style>

<div class="page-container">
    <h2 class="section-title">Pengaturan</h2>

    <div class="settings-layout">
        <div class="settings-sidebar">
            <div class="sidebar-card">
                <a href="?page=pengaturan&tab=profil" class="sidebar-item <?= $tab == 'profil' ? 'active' : '' ?>">
                    <i data-lucide="user" style="width: 18px; height: 18px;"></i> Ubah Profil
                </a>
                <a href="?page=pengaturan&tab=password" class="sidebar-item <?= $tab == 'password' ? 'active' : '' ?>">
                    <i data-lucide="lock" style="width: 18px; height: 18px;"></i> Ubah Kata Sandi
                </a>
            </div>
        </div>

        <div class="settings-content">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'profil'): ?>
                <div class="content-title">Ubah Profil</div>
                
                <form action="?page=pengaturan&tab=profil" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_profil" value="1">
                    
                    <div class="avatar-edit-wrapper">
                        <?php if (!empty($user['foto']) && file_exists(__DIR__ . '/../../../backend/uploads/profil/' . basename($user['foto']))): ?>
                            <img src="backend/uploads/profil/<?= htmlspecialchars(basename($user['foto'])) ?>" id="avatar-preview" class="avatar-img">
                        <?php else: ?>
                            <?php $firstName = explode(' ', trim($user['nama']))[0]; ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($firstName) ?>&background=374151&color=fff&bold=true&size=200" id="avatar-preview" class="avatar-img">
                        <?php endif; ?>
                        
                        <label for="foto_profil" class="avatar-edit-btn">
                            <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                        </label>
                        <input type="file" name="foto_profil" id="foto_profil" class="avatar-file-input" accept="image/*" onchange="previewImage(this)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">No Hp</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" placeholder="081234567890">
                    </div>

                    <div class="form-group" style="margin-bottom: 40px;">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>

                    <button type="submit" class="btn-submit">Simpan</button>
                </form>

                <script>
                    function previewImage(input) {
                        if (input.files && input.files[0]) {
                            var reader = new FileReader();
                            reader.onload = function(e) {
                                document.getElementById('avatar-preview').src = e.target.result;
                            }
                            reader.readAsDataURL(input.files[0]);
                        }
                    }
                </script>

            <?php elseif ($tab === 'password'): ?>
                <div class="content-title">Ubah Kata Sandi</div>

                <form action="?page=pengaturan&tab=password" method="POST">
                    <input type="hidden" name="update_password" value="1">

                    <div class="form-group">
                        <div class="password-input-wrap">
                            <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Kata Sandi Saat Ini" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)">
                                <span class="icon-eye"><i data-lucide="eye" style="width: 18px; height: 18px;"></i></span>
                                <span class="icon-eye-off" style="display: none;"><i data-lucide="eye-off" style="width: 18px; height: 18px;"></i></span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="password-input-wrap">
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Kata Sandi Baru" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                                <span class="icon-eye"><i data-lucide="eye" style="width: 18px; height: 18px;"></i></span>
                                <span class="icon-eye-off" style="display: none;"><i data-lucide="eye-off" style="width: 18px; height: 18px;"></i></span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="password-input-wrap">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Konfirmasi Kata Sandi Baru" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                <span class="icon-eye"><i data-lucide="eye" style="width: 18px; height: 18px;"></i></span>
                                <span class="icon-eye-off" style="display: none;"><i data-lucide="eye-off" style="width: 18px; height: 18px;"></i></span>
                            </button>
                        </div>
                    </div>

                    <div class="password-rules">
                        <div class="rule-item">
                            <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Minimal 8 Karakter
                        </div>
                        <div class="rule-item">
                            <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Mengandung huruf kapital (A-Z)
                        </div>
                        <div class="rule-item">
                            <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Mengandung huruf kecil (a-z)
                        </div>
                        <div class="rule-item">
                            <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Mengandung angka (0-9)
                        </div>
                        <div class="rule-item">
                            <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Mengandung karakter khusus (!@#$%^&*)
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Simpan</button>
                </form>

                <script>
                    function togglePassword(inputId, btn) {
                        const input = document.getElementById(inputId);
                        const iconEye = btn.querySelector('.icon-eye');
                        const iconEyeOff = btn.querySelector('.icon-eye-off');
                        
                        if (input.type === 'password') {
                            input.type = 'text';
                            iconEye.style.display = 'none';
                            iconEyeOff.style.display = 'inline-block';
                        } else {
                            input.type = 'password';
                            iconEye.style.display = 'inline-block';
                            iconEyeOff.style.display = 'none';
                        }
                    }

                    // Optional: dynamic check script (simplified)
                    const newPass = document.getElementById('new_password');
                    if (newPass) {
                        newPass.addEventListener('input', function() {
                            const val = this.value;
                            const rules = document.querySelectorAll('.rule-item i');
                            
                            // Min 8
                            rules[0].style.color = val.length >= 8 ? '#11a654' : '#d1d5db';
                            // Uppercase
                            rules[1].style.color = /[A-Z]/.test(val) ? '#11a654' : '#d1d5db';
                            // Lowercase
                            rules[2].style.color = /[a-z]/.test(val) ? '#11a654' : '#d1d5db';
                            // Number
                            rules[3].style.color = /[0-9]/.test(val) ? '#11a654' : '#d1d5db';
                            // Special char
                            rules[4].style.color = /[^A-Za-z0-9]/.test(val) ? '#11a654' : '#d1d5db';
                        });
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>
