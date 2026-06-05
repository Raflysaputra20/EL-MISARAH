<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['user_id'])) {
    echo "header('Location: index.php?login_modal=1&msg=auth_required'); exit;";
    exit;
}

$userId = $_SESSION['user_id'];

// Delete booking handler
if (isset($_POST['delete_booking_id'])) {
    $delId = $_POST['delete_booking_id'];
    $stmtDel = $conn->prepare("DELETE FROM booking WHERE id = ? AND user_id = ?");
    $stmtDel->execute([$delId, $userId]);
    echo "<script>window.location.href='index.php?page=profil';</script>";
    exit;
}

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil riwayat booking dengan relasi
$stmtBooking = $conn->prepare("
    SELECT 
        b.id,
        b.tanggal_booking,
        b.tanggal_masuk,
        b.durasi_bulan,
        b.status,
        k.tipe,
        k.harga,
        p.status as status_bayar,
        p.metode
    FROM booking b
    JOIN kamar k ON b.kamar_id = k.id
    LEFT JOIN (
        SELECT booking_id, status, metode 
        FROM pembayaran 
        WHERE id IN (SELECT MAX(id) FROM pembayaran GROUP BY booking_id)
    ) p ON b.id = p.booking_id
    WHERE b.user_id = ?
    ORDER BY b.id DESC
");
$stmtBooking->execute([$userId]);
$bookings = $stmtBooking->fetchAll(PDO::FETCH_ASSOC);

$joinDateRaw = $user['created_at'] ?? date('Y-m-d');
$joinDate = date('d F Y', strtotime($joinDateRaw));
// Translating months
$months = ['January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'];
$joinDate = strtr($joinDate, $months);
?>
<style>
    .app-navbar { position: relative !important; background: #EEEADF !important; }
    .navbar-logo, .navbar-menu a, .login-link, .register-btn, .auth-separator { color: #1f2937 !important; }
    .nav-arrow { stroke: #1f2937 !important; }
    .mobile-toggle svg { stroke: #1f2937 !important; }
    .dropdown-menu { border: 1px solid #e5e7eb; }

    .page-container {
        max-width: 900px;
        margin: 40px auto 80px;
        padding: 0 20px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 20px;
    }

    .profil-card {
        background: #1f2937; /* matches design */
        border-radius: 24px;
        padding: 40px;
        display: flex;
        align-items: center;
        gap: 40px;
        margin-bottom: 50px;
    }

    .profil-avatar {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        object-fit: cover;
    }

    .profil-avatar-placeholder {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: #374151;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 50px;
        font-weight: 700;
    }

    .profil-details {
        flex: 1;
        color: white;
    }

    .profil-name {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 4px 0;
    }

    .profil-date {
        font-size: 13px;
        color: #9ca3af;
        margin: 0 0 20px 0;
    }

    .profil-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px 24px;
    }

    .profil-item {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        color: #e5e7eb;
    }

    .riwayat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        transition: box-shadow 0.2s;
    }

    .riwayat-card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .riwayat-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 16px;
    }

    .riwayat-date-label {
        font-size: 13px;
        font-weight: 800;
        color: #1f2937;
    }

    .riwayat-date-val {
        font-size: 12px;
        color: #6b7280;
    }

    .riwayat-price {
        font-size: 15px;
        font-weight: 800;
        color: #1f2937;
        text-align: right;
    }

    .riwayat-status {
        font-size: 11px;
        color: #6b7280;
        text-align: right;
        margin-top: 2px;
    }

    .riwayat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }

    .riwayat-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .riwayat-item-icon {
        color: #1f2937;
        margin-top: 2px;
    }

    .riwayat-item-label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 2px;
    }

    .riwayat-item-val {
        font-size: 13px;
        font-weight: 800;
        color: #1f2937;
    }

    @media (max-width: 768px) {
        .profil-card {
            flex-direction: column;
            text-align: center;
            padding: 30px 20px;
            gap: 20px;
        }
        .profil-grid {
            grid-template-columns: 1fr;
            text-align: left;
        }
        .riwayat-grid {
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    }
</style>

<div class="page-container">
    
    <h2 class="section-title">Profil Anda</h2>

    <div class="profil-card">
        <?php if (!empty($user['foto']) && file_exists(__DIR__ . '/../../../backend/uploads/profil/' . basename($user['foto']))): ?>
            <img src="backend/uploads/profil/<?= htmlspecialchars(basename($user['foto'])) ?>" alt="Foto Profil" class="profil-avatar">
        <?php else: ?>
            <?php 
                $firstName = explode(' ', trim($user['nama']))[0];
            ?>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($firstName) ?>&background=374151&color=fff&bold=true&size=200" alt="Foto Profil" class="profil-avatar">
        <?php endif; ?>

        <div class="profil-details">
            <h3 class="profil-name"><?= htmlspecialchars($user['nama']) ?></h3>
            <p class="profil-date"><?= htmlspecialchars($joinDate) ?></p>

            <div class="profil-grid">
                <div class="profil-item">
                    <i data-lucide="phone" style="width: 18px; height: 18px;"></i>
                    <span><?= htmlspecialchars($user['no_hp'] ?: 'Belum diisi') ?></span>
                </div>
                <div class="profil-item">
                    <i data-lucide="mail" style="width: 18px; height: 18px;"></i>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="profil-item">
                    <i data-lucide="map-pin" style="width: 18px; height: 18px;"></i>
                    <span><?= htmlspecialchars($user['alamat'] ?: 'Belum diisi') ?></span>
                </div>
                <div class="profil-item">
                    <i data-lucide="circle-dashed" style="width: 18px; height: 18px;"></i>
                    <span style="text-transform: capitalize;"><?= htmlspecialchars($user['role'] === 'penghuni' ? 'Mahasiswa / Penghuni' : 'User') ?></span>
                </div>
            </div>
        </div>
    </div>

    <h2 class="section-title">Riwayat Pemesanan</h2>

    <?php if (empty($bookings)): ?>
        <div style="text-align: center; padding: 40px; background: white; border: 1px solid #e5e7eb; border-radius: 16px;">
            <i data-lucide="clipboard-list" style="width: 48px; height: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
            <h4 style="font-weight: 700; color: #374151;">Belum Ada Pemesanan</h4>
            <p style="color: #6b7280; margin-bottom: 24px; font-size: 14px;">Anda belum melakukan pemesanan kamar kost sama sekali.</p>
            <a href="index.php#daftar-kamar" class="btn" style="background-color: #1f2937; color: white; padding: 10px 24px; border-radius: 8px; font-weight: 600;">Lihat Kamar</a>
        </div>
    <?php else: ?>
        <?php foreach ($bookings as $b): 
            $statusStr = strtolower($b['status']);
            
            // Build Status text
            $paymentMethod = !empty($b['metode']) ? ' - ' . ucfirst($b['metode']) : '';
            $bayarInfo = "Menunggu Pembayaran"; // Default jika belum ada data pembayaran

            if ($b['status_bayar'] === 'valid') {
                if ($statusStr === 'disetujui' || $statusStr === 'selesai') {
                    $bayarInfo = "Disetujui" . $paymentMethod;
                } else {
                    $bayarInfo = "Menunggu Konfirmasi" . $paymentMethod;
                }
            } elseif ($b['status_bayar'] === 'menunggu_verifikasi') {
                $bayarInfo = "Menunggu Verifikasi" . $paymentMethod;
            } elseif ($statusStr === 'ditolak' || $b['status_bayar'] === 'tidak_valid') {
                $bayarInfo = "Ditolak / Batal";
            }
            
            $targetLink = "index.php?page=konfirmasi_pesanan&id=" . $b['id'];
            if ($b['status_bayar']) {
                $targetLink = "index.php?page=menunggu_konfirmasi&id=" . $b['id'];
            }
        ?>
        <div class="riwayat-card" onclick="window.location.href='<?= $targetLink ?>'" style="cursor: pointer; position: relative;">
            <div class="riwayat-top">
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div>
                        <div class="riwayat-date-label">Tanggal Pesanan</div>
                        <div class="riwayat-date-val"><?= !empty($b['tanggal_booking']) ? date('d-m-Y', strtotime($b['tanggal_booking'])) : '-' ?></div>
                    </div>
                    <!-- Delete Button -->
                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus riwayat pesanan ini?');">
                        <input type="hidden" name="delete_booking_id" value="<?= $b['id'] ?>">
                        <button type="submit" onclick="event.stopPropagation();" style="background: none; border: none; color: #ef4444; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; padding: 0;">
                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Hapus Riwayat
                        </button>
                    </form>
                </div>
                <div style="text-align: right;">
                    <div class="riwayat-price">Rp <?= number_format($b['harga'] ?? 0, 0, '.', '.') ?></div>
                    <div class="riwayat-status" style="<?= ($statusStr === 'ditolak' || $b['status_bayar'] === 'tidak_valid') ? 'color: #ef4444;' : '' ?>"><?= htmlspecialchars($bayarInfo) ?></div>
                </div>
            </div>
            <div class="riwayat-grid">
                <div class="riwayat-item">
                    <i data-lucide="home" class="riwayat-item-icon" style="width: 20px; height: 20px;"></i>
                    <div>
                        <div class="riwayat-item-label">Tipe</div>
                        <div class="riwayat-item-val"><?= htmlspecialchars($b['tipe'] ?? '-') ?></div>
                    </div>
                </div>
                <div class="riwayat-item">
                    <i data-lucide="calendar" class="riwayat-item-icon" style="width: 20px; height: 20px;"></i>
                    <div>
                        <div class="riwayat-item-label">Mulai</div>
                        <div class="riwayat-item-val"><?= !empty($b['tanggal_masuk']) ? date('d-m-Y', strtotime($b['tanggal_masuk'])) : '-' ?></div>
                    </div>
                </div>
                <div class="riwayat-item">
                    <i data-lucide="clock" class="riwayat-item-icon" style="width: 20px; height: 20px;"></i>
                    <div>
                        <div class="riwayat-item-label">Durasi</div>
                        <div class="riwayat-item-val"><?= htmlspecialchars($b['durasi_bulan'] ?? 0) ?> Bulan</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
