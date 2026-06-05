<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?login_modal=1&msg=auth_required'); exit;
    exit;
}

$bookingId = $_GET['id'] ?? null;
if (!$bookingId) {
    echo "<script>alert('ID Booking tidak valid'); window.location.href='index.php';</script>";
    exit;
}

try {
    $sql = "
        SELECT b.*, u.nama, u.no_hp, u.email, u.alamat, u.no_ktp, 
               k.tipe, k.harga as harga_per_bulan
        FROM booking b
        JOIN users u ON b.user_id = u.id
        JOIN kamar k ON b.kamar_id = k.id
        WHERE b.id = ?
    ";

    $params = [$bookingId];
    if (($_SESSION['role'] ?? '') !== 'admin') {
        $sql .= " AND b.user_id = ?";
        $params[] = $_SESSION['user_id'];
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo "<script>alert('Data booking tidak ditemukan'); window.location.href='index.php';</script>";
        exit;
    }
} catch (PDOException $e) {
    die("Database Error di konfirmasi_pesanan.php: " . $e->getMessage());
} catch (Exception $e) {
    die("General Error di konfirmasi_pesanan.php: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<script>window.location.href='index.php?page=pembayaran_booking&id=$bookingId';</script>";
    exit;
}

$totalHarga = $booking['harga_per_bulan'] * $booking['durasi_bulan'];

// Extract note and emergency contact if formatted like "Kontak Darurat: XXX\nNotes"
$rawCatatan = $booking['catatan'] ?? '-';
?>
<style>
    body { background-color: #E2E2E2; } /* Outer gray background similar to mockup overlay backdrop */
    .app-navbar { position: relative !important; background: #EEEADF !important; }
    .navbar-logo, .navbar-menu a, .login-link, .register-btn, .auth-separator { color: #1f2937 !important; }
    .nav-arrow { stroke: #1f2937 !important; }
    .mobile-toggle svg { stroke: #1f2937 !important; }
    
    .overlay-container {
        max-width: 650px;
        margin: 60px auto 100px;
        background: #EEEADF; /* The card itself has a cream background in the mockup */
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }

    .overlay-title {
        font-size: 24px;
        font-weight: 800;
        color: #172554; /* Dark blue/navy color from mockup */
        margin-bottom: 8px;
    }

    .overlay-subtitle {
        font-size: 14px;
        color: #4b5563;
        margin-bottom: 30px;
    }

    .info-card {
        background-color: transparent;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 13px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
        font-size: 14px;
        align-items: center;
    }

    .info-row:last-child {
        margin-bottom: 0;
    }

    .info-label {
        color: #4b5563;
        font-weight: 500;
    }

    .info-value {
        color: #111827;
        font-weight: 700;
        text-align: right;
    }

    .action-buttons {
        display: flex;
        gap: 20px;
    }

    .btn-outline-custom {
        flex: 1;
        background-color: transparent;
        color: #4b5563;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        padding: 14px 20px;
        font-weight: 600;
        font-size: 14px;
        text-align: center;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-outline-custom:hover {
        background-color: #e5e7eb;
        color: #1f2937;
    }

    .btn-solid-custom {
        flex: 1;
        background-color: #4b5563; /* Dark gray from mockup */
        color: white;
        border: none;
        border-radius: 10px;
        padding: 14px 20px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-solid-custom:hover {
        background-color: #374151;
    }
</style>

<div class="overlay-container">
    <div class="overlay-title">Konfirmasi Pesanan</div>
    <div class="overlay-subtitle">Pastikan data yang kamu masukan sudah benar sebelum melanjutkan</div>

    <div class="info-card">
        <div class="section-title">DATA DIRI</div>
        <div class="info-row">
            <span class="info-label">Nama Lengkap</span>
            <span class="info-value"><?= htmlspecialchars($booking['nama'] ?? '') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Alamat</span>
            <span class="info-value"><?= htmlspecialchars($booking['alamat'] ?? '-') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value"><?= htmlspecialchars($booking['email'] ?? '') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">No Hp</span>
            <span class="info-value"><?= htmlspecialchars($booking['no_hp'] ?? '-') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">No KTP</span>
            <span class="info-value"><?= htmlspecialchars($booking['no_ktp'] ?? '-') ?></span>
        </div>

        <div style="height: 1px; background: #d1d5db; margin: 30px 0;"></div>

        <div class="section-title">DATA PESANAN</div>
        <div class="info-row">
            <span class="info-label">Tipe Kamar</span>
            <span class="info-value"><?= htmlspecialchars($booking['tipe'] ?? '') ?></span>
        </div>
        <!-- No Kamar is intentionally omitted as per new flow -->
        <div class="info-row">
            <span class="info-label">Tanggal Sewa</span>
            <span class="info-value"><?= !empty($booking['tanggal_masuk']) ? date('d-m-Y', strtotime($booking['tanggal_masuk'])) : '-' ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Durasi Sewa</span>
            <span class="info-value"><?= $booking['durasi_bulan'] ?? 0 ?> Bulan</span>
        </div>
        <div class="info-row">
            <span class="info-label">Catatan</span>
            <span class="info-value" style="max-width: 60%; word-break: break-word;"><?= nl2br(htmlspecialchars($rawCatatan)) ?></span>
        </div>
        <div class="info-row" style="margin-top: 20px;">
            <span class="info-label" style="font-weight: 700; color: #111827;">Total Harga</span>
            <span class="info-value" style="font-size: 16px;">Rp <?= number_format($totalHarga ?? 0, 0, ',', '.') ?></span>
        </div>
    </div>

    <form method="POST">
        <div class="action-buttons">
            <a href="index.php?page=batal_booking&action=edit&id=<?= $bookingId ?>" class="btn-outline-custom">Edit Pemesanan</a>
            <button type="submit" class="btn-solid-custom">Ya, Lanjutkan</button>
        </div>
    </form>
</div>
