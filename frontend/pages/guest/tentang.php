<!-- Import Google Fonts Outfit -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════
   GLOBAL & NAVBAR OVERRIDES
   ═══════════════════════════════════════════════════ */
.app-navbar {
    position: relative !important;
    background: #EEEADF !important;
}
.navbar-logo, .navbar-menu a, .login-link, .register-btn, .auth-separator { color: #1f2937 !important; }
.nav-arrow { stroke: #1f2937 !important; }
.mobile-toggle svg { stroke: #1f2937 !important; }
.dropdown-menu { border: 1px solid #e5e7eb; }

/* ═══════════════════════════════════════════════════
   DESIGN SYSTEM VARIABLES (RUKITA THEME MATCH)
   ═══════════════════════════════════════════════════ */
:root {
    --rukita-teal: #0F9D9A;
    --rukita-teal-dark: #0b8986;
    --rukita-teal-light: #e6f5f5;
    --bg-light: #ffffff;
    --bg-cream: #f7f4eb;
    --bg-cream-light: #faf8f4;
    --dark-text: #1a1a1a;
    --muted-text: #555555;
    --border-color: #e5e7eb;
}

/* ═══════════════════════════════════════════════════
   PAGE & CONTAINERS
   ═══════════════════════════════════════════════════ */
.tt-page {
    background-color: var(--bg-light);
    color: var(--dark-text);
    font-family: 'Outfit', sans-serif;
    padding-bottom: 0;
    overflow-x: hidden;
}
.tt-container {
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 24px;
}
.tt-section {
    padding: 80px 0;
}
.tt-section.alternate {
    background-color: var(--bg-cream-light);
}

/* Typography */
.tt-section-header {
    text-align: left;
    margin-bottom: 48px;
}
.tt-section-title {
    font-size: 38px;
    font-weight: 800;
    color: var(--dark-text);
    margin-bottom: 12px;
    letter-spacing: -1px;
}
.tt-section-sub {
    font-size: 16px;
    color: var(--muted-text);
    line-height: 1.6;
}

/* ═══════════════════════════════════════════════════
   1. HERO SECTION (Identical to Rukita Screenshot)
   ═══════════════════════════════════════════════════ */
.tt-hero-wrapper {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    min-height: 520px;
    margin-bottom: 80px;
    background: #ffffff;
}
.tt-hero-left {
    background-color: var(--bg-cream);
    border-top-left-radius: 160px;
    padding: 80px 60px 80px calc((100vw - 1140px) / 2 + 24px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
}
.tt-hero-title {
    font-size: 56px;
    font-weight: 800;
    line-height: 1.1;
    color: var(--dark-text);
    margin-bottom: 24px;
    letter-spacing: -1.5px;
}
.tt-hero-sub {
    font-size: 17px;
    line-height: 1.6;
    color: var(--muted-text);
    margin-bottom: 36px;
}
.tt-hero-btn {
    display: inline-block;
    background: #1f2937; /* Solid Dark */
    color: #fff;
    padding: 15px 36px;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    font-size: 16px;
    transition: all 0.3s ease;
}
.tt-hero-btn:hover {
    background: #000;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    color: #fff;
}
.tt-hero-right {
    background: url('frontend/assets/image/tentang.png') no-repeat center center;
    background-size: cover;
}

@media (max-width: 1200px) {
    .tt-hero-left {
        padding-left: 40px;
        border-top-left-radius: 80px;
    }
}
@media (max-width: 992px) {
    .tt-hero-wrapper {
        grid-template-columns: 1fr;
    }
    .tt-hero-left {
        padding: 60px 24px;
        border-top-left-radius: 40px;
    }
    .tt-hero-right {
        min-height: 300px;
    }
}

/* ═══════════════════════════════════════════════════
   2. EKOSISTEM PRODUK RUKITA STYLE (5 Columns)
   ═══════════════════════════════════════════════════ */
.tt-eco-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
}
.tt-eco-card {
    background-color: var(--bg-cream-light);
    border-radius: 16px;
    overflow: hidden;
    text-align: center;
    padding-bottom: 24px;
    border: 1px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}
.tt-eco-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(15, 157, 154, 0.08);
    border-color: var(--rukita-teal);
}
.tt-eco-img {
    width: 100%;
    height: 140px;
    object-fit: cover;
}
.tt-eco-logo {
    font-weight: 800;
    font-size: 16px;
    color: var(--dark-text);
    margin: 20px 0 8px;
    letter-spacing: -0.5px;
}
.tt-eco-logo span {
    color: var(--rukita-teal);
}
.tt-eco-desc {
    font-size: 13px;
    color: var(--muted-text);
    padding: 0 16px;
    line-height: 1.5;
    flex-grow: 1;
}

@media (max-width: 992px) {
    .tt-eco-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 600px) {
    .tt-eco-grid {
        grid-template-columns: 1fr;
    }
}

/* ═══════════════════════════════════════════════════
   3. KEUNTUNGAN TINGGAL (Rukita Layout: Image + Text)
   ═══════════════════════════════════════════════════ */
.tt-keuntungan-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px 30px;
}
.tt-k-item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.tt-k-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 16px;
    margin-bottom: 16px;
}
.tt-k-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--dark-text);
    margin-bottom: 8px;
}
.tt-k-desc {
    font-size: 14px;
    color: var(--muted-text);
    line-height: 1.6;
}

