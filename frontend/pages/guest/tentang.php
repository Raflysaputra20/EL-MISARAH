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
   DESIGN SYSTEM VARIABLES
   ═══════════════════════════════════════════════════ */
:root {
    --green-primary: #11a654;
    --green-dark: #0b8e47;
    --green-light: #e8f7f0;
    --bg-light: #ffffff;
    --bg-cream: #faf8f4;
    --bg-cream-dark: #eeeadf;
    --dark-text: #1f2937;
    --muted-text: #6b7280;
    --border-color: #e5e7eb;
}

/* ═══════════════════════════════════════════════════
   PAGE & CONTAINERS
   ═══════════════════════════════════════════════════ */
.tt-page {
    background-color: var(--bg-light);
    color: var(--dark-text);
    font-family: 'Poppins', sans-serif;
    padding-bottom: 0;
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
    background-color: var(--bg-cream);
}

/* Typography */
.tt-section-header {
    text-align: center;
    margin-bottom: 48px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}
.tt-section-title {
    font-size: 32px;
    font-weight: 800;
    color: var(--dark-text);
    margin-bottom: 16px;
    letter-spacing: -0.5px;
}
.tt-section-sub {
    font-size: 15px;
    color: var(--muted-text);
    line-height: 1.6;
}

/* ═══════════════════════════════════════════════════
   1. HERO SECTION (Full-Width Banner)
   ═══════════════════════════════════════════════════ */
.tt-hero-section {
    position: relative;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.65) 100%), url('frontend/assets/image/tentang.png') no-repeat center center;
    background-size: cover;
    height: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #fff;
    padding: 0 24px;
}
.tt-hero-content {
    max-width: 760px;
    z-index: 2;
}
.tt-hero-title {
    font-size: 52px;
    font-weight: 900;
    line-height: 1.2;
    margin-bottom: 16px;
    letter-spacing: -1px;
    text-transform: capitalize;
}
.tt-hero-sub {
    font-size: 17px;
    line-height: 1.6;
    margin-bottom: 32px;
    color: rgba(255, 255, 255, 0.9);
}
.tt-hero-btn {
    display: inline-block;
    background: var(--green-primary);
    color: #fff;
    padding: 14px 38px;
    border-radius: 30px;
    font-weight: 700;
    text-decoration: none;
    font-size: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(17, 166, 84, 0.3);
}
.tt-hero-btn:hover {
    background: var(--green-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(17, 166, 84, 0.4);
    color: #fff;
}

/* ═══════════════════════════════════════════════════
   2. SEKILAS & STATS SECTION
   ═══════════════════════════════════════════════════ */
.tt-split-section {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 60px;
    align-items: center;
}
.tt-split-left h2 {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 24px;
    color: var(--dark-text);
    letter-spacing: -0.5px;
}
.tt-split-left p {
    font-size: 14.5px;
    line-height: 1.75;
    color: var(--muted-text);
    margin-bottom: 20px;
    text-align: justify;
}
.tt-stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}
.tt-stat-card {
    background: #fff;
    border: 1px solid var(--border-color);
    padding: 24px 28px;
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
}
.tt-stat-num {
    font-size: 42px;
    font-weight: 900;
    color: var(--green-primary);
    line-height: 1;
    margin-bottom: 6px;
}
.tt-stat-label {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--dark-text);
}
.tt-stat-desc {
    font-size: 12.5px;
    color: var(--muted-text);
    margin-top: 2px;
}

/* ═══════════════════════════════════════════════════
   3. DYNAMIC TABS (FASILITAS KOST)
   ═══════════════════════════════════════════════════ */
.tt-tabs-container {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}
.tt-tab-btn {
    border: none;
    background: var(--bg-cream-dark);
    color: var(--dark-text);
    padding: 12px 26px;
    border-radius: 30px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: 'Poppins', sans-serif;
}
.tt-tab-btn:hover {
    background: var(--green-light);
    color: var(--green-primary);
}
.tt-tab-btn.active {
    background: var(--green-primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(17, 166, 84, 0.25);
}
.tt-tab-content {
    display: none;
}
.tt-tab-content.active {
    display: block;
    animation: tabFadeIn 0.4s ease forwards;
}
@keyframes tabFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.tt-fas-grid-modern {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
.tt-fas-item-modern {
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.01);
    transition: all 0.2s ease;
}
.tt-fas-item-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03);
}
.tt-fas-item-modern i {
    color: var(--green-primary);
    width: 22px;
    height: 22px;
    flex-shrink: 0;
}
.tt-fas-item-modern span {
    font-size: 14px;
    font-weight: 600;
    color: var(--dark-text);
}

