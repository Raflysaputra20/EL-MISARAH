<?php
if (!isset($_SESSION['user_id'])) {
    echo "header('Location: index.php?login_modal=1&msg=auth_required'); exit;";
    exit;
}

$userId = $_SESSION['user_id'];

// Ambil riwayat booking dengan relasi tipe kamar, kamar_nomor, dan status pembayaran terakhir
$stmt = $conn->prepare("
    SELECT 
        b.id,
        b.tanggal_booking,
        b.tanggal_masuk,
        b.durasi_bulan,
        b.status,
        k.tipe,
        k.harga,
        k.nomor_kamar,
        p.status as status_bayar,
        p.bukti_bayar
    FROM booking b
    JOIN kamar k ON b.kamar_id = k.id
    LEFT JOIN (
        SELECT booking_id, status, bukti_bayar 
        FROM pembayaran 
        WHERE id IN (SELECT MAX(id) FROM pembayaran GROUP BY booking_id)
    ) p ON b.id = p.booking_id
    WHERE b.user_id = ?
    ORDER BY b.id DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
    .riwayat-header {
        background-color: #1f2937;
        color: white;
        padding: 60px 0 40px;
        text-align: center;
        margin-bottom: 40px;
    }
    
    .booking-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid #f3f4f6;
        padding: 24px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.2s;
    }
    
    .booking-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.06);
    }
    
    .booking-info h5 {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 6px;
        font-size: 18px;
    }
    
    .booking-info p {
        color: #6b7280;
        margin-bottom: 4px;
        font-size: 14px;
    }
    
    .booking-price {
        font-weight: 700;
        color: #11a654;
        font-size: 18px;
    }
    
    .status-badge {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        display: inline-block;
        text-transform: capitalize;
    }
    
    .status-pending { background-color: #fef3c7; color: #d97706; }
    .status-verifikasi { background-color: #e0f2fe; color: #0284c7; }
    .status-disetujui, .status-aktif { background-color: #e8f7f0; color: #11a654; }
    .status-ditolak { background-color: #fee2e2; color: #ef4444; }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    
    @media (max-width: 768px) {
        .booking-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }
        .booking-actions {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    }
</style>

<div class="riwayat-header">
    <div class="container">
        <h2 style="font-weight: 700; font-size: 32px;">Riwayat Booking</h2>
        <p style="opacity: 0.8; font-size: 15px;">Pantau status pengajuan pemesanan kamar Anda</p>
    </div>
</div>

<div class="container" style="max-width: 900px; padding-bottom: 80px;">
    
    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <i data-lucide="clipboard-list" style="width: 60px; height: 60px; color: #d1d5db; margin-bottom: 20px;"></i>
            <h4 style="font-weight: 700; color: #374151;">Belum Ada Pemesanan</h4>
            <p style="color: #6b7280; margin-bottom: 24px;">Anda belum melakukan pemesanan kamar kost sama sekali.</p>
            <a href="index.php#daftar-kamar" class="btn" style="background-color: #11a654; color: white; padding: 10px 24px; border-radius: 8px; font-weight: 600;">Lihat Kamar</a>
        </div>
    <?php else: ?>
        
        <?php foreach ($bookings as $b): 
            $statusStr = strtolower($b['status']);
            $badgeClass = 'status-pending';
            $statusLabel = 'Menunggu Konfirmasi';
            
            if ($statusStr === 'disetujui' || $statusStr === 'aktif') {
                $badgeClass = 'status-disetujui';
                $statusLabel = 'Disetujui';
            } elseif ($statusStr === 'menunggu_dp') {
                if (!empty($b['bukti_bayar'])) {
                    $badgeClass = 'status-verifikasi';
                    $statusLabel = 'Menunggu Verifikasi';
                } else {
                    $badgeClass = 'status-pending';
                    $statusLabel = 'Menunggu Pembayaran';
                }
            } elseif ($statusStr === 'ditolak') {
                $badgeClass = 'status-ditolak';
                $statusLabel = 'Ditolak';
            } elseif ($statusStr === 'dibatalkan') {
                $badgeClass = 'status-ditolak';
                $statusLabel = 'Dibatalkan';
            } elseif ($statusStr === 'selesai') {
                $badgeClass = 'status-disetujui';
                $statusLabel = 'Selesai';
            }

            // Determine target link
            $targetLink = "index.php?page=konfirmasi_pesanan&id=" . $b['id'];
            if ($b['status_bayar'] || in_array($statusStr, ['ditolak', 'menunggu_dp'])) {
                $targetLink = "index.php?page=menunggu_konfirmasi&id=" . $b['id'];
            }
            
            $isClickable = !in_array($statusStr, ['dibatalkan', 'selesai']);
        ?>
            <div class="booking-card" <?= $isClickable ? "onclick=\"window.location.href='$targetLink'\" style=\"cursor: pointer;\"" : "onclick=\"alert('Pemesanan ini sudah " . $statusLabel . " dan tidak dapat diubah lagi.');\" style=\"cursor: not-allowed; opacity: 0.8;\"" ?>>
                <div class="booking-info">
                    <h5>
                        Tipe <?= htmlspecialchars($b['tipe'] ?? '') ?>
                        <?php if (!empty($b['nomor_kamar'])): ?>
                            <span style="font-weight: 500; color: #6b7280; font-size: 15px;">(Kamar <?= htmlspecialchars($b['nomor_kamar']) ?>)</span>
                        <?php endif; ?>
                    </h5>
                    
                    <div style="display: flex; gap: 20px; margin-top: 10px;">
                        <p>
                            <i data-lucide="calendar" style="width: 14px; height: 14px; margin-right: 4px; display: inline-block; vertical-align: text-bottom;"></i>
                            <strong>Tgl Masuk:</strong> <?= !empty($b['tanggal_masuk']) ? date('d M Y', strtotime($b['tanggal_masuk'])) : '-' ?>
                        </p>
                        <p>
                            <i data-lucide="clock" style="width: 14px; height: 14px; margin-right: 4px; display: inline-block; vertical-align: text-bottom;"></i>
                            <strong>Durasi:</strong> <?= htmlspecialchars($b['durasi_bulan'] ?? 0) ?> Bulan
                        </p>
                    </div>
                    
                    <p style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                        Diajukan pada: <?= !empty($b['tanggal_booking']) ? date('d M Y', strtotime($b['tanggal_booking'])) : '-' ?>
                    </p>
                </div>
                
                <div class="booking-actions text-md-end text-start mt-3 mt-md-0">
                    <div class="booking-price mb-2">Rp <?= number_format($b['harga'] ?? 0, 0, ',', '.') ?> <span style="font-size: 12px; color: #9ca3af; font-weight: 400;">/ bln</span></div>
                    <div class="status-badge <?= $badgeClass ?>">
                        <?= $statusLabel ?>
                    </div>
                    
                    <?php 
                    $showPayButton = ($statusStr === 'disetujui' && ($b['status_bayar'] ?? '') !== 'valid' && ($b['status_bayar'] ?? '') !== 'pending');
                    $canCancel = in_array($statusStr, ['pending', 'disetujui']) && ($b['status_bayar'] ?? '') !== 'valid' && ($b['status_bayar'] ?? '') !== 'pending';

                    if ($showPayButton): 
                    ?>
                        <div class="mt-2 d-flex flex-wrap gap-2 justify-content-md-end">
                            <a href="index.php?page=konfirmasi_pembayaran&id=<?= $b['id'] ?>" class="btn btn-sm" style="background-color: #1f2937; color: white; font-size: 12px; border-radius: 6px;" onclick="event.stopPropagation();">Bayar Sekarang</a>
                        </div>
                    <?php elseif ($statusStr === 'aktif'): ?>
                        <div class="mt-2">
                            <span class="badge bg-success" style="font-size: 11px;">Penghuni Aktif</span>
                        </div>
                    <?php elseif (($b['status_bayar'] ?? '') === 'pending'): ?>
                        <div class="mt-2">
                            <span class="badge bg-warning text-dark" style="font-size: 11px;">Menunggu Verifikasi Bayar</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($canCancel): ?>
                        <div class="mt-2 text-md-end">
                            <a href="index.php?page=batal_booking&action=batal&id=<?= $b['id'] ?>" class="btn btn-sm text-danger border border-danger bg-transparent mt-1" style="font-size: 11px; border-radius: 6px;" onclick="event.stopPropagation(); return confirm('Yakin ingin membatalkan pesanan ini?');">Batalkan Booking</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
    <?php endif; ?>
    
</div>