@media (max-width: 992px) {
    .tt-keuntungan-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 600px) {
    .tt-keuntungan-grid {
        grid-template-columns: 1fr;
    }
}

/* ═══════════════════════════════════════════════════
   4. PENCAPAIAN KAMI
   ═══════════════════════════════════════════════════ */
.tt-stats-container {
    background-color: var(--bg-cream);
    border-radius: 24px;
    padding: 50px 40px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    text-align: center;
}
.tt-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.tt-stat-num {
    font-size: 46px;
    font-weight: 800;
    color: var(--rukita-teal);
    margin-bottom: 10px;
    line-height: 1;
}
.tt-stat-label {
    font-size: 16px;
    font-weight: 700;
    color: var(--dark-text);
    margin-bottom: 6px;
}
.tt-stat-desc {
    font-size: 13px;
    color: var(--muted-text);
    line-height: 1.5;
    max-width: 200px;
}

@media (max-width: 992px) {
    .tt-stats-container {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 600px) {
    .tt-stats-container {
        grid-template-columns: 1fr;
    }
}

/* ═══════════════════════════════════════════════════
   5. KATA PENGHUNI (Rukita Testimoni Box & Slider)
   ═══════════════════════════════════════════════════ */
.tt-testi-box {
    background-color: var(--bg-cream);
    border-radius: 32px;
    border-top-right-radius: 120px;
    border-bottom-left-radius: 120px;
    padding: 60px;
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 50px;
    align-items: center;
}
.tt-testi-img-wrapper {
    position: relative;
    width: 100%;
    height: 360px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
}
.tt-testi-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.tt-testi-badge {
    position: absolute;
    bottom: 20px;
    left: 20px;
    background-color: rgba(26, 26, 26, 0.85);
    color: #ffffff;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
}
.tt-testi-right {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.tt-testi-quote {
    color: var(--rukita-teal);
    font-size: 80px;
    line-height: 1;
    margin-bottom: -10px;
    font-family: Georgia, serif;
}
.tt-testi-heading {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 20px;
    color: var(--dark-text);
}
.tt-testi-text {
    font-size: 16px;
    line-height: 1.7;
    color: var(--muted-text);
    margin-bottom: 24px;
    font-style: italic;
}
.tt-testi-profile {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 30px;
}
.tt-testi-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}
.tt-testi-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--dark-text);
    margin: 0 0 2px;
}
.tt-testi-role {
    font-size: 13px;
    color: var(--muted-text);
    margin: 0;
}
.tt-testi-nav {
    display: flex;
    gap: 12px;
}
.tt-testi-nav-btn {
    width: 44px;
    height: 44px;
    background-color: #1a1a1a;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.tt-testi-nav-btn:hover {
    background-color: var(--rukita-teal);
    transform: translateY(-2px);
}
.tt-testi-nav-btn svg {
    width: 20px;
    height: 20px;
}

@media (max-width: 992px) {
    .tt-testi-box {
        grid-template-columns: 1fr;
        padding: 40px 24px;
        border-top-right-radius: 60px;
        border-bottom-left-radius: 60px;
    }
    .tt-testi-img-wrapper {
        height: 280px;
    }
}

