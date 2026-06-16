<?php
session_start();
require_once "../../config/database.php";

$message = "";
$isSuccess = false;

if (isset($_GET["success"])) {
    $message = "Register berhasil, silakan login";
    $isSuccess = true;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $msg = isset($_GET['success']) ? "&msg=register_success" : "";
    header("Location: ../../../index.php?login_modal=1" . $msg);
    exit;
}

if (!isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $captcha = intval($_POST["captcha"] ?? 0);

    $expected = intval($_SESSION['captcha_num1'] ?? 0) + intval($_SESSION['captcha_num2'] ?? 0);

    if ($email === "" || $password === "") {
        $message = "Email/No Hp dan kata sandi wajib diisi";
    } elseif ($captcha !== $expected) {
        $message = "Jawaban CAPTCHA salah";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR no_hp = ? LIMIT 1");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $message = "Email atau No Hp tidak ditemukan";
        } elseif (($user["status"] ?? "aktif") !== "aktif") {
            $message = "Akun tidak aktif";
        } elseif (!password_verify($password, $user["password"])) {
            $message = "Kata sandi salah";
        } else {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["nama"] = $user["nama"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            // Clear captcha on success
            unset($_SESSION['captcha_num1']);
            unset($_SESSION['captcha_num2']);

            if (isset($_POST['is_ajax'])) {
                $redirect_url = ($user["role"] === "admin") ? "index.php?page=admin-dashboard" : "index.php";
                echo json_encode(["status" => "success", "redirect" => $redirect_url]);
                exit;
            }

            if ($user["role"] === "admin") {
                header("Location: ../../../index.php?page=admin-dashboard");
            } else {
                header("Location: ../../../index.php");
            }
            exit;
        }
    }
    
    // Regenerate CAPTCHA on failure
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
    
    if (isset($_POST['is_ajax'])) {
        echo json_encode([
            "status" => "error", 
            "message" => $message,
            "captcha_num1" => $_SESSION['captcha_num1'],
            "captcha_num2" => $_SESSION['captcha_num2']
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun - Kost Elmi Sarah</title>

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

        .login-wrapper {
            width: min(1270px, 100%);
            min-height: 845px;
            background: #EBE7DE;
            border-radius: 16px;
            padding: 12px 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 34px;
        }

        .login-content {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 52px 20px;
        }

        .login-box {
            width: 100%;
            max-width: 482px;
        }

        h1 {
            margin: 0 0 18px;
            font-size: 50px;
            line-height: 1.2;
            font-weight: 800;
            color: #000;
        }

        .subtitle {
            margin: 0 0 64px;
            font-size: 15px;
            color: #000;
        }

        .message {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 9px;
            background: #ffe3e3;
            color: #c1121f;
            font-size: 14px;
        }

        .message.success {
            background: #e7f8ec;
            color: #198754;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            font-size: 15px;
            color: #000;
        }

        .form-control {
            width: 100%;
            height: 58px;
            border: 1px solid #D9D9D9;
            border-radius: 9px;
            background: #fff;
            padding: 0 28px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            outline: none;
        }

        .form-control::placeholder {
            color: #D9D9D9;
        }

        .login-options {
            margin: 6px 0 31px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 15px;
            color: #000;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .remember input {
            width: 22px;
            height: 22px;
            accent-color: #1E1E1E;
        }

        .forgot-link {
            color: #000;
            text-decoration: none;
        }

        .submit-btn {
            width: 100%;
            height: 58px;
            border: 1px solid #D9D9D9;
            border-radius: 9px;
            background: #2C3E50;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            cursor: pointer;
        }

        .social-row {
            margin-top: 28px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
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
            filter: drop-shadow(0px 4px 4px rgba(0,0,0,0.25));
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
            filter: drop-shadow(0px 4px 4px rgba(0,0,0,0.25));
        }

        .register-text {
            margin-top: 36px;
            text-align: center;
            color: #525252;
            font-size: 15px;
        }

        .register-text a {
            color: #000;
            text-decoration: none;
            font-weight: 500;
        }

        .login-visual {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url("../../../frontend/assets/image/login.png");
            background-size: cover;
            background-position: center;
            min-height: 821px;
        }

        .visual-brand {
            position: absolute;
            top: 43px;
            left: 36px;
            color: #fff;
            font-size: 25px;
            font-weight: 700;
        }

        .visual-pill {
            position: absolute;
            top: 34px;
            right: 20px;
            width: 169px;
            height: 40px;
            background: #fff;
            border-radius: 21px;
        }

        .visual-caption {
            position: absolute;
            left: 50%;
            bottom: 76px;
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
            align-items: center;
            gap: 16px;
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

        @media (max-width: 992px) {
            body {
                padding: 20px;
            }

            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .login-visual {
                min-height: 320px;
                order: -1;
            }

            .login-content {
                padding: 30px 10px;
            }

            h1 {
                font-size: 38px;
            }

            .subtitle {
                margin-bottom: 34px;
            }
        }

        @media (max-width: 576px) {
            .social-row {
                grid-template-columns: 1fr;
            }

            .login-options {
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
            }

            .visual-caption {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-content">
        <div class="login-box">
            <h1>Selamat Datang</h1>

            <p class="subtitle">
                Masukkan email dan kata sandi untuk mengakses akun kost
            </p>

            <?php if ($message !== ""): ?>
                <div class="message <?php echo $isSuccess ? 'success' : ''; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email/No Hp</label>
                    <input
                        class="form-control"
                        type="text"
                        name="email"
                        placeholder="Masukan Email atau No Hp"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Kata Sandi</label>
                    <input
                        class="form-control"
                        type="password"
                        name="password"
                        placeholder="Masukkan kata sandi"
                    >
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 9px;">
                        <label id="captcha-question-page" style="margin-bottom: 0;">Berapa hasil dari <?php echo $_SESSION['captcha_num1']; ?> + <?php echo $_SESSION['captcha_num2']; ?>?</label>
                        <button type="button" onclick="refreshCaptchaPage()" style="background: none; border: none; color: #2C3E50; cursor: pointer; padding: 0; display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; font-family: 'Poppins', sans-serif;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-refresh-cw" style="vertical-align: middle;"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg> Ubah Soal
                        </button>
                    </div>
                    <input
                        class="form-control"
                        type="number"
                        name="captcha"
                        id="loginCaptchaPage"
                        placeholder="Masukkan jawaban angka"
                        required
                    >
                </div>

                <div class="login-options">
                    <label class="remember">
                        <input type="checkbox" name="remember" checked>
                        <span>Ingat saya</span>
                    </label>

                    <a href="lupa_password.php" class="forgot-link">Lupa kata sandi?</a>
                </div>

                <button type="submit" class="submit-btn">Masuk</button>
            </form>

            <div class="social-row">
                <a href="#" class="social-btn">
                    <img class="google-icon" src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                    Google
                </a>
            </div>

            <div class="register-text">
                Belum punya akun? <a href="register.php">Daftar</a>
            </div>
        </div>
    </div>

    <div class="login-visual">
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
</div>

<script>
async function refreshCaptchaPage() {
    try {
        const response = await fetch('refresh_captcha.php');
        const data = await response.json();
        if (data.status === 'success') {
            document.getElementById('captcha-question-page').textContent = `Berapa hasil dari ${data.captcha_num1} + ${data.captcha_num2}?`;
            const inputEl = document.getElementById('loginCaptchaPage');
            if (inputEl) inputEl.value = '';
        }
    } catch (e) {
        console.error("Gagal memuat CAPTCHA baru", e);
    }
}
</script>

</body>
</html>