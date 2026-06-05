<?php
// room-card.php - Compatible with type-based kamar system
// $room comes from kamar table (tipe-based, no nomor_kamar/status)

$foto = !empty($room['foto']) && file_exists(__DIR__ . '/../assets/image/' . $room['foto']) 
    ? 'frontend/assets/image/' . $room['foto'] 
    : 'frontend/assets/image/kost.png';

$tersedia = $room['tersedia'] ?? 0;
$total = $room['total_kamar'] ?? 0;
?>
<div class="col-12 col-md-6 col-lg-4 mb-3">
    <div class="card room-card h-100 shadow-sm border-0">
        <img src="<?= htmlspecialchars($foto) ?>" class="card-img-top" alt="Foto Kamar" style="height: 200px; object-fit: cover;">
        <div class="card-body d-flex flex-column">

            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <h5 class="card-title fw-bold mb-0">
                    <?= htmlspecialchars($room["tipe"] ?? "-") ?>
                </h5>

                <span class="badge <?= $tersedia > 0 ? 'text-bg-success' : 'text-bg-danger' ?> status-badge">
                    <?= $tersedia > 0 ? $tersedia . ' Tersedia' : 'Penuh' ?>
                </span>
            </div>

            <p class="room-meta mb-2" style="color: #11a654; font-weight: 700;">
                Rp <?= number_format((float)($room["harga"] ?? 0), 0, ',', '.'); ?> / bulan
            </p>

            <p class="room-meta mb-3" style="font-size:13px; color:#6b7280;">
                <?php 
                    $fasilitasArr = !empty($room['fasilitas']) ? array_map('trim', explode(',', $room['fasilitas'])) : [];
                    foreach(array_slice($fasilitasArr, 0, 5) as $f): 
                ?>
                    <span class="me-2 d-inline-block mb-1"><i data-lucide="<?= getFrontIcon($f) ?>" style="width:14px; height:14px;"></i> <?= htmlspecialchars($f) ?></span>
                <?php endforeach; ?>
            </p>

            <div class="mt-auto pt-2">
                <a href="index.php?page=detail_kamar&id=<?= $room['id'] ?>" class="btn w-100 fw-bold" style="background-color: transparent; border: 1px solid #11a654; color: #11a654; border-radius: 8px;">
                    Lihat Detail
                </a>
            </div>

        </div>
    </div>
</div>