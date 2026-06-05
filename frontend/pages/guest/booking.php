<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?login_modal=1&msg=auth_required'); exit;
    exit;
}

$userId = $_SESSION['user_id'];
$successMsg = "";
$errorMsg = "";

// Fetch user data for autofill
$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$userAutofill = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Check if user already has an active booking that cannot be edited
$stmtCheckActive = $conn->prepare("SELECT id FROM booking WHERE user_id = ? AND status IN ('menunggu_dp', 'disetujui', 'aktif') LIMIT 1");
$stmtCheckActive->execute([$userId]);
$hasActiveBooking = $stmtCheckActive->fetch();

if ($hasActiveBooking || $_SESSION['role'] === 'penghuni') {
    echo "<script>alert('Satu akun hanya dapat memiliki satu kamar aktif. Anda sudah memiliki pesanan kamar atau sedang menjadi penghuni aktif.'); window.location.href='index.php?page=riwayat_booking';</script>";
    exit;
}

// Check for pending booking specifically to pre-fill the form and change button text
$stmtCheckPending = $conn->prepare("SELECT * FROM booking WHERE user_id = ? AND status = 'pending' LIMIT 1");
$stmtCheckPending->execute([$userId]);
$pendingBooking = $stmtCheckPending->fetch(PDO::FETCH_ASSOC);
$hasPendingBooking = !empty($pendingBooking);

$selectedKamarId = '';
$selectedStartDate = '';
$selectedDuration = '';
$emergencyContactVal = '';
$notesVal = '';

