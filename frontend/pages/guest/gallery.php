<?php /* gallery.php – Layout sesuai desain Figma, tanpa foto (placeholder only) */ ?>

<style>
/* ── Navbar override cream ───────────────────────── */
.app-navbar {
    position: relative !important;
    background: #EEEADF !important;
}
.navbar-logo, .navbar-menu a, .login-link, .register-btn, .auth-separator { color: #1f2937 !important; }
.nav-arrow { stroke: #1f2937 !important; }
.mobile-toggle svg { stroke: #1f2937 !important; }
.dropdown-menu { border: 1px solid #e5e7eb; }

/* ── BASE PAGE ───────────────────────────────────── */
.gl-page {
    background: #fff;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
}

.gl-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ── HERO ────────────────────────────────────────── */
.gl-hero {
    background: #1a1f2e;
    padding: 56px 0 48px;
    text-align: center;
}
.gl-hero-eyebrow {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #c9a84c;
    margin: 0 0 10px;
}
.gl-hero-title {
    font-size: 36px;
    font-weight: 900;
    color: #fff;
    margin: 0 0 10px;
    line-height: 1.2;
}
.gl-hero-title span { color: #c9a84c; }
.gl-hero-sub {
    font-size: 14px;
    color: #9ca3af;
    margin: 0 auto 28px;
    max-width: 440px;
    line-height: 1.6;
}
.gl-hero-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}
.gl-hero-stat-num {
    font-size: 26px;
    font-weight: 900;
    color: #fff;
    line-height: 1;
    margin: 0 0 3px;
}
.gl-hero-stat-lbl {
    font-size: 12px;
    color: #6b7280;
    margin: 0;
}

/* ── GALLERY BODY ────────────────────────────────── */
.gl-body {
    padding: 48px 0 80px;
}

/* ── SECTION BLOCK ───────────────────────────────── */
.gl-section {
    margin-bottom: 52px;
}
.gl-section-title {
    font-size: 20px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 16px;
    line-height: 1.2;
}
.gl-sub-label {
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    margin: 0 0 10px;
}

/* ── PLACEHOLDER BOX ─────────────────────────────── */
.ph {
    background: #e0ddd8;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #b0a898;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.04em;
    transition: background 0.2s;
    overflow: hidden;
    position: relative;
}
.ph:hover { background: #d4cfc8; }
.ph i { width: 22px; height: 22px; }
.ph img, .ph video { width: 100%; height: 100%; object-fit: cover; display: block; }

/* ═══════════════════════════════════════════════════
   SUASANA LANTAI — Layout: [LARGE | sm sm / WIDE]
═══════════════════════════════════════════════════ */
.gl-suasana-grid {
    display: grid;
    grid-template-columns: 1.3fr 1fr 1fr;
    grid-template-rows: 180px 180px;
    gap: 10px;
}
/* Foto 1: besar kiri, span 2 baris */
.gl-suasana-grid .ph-main  {
    grid-column: 1;
    grid-row: 1 / 3;
    border-radius: 16px;
}
/* Foto 2: kanan atas kiri */
.gl-suasana-grid .ph-tr-1  { grid-column: 2; grid-row: 1; }
/* Foto 3: kanan atas kanan */
.gl-suasana-grid .ph-tr-2  { grid-column: 3; grid-row: 1; }
/* Foto 4: kanan bawah lebar (span 2 kolom) */
.gl-suasana-grid .ph-br    { grid-column: 2 / 4; grid-row: 2; }

/* ═══════════════════════════════════════════════════
   SINGLE VIDEO (VERTICAL)
═══════════════════════════════════════════════════ */
.gl-single-video-wrapper {
    position: relative;
    width: 100%;
    max-width: 360px; /* Estetik untuk vertical video (ukuran HP) */
    margin: 0 auto; /* Supaya ke tengah */
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    background: #e0ddd8;
    aspect-ratio: 9 / 16;
}
.gl-single-video-wrapper .gl-single-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    position: relative;
    z-index: 2;
}

/* ═══════════════════════════════════════════════════
   SUASANA KAMAR — 6 slot (2 baris mosaic)
═══════════════════════════════════════════════════ */
.gl-kamar-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-template-rows: 160px 160px;
    gap: 10px;
}
.gl-kamar-grid .ph { border-radius: 14px; }
/* Baris 1: lebar (span 2) + sm + sm */
.gl-kamar-grid .km-1 { grid-column: 1 / 3; grid-row: 1; }
.gl-kamar-grid .km-2 { grid-column: 3;     grid-row: 1; }
.gl-kamar-grid .km-3 { grid-column: 4;     grid-row: 1; }
/* Baris 2: sm + sm + medium (span2) */
.gl-kamar-grid .km-4 { grid-column: 1;     grid-row: 2; }
.gl-kamar-grid .km-5 { grid-column: 2;     grid-row: 2; }
.gl-kamar-grid .km-6 { grid-column: 3 / 5; grid-row: 2; }

/* ═══════════════════════════════════════════════════
   FASILITAS PARKIR — 4 foto
═══════════════════════════════════════════════════ */
.gl-parkir-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1.3fr;
    grid-template-rows: 180px 180px;
    gap: 10px;
}
.gl-parkir-grid .pk-1 { grid-column: 1; grid-row: 1; }
.gl-parkir-grid .pk-2 { grid-column: 2; grid-row: 1; }
.gl-parkir-grid .pk-3 { grid-column: 1 / 3; grid-row: 2; }
.gl-parkir-grid .pk-4 { grid-column: 3; grid-row: 1 / 3; border-radius: 16px; }

/* ═══════════════════════════════════════════════════
   FASILITAS DAPUR — 4 foto rata
═══════════════════════════════════════════════════ */
.gl-dapur-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}
.gl-dapur-grid .ph { height: 160px; }

/* ═══════════════════════════════════════════════════
   FASILITAS LAINNYA — tall kiri + 2 kanan
═══════════════════════════════════════════════════ */
.gl-lainnya-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 175px 175px;
    gap: 10px;
}
/* Kiri tall */
.gl-lainnya-grid .ln-left { grid-column: 1; grid-row: 1 / 3; }
/* Kanan atas lebar (sekarang 2 foto) */
.gl-lainnya-grid .ln-rt   { 
    grid-column: 2; 
    grid-row: 1; 
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.gl-lainnya-grid .ln-rt .ph { border-radius: 14px; height: 100%; }
/* Kanan bawah: 2 foto berdampingan — pakai sub-grid trick */
.gl-lainnya-grid .ln-rb   {
    grid-column: 2;
    grid-row: 2;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.gl-lainnya-grid .ln-rb .ph { border-radius: 14px; height: 100%; }

/* ── DIVIDER ─────────────────────────────────────── */
.gl-divider {
    border: none;
    border-top: 1.5px solid #f0ece4;
    margin: 40px 0;
}

/* ── RESPONSIVE ──────────────────────────────────── */
@media (max-width: 768px) {
    .gl-suasana-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 150px 120px 120px;
    }
    .gl-suasana-grid .ph-main { grid-column: 1 / 3; grid-row: 1; }
    .gl-suasana-grid .ph-tr-1 { grid-column: 1; grid-row: 2; }
    .gl-suasana-grid .ph-tr-2 { grid-column: 2; grid-row: 2; }
    .gl-suasana-grid .ph-br   { grid-column: 1 / 3; grid-row: 3; }

    .gl-kamar-grid { grid-template-columns: repeat(2,1fr); grid-template-rows: repeat(3, 150px); }
    .gl-kamar-grid .km-1, .gl-kamar-grid .km-6 { grid-column: 1 / 3; }
    .gl-kamar-grid .km-2 { grid-column: 1; grid-row: 2; }
    .gl-kamar-grid .km-3 { grid-column: 2; grid-row: 2; }
    .gl-kamar-grid .km-4 { grid-column: 1; grid-row: 3; }
    .gl-kamar-grid .km-5 { grid-column: 2; grid-row: 3; }
    .gl-kamar-grid .km-6 { grid-row: auto; } /* Let it place naturally if needed, or explicitly set below */
    
    .gl-parkir-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 150px 120px 120px;
    }
    .gl-parkir-grid .pk-4 { grid-column: 1 / 3; grid-row: 1; }
    .gl-parkir-grid .pk-1 { grid-column: 1; grid-row: 2; }
    .gl-parkir-grid .pk-2 { grid-column: 2; grid-row: 2; }
    .gl-parkir-grid .pk-3 { grid-column: 1 / 3; grid-row: 3; }

    .gl-dapur-grid   { grid-template-columns: repeat(2,1fr); }

    .gl-lainnya-grid { grid-template-columns: 1fr; grid-template-rows: 180px 130px 130px; }
    .gl-lainnya-grid .ln-left { grid-column: 1; grid-row: 1; }
    .gl-lainnya-grid .ln-rt   { grid-column: 1; grid-row: 2; }
    .gl-lainnya-grid .ln-rb   { grid-column: 1; grid-row: 3; }
}