/* ═══════════════════════════════════════════════════
   4. KEUNTUNGAN TINGGAL (Rukita Card Grid)
   ═══════════════════════════════════════════════════ */
.tt-keuntungan-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}
.tt-k-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid var(--border-color);
    padding: 36px 28px;
    text-align: center;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.02);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.tt-k-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.05);
}
.tt-k-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--green-light);
    color: var(--green-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(17, 166, 84, 0.1);
}
.tt-k-icon-circle i {
    width: 26px;
    height: 26px;
}
.tt-k-card h4 {
    font-size: 16.5px;
    font-weight: 700;
    margin: 0 0 12px;
    color: var(--dark-text);
}
.tt-k-card p {
    font-size: 13px;
    color: var(--muted-text);
    line-height: 1.6;
    margin: 0;
}

/* ═══════════════════════════════════════════════════
   5. TARGET PENGHUNI (2-Column Values List)
   ═══════════════════════════════════════════════════ */
.tt-target-section {
    display: grid;
    grid-template-columns: 0.9fr 1.1fr;
    gap: 60px;
    align-items: center;
}
.tt-target-left h2 {
    font-size: 32px;
    font-weight: 800;
    color: var(--dark-text);
    margin-bottom: 20px;
    letter-spacing: -0.5px;
}
.tt-target-left p {
    font-size: 14.5px;
    color: var(--muted-text);
    line-height: 1.7;
}
.tt-target-card-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.tt-target-card {
    display: flex;
    gap: 20px;
    background: #fff;
    padding: 24px;
    border-radius: 18px;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    transition: all 0.3s ease;
}
.tt-target-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
}
.tt-target-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: var(--green-light);
    color: var(--green-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.tt-target-icon i {
    width: 22px;
    height: 22px;
}
.tt-target-info h4 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 6px;
    color: var(--dark-text);
}
.tt-target-info p {
    font-size: 13px;
    color: var(--muted-text);
    line-height: 1.6;
    margin: 0;
}

/* ═══════════════════════════════════════════════════
   6. ATURAN KOST
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
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease;
}
.tt-aturan-card:hover {
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.04);
}
.tt-aturan-card h4 {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 18px;
    color: var(--dark-text);
    border-left: 4px solid var(--green-primary);
    padding-left: 12px;
}
.tt-aturan-card p, .tt-aturan-card li {
    font-size: 13px;
    color: var(--muted-text);
    line-height: 1.6;
    margin-bottom: 12px;
}
.tt-aturan-card ul {
    padding-left: 18px;
    margin: 0;
}

/* ═══════════════════════════════════════════════════
   7. KATA PENGHUNI (TESTIMONI SLIDER)
   ═══════════════════════════════════════════════════ */
.tt-testi-wrap {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 60px;
    align-items: center;
}
.tt-testi-img {
    width: 100%;
    border-radius: 24px;
    object-fit: cover;
    height: 380px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}
