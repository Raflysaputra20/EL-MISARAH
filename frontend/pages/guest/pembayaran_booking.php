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

// Fetch booking data
$stmt = $conn->prepare("
    SELECT b.*, k.harga as harga_per_bulan, k.tipe, k.harga, k.harga_3_bulan, k.harga_6_bulan, k.harga_tahun
    FROM booking b
    JOIN kamar k ON b.kamar_id = k.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "<script>alert('Data booking tidak ditemukan'); window.location.href='index.php';</script>";
    exit;
}

if ($booking['status'] === 'ditolak' || $booking['status'] === 'dibatalkan' || $booking['status'] === 'selesai') {
    echo "<script>alert('Booking ini sudah ditolak atau tidak valid untuk dibayar.'); window.location.href='index.php?page=riwayat_booking';</script>";
    exit;
}

// Jika sudah menunggu_dp, redirect ke halaman status (cegah pembayaran duplikat)
// Jika sudah ada record pembayaran, redirect ke halaman konfirmasi
$stmtCheckPay = $conn->prepare("SELECT id FROM pembayaran WHERE booking_id = ? LIMIT 1");
$stmtCheckPay->execute([$bookingId]);
if ($stmtCheckPay->fetch()) {
    echo "<script>window.location.href='index.php?page=menunggu_konfirmasi&id=$bookingId';</script>";
    exit;
}

$totalHarga = hitung_total_harga($booking, $booking['durasi_bulan']);
$dpHarga = $totalHarga * 0.3;

$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenisPembayaran  = $_POST['jenis_pembayaran']  ?? 'Penuh';
    $metodePembayaran = $_POST['metode_pembayaran'] ?? 'QRIS';
    $jumlahBayar = ($jenisPembayaran === 'DP 30%') ? $dpHarga : $totalHarga;
    
    // Modal check
    if (!isset($_POST['setuju_ketentuan'])) {
        $errorMsg = "Anda harus menyetujui kebijakan dan ketentuan.";
    }

    if (empty($errorMsg)) {
        try {
            $cols = $conn->query("SHOW COLUMNS FROM pembayaran LIKE 'jenis_pembayaran'")->fetch();
            if ($cols) {
                $stmtPay = $conn->prepare("INSERT INTO pembayaran (booking_id, tanggal_bayar, jumlah, jenis_pembayaran, metode, status, durasi_bulan) VALUES (?, CURDATE(), ?, ?, ?, 'menunggu_verifikasi', ?)");
                $stmtPay->execute([$bookingId, $jumlahBayar, $jenisPembayaran, $metodePembayaran, $booking['durasi_bulan']]);
            } else {
                $stmtPay = $conn->prepare("INSERT INTO pembayaran (booking_id, tanggal_bayar, jumlah, metode, status, durasi_bulan) VALUES (?, CURDATE(), ?, ?, 'menunggu_verifikasi', ?)");
                $stmtPay->execute([$bookingId, $jumlahBayar, $metodePembayaran . ' - ' . $jenisPembayaran, $booking['durasi_bulan']]);
            }

            // Update status booking ke 'menunggu_dp' agar langsung muncul di dashboard admin
            $stmtUpdB = $conn->prepare("UPDATE booking SET status = 'menunggu_dp' WHERE id = ?");
            $stmtUpdB->execute([$bookingId]);

            echo "<script>window.location.href='index.php?page=menunggu_konfirmasi&id=$bookingId';</script>";
            exit;
        } catch (PDOException $e) {
            $errorMsg = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
        }
    }
}
?>