/* ═══════════════════════════════════════════════════
   6. SEKILAS & CORE VALUES
   ═══════════════════════════════════════════════════ */
.tt-value-section {
    display: grid;
    grid-template-columns: 0.9fr 1.1fr;
    gap: 60px;
    align-items: center;
}
.tt-value-left h2 {
    font-size: 36px;
    font-weight: 800;
    color: var(--dark-text);
    margin-bottom: 20px;
    letter-spacing: -0.5px;
}
.tt-value-left p {
    font-size: 16px;
    color: var(--muted-text);
    line-height: 1.7;
    margin-bottom: 24px;
}
.tt-value-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.tt-value-card {
    display: flex;
    gap: 20px;
    background: #fff;
    padding: 24px;
    border-radius: 18px;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.01);
    transition: all 0.3s ease;
}
.tt-value-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(15, 157, 154, 0.08);
    border-color: var(--rukita-teal);
}
.tt-value-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: var(--rukita-teal-light);
    color: var(--rukita-teal);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.tt-value-card-icon i {
    width: 22px;
    height: 22px;
}
.tt-value-info h4 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--dark-text);
}
.tt-value-info p {
    font-size: 13.5px;
    color: var(--muted-text);
    line-height: 1.6;
    margin: 0;
}

@media (max-width: 992px) {
    .tt-value-section {
        grid-template-columns: 1fr;
    }
}



/* ═══════════════════════════════════════════════════
   8. ATURAN KOST
   ═══════════════════════════════════════════════════ */
.tt-aturan-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.tt-aturan-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid var(--border-color);
    padding: 32px 26px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.01);
    transition: all 0.2s ease;
}
.tt-aturan-card:hover {
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.03);
}
.tt-aturan-card h4 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 18px;
    color: var(--dark-text);
    border-left: 4px solid var(--rukita-teal);
    padding-left: 12px;
}
.tt-aturan-card p, .tt-aturan-card li {
    font-size: 13.5px;
    color: var(--muted-text);
    line-height: 1.6;
    margin-bottom: 12px;
}
.tt-aturan-card ul {
    padding-left: 18px;
    margin: 0;
}

@media (max-width: 992px) {
    .tt-aturan-grid {
        grid-template-columns: 1fr;
    }
}

/* ═══════════════════════════════════════════════════
   9. BOTTOM CTA (Teal Gradient Banner)
   ═══════════════════════════════════════════════════ */
.tt-cta-box-modern {
    background: linear-gradient(135deg, var(--rukita-teal) 0%, var(--rukita-teal-dark) 100%);
    border-radius: 24px;
    padding: 64px 40px;
    text-align: center;
    color: #fff;
    box-shadow: 0 12px 35px rgba(15, 157, 154, 0.25);
}
.tt-cta-box-modern h2 {
    font-size: 38px;
    font-weight: 800;
    margin-bottom: 16px;
    letter-spacing: -0.5px;
}
.tt-cta-box-modern p {
    font-size: 16px;
    margin-bottom: 32px;
    color: rgba(255, 255, 255, 0.9);
}
.tt-cta-btn-modern {
    display: inline-block;
    background: #fff;
    color: var(--rukita-teal);
    padding: 14px 40px;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    font-size: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}
.tt-cta-btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    background: var(--rukita-teal-light);
    color: var(--rukita-teal);
}
</style>