/* ── LIGHTBOX ────────────────────────────────────── */
.gl-lightbox {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.85);
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}
.gl-lightbox.active {
    display: flex;
    animation: fadeIn 0.2s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.gl-lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.gl-lightbox-content img, .gl-lightbox-content video {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 8px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.6);
    object-fit: contain;
}
.gl-lightbox-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: #fff;
    font-size: 40px;
    font-weight: 300;
    cursor: pointer;
    z-index: 10000;
    transition: color 0.2s;
    line-height: 1;
}
.gl-lightbox-close:hover {
    color: #ef4444;
}
.ph img, .ph video {
    transition: transform 0.3s ease;
}
.ph:hover img, .ph:hover video {
    transform: scale(1.05);
}

/* ═══════════════════════════════════════════════════
   CALL TO ACTION (CTA)
═══════════════════════════════════════════════════ */
.gl-cta {
    background: #eeeadf;
    border-radius: 24px;
    padding: 48px;
    text-align: center;
    margin-top: 60px;
    margin-bottom: 20px;
}
.gl-cta h3 {
    font-weight: 800;
    font-size: 1.8rem;
    margin-bottom: 12px;
    color: #1f2937;
}
.gl-cta p {
    color: #6b7280;
    margin-bottom: 24px;
    font-size: 1rem;
}
.btn-gl {
    background: #1f2937;
    color: #fff;
    padding: 12px 32px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    font-size: 15px;
    display: inline-block;
    transition: opacity 0.2s;
}
.btn-gl:hover {
    opacity: 0.85;
    color: #fff;
}
</style>

