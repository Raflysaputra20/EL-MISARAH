<?php
require_once "../../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    header("Location: ../../../index.php?register_modal=1");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_depan = trim($_POST["nama_depan"] ?? "");
    $nama_belakang = trim($_POST["nama_belakang"] ?? "");
    $nama = trim($nama_depan . " " . $nama_belakang);

    $email = trim($_POST["email"] ?? "");
    $no_hp = trim($_POST["no_hp"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $confirm_password = trim($_POST["confirm_password"] ?? "");
    $alamat = "-";

    if ($nama_depan === "" || $email === "" || $no_hp === "" || $password === "" || $confirm_password === "") {
        $message = "Semua field wajib diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid";
    } elseif (strlen($password) < 8) {
        $message = "Kata sandi minimal harus 8 karakter";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $message = "Kata sandi harus mengandung kombinasi huruf besar, huruf kecil, angka, dan simbol";
    } elseif ($password !== $confirm_password) {
        $message = "Konfirmasi password tidak sesuai";
    } elseif (!isset($_POST["terms"])) {
        $message = "Anda harus menyetujui syarat dan ketentuan";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = "Email sudah terdaftar";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (nama, email, password, no_hp, role, alamat, status)
                VALUES (?, ?, ?, ?, 'user', ?, 'aktif')
            ");

            $result = $stmt->execute([$nama, $email, $hash, $no_hp, $alamat]);

            if ($result) {
                if (isset($_POST['is_ajax'])) {
                    echo json_encode(["status" => "success", "redirect" => "index.php?login_modal=1&msg=register_success"]);
                    exit;
                }
                header("Location: login.php?success=1");
                exit;
            } else {
                $message = "Register gagal";
            }
        }
    }
    
    if (isset($_POST['is_ajax'])) {
        echo json_encode(["status" => "error", "message" => $message]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Kost Elmi Sarah</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #2C3E50;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .register-wrapper {
            width: min(1270px, 100%);
            min-height: 845px;
            background: #EBE7DE;
            border-radius: 16px;
            padding: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
        }

        .register-visual {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background-image: linear-gradient(rgba(0,0,0,0.32), rgba(0,0,0,0.32)), url("../../../frontend/assets/image/register.png");
            background-size: cover;
            background-position: center;
            min-height: 817px;
        }

        .visual-brand {
            position: absolute;
            top: 42px;
            left: 36px;
            color: #fff;
            font-size: 25px;
            font-weight: 700;
        }

        .visual-pill {
            position: absolute;
            top: 35px;
            right: 22px;
            width: 169px;
            height: 40px;
            background: rgba(255,255,255,0.75);
            border-radius: 21px;
        }

        .visual-caption {
            position: absolute;
            left: 50%;
            bottom: 78px;
            transform: translateX(-50%);
            width: 420px;
            max-width: 90%;
            color: #fff;
            text-align: center;
            font-size: 30px;
            line-height: 1.5;
            font-weight: 400;
        }

        .visual-dots {
            position: absolute;
            left: 50%;
            bottom: 34px;
            transform: translateX(-50%);
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .visual-dots span {
            width: 50px;
            height: 5px;
            background: #525252;
            border-radius: 16px;
        }

        .visual-dots span.active {
            width: 60px;
            background: #fff;
        }

        .register-content {
            padding: 52px 12px 40px 0;
            display: flex;
            align-items: center;
        }

        .register-box {
            width: 100%;
            max-width: 575px;
            margin: 0 auto;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 50px;
            line-height: 1.2;
            font-weight: 800;
            color: #000;
        }

        .login-text {
            margin: 0 0 44px;
            font-size: 15px;
            color: #000;
        }

        .login-text a {
            color: #2F80FF;
            text-decoration: none;
        }

        .message {
            background: #ffe3e3;
            color: #c1121f;
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 26px 13px;
        }

        .form-control {
            width: 100%;
            height: 58px;
            border: 1px solid #D9D9D9;
            border-radius: 9px;
            background: #fff;
            padding: 0 21px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            outline: none;
        }

        .form-control::placeholder {
            color: #D9D9D9;
        }

        .form-control.full {
            grid-column: span 2;
        }

        .terms {
            margin: 24px 0 28px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            color: #000;
        }

        .terms input {
            width: 22px;
            height: 22px;
            accent-color: #1E1E1E;
        }

        .terms a {
            color: #2F80FF;
            text-decoration: none;
        }

        .submit-btn {
            width: 100%;
            height: 58px;
            border: none;
            border-radius: 9px;
            background: #2C3E50;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            cursor: pointer;
        }

        .divider {
            margin: 27px 0 22px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 26px;
            color: #525252;
            font-size: 15px;
        }

        .divider::before,
        .divider::after {
            content: "";
            height: 1px;
            background: #000;
        }

        .social-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 13px;
        }

        .social-btn {
            height: 58px;
            border: 1px solid #D9D9D9;
            border-radius: 9px;
            background: rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            color: #000;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            font-size: 20px;
        }

        .google-icon {
    width: 29px;
    height: 29px;
    object-fit: contain;
    filter: drop-shadow(0px 4px 4px rgba(0, 0, 0, 0.25));
}

        .facebook-icon {
            width: 29px;
            height: 29px;
            background: #1877F2;
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-family: Arial, sans-serif;
        }

        @media (max-width: 992px) {
            body {
                padding: 20px;
            }

            .register-wrapper {
                grid-template-columns: 1fr;
            }

            .register-visual {
                min-height: 320px;
            }

            .register-content {
                padding: 30px 10px;
            }

            h1 {
                font-size: 38px;
            }
        }

        @media (max-width: 576px) {
            .form-grid,
            .social-row {
                grid-template-columns: 1fr;
            }

            .form-control.full {
                grid-column: span 1;
            }

            .visual-caption {
                font-size: 22px;

        /* Terms Modal Overlay Styles */
        .login-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            padding: 20px;
        }
        .login-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .login-modal {
            position: relative;
            background: #fff;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            width: min(600px, 100%);
            animation: modalFadeIn 0.3s ease forwards;
        }
        @keyframes modalFadeIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .login-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            transition: color 0.2s;
        }
        .login-close:hover {
            color: #333;
        }
    </style>
</head>
<body>

<div class="register-wrapper">
    <div class="register-visual">
        <div class="visual-brand">Elmi Sarah</div>
        <div class="visual-pill"></div>

        <div class="visual-caption">
            Hunian Nyaman untuk<br>
            Tinggal dan Beraktivitas
        </div>

        <div class="visual-dots">
            <span></span>
            <span></span>
            <span class="active"></span>
        </div>
    </div>

    <div class="register-content">
        <div class="register-box">
            <h1>Daftar Akun</h1>

            <p class="login-text">
                Sudah punya akun? <a href="login.php">Masuk</a>
            </p>

            <?php if ($message !== ""): ?>
                <div class="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <input class="form-control" type="text" name="nama_depan" placeholder="Nama Depan" value="<?php echo htmlspecialchars($_POST['nama_depan'] ?? ''); ?>">
                    <input class="form-control" type="text" name="nama_belakang" placeholder="Nama Belakang" value="<?php echo htmlspecialchars($_POST['nama_belakang'] ?? ''); ?>">

                    <input class="form-control full" type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <input class="form-control full" type="text" name="no_hp" placeholder="No Hp" value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>">

                    <input class="form-control" type="password" name="password" id="regPasswordStandalone" placeholder="Password">
                    <input class="form-control" type="password" name="confirm_password" placeholder="Konfirmasi Password">
                    <div class="pw-checker-container" style="grid-column: span 2; margin-top: -10px; background: rgba(0,0,0,0.02); padding: 8px 12px; border-radius: 8px; border: 1px solid #D9D9D9; text-align: left;">
                        <div style="font-size: 12px; font-weight: 600; color: #000; margin-bottom: 6px;">Kriteria Kata Sandi:</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 12px;">
                            <div class="pw-rule-sa" id="rule-sa-length" style="font-size: 11px; color: #525252; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Min. 8 karakter
                            </div>
                            <div class="pw-rule-sa" id="rule-sa-upper" style="font-size: 11px; color: #525252; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Huruf besar (A-Z)
                            </div>
                            <div class="pw-rule-sa" id="rule-sa-lower" style="font-size: 11px; color: #525252; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Huruf kecil (a-z)
                            </div>
                            <div class="pw-rule-sa" id="rule-sa-number" style="font-size: 11px; color: #525252; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Angka (0-9)
                            </div>
                            <div class="pw-rule-sa" id="rule-sa-symbol" style="font-size: 11px; color: #525252; display: flex; align-items: center; gap: 6px; transition: all 0.2s; grid-column: span 2;">
                                <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Simbol (!@#$%^&*)
                            </div>
                        </div>
                    </div>
                </div>

                <label class="terms">
                    <input type="checkbox" name="terms" checked>
                    <span>Saya setuju dengan <a href="#" onclick="openTermsModal(event)">Syarat & Ketentuan</a></span>
                </label>

                <button type="submit" class="submit-btn">Daftar Sekarang</button>
            </form>

            <div class="divider">
                <span>Atau daftar dengan</span>
            </div>

            <div class="social-row">
                <a href="#" class="social-btn">
                    <img class="google-icon" src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                    Google
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Terms Modal Functions
function openTermsModal(e) {
    if (e) e.preventDefault();
    document.getElementById('termsOverlay').classList.add('active');
}
function closeTermsModal() {
    document.getElementById('termsOverlay').classList.remove('active');
}

// Dynamic Password Checker (Standalone)
function initPasswordChecker() {
    const passwordInput = document.getElementById('regPasswordStandalone');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            
            // Check length
            updateRuleState('rule-sa-length', val.length >= 8);
            
            // Check uppercase
            updateRuleState('rule-sa-upper', /[A-Z]/.test(val));
            
            // Check lowercase
            updateRuleState('rule-sa-lower', /[a-z]/.test(val));
            
            // Check number
            updateRuleState('rule-sa-number', /[0-9]/.test(val));
            
            // Check symbol
            updateRuleState('rule-sa-symbol', /[^A-Za-z0-9]/.test(val));
        });
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordChecker);
} else {
    initPasswordChecker();
}

