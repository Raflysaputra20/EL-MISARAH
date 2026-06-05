<?php
if (!isset($_SESSION['user_id'])) {
    echo "header('Location: index.php?login_modal=1&msg=auth_required'); exit;";
    exit;
}

$bookingId = $_GET['id'] ?? null;
if (!$bookingId) {
    echo "<script>alert('ID Booking tidak valid'); window.location.href='index.php';</script>";
    exit;
}

// ─── HANDLE UPLOAD BUKTI ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_bayar'])) {
    if ($_FILES['bukti_bayar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/../../../frontend/assets/image/bukti/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $buktiFileName = time() . "_bukti_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['bukti_bayar']['name']));
        if (move_uploaded_file($_FILES['bukti_bayar']['tmp_name'], $uploadDir . $buktiFileName)) {
            // Update bukti di tabel pembayaran
            $stmtUpd = $conn->prepare("UPDATE pembayaran SET bukti_bayar = ?, status = 'menunggu_verifikasi' WHERE booking_id = ?");
            $stmtUpd->execute([$buktiFileName, $bookingId]);
            
            // Update status booking ke 'menunggu_dp' agar muncul di dashboard admin
            $stmtUpdBooking = $conn->prepare("UPDATE booking SET status = 'menunggu_dp' WHERE id = ? AND status = 'pending'");
            $stmtUpdBooking->execute([$bookingId]);
            
            echo "<script>alert('Bukti pembayaran berhasil diunggah!'); window.location.href='index.php?page=menunggu_konfirmasi&id=$bookingId';</script>";
            exit;
        }
    }
}

// Cek kolom jenis_pembayaran
$hasJenis = $conn->query("SHOW COLUMNS FROM pembayaran LIKE 'jenis_pembayaran'")->fetch();
$jenisSql = $hasJenis ? "p.jenis_pembayaran," : "'Penuh' as jenis_pembayaran,";

// Fetch booking data
$sql = "
    SELECT b.*, u.nama, u.no_hp, u.email, u.alamat, u.no_ktp, u.role as current_role,
           k.tipe, k.harga as harga_per_bulan, k.nomor_kamar,
           p.jumlah, $jenisSql p.status as status_bayar, p.created_at as payment_time, p.metode, p.bukti_bayar
    FROM booking b
    JOIN users u ON b.user_id = u.id
    JOIN kamar k ON b.kamar_id = k.id
    LEFT JOIN pembayaran p ON b.id = p.booking_id
    WHERE b.id = ?
";

$params = [$bookingId];
if (($_SESSION['role'] ?? '') !== 'admin') {
    $sql .= " AND b.user_id = ?";
    $params[] = $_SESSION['user_id'];
}
$sql .= " ORDER BY p.id DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "<script>alert('Data booking tidak ditemukan'); window.location.href='index.php';</script>";
    exit;
}

$isVerified = ($booking['status_bayar'] === 'valid' || in_array($booking['status'], ['disetujui', 'aktif', 'selesai']));
$isSelesai = in_array($booking['status'], ['aktif', 'selesai']);