<?php
// Auto-create galeri table if not exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS galeri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kategori VARCHAR(50) NOT NULL,
        tipe_file ENUM('foto', 'video') NOT NULL DEFAULT 'foto',
        file_path VARCHAR(255) NOT NULL,
        caption VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// Auto-seed galeri if empty
$checkGal = $conn->query("SELECT COUNT(*) FROM galeri")->fetchColumn();
if ($checkGal == 0) {
    $initial_items = [
        ['suasana_lokasi', 'video', 'suasana lokasi kost/IMG_3801.mp4', 'Suasana Lokasi Kost'],
        ['suasana_lantai_1', 'foto', 'suasana lantai satu/IMG_3859.jpg', 'Suasana Lantai Satu - Depan'],
        ['suasana_lantai_1', 'foto', 'suasana lantai satu/IMG_3869.jpg', 'Suasana Lantai Satu - Lorong'],
        ['suasana_lantai_1', 'foto', 'suasana lantai satu/IMG_3875.jpg', 'Suasana Lantai Satu - Tangga'],
        ['suasana_lantai_1', 'foto', 'suasana lantai satu/IMG_3885.jpg', 'Suasana Lantai Satu - Pintu'],
        ['suasana_lantai_2', 'foto', 'suasana lantai dua/IMG_3898.jpg', 'Suasana Lantai Dua - Balkon'],
        ['suasana_lantai_2', 'foto', 'suasana lantai dua/IMG_3899.jpg', 'Suasana Lantai Dua - Lorong'],
        ['suasana_lantai_2', 'foto', 'suasana lantai dua/IMG_4062.jpg', 'Suasana Lantai Dua - Tangga'],
        ['suasana_lantai_2', 'foto', 'suasana lantai dua/IMG_4066.jpg', 'Suasana Lantai Dua - Teras'],
        ['suasana_kamar', 'foto', 'Suasana Kamar/IMG_4438.jpg', 'Suasana Kamar - Kasur'],
        ['suasana_kamar', 'video', 'Suasana Kamar/VIDEO-2026-04-21-13-10-25.mp4', 'Suasana Kamar - Video Cuplikan'],
        ['suasana_kamar', 'foto', 'Suasana Kamar/IMG_4441.jpg', 'Suasana Kamar - Meja Belajar'],
        ['suasana_kamar', 'foto', 'Suasana Kamar/IMG_4444.jpg', 'Suasana Kamar - Lemari'],
        ['suasana_kamar', 'video', 'Suasana Kamar/VIDEO-2026-04-21-13-10-25 (1).mp4', 'Suasana Kamar - Detail Kamar Mandi'],
        ['suasana_kamar', 'foto', 'Suasana Kamar/IMG_4445.jpg', 'Suasana Kamar - Kamar Mandi'],
        ['parkir', 'foto', 'fasilitas parkir/IMG_3887.jpg', 'Fasilitas Parkir - Motor'],
        ['parkir', 'foto', 'fasilitas parkir/IMG_3897.jpg', 'Fasilitas Parkir - Depan'],
        ['parkir', 'foto', 'fasilitas parkir/IMG_4455.jpg', 'Fasilitas Parkir - Luas'],
        ['parkir', 'foto', 'fasilitas parkir/IMG_4457.jpg', 'Fasilitas Parkir - Pintu Masuk'],
        ['dapur', 'foto', 'fasilitas dapur/IMG_3849.jpg', 'Fasilitas Dapur - Kompor'],
        ['dapur', 'video', 'fasilitas dapur/IMG_3850.mp4', 'Fasilitas Dapur - Suasana Memasak'],
        ['dapur', 'foto', 'fasilitas dapur/IMG_3864.jpg', 'Fasilitas Dapur - Wastafel'],
        ['dapur', 'foto', 'fasilitas dapur/IMG_3865.jpg', 'Fasilitas Dapur - Lemari Es'],
        ['lainnya', 'foto', 'fasilitas lainnya/IMG_3861.jpg', 'Fasilitas Lainnya - Tempat Jemuran'],
        ['lainnya', 'foto', 'fasilitas lainnya/IMG_3894.jpg', 'Fasilitas Lainnya - Ruang Santai'],
        ['lainnya', 'foto', 'fasilitas lainnya/IMG_3896.jpg', 'Fasilitas Lainnya - Korong Belakang'],
        ['lainnya', 'foto', 'fasilitas lainnya/IMG_4449.jpg', 'Fasilitas Lainnya - Ruang Cuci'],
        ['lainnya', 'foto', 'fasilitas lainnya/IMG_4456.jpg', 'Fasilitas Lainnya - CCTV & Keamanan'],
    ];

    $insGal = $conn->prepare("INSERT INTO galeri (kategori, tipe_file, file_path, caption) VALUES (?, ?, ?, ?)");
    foreach ($initial_items as $item) {
        $insGal->execute($item);
    }
}