function updateRuleState(ruleId, isMet) {
    const el = document.getElementById(ruleId);
    if (!el) return;
    const icon = el.querySelector('.rule-icon');
    if (isMet) {
        el.style.color = '#198754';
        el.style.fontWeight = '600';
        icon.textContent = '✔';
        icon.style.color = '#198754';
    } else {
        el.style.color = '#525252';
        el.style.fontWeight = 'normal';
        icon.textContent = '○';
        icon.style.color = '#525252';
    }
}
</script>

<div class="login-overlay" id="termsOverlay" onclick="if(event.target===this) closeTermsModal()">
    <div class="login-modal" style="max-width: 600px; background: #fff; border-radius: 16px; padding: 0; overflow: hidden; font-family: 'Poppins', sans-serif;">
        <button class="login-close" onclick="closeTermsModal()">&times;</button>
        <div style="padding: 30px;">
            <h2 style="font-weight: 800; font-size: 24px; margin-top: 0; margin-bottom: 20px; color: #000;">Syarat & Ketentuan</h2>
            <div style="font-size: 13.5px; line-height: 1.6; color: #333; max-height: 350px; overflow-y: auto; padding-right: 10px; margin-bottom: 24px; text-align: left;">
                <p style="margin-top: 0;"><strong>1. Ketentuan Umum</strong><br>Dengan mendaftar dan menggunakan layanan Kost Elmi Sarah, Anda menyetujui seluruh aturan dan ketentuan yang berlaku di lingkungan kost.</p>
                <p><strong>2. Pembayaran & Booking</strong><br>Booking kamar hanya dianggap sah setelah Anda membayar uang muka (DP) sesuai nominal yang ditentukan dan diverifikasi oleh Admin. Pembayaran bulanan jatuh tempo setiap bulan sesuai tanggal mulai sewa.</p>
                <p><strong>3. Tata Tertib Kost</strong><br>Setiap penghuni wajib menjaga ketertiban, keamanan, kebersihan, serta mematuhi aturan jam malam dan larangan membawa tamu menginap tanpa izin pengelola.</p>
                <p><strong>4. Penggunaan Fasilitas</strong><br>Fasilitas kamar dan area bersama harus dirawat dengan baik. Kerusakan akibat kelalaian penghuni akan dikenakan biaya perbaikan.</p>
                <p style="margin-bottom: 0;"><strong>5. Pembatalan Booking</strong><br>Pembatalan sepihak setelah pembayaran DP dapat mengakibatkan hangusnya uang muka sesuai dengan kebijakan pengelola.</p>
            </div>
            <button onclick="closeTermsModal()" class="submit-btn" style="background: #2C3E50; width: 100%; height: 46px; border: none; border-radius: 8px; color: #fff; font-size: 14.5px; font-weight: 600; cursor: pointer;">Saya Mengerti</button>
        </div>
    </div>
</div>

</body>
</html>