if ($hasPendingBooking) {
    $selectedKamarId = $pendingBooking['kamar_id'];
    $selectedStartDate = $pendingBooking['tanggal_masuk'];
    $selectedDuration = $pendingBooking['durasi_bulan'];
    $rawCatatan = $pendingBooking['catatan'] ?? '';
    $notesVal = $rawCatatan;
    if (preg_match('/^Kontak Darurat:\s*([^\r\n]+)(?:\r?\n(.*))?/s', $rawCatatan, $matches)) {
        $emergencyContactVal = trim($matches[1]);
        $notesVal = isset($matches[2]) ? trim($matches[2]) : '';
    }
} else {
    $selectedKamarId = $_GET['id'] ?? $_GET['kamar'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $namaLengkap = $first_name . ' ' . $last_name;
    
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $ktp_number = trim($_POST['ktp_number'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    
    $kamarId = $_POST['kamar_id'] ?? null;
    $start_date = trim($_POST['start_date'] ?? '');
    $duration = (int)($_POST['duration'] ?? 1);
    $notes = trim($_POST['notes'] ?? '');
    
    // Validasi User Hanya Booking Satu
    $stmtCheckActive = $conn->prepare("SELECT id, status FROM booking WHERE user_id = ? AND status IN ('pending', 'menunggu_dp', 'disetujui') LIMIT 1");
    $stmtCheckActive->execute([$userId]);
    $existingBooking = $stmtCheckActive->fetch();
    
    if ($existingBooking && $existingBooking['status'] !== 'pending') {
        $errorMsg = "Anda masih memiliki pesanan aktif yang belum selesai atau belum dibatalkan. Tidak dapat membuat pesanan baru.";
    } elseif (!$kamarId) {
        $errorMsg = "Harap pilih tipe kamar.";
    } else {
        // ═══ ISSUE #4: Cek status kamar sebelum booking ═══
        $stmtCekKamar = $conn->prepare("SELECT status, nomor_kamar FROM kamar WHERE id = ?");
        $stmtCekKamar->execute([$kamarId]);
        $cekKamar = $stmtCekKamar->fetch(PDO::FETCH_ASSOC);
        
        if ($cekKamar && $cekKamar['status'] === 'dibooking') {
            // Cek apakah dibooking oleh user ini sendiri (pending booking)
            $isOwnBooking = ($existingBooking && $existingBooking['status'] === 'pending');
            if (!$isOwnBooking) {
                $errorMsg = "Maaf, kamar " . $cekKamar['nomor_kamar'] . " sedang dalam proses booking oleh penyewa lain. Silakan pilih kamar lain.";
            }
        } elseif ($cekKamar && $cekKamar['status'] === 'terisi') {
            $errorMsg = "Maaf, kamar " . $cekKamar['nomor_kamar'] . " sudah terisi. Silakan pilih kamar lain.";
        }
    }
    
    if (empty($errorMsg)) {
        // Handle KTP Upload
        $ktpFileName = $userAutofill['foto_ktp'] ?? null;
        if (isset($_FILES['ktp_image']) && $_FILES['ktp_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . "/../../../backend/uploads/profil/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ktpFileName = time() . "_ktp_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['ktp_image']['name']));
            move_uploaded_file($_FILES['ktp_image']['tmp_name'], $uploadDir . $ktpFileName);
        }
        
        // Update user profile (Only for non-admins to prevent account damage during tests)
        if (($_SESSION['role'] ?? '') !== 'admin') {
            try {
                $updateQ = "UPDATE users SET nama = ?, alamat = ?, no_hp = ?, no_ktp = ?";
                $paramsU = [$namaLengkap, $address, $phone, $ktp_number];
                if (isset($_FILES['ktp_image']) && $_FILES['ktp_image']['error'] === UPLOAD_ERR_OK) {
                    $updateQ .= ", foto_ktp = ?";
                    $paramsU[] = $ktpFileName;
                }
                $updateQ .= " WHERE id = ?";
                $paramsU[] = $userId;
                $stmtUpdate = $conn->prepare($updateQ);
                $stmtUpdate->execute($paramsU);
                
                // Update session so it reflects immediately on navbar
                $_SESSION['nama'] = $namaLengkap;
            } catch (PDOException $e) {
                // Log error but continue with booking
                error_log("Gagal update profil: " . $e->getMessage());
            }
        }

        
        try {
            // Include emergency contact in notes if provided
            $finalNotes = $notes;
            if ($emergency_contact) {
                $finalNotes = "Kontak Darurat: " . $emergency_contact . "\n" . $notes;
            }

            // Update existing pending booking or insert new
            if ($existingBooking && $existingBooking['status'] === 'pending') {
                $stmtBook = $conn->prepare("UPDATE booking SET kamar_id = ?, tanggal_masuk = ?, durasi_bulan = ?, catatan = ?, tanggal_booking = CURDATE() WHERE id = ?");
                $stmtBook->execute([$kamarId, $start_date, $duration, $finalNotes, $existingBooking['id']]);
                $bookingId = $existingBooking['id'];
            } else {
                $stmtBook = $conn->prepare("INSERT INTO booking (user_id, kamar_id, tanggal_booking, tanggal_masuk, durasi_bulan, status, catatan) VALUES (?, ?, CURDATE(), ?, ?, 'pending', ?)");
                $stmtBook->execute([$userId, $kamarId, $start_date, $duration, $finalNotes]);
                $bookingId = $conn->lastInsertId();
            }
            
            // Redirect to konfirmasi pesanan
            echo "<script>window.location.href='index.php?page=konfirmasi_pesanan&id=$bookingId';</script>";
            exit;
        } catch (PDOException $e) {
            // Fallback for kamar_nomor_id constraint if it exists
            if (strpos($e->getMessage(), 'kamar_nomor_id') !== false) {
                try {
                    if ($existingBooking && $existingBooking['status'] === 'pending') {
                        $stmtBook = $conn->prepare("UPDATE booking SET kamar_id = ?, kamar_nomor_id = NULL, tanggal_masuk = ?, durasi_bulan = ?, catatan = ?, tanggal_booking = CURDATE() WHERE id = ?");
                        $stmtBook->execute([$kamarId, $start_date, $duration, $finalNotes, $existingBooking['id']]);
                        $bookingId = $existingBooking['id'];
                    } else {
                        $stmtBook = $conn->prepare("INSERT INTO booking (user_id, kamar_id, kamar_nomor_id, tanggal_booking, tanggal_masuk, durasi_bulan, status, catatan) VALUES (?, ?, NULL, CURDATE(), ?, ?, 'pending', ?)");
                        $stmtBook->execute([$userId, $kamarId, $start_date, $duration, $finalNotes]);
                        $bookingId = $conn->lastInsertId();
                    }
                    echo "<script>window.location.href='index.php?page=konfirmasi_pesanan&id=$bookingId';</script>";
                    exit;
                } catch (PDOException $e2) {
                    $errorMsg = "Gagal membuat/update pesanan (Fallback): " . $e2->getMessage();
                }
            } else {
                $errorMsg = "Gagal membuat pesanan: " . $e->getMessage();
            }
        }
    }
}
?>
<style>
    /* Gunakan navbar standar (cream color, text gelap) */
    .app-navbar { position: relative !important; background: #EEEADF !important; }
    .navbar-logo, .navbar-menu a, .login-link, .register-btn, .auth-separator { color: #1f2937 !important; }
    .nav-arrow { stroke: #1f2937 !important; }
    .mobile-toggle svg { stroke: #1f2937 !important; }
    
    
    .hero-booking {
        width: 100%;
        height: 400px;
        background: linear-gradient(to bottom, rgba(0,0,0,0.6), rgba(0,0,0,0.2)), url('frontend/assets/image/booking.png') center/cover no-repeat;
    }

    .booking-form-container {
        max-width: 750px;
        margin: -60px auto 100px;
        background: white;
        border-radius: 20px;
        padding: 50px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        position: relative;
        z-index: 10;
    }

    .booking-title {
        text-align: center;
        font-size: 24px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 12px;
    }

    .booking-subtitle {
        text-align: center;
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .section-heading {
        font-size: 16px;
        font-weight: 800;
        color: #374151;
        margin-top: 50px;
        margin-bottom: 24px;
    }

    .custom-input {
        width: 100%;
        height: 52px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 0 20px;
        font-size: 14px;
        color: #1f2937;
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        margin-top: 8px;
    }

    .custom-input:focus {
        border-color: #9ca3af;
        box-shadow: 0 0 0 3px rgba(156, 163, 175, 0.1);
    }

    .custom-input::placeholder {
        color: #d1d5db;
        font-weight: 400;
    }

    .input-label {
        font-size: 13px;
        font-weight: 600;
        color: #4b5563;
        display: block;
    }

    .input-row {
        display: flex;
        gap: 20px;
        margin-bottom: 24px;
    }

    .input-col {
        flex: 1;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .btn-booking {
        background: #1f2937;
        color: white;
        height: 54px;
        border: none;
        border-radius: 10px;
        padding: 0 40px;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
        width: 200px;
    }

    .btn-booking:hover {
        background: #111827;
    }
    
    .btn-booking:active {
        transform: scale(0.98);
    }

    @media (max-width: 768px) {
        .input-row {
            flex-direction: column;
            gap: 24px;
        }
        .booking-form-container {
            padding: 30px 20px;
            margin: -40px 20px 60px;
            border-radius: 16px;
        }
        .btn-booking {
            width: 100%;
        }
    }
</style>

<div class="hero-booking"></div>

<div class="booking-form-container">
    <div class="booking-title">Form Pemesanan Kamar Kost</div>
    <div class="booking-subtitle">Silakan lengkapi data berikut untuk mengajukan pemesanan kamar</div>
    <div class="booking-subtitle" style="margin-bottom: 40px;">Pemesanan Anda akan dikonfirmasi oleh admin</div>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger" style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 12px; font-size: 14px; margin-bottom: 30px; border-left: 4px solid #ef4444;">
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        
        <div class="section-heading">Data Diri Penyewa</div>
        
        <?php
        $names = explode(' ', $userAutofill['nama'] ?? '', 2);
        $firstName = $names[0] ?? '';
        $lastName = $names[1] ?? '';
        ?>

        <div class="form-group">
            <label class="input-label">Nama Lengkap</label>
            <div class="input-row" style="margin-bottom: 0;">
                <div class="input-col">
                    <input type="text" class="custom-input" name="first_name" placeholder="Nama Depan" value="<?= htmlspecialchars($firstName) ?>" required readonly style="background:#f3f4f6;cursor:not-allowed;">
                </div>
                <div class="input-col">
                    <input type="text" class="custom-input" name="last_name" placeholder="Nama Belakang" value="<?= htmlspecialchars($lastName) ?>" required readonly style="background:#f3f4f6;cursor:not-allowed;">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="input-label">Alamat</label>
            <input type="text" class="custom-input" name="address" placeholder="Masukan Alamat Asal" value="<?= htmlspecialchars($userAutofill['alamat'] ?? '') ?>" required>
        </div>

        <div class="input-row">
            <div class="input-col">
                <label class="input-label">Email</label>
                <input type="email" class="custom-input" name="email" placeholder="Masukan Email" value="<?= htmlspecialchars($userAutofill['email'] ?? '') ?>" required readonly style="background:#f3f4f6;cursor:not-allowed;">
            </div>
            <div class="input-col">
                <label class="input-label">No Handphone</label>
                <input type="text" class="custom-input" name="phone" placeholder="Masukan No Handphone" value="<?= htmlspecialchars($userAutofill['no_hp'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="input-label">No KTP</label>
            <input type="text" class="custom-input" name="ktp_number" placeholder="Masukan No KTP" value="<?= htmlspecialchars($userAutofill['no_ktp'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label class="input-label">Upload KTP</label>
            <input type="file" class="custom-input" name="ktp_image" accept="image/*" style="padding-top: 13px;" <?= empty($userAutofill['foto_ktp']) ? 'required' : '' ?>>
            <?php if (!empty($userAutofill['foto_ktp'])): ?>
                <span class="text-success" style="font-size:12.5px; display:block; margin-top:5px;"><i data-lucide="check-circle" style="width:14px;height:14px;display:inline-block;margin-bottom:-2px;margin-right:4px;"></i> KTP sudah terunggah sebelumnya</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="input-label">Kontak Darurat</label>
            <input type="text" class="custom-input" name="emergency_contact" placeholder="Kontak Orang Terdekat" value="<?= htmlspecialchars($emergencyContactVal) ?>" required>
        </div>


        <div class="section-heading" style="margin-top: 60px;">DATA PEMESANAN</div>

        <?php
        // ISSUE #4: Hanya tampilkan kamar yang benar-benar tersedia
        // Kamar dengan status 'dibooking' atau 'terisi' TIDAK boleh dipilih user lain
        $stmtTipes = $conn->prepare("
            SELECT id, tipe, nomor_kamar, status
            FROM kamar 
            WHERE status = 'tersedia' OR id = ?
            ORDER BY tipe ASC, CAST(nomor_kamar AS UNSIGNED) ASC
        ");
        $stmtTipes->execute([$selectedKamarId]);
        $allTipes = $stmtTipes->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter: jika kamar yang di-select sebelumnya sudah dibooking orang lain, hapus dari list
        $filteredTipes = [];
        foreach ($allTipes as $t) {
            if ($t['status'] === 'tersedia') {
                $filteredTipes[] = $t;
            } elseif ($t['id'] == $selectedKamarId && $hasPendingBooking) {
                // Kamar ini milik pending booking user ini sendiri, tetap tampilkan
                $filteredTipes[] = $t;
            }
            // Kamar dibooking/terisi oleh orang lain → tidak ditampilkan
        }
        $allTipes = $filteredTipes;
        ?>

        <div class="form-group">
            <label class="input-label">Pilih Kamar Tersedia</label>
            <select name="kamar_id" class="custom-input" required style="cursor: pointer;">
                <option value="">Pilih Kamar...</option>
                <?php foreach($allTipes as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($selectedKamarId == $t['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['tipe'] . ' - No. ' . $t['nomor_kamar']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="input-label">Tanggal Sewa</label>
            <input type="date" class="custom-input" name="start_date" value="<?= htmlspecialchars($selectedStartDate) ?>" required>
        </div>

        <div class="form-group">
            <label class="input-label">Durasi Sewa</label>
            <select name="duration" class="custom-input" required style="cursor: pointer;">
                <option value="">Masukan Durasi Sewa</option>
                <option value="1" <?= ($selectedDuration == 1) ? 'selected' : '' ?>>1 Bulan</option>
                <option value="3" <?= ($selectedDuration == 3) ? 'selected' : '' ?>>3 Bulan</option>
                <option value="6" <?= ($selectedDuration == 6) ? 'selected' : '' ?>>6 Bulan</option>
                <option value="12" <?= ($selectedDuration == 12) ? 'selected' : '' ?>>1 Tahun</option>
                <option value="24" <?= ($selectedDuration == 24) ? 'selected' : '' ?>>2 Tahun</option>
                <option value="36" <?= ($selectedDuration == 36) ? 'selected' : '' ?>>3 Tahun</option>
                <option value="48" <?= ($selectedDuration == 48) ? 'selected' : '' ?>>4 Tahun</option>
                <option value="60" <?= ($selectedDuration == 60) ? 'selected' : '' ?>>5 Tahun</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 50px;">
            <label class="input-label">Catatan</label>
            <textarea class="custom-input" name="notes" rows="4" style="height: auto; padding-top: 16px;" placeholder="Masukan Detail Catatan (Bila Ada)"><?= htmlspecialchars($notesVal) ?></textarea>
        </div>

        <div style="background-color: #fffbeb; border: 1px solid #fef08a; padding: 16px; border-radius: 10px; margin-bottom: 40px; font-size: 13.5px; color: #b45309; line-height: 1.6;">
            <strong><i data-lucide="info" style="width:16px;height:16px;display:inline-block;margin-bottom:-3px;margin-right:4px;"></i> Informasi Pembatalan Booking:</strong><br>
            Anda dapat membatalkan pesanan kamar melalui halaman <b>Riwayat Booking</b> kapan saja sebelum melakukan pembayaran pertama. Pastikan data pemesanan sudah benar sebelum menekan tombol Booking.
        </div>

        <div style="text-align: center;">
            <button type="submit" class="btn-booking"><?= $hasPendingBooking ? 'SIMPAN PERUBAHAN' : 'BOOKING' ?></button>
        </div>

    </form>
</div>