// Fetch all gallery items
$stmtG = $conn->query("SELECT * FROM galeri ORDER BY id ASC");
$allItems = $stmtG->fetchAll(PDO::FETCH_ASSOC);

$galByCat = [];
foreach ($allItems as $g) {
    $galByCat[$g['kategori']][] = $g;
}

if (!function_exists('renderGalleryItem')) {
    function renderGalleryItem($item) {
        if (!$item) {
            return '<div class="ph-empty" style="padding: 20px; text-align: center; color: #999;">Belum ada media</div>';
        }
        $filePath = $item['file_path'];
        if (strpos($filePath, '/') === false) {
            // Uploaded via admin
            $path = "frontend/assets/image/uploads/" . $filePath;
        } else {
            // Seeded/existing
            $path = "frontend/assets/image/" . $filePath;
        }
        
        if ($item['tipe_file'] === 'video') {
            return '<video src="' . htmlspecialchars($path) . '" autoplay loop muted playsinline></video>';
        } else {
            return '<img src="' . htmlspecialchars($path) . '" alt="' . htmlspecialchars($item['caption'] ?? '') . '">';
        }
    }
}
?>

<div class="gl-page">

    <!-- ═══ HERO ═══════════════════════════════════ -->
    <div class="gl-hero">
        <div class="gl-container">
            <p class="gl-hero-eyebrow">Kost Elmi Sarah</p>
            <h1 class="gl-hero-title">Gallery <span>Elmi Sarah</span></h1>
            <p class="gl-hero-sub">Kamar, Fasilitas, Lokasi, dan suasana kost yang nyaman untuk hunian terbaik kamu.</p>
        </div>
    </div>

    <!-- ═══ BODY ════════════════════════════════════ -->
    <div class="gl-body">
    <div class="gl-container">

        <!-- ─── Video Cuplikan Kost (Vertical) ──────── -->
        <div class="gl-section">
            <h2 class="gl-section-title" style="text-align: center;">Suasana Lokasi Kost</h2>
            <div class="gl-single-video-wrapper">
                <?php
                $item = $galByCat['suasana_lokasi'][0] ?? null;
                if ($item):
                ?>
                    <div class="ph" style="height: 100%;">
                        <?= renderGalleryItem($item) ?>
                    </div>
                <?php else: ?>
                    <div class="ph" style="height: 100%; min-height: 400px; display:flex; align-items:center; justify-content:center;">Belum ada video</div>
                <?php endif; ?>
            </div>
            <?php if (count($galByCat['suasana_lokasi'] ?? []) > 1): ?>
                <div class="row g-2 mt-3 justify-content-center">
                    <?php for ($i = 1; $i < count($galByCat['suasana_lokasi']); $i++): ?>
                        <div class="col-md-3 col-6">
                            <div class="ph" style="height: 150px;"><?= renderGalleryItem($galByCat['suasana_lokasi'][$i]) ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <hr class="gl-divider">

        <!-- ─── Suasana Lantai Satu ─────────────── -->
        <div class="gl-section">
            <h2 class="gl-section-title">Suasana Lantai Satu</h2>
            <div class="gl-suasana-grid">
                <div class="ph ph-main"><?= renderGalleryItem($galByCat['suasana_lantai_1'][0] ?? null) ?></div>
                <div class="ph ph-tr-1"><?= renderGalleryItem($galByCat['suasana_lantai_1'][1] ?? null) ?></div>
                <div class="ph ph-tr-2"><?= renderGalleryItem($galByCat['suasana_lantai_1'][2] ?? null) ?></div>
                <div class="ph ph-br"><?= renderGalleryItem($galByCat['suasana_lantai_1'][3] ?? null) ?></div>
            </div>
            <?php if (count($galByCat['suasana_lantai_1'] ?? []) > 4): ?>
                <div class="row g-2 mt-2">
                    <?php for ($i = 4; $i < count($galByCat['suasana_lantai_1']); $i++): ?>
                        <div class="col-md-3 col-6">
                            <div class="ph" style="height: 150px;"><?= renderGalleryItem($galByCat['suasana_lantai_1'][$i]) ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <hr class="gl-divider">

        <!-- ─── Suasana Lantai Dua ──────────────── -->
        <div class="gl-section">
            <h2 class="gl-section-title">Suasana Lantai Dua</h2>
            <div class="gl-suasana-grid">
                <div class="ph ph-main"><?= renderGalleryItem($galByCat['suasana_lantai_2'][0] ?? null) ?></div>
                <div class="ph ph-tr-1"><?= renderGalleryItem($galByCat['suasana_lantai_2'][1] ?? null) ?></div>
                <div class="ph ph-tr-2"><?= renderGalleryItem($galByCat['suasana_lantai_2'][2] ?? null) ?></div>
                <div class="ph ph-br"><?= renderGalleryItem($galByCat['suasana_lantai_2'][3] ?? null) ?></div>
            </div>
            <?php if (count($galByCat['suasana_lantai_2'] ?? []) > 4): ?>
                <div class="row g-2 mt-2">
                    <?php for ($i = 4; $i < count($galByCat['suasana_lantai_2']); $i++): ?>
                        <div class="col-md-3 col-6">
                            <div class="ph" style="height: 150px;"><?= renderGalleryItem($galByCat['suasana_lantai_2'][$i]) ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <hr class="gl-divider">

        <!-- ─── Suasana Kamar ───────────────────── -->
        <div class="gl-section">
            <h2 class="gl-section-title">Suasana Kamar</h2>
            <div class="gl-kamar-grid">
                <div class="ph km-1"><?= renderGalleryItem($galByCat['suasana_kamar'][0] ?? null) ?></div>
                <div class="ph km-2"><?= renderGalleryItem($galByCat['suasana_kamar'][1] ?? null) ?></div>
                <div class="ph km-3"><?= renderGalleryItem($galByCat['suasana_kamar'][2] ?? null) ?></div>
                <div class="ph km-4"><?= renderGalleryItem($galByCat['suasana_kamar'][3] ?? null) ?></div>
                <div class="ph km-5"><?= renderGalleryItem($galByCat['suasana_kamar'][4] ?? null) ?></div>
                <div class="ph km-6"><?= renderGalleryItem($galByCat['suasana_kamar'][5] ?? null) ?></div>
            </div>
            <?php if (count($galByCat['suasana_kamar'] ?? []) > 6): ?>
                <div class="row g-2 mt-2">
                    <?php for ($i = 6; $i < count($galByCat['suasana_kamar']); $i++): ?>
                        <div class="col-md-3 col-6">
                            <div class="ph" style="height: 150px;"><?= renderGalleryItem($galByCat['suasana_kamar'][$i]) ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <hr class="gl-divider">

        <!-- ─── Fasilitas Bersama ───────────────── -->
        <div class="gl-section">
            <h2 class="gl-section-title">Fasilitas Bersama</h2>

            <!-- Fasilitas Parkir -->
            <p class="gl-sub-label">Fasilitas Parkir</p>
            <div class="gl-parkir-grid" style="margin-bottom:28px;">
                <div class="ph pk-1"><?= renderGalleryItem($galByCat['parkir'][0] ?? null) ?></div>
                <div class="ph pk-2"><?= renderGalleryItem($galByCat['parkir'][1] ?? null) ?></div>
                <div class="ph pk-3"><?= renderGalleryItem($galByCat['parkir'][2] ?? null) ?></div>
                <div class="ph pk-4"><?= renderGalleryItem($galByCat['parkir'][3] ?? null) ?></div>
            </div>
            <?php if (count($galByCat['parkir'] ?? []) > 4): ?>
                <div class="row g-2 mt-2" style="margin-bottom:28px;">
                    <?php for ($i = 4; $i < count($galByCat['parkir']); $i++): ?>
                        <div class="col-md-3 col-6">
                            <div class="ph" style="height: 150px;"><?= renderGalleryItem($galByCat['parkir'][$i]) ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            <!-- Fasilitas Dapur -->
            <p class="gl-sub-label">Fasilitas Dapur</p>
            <div class="gl-dapur-grid" style="margin-bottom:28px;">
                <div class="ph"><?= renderGalleryItem($galByCat['dapur'][0] ?? null) ?></div>
                <div class="ph"><?= renderGalleryItem($galByCat['dapur'][1] ?? null) ?></div>
                <div class="ph"><?= renderGalleryItem($galByCat['dapur'][2] ?? null) ?></div>
                <div class="ph"><?= renderGalleryItem($galByCat['dapur'][3] ?? null) ?></div>
            </div>
            <?php if (count($galByCat['dapur'] ?? []) > 4): ?>
                <div class="row g-2 mt-2" style="margin-bottom:28px;">
                    <?php for ($i = 4; $i < count($galByCat['dapur']); $i++): ?>
                        <div class="col-md-3 col-6">
                            <div class="ph" style="height: 150px;"><?= renderGalleryItem($galByCat['dapur'][$i]) ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            <!-- Fasilitas Lainnya -->
            <p class="gl-sub-label">Fasilitas Lainnya</p>
            <div class="gl-lainnya-grid">
                <div class="ph ln-left"><?= renderGalleryItem($galByCat['lainnya'][0] ?? null) ?></div>
                <div class="ln-rt">
                    <div class="ph"><?= renderGalleryItem($galByCat['lainnya'][1] ?? null) ?></div>
                    <div class="ph"><?= renderGalleryItem($galByCat['lainnya'][2] ?? null) ?></div>
                </div>
                <div class="ln-rb">
                    <div class="ph"><?= renderGalleryItem($galByCat['lainnya'][3] ?? null) ?></div>
                    <div class="ph"><?= renderGalleryItem($galByCat['lainnya'][4] ?? null) ?></div>
                </div>
            </div>
            <?php if (count($galByCat['lainnya'] ?? []) > 5): ?>
                <div class="row g-2 mt-2">
                    <?php for ($i = 5; $i < count($galByCat['lainnya']); $i++): ?>
                        <div class="col-md-3 col-6">
                            <div class="ph" style="height: 150px;"><?= renderGalleryItem($galByCat['lainnya'][$i]) ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        </div><!-- /Fasilitas Bersama -->

        <!-- ─── CALL TO ACTION ──────────────────── -->
        <div class="gl-cta">
            <h3>Siap Bergabung?</h3>
            <p>Hubungi kami atau langsung booking kamar impianmu sekarang.</p>
            <a href="index.php?page=booking" class="btn-gl">Booking Kamar Sekarang</a>
        </div>

    </div><!-- /gl-container -->
    </div><!-- /gl-body -->