// WhatsApp Link Generator
$adminWA = "6283821463041"; // Admin WhatsApp Number
$waMsg = "Halo Admin, saya telah melakukan pembayaran untuk booking ID #" . $bookingId . " atas nama " . $booking['nama'] . ". Mohon konfirmasinya. Terima kasih.";
$waUrl = "https://wa.me/" . $adminWA . "?text=" . urlencode($waMsg);
?>
<style>
    body { background-color: #EEEADF; }
    .status-container { max-width: 600px; margin: 40px auto 100px; }
    .page-title { text-align: center; font-size: 20px; font-weight: 800; color: #172554; margin-bottom: 8px; }
    .page-subtitle { text-align: center; font-size: 14px; color: #64748b; margin-bottom: 30px; }
    .status-banner { background-color: #e6ce92; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border: 1px solid #d4b872; }
    .status-icon { width: 24px; height: 24px; border-radius: 50%; border: 2px solid #1f2937; display: flex; align-items: center; justify-content: center; }
    .status-text h4 { font-size: 15px; font-weight: 700; color: #1f2937; margin: 0 0 4px 0; }
    .status-text p { font-size: 12px; color: #4b5563; margin: 0; }
    .detail-card { background: white; border-radius: 12px; padding: 30px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px 16px; }
    .info-item .label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 6px; }
    .info-item .val { font-size: 13px; font-weight: 700; color: #1e293b; }
    .divider { height: 1px; background: #e5e7eb; margin: 24px 0; }
    .total-val { font-size: 24px; font-weight: 800; color: #1e293b; }
    .btn-wa { display: flex; align-items: center; justify-content: center; gap: 10px; background: #25D366; color: white; padding: 14px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; margin-top: 20px; transition: opacity 0.2s; }
    .btn-wa:hover { opacity: 0.9; color: white; }
    .upload-box { background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; }
</style>

<div class="status-container">
    <div class="page-title">Status Pesanan</div>
    <div class="page-subtitle">Lacak pembayaran kost</div>

    <?php if ($booking['status'] === 'ditolak'): ?>
        <div class="status-banner" style="background-color: #fee2e2; border-color: #fca5a5;">
            <div class="status-icon" style="border-color: #991b1b; display: flex; align-items: center; justify-content: center; background-color: #fee2e2;">
                <i data-lucide="x" style="width: 14px; height: 14px; color: #991b1b; stroke-width: 3;"></i>
            </div>
            <div class="status-text">
                <h4 style="color: #991b1b;">Pesanan Ditolak</h4>
                <p style="color: #7f1d1d;">Mohon maaf, pengajuan booking Anda ditolak oleh admin.</p>
            </div>
        </div>
    <?php elseif ($isSelesai): ?>
        <div class="status-banner" style="background-color: #d1fae5; border-color: #34d399;">
            <div class="status-icon" style="border-color: #065f46;"><i data-lucide="check" style="width: 14px; height: 14px; color: #065f46;"></i></div>
            <div class="status-text"><h4 style="color: #065f46;">Selesai</h4><p style="color: #065f46;">Pesanan telah selesai. Selamat menempati kamar.</p></div>
        </div>
    <?php elseif ($isVerified): ?>
        <div class="status-banner" style="background-color: #dbeafe; border-color: #60a5fa;">
            <div class="status-icon" style="border-color: #1e40af;"><i data-lucide="check" style="width: 14px; height: 14px; color: #1e40af;"></i></div>
            <div class="status-text"><h4 style="color: #1e40af;">Diverifikasi</h4><p style="color: #1e40af;">Pembayaran berhasil diverifikasi. Silakan datang ke lokasi.</p></div>
        </div>
    <?php else: ?>
        <?php 
        $hasBuktiBayar = !empty($booking['bukti_bayar']);
        if (!$hasBuktiBayar): ?>
        <div class="status-banner" style="background-color: #fef3c7; border-color: #fcd34d;">
            <div class="status-icon" style="border-color: #92400e; display: flex; align-items: center; justify-content: center; background-color: #fef3c7;">
                <i data-lucide="clock" style="width: 14px; height: 14px; color: #92400e;"></i>
            </div>
            <div class="status-text">
                <h4 style="color: #92400e;">Menunggu Pembayaran</h4>
                <p style="color: #78350f;">Silakan unggah bukti transfer di bawah untuk melanjutkan proses</p>
            </div>
        </div>
        <?php else: ?>
        <div class="status-banner" style="background-color: #dbeafe; border-color: #93c5fd;">
            <div class="status-icon" style="border-color: #1e40af; display: flex; align-items: center; justify-content: center; background-color: #dbeafe;">
                <i data-lucide="search" style="width: 14px; height: 14px; color: #1e40af;"></i>
            </div>
            <div class="status-text">
                <h4 style="color: #1e40af;">Bukti Dikirim — Menunggu Verifikasi</h4>
                <p style="color: #1e3a8a;">Admin sedang mengecek bukti pembayaran Anda</p>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- DETAIL ALASAN PENOLAKAN -->
    <?php if ($booking['status'] === 'ditolak'): ?>
    <div class="detail-card" style="border-left: 4px solid #ef4444;">
        <h5 style="font-size:16px; font-weight:800; color: #ef4444; margin-bottom:12px;">Alasan Penolakan dari Admin</h5>
        <p style="font-size:14.5px; color:#4b5563; line-height:1.6; margin-bottom:0; background: #fff5f5; padding: 15px; border-radius: 8px; font-style: italic;">
            "<?= htmlspecialchars($booking['alasan_penolakan'] ?? 'Tidak ada alasan spesifik yang diberikan.') ?>"
        </p>
    </div>
    <?php endif; ?>

    <!-- UPLOAD BUKTI & WA (Hanya jika belum diverifikasi dan tidak ditolak) -->
    <?php if (!$isVerified && $booking['status'] !== 'ditolak'): ?>
    <div class="detail-card">
        <h5 style="font-size:16px; font-weight:800; margin-bottom:20px;">Konfirmasi Pembayaran</h5>
        
        <?php if (empty($booking['bukti_bayar'])): ?>
            <form method="POST" enctype="multipart/form-data" class="upload-box">
                <p style="font-size:13px; color:#64748b; margin-bottom:15px;">Silakan unggah foto bukti transfer Anda di sini</p>
                <input type="file" name="bukti_bayar" class="form-control mb-3" accept="image/*" required>
                <button type="submit" class="btn btn-dark w-100">Unggah Bukti</button>
            </form>
        <?php else: ?>
            <div style="text-align:center; padding: 20px; background:#f0fdf4; border-radius:12px; border:1px solid #bbf7d0;">
                <i data-lucide="image" style="width:32px; height:32px; color:#16a34a; margin-bottom:10px;"></i>
                <p style="font-size:13px; color:#166534; font-weight:600; margin-bottom:0;">Bukti sudah terkirim. Menunggu pengecekan admin.</p>
                <a href="frontend/assets/image/bukti/<?= $booking['bukti_bayar'] ?>" target="_blank" style="font-size:11px; color:#16a34a;">Lihat Foto Bukti</a>
            </div>
        <?php endif; ?>

        <a href="<?= $waUrl ?>" target="_blank" class="btn-wa">
            <i data-lucide="message-circle" style="width:20px; height:20px;"></i>
            Konfirmasi via WhatsApp
        </a>
    </div>
    <?php endif; ?>

    <div class="detail-card">
        <div class="info-section">
            <div class="info-grid">
                <div class="info-item"><div class="label">Nama Lengkap</div><div class="val"><?= htmlspecialchars($booking['nama'] ?? '') ?></div></div>
                <div class="info-item"><div class="label">No Hp</div><div class="val"><?= htmlspecialchars($booking['no_hp'] ?? '-') ?></div></div>
                <div class="info-item"><div class="label">Tipe Kamar</div><div class="val" style="text-transform: uppercase;"><?= htmlspecialchars($booking['tipe'] ?? '') ?></div></div>
                <div class="info-item"><div class="label">No Kamar</div><div class="val"><?= htmlspecialchars($booking['nomor_kamar'] ?? '-') ?></div></div>
                <div class="info-item"><div class="label">Metode</div><div class="val" style="text-transform: uppercase;"><?= htmlspecialchars($booking['metode'] ?? 'QRIS') ?></div></div>
                <div class="info-item"><div class="label">Jenis</div><div class="val" style="text-transform: uppercase;"><?= htmlspecialchars($booking['jenis_pembayaran'] ?? 'BAYAR PENUH') ?></div></div>
            </div>
        </div>
        <div class="divider"></div>
        <div class="total-val">Total: Rp <?= number_format($booking['jumlah'] ?? 0, 0, ',', '.') ?></div>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
