<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM kamar WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header("Location: index.php");
    exit;
}

/* ── Ambil ketersediaan nomor kamar ─────────────── */
$kamarNomorList = [];
try {
    $stmtN = $conn->prepare("SELECT id, nomor_kamar as nomor, status FROM kamar WHERE tipe = ? ORDER BY CAST(nomor_kamar AS UNSIGNED) ASC, nomor_kamar ASC");
    $stmtN->execute([$room['tipe']]);
    $kamarNomorList = $stmtN->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

/* ── Gabungkan Semua Foto (6 Slot + Galeri Tambahan) ─────────────── */
$allGalleryPhotos = [];
if (!empty($room['foto'])) $allGalleryPhotos[] = $room['foto'];
if (!empty($room['foto_2'])) $allGalleryPhotos[] = $room['foto_2'];
if (!empty($room['foto_3'])) $allGalleryPhotos[] = $room['foto_3'];
if (!empty($room['foto_4'])) $allGalleryPhotos[] = $room['foto_4'];
if (!empty($room['foto_5'])) $allGalleryPhotos[] = $room['foto_5'];

try {
    $stmtG = $conn->prepare("SELECT foto FROM galeri_kamar WHERE tipe = ?");
    $stmtG->execute([$room['tipe']]);
    $extra = $stmtG->fetchAll(PDO::FETCH_COLUMN);
    $allGalleryPhotos = array_merge($allGalleryPhotos, $extra);
} catch (Exception $e) { }

$totalKamar    = count($kamarNomorList);
$totalTersedia = count(array_filter($kamarNomorList, fn($r) => strtolower($r['status']) === 'tersedia'));

// Cek apakah user sudah punya booking / penghuni aktif
$isBlockedFromBooking = false;
$roleStr = $_SESSION['role'] ?? '';
if (isset($_SESSION['user_id'])) {
    if ($roleStr === 'penghuni') {
        $isBlockedFromBooking = true;
    } else {
        try {
            $stCheck = $conn->prepare("SELECT id FROM booking WHERE user_id = ? AND status IN ('menunggu_dp', 'disetujui', 'aktif', 'selesai') LIMIT 1");
            $stCheck->execute([$_SESSION['user_id']]);
            if ($stCheck->fetch()) {
                $isBlockedFromBooking = true;
            }
        } catch(Exception $e) {}
    }
}

$hasPendingBooking = false;
if (isset($_SESSION['user_id']) && !$isBlockedFromBooking) {
    try {
        $stPend = $conn->prepare("SELECT id FROM booking WHERE user_id = ? AND status = 'pending' LIMIT 1");
        $stPend->execute([$_SESSION['user_id']]);
        if ($stPend->fetch()) {
            $hasPendingBooking = true;
        }
    } catch(Exception $e) {}
}

/* ── Fasilitas ──────────────────────────────────────────────────────── */
$fasilitasRaw = !empty($room['fasilitas']) ? array_map('trim', explode(',', $room['fasilitas'])) : [];
$fasilitasNormal = [];
$luasItem = null;
foreach ($fasilitasRaw as $item) {
    if (preg_match('/\d+\s*m2/i', $item)) {
        $luasItem = $item;
    } else {
        $fasilitasNormal[] = $item;
    }
}

/* ── Helper foto ────────────────────────────────────────────────────── */
function getImgPath($filename) {
    if (!empty($filename) && file_exists(__DIR__ . '/../../assets/image/' . $filename))
        return 'frontend/assets/image/' . $filename;
    return null;
}
function renderPhoto($filename, $height = '100%', $alt = 'Foto Kamar') {
    $p = getImgPath($filename);
    if ($p) return "<img src='".htmlspecialchars($p)."' alt='".htmlspecialchars($alt)."' style='width:100%;height:{$height};object-fit:cover;display:block;'>";
    return "<div class='dk-photo-placeholder'><i data-lucide='image'></i></div>";
}

$harga = (float)($room['harga'] ?? 0);
$deskripsi = !empty($room['deskripsi'])
    ? $room['deskripsi']
    : 'Tipe kamar standar yang nyaman untuk satu penghuni. Kamar ini dilengkapi dengan fasilitas dasar seperti tempat tidur, lemari pakaian, meja belajar, serta ventilasi atau jendela untuk sirkulasi udara yang baik. Tipe kamar ini cocok bagi mahasiswa atau pekerja yang mencari hunian praktis, nyaman, dan dengan harga yang lebih terjangkau.';

/* ── Denah ──────────────────────────────────────────────────────────── */
$denahPath = getImgPath($room['foto_denah'] ?? null) ?? 'frontend/assets/image/denah_kamar.png';
?>

<!-- Override navbar warna cream untuk halaman ini -->
<style>
.app-navbar {
    position: relative !important;
    background: #EEEADF !important;
}
.navbar-logo { color: #1f2937 !important; }
.navbar-menu a { color: #1f2937 !important; }
.nav-arrow { stroke: #1f2937 !important; }
.login-link { color: #1f2937 !important; }
.register-btn { color: #1f2937 !important; }
.auth-separator { color: #1f2937 !important; }
.mobile-toggle svg { stroke: #1f2937 !important; }
.dropdown-menu { border: 1px solid #e5e7eb; }

/* ─── DETAIL KAMAR ─────────────────────────────── */
.dk-wrap {
    max-width: 1140px;
    margin: 0 auto;
    padding: 32px 24px 80px;
}

/* Gallery */
.dk-gallery {
    display: grid;
    grid-template-columns: 1.6fr 1fr 1fr;
    grid-template-rows: 180px 180px;
    gap: 12px;
    margin-bottom: 12px;
    border-radius: 20px;
    overflow: hidden;
}

/* Thumbnail Scroll */
.dk-thumb-container {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 10px 0;
    margin-bottom: 30px;
    scrollbar-width: thin;
    scrollbar-color: #11a654 #f3f4f6;
}
.dk-thumb-container::-webkit-scrollbar { height: 6px; }
.dk-thumb-container::-webkit-scrollbar-thumb { background: #11a654; border-radius: 10px; }
.dk-thumb-item {
    flex: 0 0 100px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s;
}
.dk-thumb-item.active { border-color: #11a654; transform: scale(1.05); }
.dk-thumb-item img { width: 100%; height: 100%; object-fit: cover; }

.dk-main-photo {
    grid-column: 1 / 2;
    grid-row: 1 / 3;
    position: relative;
    background: #e5e7eb;
    overflow: hidden;
}

.dk-main-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
    cursor: pointer;
}
.dk-main-photo:hover img {
    transform: scale(1.05);
}

.dk-btn-back {
    position: absolute;
    top: 16px;
    left: 16px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    z-index: 2;
}

.dk-btn-back svg {
    width: 14px;
    height: 14px;
}

.dk-nav-btns {
    position: absolute;
    top: 16px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 2;
}

.dk-nav-btn {
    width: 36px;
    height: 36px;
    background: #fff;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.dk-nav-btn svg {
    width: 16px;
    height: 16px;
}

.dk-sub-photo {
    overflow: hidden;
    cursor: pointer;
    background: #e5e7eb;
}

.dk-sub-photo:nth-child(2) {
    grid-column: 2 / 4;
    grid-row: 1 / 2;
}

.dk-sub-photo:nth-child(3) {
    grid-column: 2 / 3;
    grid-row: 2 / 3;
}

.dk-sub-photo:nth-child(4) {
    grid-column: 3 / 4;
    grid-row: 2 / 3;
}

.dk-sub-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.dk-sub-photo:hover img {
    transform: scale(1.05);
}

.dk-photo-placeholder {
    width: 100%;
    height: 100%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}
.dk-photo-placeholder svg {
    width: 32px;
    height: 32px;
}

/* Info Section */
.dk-info {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 40px;
    align-items: flex-start;
}

/* LEFT */
.dk-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
}

.dk-title {
    margin: 0;
    font-size: 28px;
    font-weight: 900;
    color: #0a0a0a;
}

.dk-luas-badge {
    padding: 4px 14px;
    background: #f3f4f6;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    color: #4b5563;
    white-space: nowrap;
}

.dk-facilities {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 20px;
    margin-bottom: 28px;
}

.dk-fac {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    color: #374151;
}

.dk-fac svg {
    width: 17px;
    height: 17px;
    stroke-width: 1.8;
    color: #374151;
}

.dk-price {
    margin: 0 0 4px;
    font-size: 36px;
    font-weight: 900;
    color: #0a0a0a;
}

.dk-price-label {
    margin: 0 0 20px;
    font-size: 14px;
    color: #9ca3af;
}

.dk-desc {
    font-size: 14px;
    line-height: 1.7;
    color: #4b5563;
    margin-bottom: 32px;
}

.dk-cta-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.dk-btn-booking {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 28px;
    background: #1f2937;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s;
}
.dk-btn-booking:hover { background: #111; color: #fff; }



/* RIGHT */
/* Ketersediaan */
.dk-avail-box {
    background: #fff;
    border: 1px solid #f0f0f0;
    border-radius: 16px;
    padding: 20px 22px;
    margin-bottom: 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}

.dk-avail-title {
    margin: 0 0 2px;
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
}

.dk-avail-sub {
    margin: 0 0 16px;
    font-size: 12px;
    color: #9ca3af;
}

.dk-nomor-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.dk-nomor {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    line-height: 1;
}

.dk-nomor span {
    font-size: 8px;
    font-weight: 400;
    opacity: 0.9;
    margin-top: 2px;
}

.dk-nomor.tersedia { background: #11a654; }
.dk-nomor.terisi   { background: #ef4444; }

/* Harga + Denah */
.dk-price-denah {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.dk-price-cards {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dk-pcard {
    background: #f9fafb;
    border-radius: 12px;
    padding: 13px 16px;
    cursor: pointer;
    transition: background 0.15s;
    position: relative;
}

.dk-pcard.active {
    background: #EEEADF;
}

.dk-pcard-label {
    font-size: 11px;
    color: #9ca3af;
    margin: 0 0 3px;
    text-transform: lowercase;
}

.dk-pcard-price {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.dk-pcard-hemat {
    font-size: 11px;
    color: #11a654;
    font-weight: 600;
    margin: 2px 0 0;
}

.dk-pcard-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #1f2937;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 999px;
}

.dk-denah {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    background: #fff;
    display: flex;
    flex-direction: column;
}

.dk-denah img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
}

.dk-denah-label {
    text-align: center;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: #9ca3af;
    padding: 6px;
    border-top: 1px solid #e5e7eb;
    margin: 0;
}

/* Responsive */
@media (max-width: 900px) {
    .dk-info {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 700px) {
    .dk-gallery {
        grid-template-columns: 1fr;
        grid-template-rows: 260px 140px 140px;
    }
    .dk-main-photo { grid-row: 1 / 2; }
}
/* Lightbox */
.dk-lightbox {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 15, 15, 0.85);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}
.dk-lightbox.active {
    opacity: 1;
    visibility: visible;
}
.dk-lightbox-content {
    max-width: 90%;
    max-height: 90%;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    transform: scale(0.95);
    transition: transform 0.3s ease;
}
.dk-lightbox.active .dk-lightbox-content {
    transform: scale(1);
}
.dk-lightbox-content img {
    width: auto;
    max-width: 100vw;
    max-height: 90vh;
    display: block;
    object-fit: contain;
}
.dk-lightbox-close {
    position: absolute;
    top: 30px;
    right: 30px;
    color: #fff;
    font-size: 40px;
    font-weight: 300;
    cursor: pointer;
    z-index: 10000;
    transition: color 0.2s;
    line-height: 1;
}
.dk-lightbox-close:hover {
    color: #ef4444;
}
</style>

<div class="dk-wrap">

    <!-- ═══════════════ GALLERY ═══════════════ -->
    <div class="dk-gallery">
        <!-- Foto Utama (kiri besar) -->
        <div class="dk-main-photo">
            <img id="main-display-img" src="<?= !empty($allGalleryPhotos[0]) ? 'frontend/assets/image/'.$allGalleryPhotos[0] : 'frontend/assets/image/placeholder.jpg' ?>" alt="Main Photo">
            <a href="javascript:history.back()" class="dk-btn-back"><i data-lucide="arrow-left"></i> Kembali</a>
            <div class="dk-nav-btns">
                <button class="dk-nav-btn" id="btn-prev-photo"><i data-lucide="chevron-up"></i></button>
                <button class="dk-nav-btn" id="btn-next-photo"><i data-lucide="chevron-down"></i></button>
            </div>
        </div>

        <!-- Grid 3 Foto Samping -->
        <div class="dk-sub-photo"><?= renderPhoto($allGalleryPhotos[1] ?? null) ?></div>
        <div class="dk-sub-photo"><?= renderPhoto($allGalleryPhotos[2] ?? null) ?></div>
        <div class="dk-sub-photo"><?= renderPhoto($allGalleryPhotos[3] ?? null) ?></div>
    </div>

    <!-- Thumbnail Scroll Bar -->
    <div class="dk-thumb-container">
        <?php foreach ($allGalleryPhotos as $index => $imgName): ?>
        <div class="dk-thumb-item <?= $index === 0 ? 'active' : '' ?>" onclick="changeMainPhoto(<?= $index ?>)">
            <img src="frontend/assets/image/<?= $imgName ?>" alt="Thumb">
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══════════════ INFO ═══════════════ -->
    <div class="dk-info">

        <!-- ─── KIRI ─────────────────────── -->
        <div class="dk-left">

            <!-- Judul + Badge Luas -->
            <div class="dk-title-row">
                <h1 class="dk-title"><?= htmlspecialchars($room['tipe'] ?? '-') ?></h1>
                <?php if ($luasItem): ?>
                    <span class="dk-luas-badge">Luas <?= htmlspecialchars($luasItem) ?></span>
                <?php endif; ?>
            </div>

            <!-- Fasilitas -->
            <div class="dk-facilities">
                <?php foreach ($fasilitasNormal as $f): ?>
                    <span class="dk-fac">
                        <i data-lucide="<?= getFrontIcon($f) ?>"></i>
                        <?= htmlspecialchars($f) ?>
                    </span>
                <?php endforeach; ?>
                <?php if ($luasItem): ?>
                    <span class="dk-fac">
                        <i data-lucide="layout-grid"></i>
                        <?= htmlspecialchars($luasItem) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Harga -->
            <p class="dk-price" id="main-price-val">Rp <?= number_format($harga, 0, ',', '.') ?></p>
            <p class="dk-price-label" id="main-price-txt">Harga Perbulan</p>

            <!-- Deskripsi -->
            <div class="dk-desc"><?= nl2br(htmlspecialchars($deskripsi)) ?></div>

            <!-- CTA -->
            <div class="dk-cta-row">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="#" onclick="openLoginModal(event)" class="dk-btn-booking">Booking</a>
                <?php elseif ($isBlockedFromBooking): ?>
                    <button class="dk-btn-booking" style="background:#ef4444; cursor:not-allowed;" disabled>Anda Sudah Punya Kamar/Pesanan</button>
                <?php elseif ($totalTersedia > 0): ?>
                    <a href="index.php?page=booking&id=<?= $room['id'] ?>" class="dk-btn-booking">
                        <?= $hasPendingBooking ? 'Edit Booking' : 'Booking' ?>
                    </a>
                <?php else: ?>
                    <button class="dk-btn-booking" style="opacity:0.5; cursor:not-allowed;" disabled>Penuh</button>
                <?php endif; ?>


            </div>
        </div>

        <!-- ─── KANAN ────────────────────── -->
        <div class="dk-right">

            <!-- Ketersediaan Kamar -->
            <div class="dk-avail-box">
                <p class="dk-avail-title">Informasi Ketersediaan Kamar</p>
                <p class="dk-avail-sub">
                    Total <?= $totalTersedia ?> dari <?= $totalKamar ?> Kamar <?= htmlspecialchars($room['tipe'] ?? '') ?>
                </p>
                <div class="dk-nomor-grid">
                    <?php foreach ($kamarNomorList as $kn):
                        $status = strtolower($kn['status'] ?? 'tersedia');
                        $cls    = $status === 'tersedia' ? 'tersedia' : 'terisi';
                        $lbl    = $cls === 'tersedia' ? 'Tersedia' : 'Terisi';
                    ?>
                        <div class="dk-nomor <?= $cls ?>">
                            <?= htmlspecialchars($kn['nomor']) ?>
                            <span><?= $lbl ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Harga & Denah -->
            <div class="dk-price-denah">

                <!-- Card Harga -->
                <div class="dk-price-cards">
                    <div class="dk-pcard active" data-price="Rp <?= number_format($harga, 0, ',', '.') ?>" data-label="Harga Perbulan">
                        <p class="dk-pcard-label">perbulan</p>
                        <p class="dk-pcard-price">Rp <?= number_format($harga, 0, ',', '.') ?></p>
                    </div>
                    <div class="dk-pcard" data-price="Rp <?= number_format($harga * 3, 0, ',', '.') ?>" data-label="Harga per 3 Bulan">
                        <p class="dk-pcard-label">per 3 bulan</p>
                        <p class="dk-pcard-price">Rp <?= number_format($harga * 3, 0, ',', '.') ?></p>
                        <p class="dk-pcard-hemat">Hemat 10%</p>
                    </div>
                    <div class="dk-pcard" data-price="Rp <?= number_format($harga * 6, 0, ',', '.') ?>" data-label="Harga per 6 Bulan">
                        <p class="dk-pcard-label">per 6 bulan</p>
                        <p class="dk-pcard-price">Rp <?= number_format($harga * 6, 0, ',', '.') ?></p>
                        <p class="dk-pcard-hemat">Hemat 10%</p>
                    </div>
                    <div class="dk-pcard" data-price="Rp <?= number_format($harga * 12, 0, ',', '.') ?>" data-label="Harga Pertahun">
                        <span class="dk-pcard-badge">Populer</span>
                        <p class="dk-pcard-label">pertahun</p>
                        <p class="dk-pcard-price">Rp <?= number_format($harga * 12, 0, ',', '.') ?></p>
                        <p class="dk-pcard-hemat">Hemat 10%</p>
                    </div>
                </div>

                <!-- Denah Kamar -->
                <div class="dk-denah">
                    <img src="<?= htmlspecialchars($denahPath) ?>" alt="Denah Kamar">
                    <p class="dk-denah-label">DENAH KAMAR KOS</p>
                </div>

            </div>
        </div><!-- /dk-right -->

    </div><!-- /dk-info -->

</div><!-- /dk-wrap -->

<!-- Lightbox Modal -->
<div id="dk-lightbox" class="dk-lightbox" onclick="closeDkLightbox()">
    <span class="dk-lightbox-close">&times;</span>
    <div class="dk-lightbox-content" onclick="event.stopPropagation()">
        <!-- Image injected here -->
    </div>
</div>

<script>
if (typeof lucide !== 'undefined') { lucide.createIcons(); }

    const allPhotos = <?= json_encode(array_map(fn($f) => 'frontend/assets/image/'.$f, $allGalleryPhotos)) ?>;
    let currentPhotoIndex = 0;

    function changeMainPhoto(index) {
        if (index < 0 || index >= allPhotos.length) return;
        currentPhotoIndex = index;
        const mainImg = document.getElementById('main-display-img');
        mainImg.style.opacity = '0';
        setTimeout(() => {
            mainImg.src = allPhotos[index];
            mainImg.style.opacity = '1';
        }, 200);

        // Update active thumbnail
        document.querySelectorAll('.dk-thumb-item').forEach((el, i) => {
            el.classList.toggle('active', i === index);
            if (i === index) el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        });
    }

    document.getElementById('btn-prev-photo').addEventListener('click', () => {
        changeMainPhoto((currentPhotoIndex - 1 + allPhotos.length) % allPhotos.length);
    });

    document.getElementById('btn-next-photo').addEventListener('click', () => {
        changeMainPhoto((currentPhotoIndex + 1) % allPhotos.length);
    });

function openDkLightbox(element) {
    const lightbox = document.getElementById('dk-lightbox');
    const content = document.querySelector('.dk-lightbox-content');
    
    // Clear previous
    content.innerHTML = '';
    
    if (element.tagName === 'IMG') {
        const img = document.createElement('img');
        img.src = element.src;
        content.appendChild(img);
    }
    
    lightbox.classList.add('active');
}

function closeDkLightbox() {
    document.getElementById('dk-lightbox').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Gallery Navigation Logic
    const galleryImgs = document.querySelectorAll('.dk-main-photo img, .dk-sub-photo img');
    const photoUrls = Array.from(galleryImgs).map(img => img.src);
    
    if (photoUrls.length > 0) {
        let currentIdx = 0;
        const mainImg = document.querySelector('.dk-main-photo img');
        const btnPrev = document.getElementById('btn-prev-photo');
        const btnNext = document.getElementById('btn-next-photo');
        
        if (mainImg && btnPrev && btnNext) {
            // Animasi fade saat ganti foto
            const changePhoto = (newSrc) => {
                mainImg.style.opacity = '0';
                setTimeout(() => {
                    mainImg.src = newSrc;
                    mainImg.style.opacity = '1';
                }, 150); // Waktu transisi
            };

            btnPrev.addEventListener('click', (e) => {
                e.preventDefault();
                currentIdx = (currentIdx - 1 + photoUrls.length) % photoUrls.length;
                changePhoto(photoUrls[currentIdx]);
            });
            
            btnNext.addEventListener('click', (e) => {
                e.preventDefault();
                currentIdx = (currentIdx + 1) % photoUrls.length;
                changePhoto(photoUrls[currentIdx]);
            });
        }
    }

    // 2. Lightbox Logic
    document.querySelectorAll('.dk-main-photo img, .dk-sub-photo img').forEach(img => {
        img.addEventListener('click', function() {
            openDkLightbox(this);
        });
    });
    // 3. Pricing Card Logic
    const priceCards = document.querySelectorAll('.dk-pcard');
    const mainPriceVal = document.getElementById('main-price-val');
    const mainPriceTxt = document.getElementById('main-price-txt');

    if (priceCards.length > 0 && mainPriceVal && mainPriceTxt) {
        priceCards.forEach(card => {
            card.addEventListener('click', () => {
                // Remove active class from all
                priceCards.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked
                card.classList.add('active');
                
                // Update main price text
                mainPriceVal.innerText = card.dataset.price;
                mainPriceTxt.innerText = card.dataset.label;
                
                // Optional: Animasi fade singkat buat harga
                mainPriceVal.style.opacity = '0.5';
                setTimeout(() => { mainPriceVal.style.opacity = '1'; }, 150);
            });
        });
    }

});
</script>