</div><!-- /gl-page -->

<!-- Lightbox Modal -->
<div id="gl-lightbox" class="gl-lightbox" onclick="closeLightbox()">
    <span class="gl-lightbox-close">&times;</span>
    <div class="gl-lightbox-content" onclick="event.stopPropagation()">
        <!-- Media injected here -->
    </div>
</div>

<script>
if (typeof lucide !== 'undefined') { lucide.createIcons(); }

function openLightbox(element) {
    const lightbox = document.getElementById('gl-lightbox');
    const content = document.querySelector('.gl-lightbox-content');
    
    // Clear previous content
    content.innerHTML = '';
    
    if (element.tagName === 'IMG') {
        const img = document.createElement('img');
        img.src = element.src;
        content.appendChild(img);
    } else if (element.tagName === 'VIDEO') {
        const video = document.createElement('video');
        video.src = element.src;
        video.controls = true;
        video.autoplay = true;
        content.appendChild(video);
    }
    
    lightbox.classList.add('active');
}

function closeLightbox() {
    document.getElementById('gl-lightbox').classList.remove('active');
    document.querySelector('.gl-lightbox-content').innerHTML = ''; // Stop video
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ph img, .ph video').forEach(el => {
        el.style.cursor = 'pointer';
        el.addEventListener('click', function() {
            openLightbox(this);
        });
    });
});
</script>