<div class="tt-page">

    <!-- 1. HERO SECTION -->
    <div class="tt-hero-wrapper">
        <div class="tt-hero-left">
            <h1 class="tt-hero-title">Home That Grows With You</h1>
            <p class="tt-hero-sub">Mewujudkan hunian berkualitas dan terjangkau untuk semua orang, di setiap fase kehidupan.</p>
            <a href="?page=rooms" class="tt-hero-btn">Cari Hunian Sekarang</a>
        </div>
        <div class="tt-hero-right"></div>
    </div>

    <!-- 2. EKOSISTEM PRODUK (5 Columns, Center Text) -->
    <section class="tt-section">
        <div class="tt-container">
            <div class="tt-section-header">
                <h2 class="tt-section-title">Ekosistem Produk Elmi Sarah</h2>
                <p class="tt-section-sub">Solusi ekosistem hunian mahasiswi putri terpadu untuk kebutuhan harian, jangka pendek, maupun jangka panjang.</p>
            </div>
            <div class="tt-eco-grid">
                <div class="tt-eco-card">
                    <img src="frontend/assets/image/demo_kamar_1.png" alt="Elmi Sarah Apartment" class="tt-eco-img">
                    <div class="tt-eco-logo">Elmi Sarah <span>Apartment</span></div>
                    <p class="tt-eco-desc">Serviced apartment dengan furnitur lengkap dan layanan menyeluruh.</p>
                </div>
                <div class="tt-eco-card">
                    <img src="frontend/assets/image/demo_kamar_2.png" alt="Elmi Sarah CoLiving" class="tt-eco-img">
                    <div class="tt-eco-logo">Elmi Sarah <span>CoLiving</span></div>
                    <p class="tt-eco-desc">Coliving eksklusif dengan fasilitas bintang lima di lokasi strategis.</p>
                </div>
                <div class="tt-eco-card">
                    <img src="frontend/assets/image/demo_kamar_3.png" alt="Elmi Sarah Residence" class="tt-eco-img">
                    <div class="tt-eco-logo">Elmi Sarah <span>Residence</span></div>
                    <p class="tt-eco-desc">Hunian kost harian, mingguan, atau bulanan dengan kemudahan akses penuh.</p>
                </div>
                <div class="tt-eco-card">
                    <img src="frontend/assets/image/desain_kamar1.png" alt="Elmi Sarah Partner" class="tt-eco-img">
                    <div class="tt-eco-logo">Elmi Sarah <span>Partner</span></div>
                    <p class="tt-eco-desc">Layanan pemasaran untuk apartemen dan kost eksklusif putri di Indonesia.</p>
                </div>
                <div class="tt-eco-card">
                    <img src="frontend/assets/image/desain_kamar2.png" alt="Elmi Sarah Finance" class="tt-eco-img">
                    <div class="tt-eco-logo">Elmi Sarah <span>Finance</span></div>
                    <p class="tt-eco-desc">Bantuan pembiayaan dan transaksi digital aman untuk ekspansi sewa properti.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. KEUNTUNGAN TINGGAL (Rukita Layout: Image + Text) -->
    <section class="tt-section alternate">
        <div class="tt-container">
            <div class="tt-section-header">
                <h2 class="tt-section-title">Keuntungan Tinggal di Elmi Sarah</h2>
                <p class="tt-section-sub">Kami memberikan kenyamanan dan fasilitas yang terintegrasi demi kelancaran aktivitas harian Anda.</p>
            </div>

            <div class="tt-keuntungan-grid">
                <div class="tt-k-item">
                    <img src="frontend/assets/image/demo_kamar_1.png" alt="Fully Furnished" class="tt-k-img">
                    <h4 class="tt-k-title">Fully furnished</h4>
                    <p class="tt-k-desc">Hunian dilengkapi furniture lengkap termasuk AC, Wi-Fi berkecepatan tinggi, dan fasilitas air mandi hangat.</p>
                </div>
                <div class="tt-k-item">
                    <img src="frontend/assets/image/demo_kamar_2.png" alt="Layanan Menyeluruh" class="tt-k-img">
                    <h4 class="tt-k-title">Layanan Menyeluruh</h4>
                    <p class="tt-k-desc">Pembersihan area bersama secara rutin, laundry, pemeliharaan fasilitas, dan pengelola yang siap membantu.</p>
                </div>
                <div class="tt-k-item">
                    <img src="frontend/assets/image/demo_kamar_3.png" alt="Pembayaran Bulanan" class="tt-k-img">
                    <h4 class="tt-k-title">Pembayaran Bulanan</h4>
                    <p class="tt-k-desc">Lebih ringan dengan pembayaran sewa bulanan terpadu yang praktis lewat Aplikasi Portal Elmi Sarah.</p>
                </div>
                <div class="tt-k-item">
                    <img src="frontend/assets/image/desain_kamar1.png" alt="Lokasi Strategis" class="tt-k-img">
                    <h4 class="tt-k-title">Lokasi Strategis</h4>
                    <p class="tt-k-desc">Dekat dengan berbagai universitas ternama, pusat kuliner mahasiswi, jalan utama, dan fasilitas umum lainnya.</p>
                </div>
                <div class="tt-k-item">
                    <img src="frontend/assets/image/desain_kamar2.png" alt="Keamanan Terjamin" class="tt-k-img">
                    <h4 class="tt-k-title">Keamanan 24 Jam</h4>
                    <p class="tt-k-desc">Area kost putri aman terkendali dengan kamera pengawas CCTV 24 jam serta gerbang lingkungan yang kondusif.</p>
                </div>
                <div class="tt-k-item">
                    <img src="frontend/assets/image/kost.png" alt="Komunitas Terbuka" class="tt-k-img">
                    <h4 class="tt-k-title">Komunitas Hangat</h4>
                    <p class="tt-k-desc">Membangun interaksi sosial yang guyub, harmonis, serta saling mendukung antarsesama mahasiswi perantau.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. PENCAPAIAN KAMI -->
    <section class="tt-section">
        <div class="tt-container">
            <div class="tt-stats-container">
                <div class="tt-stat-item">
                    <span class="tt-stat-num">100+</span>
                    <span class="tt-stat-label">Mahasiswi Aktif</span>
                    <span class="tt-stat-desc">Mahasiswi dari berbagai universitas yang mempercayakan huniannya pada kami.</span>
                </div>
                <div class="tt-stat-item">
                    <span class="tt-stat-num">30+</span>
                    <span class="tt-stat-label">Pilihan Kamar</span>
                    <span class="tt-stat-desc">Variasi tipe kamar khusus putri yang terawat dan siap huni.</span>
                </div>
                <div class="tt-stat-item">
                    <span class="tt-stat-num">4.8 ★</span>
                    <span class="tt-stat-label">Rating Kepuasan</span>
                    <span class="tt-stat-desc">Penilaian berdasarkan ulasan asli mahasiswi mengenai kebersihan & pelayanan.</span>
                </div>
                <div class="tt-stat-item">
                    <span class="tt-stat-num">100%</span>
                    <span class="tt-stat-label">Terpercaya</span>
                    <span class="tt-stat-desc">Berkomitmen menjaga keamanan, ketertiban, dan transparansi administrasi.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. KATA PENGHUNI (RUKITA TESTIMONI SLIDER) -->
    <?php
    $stmtUlasan = $conn->query("
        SELECT u.rating, u.komentar, u.foto_ulasan, u.created_at, usr.nama, usr.foto
        FROM ulasan u
        JOIN users usr ON u.user_id = usr.id
        WHERE u.tampilkan = 1
        ORDER BY u.created_at DESC
    ");
    $ulasan_data = $stmtUlasan->fetchAll(PDO::FETCH_ASSOC);

    if (empty($ulasan_data)) {
        $ulasan_data = [
            [
                'nama' => 'Rere Kucing',
                'komentar' => 'Kost ini sangat nyaman dan bersih. Fasilitasnya lengkap seperti AC dingin, WiFi cepat, dan kamar yang sudah fully furnished sehingga praktis tinggal bawa baju. Rekomendasi sekali!',
                'foto' => '',
                'foto_ulasan' => '',
                'rating' => 5
            ]
        ];
    }
    ?>
    <?php 
    $ulasan_foto_url = !empty($ulasan_data[0]['foto_ulasan']) && file_exists(__DIR__ . '/../../../uploads/ulasan/' . $ulasan_data[0]['foto_ulasan']) 
        ? 'uploads/ulasan/' . htmlspecialchars($ulasan_data[0]['foto_ulasan']) 
        : 'frontend/assets/image/kost.png';
    ?>
    <section class="tt-section alternate">
        <div class="tt-container">
            <div class="tt-testi-box">
                <!-- Sisi Kiri: Foto kamar dengan badge -->
                <div class="tt-testi-img-wrapper">
                    <img id="testi-ulasan-img" src="<?= $ulasan_foto_url ?>" alt="Kamar Kost" class="tt-testi-img">
                    <div class="tt-testi-badge">Elmi Sarah Premium Room</div>
                </div>

                <!-- Sisi Kanan: Ulasan -->
                <div class="tt-testi-right">
                    <div class="tt-testi-quote">“</div>
                    <h2 class="tt-testi-heading">Kata Penghuni</h2>
                    
                    <p id="testi-text">"<?= htmlspecialchars($ulasan_data[0]['komentar']) ?>"</p>
                    
                    <div class="tt-testi-profile">
                        <?php 
                        $foto_url = !empty($ulasan_data[0]['foto']) && file_exists(__DIR__ . '/../../../uploads/profil/' . $ulasan_data[0]['foto']) 
                            ? 'uploads/profil/' . htmlspecialchars($ulasan_data[0]['foto']) 
                            : 'https://ui-avatars.com/api/?name=' . urlencode($ulasan_data[0]['nama']) . '&background=random&color=fff';
                        ?>
                        <img id="testi-img" src="<?= $foto_url ?>" alt="Profil" class="tt-testi-avatar">
                        <div class="tt-testi-user-info">
                            <h5 id="testi-name" class="tt-testi-name"><?= htmlspecialchars($ulasan_data[0]['nama']) ?></h5>
                            <span class="tt-testi-role">Penghuni Kost Elmi Sarah</span>
                            <div id="testi-rating" style="color: var(--rukita-teal); font-size: 14px; margin-top: 4px; letter-spacing: 2px;">
                                <?php 
                                $rating = isset($ulasan_data[0]['rating']) ? (int)$ulasan_data[0]['rating'] : 5;
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <?php if (count($ulasan_data) > 1): ?>
                    <div class="tt-testi-nav">
                        <button class="tt-testi-nav-btn" onclick="prevTesti()">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button class="tt-testi-nav-btn" onclick="nextTesti()">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. SEKILAS & CORE VALUES -->
    <section class="tt-section">
        <div class="tt-container tt-value-section">
            <div class="tt-value-left">
                <h2>Sekilas Tentang Elmi Sarah</h2>
                <p>Nama <strong>"Elmi Sarah"</strong> diambil dari gabungan nama pengelola dan pemilik kost ini. Didirikan dengan komitmen kuat untuk memberikan kenyamanan, kedamaian, dan perlindungan bagi mahasiswi yang menempuh masa studi perkuliahan.</p>
                <p>Kami meyakini bahwa lingkungan hunian yang sehat, teratur, dan penuh rasa kekeluargaan merupakan fondasi utama bagi pencapaian akademis yang cemerlang. Oleh karena itu, kami merancang layanan hunian terintegrasi dengan berpegang teguh pada tiga nilai inti kami.</p>
            </div>
            
            <div class="tt-value-list">
                <div class="tt-value-card">
                    <div class="tt-value-card-icon"><i data-lucide="smile"></i></div>
                    <div class="tt-value-info">
                        <h4>Kenyamanan (Comfort)</h4>
                        <p>Menyediakan kamar siap huni (fully furnished) dengan sirkulasi udara baik, AC dingin, fasilitas bersama lengkap, serta suasana belajar yang tenang dan bebas gangguan.</p>
                    </div>
                </div>
                <div class="tt-value-card">
                    <div class="tt-value-card-icon"><i data-lucide="shield-check"></i></div>
                    <div class="tt-value-info">
                        <h4>Keamanan & Privasi (Safety)</h4>
                        <p>Mengutamakan rasa aman penghuni melalui sistem satu pintu gerbang khusus putri, pemantauan CCTV 24 jam di koridor, dan lingkungan perumahan yang aman.</p>
                    </div>
                </div>
                <div class="tt-value-card">
                    <div class="tt-value-card-icon"><i data-lucide="heart-handshake"></i></div>
                    <div class="tt-value-info">
                        <h4>Keramahan Pelayanan (Hospitality)</h4>
                        <p>Pengelola kost yang responsif dan tinggal dekat lokasi untuk membantu menangani pemeliharaan fasilitas, kebutuhan darurat, serta keluhan penghuni kapan pun diperlukan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- 8. ATURAN KOST -->
    <section class="tt-section">
        <div class="tt-container">
            <div class="tt-section-header">
                <h2 class="tt-section-title">Aturan Kost Elmi Sarah</h2>
                <p class="tt-section-sub">Demi kenyamanan, ketertiban, dan keharmonisan bersama, seluruh penghuni diharapkan mematuhi aturan berikut.</p>
            </div>

            <div class="tt-aturan-grid">
                <div class="tt-aturan-card">
                    <h4>Check-in / Check-out</h4>
                    <p>Check-in dapat dilakukan mulai pukul 08.00 WIB hingga 20.00 WIB. Calon penghuni diwajibkan melapor terlebih dahulu kepada pengelola kost sebelum menempati kamar.</p>
                    <p>Proses check-out paling lambat dilakukan pukul 12.00 WIB pada hari terakhir masa sewa. Pastikan melakukan koordinasi penyerahan kunci dengan pengelola.</p>
                </div>
                
                <div class="tt-aturan-card">
                    <h4>Kebijakan Deposit</h4>
                    <p>Dalam proses booking kamar di Kost Elmi Sarah, calon penghuni diwajibkan menyetorkan deposit sebesar 30% dari total sewa sebagai tanda jadi pemesanan.</p>
                    <p>Pelunasan sisa biaya sewa kamar dapat dilakukan saat dalam perjalanan menuju lokasi kost, atau ketika telah tiba dan siap menempati kamar.</p>
                </div>

                <div class="tt-aturan-card">
                    <h4>Informasi Pembatalan</h4>
                    <p>Apabila calon penghuni membatalkan pesanan secara sepihak setelah menyetor deposit, maka dana deposit yang telah masuk tidak dapat dikembalikan.</p>
                    <p>Jika ada kendala khusus atau force majeure yang mendesak, mohon segera komunikasikan dengan pihak pengelola kost untuk solusi terbaik.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 9. BOTTOM CTA -->
    <section class="tt-section alternate">
        <div class="tt-container">
            <div class="tt-cta-box-modern">
                <h2>Yuk, Cari Hunian Idealmu Sekarang!</h2>
                <p>Temukan kenyamanan, ketenangan, dan rasa kekeluargaan layaknya rumah sendiri di Kost Elmi Sarah.</p>
                <a href="?page=rooms" class="tt-cta-btn-modern">Lihat Kamar Tersedia</a>
            </div>
        </div>
    </section>

</div> <!-- /tt-page -->

<script>
// Testimonials Slider JS
const ulasanData = <?= json_encode($ulasan_data) ?>;
let currentTesti = 0;

function updateTesti(index) {
    const data = ulasanData[index];
    const textEl = document.getElementById('testi-text');
    const nameEl = document.getElementById('testi-name');
    const imgEl = document.getElementById('testi-img');
    const ratingEl = document.getElementById('testi-rating');
    const ulasanImgEl = document.getElementById('testi-ulasan-img');

    // Animate out
    textEl.style.opacity = 0;
    nameEl.style.opacity = 0;
    imgEl.style.opacity = 0;
    ratingEl.style.opacity = 0;
    if (ulasanImgEl) ulasanImgEl.style.opacity = 0;

    setTimeout(() => {
        textEl.innerText = '"' + data.komentar + '"';
        nameEl.innerText = data.nama;
        
        if (data.foto && data.foto.trim() !== '') {
            imgEl.src = 'uploads/profil/' + data.foto;
        } else {
            imgEl.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(data.nama) + '&background=random&color=fff';
        }

        if (ulasanImgEl) {
            if (data.foto_ulasan && data.foto_ulasan.trim() !== '') {
                ulasanImgEl.src = 'uploads/ulasan/' + data.foto_ulasan;
            } else {
                ulasanImgEl.src = 'frontend/assets/image/kost.png';
            }
        }

        // Update rating stars
        let ratingHtml = '';
        const rating = data.rating ? parseInt(data.rating) : 5;
        for (let i = 1; i <= 5; i++) {
            ratingHtml += (i <= rating) ? '★' : '☆';
        }
        ratingEl.innerHTML = ratingHtml;

        // Animate in
        textEl.style.transition = "opacity 0.3s";
        nameEl.style.transition = "opacity 0.3s";
        imgEl.style.transition = "opacity 0.3s";
        ratingEl.style.transition = "opacity 0.3s";
        if (ulasanImgEl) ulasanImgEl.style.transition = "opacity 0.3s";

        textEl.style.opacity = 1;
        nameEl.style.opacity = 1;
        imgEl.style.opacity = 1;
        ratingEl.style.opacity = 1;
        if (ulasanImgEl) ulasanImgEl.style.opacity = 1;
    }, 300);
}

function prevTesti() {
    currentTesti = (currentTesti === 0) ? ulasanData.length - 1 : currentTesti - 1;
    updateTesti(currentTesti);
}

function nextTesti() {
    currentTesti = (currentTesti === ulasanData.length - 1) ? 0 : currentTesti + 1;
    updateTesti(currentTesti);
}

if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>