<?php
session_start();
require_once "../../config/database.php";

$message = "";
$isSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identity = trim($_POST["identity"] ?? "");
    $nama = trim($_POST["nama"] ?? "");
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($identity === "" || $nama === "" || $new_password === "" || $confirm_password === "") {
        $message = "Semua kolom verifikasi wajib diisi!";
    } elseif (strlen($new_password) < 6) {
        $message = "Kata sandi baru minimal 6 karakter!";
    } elseif ($new_password !== $confirm_password) {
        $message = "Konfirmasi kata sandi tidak cocok!";
    } else {
        // Find user by email or no_hp and match name (case-insensitive and trimmed)
        $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR no_hp = ?) LIMIT 1");
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $message = "Akun dengan Email/No HP tersebut tidak ditemukan.";
        } else {
            // Compare names ignoring case and whitespace
            $dbName = strtolower(preg_replace('/\s+/', '', $user['nama']));
            $inputName = strtolower(preg_replace('/\s+/', '', $nama));

            if ($dbName !== $inputName) {
                $message = "Nama Lengkap tidak cocok dengan data akun.";
            } else {
                // Update password
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmtUpd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmtUpd->execute([$hashed, $user['id']]);

                $message = "Kata sandi berhasil diatur ulang! Silakan masuk.";
                $isSuccess = true;
            }
        }
    }
}

$adminWA = "6283821463041";
$waMsg = "Halo Admin Kost Elmi Sarah, saya mengalami kendala lupa kata sandi akun saya. Mohon bantuannya untuk mereset akun.";
$waUrl = "https://wa.me/" . $adminWA . "?text=" . urlencode($waMsg);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi - Kost Elmi Sarah</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
            font-size: 44px;
            line-height: 1.2;
            font-weight: 800;
            color: #000;
        }

        .subtitle {
            margin: 0 0 40px;
            font-size: 15px;
            color: #525252;
            line-height: 1.6;
        }

        .message {
            margin-bottom: 24px;
            padding: 14px 18px;
            border-radius: 9px;
            background: #ffe3e3;
            color: #c1121f;
            font-size: 13.5px;
            font-weight: 500;
            line-height: 1.5;
            border-left: 4px solid #c1121f;
        }

        .message.success {
            background: #e7f8ec;
            color: #198754;
            border-left-color: #198754;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #000;
        }

        .form-control {
            width: 100%;
            height: 54px;
            border: 1px solid #D9D9D9;
            border-radius: 9px;
            background: #fff;
            padding: 0 20px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: #2C3E50;
        }

        .form-control::placeholder {
            color: #D9D9D9;
        }

        .submit-btn {
            width: 100%;
            height: 54px;
            border: none;
            border-radius: 9px;
            background: #2C3E50;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .submit-btn:hover {
            background: #1A252F;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: #888;
            font-size: 13px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #D9D9D9;
        }

        .divider:not(:empty)::before {
            margin-right: .5em;
        }

        .divider:not(:empty)::after {
            margin-left: .5em;
        }

        .wa-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            height: 54px;
            border: 1px solid #25D366;
            border-radius: 9px;
            background: #25D366;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .wa-btn:hover {
            opacity: 0.9;
        }

        .register-text {
            margin-top: 30px;
            text-align: center;
            color: #525252;
            font-size: 14px;
        }

        .register-text a {
            color: #2C3E50;
            text-decoration: none;
            font-weight: 600;
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
                font-size: 34px;
            }

            .subtitle {
                margin-bottom: 24px;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-content">
        <div class="login-box">
            <h1>Lupa Kata Sandi</h1>

            <p class="subtitle">
                Verifikasi data akun Anda untuk membuat kata sandi baru secara instan, atau hubungi Admin.
            </p>

            <?php if ($message !== ""): ?>
                <div class="message <?php echo $isSuccess ? 'success' : ''; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email atau No HP Terdaftar</label>
                    <input
                        class="form-control"
                        type="text"
                        name="identity"
                        placeholder="Masukkan Email atau No HP Anda"
                        value="<?php echo htmlspecialchars($_POST['identity'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Nama Lengkap Akun</label>
                    <input
                        class="form-control"
                        type="text"
                        name="nama"
                        placeholder="Masukkan Nama Lengkap Anda"
                        value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Kata Sandi Baru</label>
                    <input
                        class="form-control"
                        type="password"
                        name="new_password"
                        placeholder="Minimal 6 karakter"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Konfirmasi Kata Sandi Baru</label>
                    <input
                        class="form-control"
                        type="password"
                        name="confirm_password"
                        placeholder="Ulangi kata sandi baru"
                        required
                    >
                </div>

                <button type="submit" class="submit-btn" style="margin-top: 10px;">Atur Ulang Kata Sandi</button>
            </form>

            <div class="divider">ATAU hubungi admin</div>

            <a href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank" class="wa-btn">
                <svg style="width:20px;height:20px;fill:currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.625 1.45 5.489.002 9.961-4.438 9.964-9.888.002-2.643-1.019-5.12-2.876-6.98C16.486 1.83 14.02 .81 11.4 1.81c-5.462 0-9.92 4.417-9.923 9.864-.001 1.702.469 3.366 1.36 4.815l-.997 3.637 3.807-.972zm12.92-5.32c-.3-.15-1.77-.875-2.045-.975-.276-.1-.476-.15-.676.15-.2.3-.775.975-.95 1.175-.175.2-.35.225-.65.075-.3-.15-1.265-.467-2.41-1.485-.89-.79-1.49-1.77-1.665-2.07-.175-.3-.02-.46.13-.61.135-.13.3-.35.45-.525.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.676-1.63-.926-2.235-.244-.589-.493-.51-.676-.519-.174-.007-.375-.01-.576-.01-.2 0-.525.075-.8.375-.275.3-1.05 1.025-1.05 2.5s1.075 2.9 1.225 3.1c.15.2 2.11 3.22 5.11 4.52.714.31 1.27.49 1.7.63.717.22 1.37.19 1.89.11.58-.09 1.77-.72 2.02-1.38.25-.66.25-1.225.175-1.38-.075-.15-.275-.225-.575-.375z"/>
                </svg>
                Hubungi via WhatsApp
            </a>

            <div class="register-text">
                Kembali ke halaman <a href="login.php">Masuk</a>
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

</body>
</html>
