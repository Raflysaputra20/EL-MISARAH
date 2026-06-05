<section class="room-section" id="daftar-kamar">
    <div class="container-custom">
        <h2 class="section-title">Tipe Kamar Elmi Sarah</h2>

        <?php if (empty($kamar)): ?>
            <p style="color:#888; text-align:center;">Belum ada data kamar.</p>
        <?php else: ?>
            <div class="room-grid">
                <?php 
                $displayKamar = array_slice($kamar, 0, 2);
                foreach ($displayKamar as $k): 
                    $foto = !empty($k['foto']) && file_exists(__DIR__ . '/../assets/image/' . $k['foto']) 
                        ? 'frontend/assets/image/' . $k['foto'] 
                        : null;

                    /* Pisahkan fasilitas menjadi array bersih */
                    $fasilitasRaw = !empty($k['fasilitas']) ? array_map('trim', explode(',', $k['fasilitas'])) : [];

                    /* Pisahkan luas (m2) dari fasilitas biasa */
                    $fasilitasNormal = [];
                    $luasItem = null;
                    foreach ($fasilitasRaw as $item) {
                        if (preg_match('/\d+\s*m2/i', $item)) {
                            $luasItem = $item;
                        } else {
                            $fasilitasNormal[] = $item;
                        }
                    }

                    /* Baris 1 = 4 fasilitas pertama, Baris 2 = sisanya + luas */
                    $baris1 = array_slice($fasilitasNormal, 0, 4);
                    $baris2 = array_slice($fasilitasNormal, 4);
                    if ($luasItem) $baris2[] = $luasItem;
                ?>
                    <div class="room-card">
                        <!-- FOTO KAMAR -->
                        <div class="room-image-wrap">
                            <?php if ($foto): ?>
                                <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($k['tipe']) ?>" class="room-img">
                            <?php else: ?>
                                <div class="room-img-placeholder"></div>
                            <?php endif; ?>
                        </div>

                        <!-- INFO KAMAR -->
                        <div class="room-body">
                            <p class="room-owner">Elmi Sarah</p>
                            <h3 class="room-title"><?= htmlspecialchars($k['tipe'] ?? '-') ?></h3>

                            <!-- Baris Fasilitas 1 -->
                            <?php if (!empty($baris1)): ?>
                            <div class="room-facilities">
                                <?php foreach ($baris1 as $f): ?>
                                    <span class="fac-item">
                                        <i data-lucide="<?= getFrontIcon($f) ?>"></i>
                                        <?= htmlspecialchars($f) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Baris Fasilitas 2 (sisa + luas) -->
                            <?php if (!empty($baris2)): ?>
                            <div class="room-facilities" style="margin-bottom: 28px;">
                                <?php foreach ($baris2 as $f): ?>
                                    <span class="fac-item">
                                        <i data-lucide="<?= preg_match('/\d+\s*m2/i', $f) ? 'layout-grid' : getFrontIcon($f) ?>"></i>
                                        <?= htmlspecialchars($f) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Harga & Tombol -->
                            <div class="room-footer">
                                <h4 class="room-price">Rp <?= number_format($k['harga'] ?? 0, 0, ',', '.') ?>/bln</h4>
                                <a href="index.php?page=detail_kamar&id=<?= $k['id'] ?>" class="btn-detail">
                                    Lihat Detail <i data-lucide="arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>