.tt-testi-content h2 {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 24px;
    letter-spacing: -0.5px;
}
.tt-testi-content p {
    font-size: 14.5px;
    color: var(--muted-text);
    line-height: 1.8;
    margin: 0 0 32px;
    font-style: italic;
}
.tt-testi-user {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 32px;
}
.tt-testi-user img {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.tt-testi-user-info h5 {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 700;
    color: var(--dark-text);
}
.tt-testi-user-info span {
    font-size: 12.5px;
    color: var(--muted-text);
}
.tt-testi-nav {
    display: flex;
    gap: 12px;
}
.tt-testi-btn {
    width: 46px;
    height: 46px;
    background: var(--dark-text);
    color: #fff;
    border: none;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.tt-testi-btn:hover {
    background: var(--green-primary);
    transform: translateY(-1px);
}
.tt-testi-btn i {
    width: 20px;
    height: 20px;
}

/* ═══════════════════════════════════════════════════
   8. BOTTOM CTA (Green Gradient Banner)
   ═══════════════════════════════════════════════════ */
.tt-cta-box-modern {
    background: linear-gradient(135deg, var(--green-primary) 0%, var(--green-dark) 100%);
    border-radius: 24px;
    padding: 64px 40px;
    text-align: center;
    color: #fff;
    box-shadow: 0 12px 35px rgba(17, 166, 84, 0.25);
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
    color: var(--green-primary);
    padding: 14px 40px;
    border-radius: 30px;
    font-weight: 700;
    text-decoration: none;
    font-size: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}
.tt-cta-btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    background: var(--green-light);
    color: var(--green-primary);
}

/* ═══════════════════════════════════════════════════
   RESPONSIVE LAYOUTS
   ═══════════════════════════════════════════════════ */
@media (max-width: 992px) {
    .tt-hero-title { font-size: 42px; }
    .tt-split-section, .tt-target-section, .tt-testi-wrap {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    .tt-keuntungan-grid, .tt-aturan-grid {
        grid-template-columns: 1fr 1fr;
    }
    .tt-fas-grid-modern {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 600px) {
    .tt-hero-title { font-size: 32px; }
    .tt-section { padding: 60px 0; }
    .tt-keuntungan-grid, .tt-aturan-grid, .tt-fas-grid-modern {
        grid-template-columns: 1fr;
    }
    .tt-cta-box-modern { padding: 48px 24px; }
    .tt-cta-box-modern h2 { font-size: 28px; }
}
</style>

<div class="tt-page">

    <!-- 1. HERO SECTION -->
    <section class="tt-hero-section">
        <div class="tt-hero-content">
            <h1 class="tt-hero-title">Kost That Grows With You</h1>
            <p class="tt-hero-sub">Mewujudkan hunian berkualitas, aman, dan nyaman khusus mahasiswi selama menempuh masa studi perkuliahan.</p>
            <a href="?page=rooms" class="tt-hero-btn">Cari Hunian Sekarang</a>
        </div>
    </section>

    <!-- 2. SEKILAS & STATS SECTION -->
    <section class="tt-section">
        <div class="tt-container tt-split-section">
            <div class="tt-split-left">
                <h2>Sekilas Tentang Elmi Sarah</h2>
                <p>Nama "Elmi Sarah" diambil dari gabungan nama Bapak dan Ibu, pengelola dan pemilik kost ini. "Elmi" dari nama Bapak, sedangkan "Sarah" dari nama Ibu. Bapak dan Ibu mendirikan kost ini dengan tujuan memberikan tempat tinggal yang nyaman dan aman bagi para pendatang, khususnya mahasiswa dan pekerja yang merantau.</p>
                <p>Awal mula berdirinya kost ini dari sebuah rumah tua yang kami renovasi dan dikembangkan menjadi bangunan kost dengan beberapa kamar. Seiring berjalannya waktu, melihat antusiasme dan kebutuhan masyarakat akan tempat tinggal yang berkualitas, kami terus melakukan renovasi dan penambahan fasilitas.</p>
                <p>Kami berkomitmen untuk terus menjaga kualitas hunian kami, kebersihan, dan keamanan demi kenyamanan seluruh penghuni. Kami berharap Elmi Sarah bisa menjadi "rumah kedua" bagi siapapun yang tinggal di sini, memberikan kedamaian dan ketenangan untuk beristirahat setelah aktivitas padat sehari-hari.</p>
            </div>
            
            <div class="tt-split-right">
                <div class="tt-stats-grid">
                    <div class="tt-stat-card">
                        <span class="tt-stat-num">100+</span>
                        <span class="tt-stat-label">Mahasiswi Aktif</span>
                        <span class="tt-stat-desc">Mahasiswi dari berbagai universitas yang mempercayakan huniannya pada kami.</span>
                    </div>
                    <div class="tt-stat-card">
                        <span class="tt-stat-num">30+</span>
                        <span class="tt-stat-label">Pilihan Kamar Siap Huni</span>
                        <span class="tt-stat-desc">Tipe kamar bervariasi khusus putri dengan fasilitas lengkap dan AC dingin.</span>
                    </div>
                    <div class="tt-stat-card">
                        <span class="tt-stat-num">4.8 ★</span>
                        <span class="tt-stat-label">Rating Kepuasan</span>
                        <span class="tt-stat-desc">Berdasarkan ulasan asli mahasiswi mengenai kebersihan, kenyamanan, dan pelayanan.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. FASILITAS KOST (DYNAMIC TABS) -->
    <section class="tt-section alternate">
        <div class="tt-container">
            <div class="tt-section-header">
                <h2 class="tt-section-title">Fasilitas Kost Elmi Sarah</h2>
                <p class="tt-section-sub">Nikmati berbagai kemudahan hidup sehari-hari dengan ragam fasilitas pendukung terlengkap di kelasnya.</p>
            </div>

            <!-- Tab Switcher -->
            <div class="tt-tabs-container">
                <button class="tt-tab-btn active" onclick="switchTab(event, 'tab-kamar')">Fasilitas Kamar</button>
                <button class="tt-tab-btn" onclick="switchTab(event, 'tab-kamarmandi')">Kamar Mandi</button>
                <button class="tt-tab-btn" onclick="switchTab(event, 'tab-bersama')">Fasilitas Bersama</button>
                <button class="tt-tab-btn" onclick="switchTab(event, 'tab-parkir')">Area Parkir</button>
            </div>

            <!-- Tab Contents -->
            <div id="tab-kamar" class="tt-tab-content active">
                <div class="tt-fas-grid-modern">
                    <div class="tt-fas-item-modern"><i data-lucide="bed"></i><span>Kasur Busa Tebal</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="door-closed"></i><span>Lemari Pakaian</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="layout-grid"></i><span>Meja Belajar/Kerja</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="armchair"></i><span>Kursi Duduk</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="snowflake"></i><span>Air Conditioner (AC)</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="bed-single"></i><span>Tempat Tidur Kokoh</span></div>
                </div>
            </div>

            <div id="tab-kamarmandi" class="tt-tab-content">
                <div class="tt-fas-grid-modern">
                    <div class="tt-fas-item-modern"><i data-lucide="bath"></i><span>Kloset Duduk Modern</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="droplet"></i><span>Bak Ember Mandi</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="droplets"></i><span>Gayung Air</span></div>
                </div>
            </div>

            <div id="tab-bersama" class="tt-tab-content">
                <div class="tt-fas-grid-modern">
                    <div class="tt-fas-item-modern"><i data-lucide="sofa"></i><span>Ruang Tamu & Bersantai</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="chef-hat"></i><span>Dapur & Peralatan Masak</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="sun"></i><span>Area Jemur Pakaian</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="refrigerator"></i><span>Kulkas Penyimpan</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="cctv"></i><span>Pantauan CCTV 24 Jam</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="shield-check"></i><span>Keamanan Lingkungan</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="cup-soda"></i><span>Dispenser Air Bersih</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="wifi"></i><span>Wi-Fi Internet Cepat</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="landmark"></i><span>Mushola Sholat</span></div>
                </div>
            </div>

            <div id="tab-parkir" class="tt-tab-content">
                <div class="tt-fas-grid-modern">
                    <div class="tt-fas-item-modern"><i data-lucide="car"></i><span>Parkir Mobil Aman</span></div>
                    <div class="tt-fas-item-modern"><i data-lucide="bike"></i><span>Parkir Motor & Sepeda</span></div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. KEUNTUNGAN TINGGAL (RUKITA STYLE) -->
    <section class="tt-section">
        <div class="tt-container">
            <div class="tt-section-header">
                <h2 class="tt-section-title">Keuntungan Tinggal di Elmi Sarah</h2>
                <p class="tt-section-sub">Kami memberikan lebih dari sekadar kamar tidur. Rasakan pengalaman tinggal yang dirancang demi produktivitas Anda.</p>
            </div>

            <div class="tt-keuntungan-grid">
                <div class="tt-k-card">
                    <div class="tt-k-icon-circle"><i data-lucide="shield-check"></i></div>
                    <h4>Lingkungan Aman & Nyaman</h4>
                    <p>Fasilitas lengkap terawat, pantauan CCTV, dan sistem penguncian gerbang yang aman untuk ketenangan istirahat.</p>
                </div>
                <div class="tt-k-card">
                    <div class="tt-k-icon-circle"><i data-lucide="map-pin"></i></div>
                    <h4>Lokasi Sangat Strategis</h4>
                    <p>Berada di zona strategis, dekat dengan beberapa universitas ternama, pusat kuliner, dan transportasi umum.</p>
                </div>
                <div class="tt-k-card">
                    <div class="tt-k-icon-circle"><i data-lucide="sparkles"></i></div>
                    <h4>Fasilitas Siap Pakai</h4>
                    <p>Kamar ber-AC dan fully furnished. Anda cukup membawa pakaian dan perlengkapan pribadi saja saat check-in.</p>
                </div>
                <div class="tt-k-card">
                    <div class="tt-k-icon-circle"><i data-lucide="heart-handshake"></i></div>
                    <h4>Pelayanan Responsif</h4>
                    <p>Admin dan pengelola kost siap sedia membantu menangani masalah pemeliharaan kamar dan keluhan dengan sigap.</p>
                </div>
                <div class="tt-k-card">
                    <div class="tt-k-icon-circle"><i data-lucide="coins"></i></div>
                    <h4>Harga Jujur & Terjangkau</h4>
                    <p>Harga sewa bulanan bersaing tanpa biaya tambahan tersembunyi, pas untuk anggaran mahasiswi.</p>
                </div>
                <div class="tt-k-card">
                    <div class="tt-k-icon-circle"><i data-lucide="moon"></i></div>
                    <h4>Suasana Tenang & Fokus</h4>
                    <p>Kawasan kost tenang dan kondusif, cocok bagi Anda yang membutuhkan konsentrasi belajar.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. TARGET PENGHUNI (2-COLUMN VALUES LIST) -->
    <section class="tt-section alternate">
        <div class="tt-container tt-target-section">
            <div class="tt-target-left">
                <h2>Target Penghuni (Kost Putri)</h2>
                <p>Kost Elmi Sarah merupakan hunian khusus putri eksklusif yang dikhususkan hanya untuk **mahasiswi (perempuan)**. Kami menghadirkan hunian yang aman, tertib, dan kondusif untuk mendukung kelancaran masa studi akademis Anda.</p>
            </div>
            
            <div class="tt-split-right">
                <div class="tt-target-card-list">
                    <div class="tt-target-card">
                        <div class="tt-target-icon"><i data-lucide="graduation-cap"></i></div>
                        <div class="tt-target-info">
                            <h4>Mahasiswi Aktif</h4>
                            <p>Hunian yang dikhususkan bagi mahasiswi untuk kelancaran perkuliahan dengan dukungan Wi-Fi cepat dan lingkungan belajar yang tenang.</p>
                        </div>
                    </div>
                    <div class="tt-target-card">
                        <div class="tt-target-icon"><i data-lucide="shield-check"></i></div>
                        <div class="tt-target-info">
                            <h4>Keamanan & Privasi Khusus Putri</h4>
                            <p>Area kost aman, terjaga privasinya, memberikan ketenangan penuh bagi mahasiswi maupun orang tua di rumah.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. ATURAN KOST -->
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

    <!-- 7. KATA PENGHUNI (TESTIMONIALS SLIDER) -->
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
        <div class="tt-container tt-testi-wrap">
            <img id="testi-ulasan-img" src="<?= $ulasan_foto_url ?>" alt="Kata Penghuni" class="tt-testi-img">
            <div class="tt-testi-content">
                <h2>Kata Penghuni</h2>
                
                <div id="testi-container">
                    <p id="testi-text">"<?= htmlspecialchars($ulasan_data[0]['komentar']) ?>"</p>
                    
                    <div class="tt-testi-user">
                        <?php 
                        $foto_url = !empty($ulasan_data[0]['foto']) && file_exists(__DIR__ . '/../../../uploads/profil/' . $ulasan_data[0]['foto']) 
                            ? 'uploads/profil/' . htmlspecialchars($ulasan_data[0]['foto']) 
                            : 'https://ui-avatars.com/api/?name=' . urlencode($ulasan_data[0]['nama']) . '&background=random&color=fff';
                        ?>
                        <img id="testi-img" src="<?= $foto_url ?>" alt="Profil">
                        <div class="tt-testi-user-info">
                            <h5 id="testi-name"><?= htmlspecialchars($ulasan_data[0]['nama']) ?></h5>
                            <span>Penghuni Kost</span>
                            <div id="testi-rating" style="color: #facc15; font-size: 14px; margin-top: 4px; letter-spacing: 2px;">
                                <?php 
                                $rating = isset($ulasan_data[0]['rating']) ? (int)$ulasan_data[0]['rating'] : 5;
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (count($ulasan_data) > 1): ?>
                <div class="tt-testi-nav">
                    <button class="tt-testi-btn" onclick="prevTesti()"><i data-lucide="chevron-left"></i></button>
                    <button class="tt-testi-btn" onclick="nextTesti()"><i data-lucide="chevron-right"></i></button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 8. BOTTOM CTA (Green Gradient Banner) -->
    <section class="tt-section">
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
// Dynamic Tabs Switcher Function
function switchTab(evt, tabId) {
    const contents = document.querySelectorAll('.tt-tab-content');
    contents.forEach(content => content.classList.remove('active'));

    const buttons = document.querySelectorAll('.tt-tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));

    document.getElementById(tabId).classList.add('active');
    evt.currentTarget.classList.add('active');
}

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