<style>
    body { background-color: #EEEADF; } /* Cream background from mockup */
    .app-navbar { position: relative !important; background: #EEEADF !important; }
    .navbar-logo, .navbar-menu a, .login-link, .register-btn, .auth-separator { color: #1f2937 !important; }
    .nav-arrow { stroke: #1f2937 !important; }
    .mobile-toggle svg { stroke: #1f2937 !important; }

    .payment-layout {
        max-width: 1000px;
        margin: 40px auto 100px;
        display: flex;
        gap: 40px;
        align-items: flex-start;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        background: #1f2937;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 30px;
    }
    
    .btn-back:hover { background: #111827; color: white; }

    .payment-left { flex: 1; }
    .payment-right { width: 380px; flex-shrink: 0; }

    .page-title {
        font-size: 24px;
        font-weight: 800;
        color: #172554;
        margin-bottom: 8px;
    }

    .page-subtitle {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 40px;
    }

    .section-label {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
    }

    /* Selection Cards */
    .opt-card {
        background: white;
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
    }

    .opt-card.active {
        background: #c3bca8; /* Based on the grayish active color in mockup */
        border-color: #a39c87;
    }

    .opt-card-title {
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }
    
    .opt-card-sub {
        font-size: 13px;
        color: #6b7280;
    }
    
    .opt-card-price {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
    }

    .opt-card.active .opt-card-title,
    .opt-card.active .opt-card-sub,
    .opt-card.active .opt-card-price {
        color: #1f2937;
    }

    .check-icon {
        display: none;
    }

    .opt-card.active .check-icon {
        display: block;
    }

    /* Accordion Style Cards */
    .acc-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .acc-card:hover { border-color: #d1d5db; }
    
    .acc-card.active {
        background: #c3bca8;
        border-color: #a39c87;
    }

    .acc-icon {
        width: 32px;
        height: 32px;
        background: #f3f4f6;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .acc-title {
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
        flex: 1;
    }

    /* Summary Card */
    .summary-card {
        background: #2b3544; /* Dark blue/gray */
        border-radius: 20px;
        padding: 30px;
        color: white;
    }

    .sum-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 24px;
        color: #d1d5db;
    }

    .sum-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        margin-bottom: 12px;
    }

    .sum-label { color: #9ca3af; }
    .sum-val { color: white; font-weight: 500; }

    .sum-total-label {
        font-size: 13px;
        color: #9ca3af;
        margin-top: 30px;
        margin-bottom: 4px;
    }

    .sum-total-val {
        font-size: 24px;
        font-weight: 800;
        color: white;
        margin-bottom: 16px;
    }

    .sum-timer {
        font-size: 12px;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 30px;
    }

    .qr-container {
        background: white;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        margin-bottom: 20px;
    }

    .qr-container img {
        width: 100%;
        max-width: 200px;
        display: block;
        margin: 0 auto;
    }

    .btn-submit {
        background: #EEEADF;
        color: #1f2937;
        width: 100%;
        border: none;
        padding: 14px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-submit:hover { background: white; }

    /* Modal Overlay */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .modal-overlay.active { display: flex; }

    .modal-card {
        background: #EEEADF;
        border-radius: 20px;
        padding: 40px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }

    .modal-title {
        font-size: 20px;
        font-weight: 800;
        color: #172554;
        margin-bottom: 8px;
    }

    .modal-subtitle {
        font-size: 14px;
        color: #4b5563;
        margin-bottom: 24px;
    }

    .modal-box {
        background: transparent;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 24px;
        font-size: 13px;
        color: #374151;
        line-height: 1.6;
        margin-bottom: 24px;
    }

    .modal-actions {
        display: flex;
        gap: 16px;
        margin-top: 24px;
    }

    .btn-outline-dark {
        flex: 1;
        background: transparent;
        border: 1px solid #d1d5db;
        color: #4b5563;
        padding: 14px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
    }

    .btn-solid-dark {
        flex: 1;
        background: #4b5563;
        border: none;
        color: white;
        padding: 14px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .payment-layout { flex-direction: column; padding: 0 20px; }
        .payment-right { width: 100%; }
    }
</style>

<div style="max-width: 1000px; margin: 40px auto 0;">
    <a href="index.php?page=konfirmasi_pesanan&id=<?= $bookingId ?>" class="btn-back">
        <i data-lucide="chevron-left" style="width: 16px; height: 16px; margin-right: 4px;"></i> Kembali
    </a>
</div>

<?php if (!empty($errorMsg)): ?>
    <div style="max-width: 1000px; margin: 0 auto;">
        <div class="alert alert-danger" style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 12px; font-size: 14px; margin-bottom: 20px;">
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    </div>
<?php endif; ?>

<form id="paymentForm" method="POST" enctype="multipart/form-data">
    <!-- Hidden inputs to store selection -->
    <input type="hidden" name="jenis_pembayaran" id="input_jenis" value="Penuh">
    <input type="hidden" name="metode_pembayaran" id="input_metode" value="QRIS">

    <div class="payment-layout">
        <!-- LEFT COLUMN -->
        <div class="payment-left">
            <div class="page-title">Detail Pemesanan</div>
            <div class="page-subtitle">Pilih metode pembayaran</div>

            <div class="section-label">Jenis Pembayaran</div>
            
            <div class="opt-card active" onclick="setJenis('Penuh', <?= $totalHarga ?>)" id="card_penuh">
                <div>
                    <div class="opt-card-title">Bayar Penuh</div>
                    <div class="opt-card-sub">Lunas Sekaligus</div>
                    <div class="opt-card-price" style="margin-top: 12px;">Rp <?= number_format($totalHarga, 0, ',', '.') ?></div>
                </div>
                <div class="check-icon">
                    <i data-lucide="check" style="width: 20px; height: 20px;"></i>
                </div>
            </div>

            <div class="opt-card" style="background: white;" onclick="setJenis('DP 30%', <?= $dpHarga ?>)" id="card_dp">
                <div>
                    <div class="opt-card-title">DP 30%</div>
                    <div class="opt-card-sub">Bayar Uang Muka dulu</div>
                    <div class="opt-card-price" style="margin-top: 12px;">Rp <?= number_format($dpHarga, 0, ',', '.') ?></div>
                </div>
                <div class="check-icon">
                    <i data-lucide="check" style="width: 20px; height: 20px;"></i>
                </div>
            </div>

            <div class="section-label" style="margin-top: 30px;">Metode Pembayaran</div>
            
            <div class="acc-card active" onclick="setMetode('QRIS')" id="metode_qris">
                <div class="acc-icon"><i data-lucide="qr-code" style="width: 18px; height: 18px; color: #1f2937;"></i></div>
                <div class="acc-title">QRIS</div>
                <div class="check-icon"><i data-lucide="check" style="width: 16px; height: 16px;"></i></div>
            </div>

            <div class="acc-card" onclick="setMetode('E-Wallet')" id="metode_ewallet">
                <div class="acc-icon"><i data-lucide="wallet" style="width: 18px; height: 18px; color: #1f2937;"></i></div>
                <div class="acc-title">E-Wallet</div>
                <div class="check-icon"><i data-lucide="check" style="width: 16px; height: 16px;"></i></div>
            </div>

            <div class="acc-card" onclick="setMetode('Transfer Bank')" id="metode_bank">
                <div class="acc-icon"><i data-lucide="arrow-right-left" style="width: 18px; height: 18px; color: #1f2937;"></i></div>
                <div class="acc-title">Transfer Bank</div>
                <div class="check-icon"><i data-lucide="check" style="width: 16px; height: 16px;"></i></div>
            </div>

        </div>

        <!-- RIGHT COLUMN -->
        <div class="payment-right">
            <div class="summary-card">
                <div class="sum-title">RINGKASAN</div>
                
                <div class="sum-row">
                    <span class="sum-label">Harga Sewa / Bulan</span>
                    <span class="sum-val">Rp <?= number_format($booking['harga_per_bulan'], 0, ',', '.') ?></span>
                </div>
                <div class="sum-row">
                    <span class="sum-label">Jenis</span>
                    <span class="sum-val" id="sum_jenis">Penuh</span>
                </div>
                <div class="sum-row">
                    <span class="sum-label">Metode</span>
                    <span class="sum-val" id="sum_metode">QRIS</span>
                </div>

                <div class="sum-total-label">Total Bayar</div>
                <div class="sum-total-val" id="sum_total">Rp <?= number_format($totalHarga, 0, ',', '.') ?></div>

                <div class="sum-timer">
                    <i data-lucide="clock" style="width: 14px; height: 14px;"></i> Berlaku 24 jam setelah dibuat
                </div>

                <div class="qr-container" id="qr_box">
                    <img src="frontend/assets/image/barcode.jpg" alt="QRIS Barcode">
                </div>
                
                <div class="sum-timer" id="dana_info" style="display:none; text-align: center; flex-direction:column; background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; color: #1f2937;">
                    <div style="font-size: 14px; margin-bottom: 5px; font-weight: 600;">Transfer ke DANA</div>
                    <div style="font-size: 20px; font-weight: 800; letter-spacing: 1px;">085933675790</div>
                    <div style="font-size: 13px; margin-top: 5px; color: #6b7280;">a/n ABD KHOLIK</div>
                </div>

                <div class="sum-timer" id="bank_info" style="display:none; text-align: center; flex-direction:column; background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; color: #1f2937;">
                    <div style="font-size: 14px; margin-bottom: 5px; font-weight: 600;">Transfer ke Rekening BRI</div>
                    <div style="font-size: 20px; font-weight: 800; letter-spacing: 1px;">152401000931531</div>
                    <div style="font-size: 13px; margin-top: 5px; color: #6b7280;">a/n ABD KHOLIK</div>
                </div>

                <!-- Info Tambahan -->
                <div style="margin-bottom: 20px;">
                    <p style="font-size: 12px; color: #d1d5db; line-height: 1.5;">Anda akan diarahkan ke halaman status untuk mengunggah bukti transfer setelah menekan tombol di bawah.</p>
                </div>

                <button type="button" class="btn-submit" onclick="showModal()">Lanjutkan Pembayaran</button>
            </div>
        </div>
    </div>

    <!-- MODAL KETENTUAN (Overlay Setelah Pembayaran) -->
    <div class="modal-overlay" id="ketentuanModal">
        <div class="modal-card">
            <div class="modal-title">KETENTUAN</div>
            <div class="modal-subtitle">Pastikan Anda Membaca Ketentuan dengan Teliti</div>

            <div class="modal-box">
                <p style="margin-bottom: 12px;">Dalam proses pemesanan kamar di Kos Elmisarah, calon penghuni diwajibkan membayar <strong>deposit sebesar 30% dari total biaya sewa</strong> sebagai tanda jadi pemesanan. Deposit ini berfungsi untuk memastikan kamar telah dipesan dan tidak dialokasikan kepada pihak lain. Sisa pembayaran dapat dilunasi saat calon penghuni dalam perjalanan menuju lokasi atau ketika telah tiba dan menempati kamar.</p>
                <p>Apabila terjadi pembatalan, calon penghuni diharapkan segera menginformasikan kepada pengelola kos. <strong>Deposit yang telah dibayarkan tidak dapat dikembalikan</strong> karena dianggap sebagai tanda jadi pemesanan. Namun, dalam kondisi tertentu yang tidak dapat dihindari, calon penghuni dapat berkomunikasi dengan pengelola untuk mencari solusi terbaik.</p>
            </div>

            <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; margin-bottom: 20px;">
                <input type="checkbox" name="setuju_ketentuan" id="setujuCheck" value="1" style="margin-top: 3px; width: 16px; height: 16px;" required>
                <span style="font-size: 13px; color: #4b5563;">Saya menyatakan data sudah benar dan menyetujui kebijakan dan ketentuan</span>
            </label>

            <div class="modal-actions">
                <button type="button" class="btn-outline-dark" onclick="hideModal()">Kembali</button>
                <button type="submit" class="btn-solid-dark" id="btnConfirm" disabled>Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</form>

<script>
    function formatRp(angka) {
        return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function setJenis(jenis, harga) {
        document.getElementById('input_jenis').value = jenis;
        document.getElementById('sum_jenis').innerText = jenis;
        document.getElementById('sum_total').innerText = formatRp(harga);

        // UI updates
        document.getElementById('card_penuh').classList.remove('active');
        document.getElementById('card_dp').classList.remove('active');
        document.getElementById('card_penuh').style.background = 'white';
        document.getElementById('card_dp').style.background = 'white';

        if(jenis === 'Penuh') {
            document.getElementById('card_penuh').classList.add('active');
            document.getElementById('card_penuh').style.background = '#c3bca8';
        } else {
            document.getElementById('card_dp').classList.add('active');
            document.getElementById('card_dp').style.background = '#c3bca8';
        }
    }

    function setMetode(metode) {
        document.getElementById('input_metode').value = metode;
        document.getElementById('sum_metode').innerText = metode;

        // UI updates
        document.getElementById('metode_qris').classList.remove('active');
        document.getElementById('metode_ewallet').classList.remove('active');
        document.getElementById('metode_bank').classList.remove('active');
        
        document.getElementById('metode_qris').style.background = 'white';
        document.getElementById('metode_ewallet').style.background = 'white';
        document.getElementById('metode_bank').style.background = 'white';

        let activeId = '';
        if(metode === 'QRIS') activeId = 'metode_qris';
        else if(metode === 'E-Wallet') activeId = 'metode_ewallet';
        else if(metode === 'Transfer Bank') activeId = 'metode_bank';

        const el = document.getElementById(activeId);
        el.classList.add('active');
        el.style.background = '#c3bca8';

        if (metode === 'QRIS') {
            document.getElementById('qr_box').style.display = 'block';
            document.getElementById('dana_info').style.display = 'none';
            document.getElementById('bank_info').style.display = 'none';
        } else if (metode === 'E-Wallet') {
            document.getElementById('qr_box').style.display = 'none';
            document.getElementById('dana_info').style.display = 'flex';
            document.getElementById('bank_info').style.display = 'none';
        } else {
            document.getElementById('qr_box').style.display = 'none';
            document.getElementById('dana_info').style.display = 'none';
            document.getElementById('bank_info').style.display = 'flex';
        }
    }

    // Modal Logic
    function showModal() {
        document.getElementById('ketentuanModal').classList.add('active');
    }

    function hideModal() {
        document.getElementById('ketentuanModal').classList.remove('active');
    }

    // Checkbox logic
    document.getElementById('setujuCheck').addEventListener('change', function() {
        document.getElementById('btnConfirm').disabled = !this.checked;
        if(this.checked) {
            document.getElementById('btnConfirm').style.opacity = '1';
        } else {
            document.getElementById('btnConfirm').style.opacity = '0.5';
        }
    });

    // Initialize button state
    document.getElementById('btnConfirm').style.opacity = '0.5';
    
    // Set initial colors
    document.getElementById('card_penuh').style.background = '#c3bca8';
    document.getElementById('metode_qris').style.background = '#c3bca8';
</script>
