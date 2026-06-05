<style>
    .app-navbar { position: relative !important; background: #EEEADF !important; }
    .navbar-logo, .navbar-menu a, .login-link, .register-btn, .auth-separator { color: #1f2937 !important; }
    .nav-arrow { stroke: #1f2937 !important; }
    .mobile-toggle svg { stroke: #1f2937 !important; }
    .dropdown-menu { border: 1px solid #e5e7eb; }
    .gl-hero { background: #1a1f2e; padding: 56px 0 48px; text-align: center; margin-bottom: 60px; }
    .gl-hero-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #c9a84c; margin: 0 0 10px; }
    .gl-hero-title { font-size: 36px; font-weight: 900; color: #fff; margin: 0 0 10px; line-height: 1.2; }
    .gl-hero-title span { color: #c9a84c; }
    .gl-hero-sub { font-size: 14px; color: #9ca3af; margin: 0 auto 28px; max-width: 440px; line-height: 1.6; }
    .lokasi-badge { display: inline-block; background: rgba(255,255,255,0.15); color: #fff; font-size: 13px; font-weight: 600; padding: 6px 16px; border-radius: 999px; margin-bottom: 18px; }
    .lokasi-map-wrap { width: 100%; height: 420px; border-radius: 20px; overflow: hidden; background: #e5e7eb; margin-bottom: 48px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
    .lokasi-map-wrap iframe { width: 100%; height: 100%; border: none; display: block; }
    .lokasi-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 48px; }
    .lokasi-info-card { background: #f3efe6; border-radius: 16px; padding: 36px 20px; text-align: center; }
    .lokasi-info-card .icon { color: #a58145; margin-bottom: 16px; display: flex; justify-content: center; }
    .lokasi-info-card .icon svg { width: 56px; height: 56px; stroke-width: 1.5; }
    .lokasi-info-card h4 { font-size: 20px; font-weight: 800; color: #021f3a; margin-bottom: 12px; }
    .lokasi-info-card p { font-size: 16px; color: #021f3a; line-height: 1.6; margin: 0; }
    .nearby-section { margin-bottom: 60px; }
    .nearby-section h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: 24px; color: #021f3a; }
    .nearby-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 48px; }
    .nearby-item { display: flex; align-items: flex-start; gap: 10px; }
    .nearby-icon { color: #374151; flex-shrink: 0; margin-top: 2px; }
    .nearby-icon svg { width: 20px; height: 20px; stroke-width: 2; }
    .nearby-info h5 { font-size: 15px; font-weight: 500; color: #374151; margin: 0; line-height: 1.4; }
    .lokasi-cta { background: #eeeadf; border-radius: 24px; padding: 48px; text-align: center; margin-bottom: 60px; }
    .lokasi-cta h3 { font-weight: 800; font-size: 1.8rem; margin-bottom: 10px; }
    .lokasi-cta p { color: #6b7280; margin-bottom: 22px; }
    .btn-lokasi { background: #1f2937; color: #fff; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; display: inline-block; margin: 4px; }
    .btn-lokasi:hover { opacity: 0.85; color: #fff; }
    @media (max-width: 768px) {
        .lokasi-info-grid { grid-template-columns: 1fr; }
        .nearby-grid { grid-template-columns: 1fr; }
        .lokasi-map-wrap { height: 280px; }
    }
</style>

<div class="gl-hero">
    <div class="container-custom">
        <p class="gl-hero-eyebrow">Kost Elmi Sarah</p>
        <h1 class="gl-hero-title">Lokasi <span>Elmi Sarah</span></h1>
        <p class="gl-hero-sub">Berlokasi strategis, dekat dengan kampus dan berbagai fasilitas pendukung kehidupan sehari-hari.</p>
    </div>
</div>

<div class="container-custom">

    <!-- PETA -->
    <div class="lokasi-map-wrap">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d396.75969285597114!2d108.5342117!3d-6.7335593!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f1d595f6c29ad%3A0x1611318a8f504622!2sPondok%20ELMISARAH!5e0!3m2!1sid!2sid!4v1699999999999!5m2!1sid!2sid"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>

    <!-- INFO CARDS -->
    <div class="lokasi-info-grid">
        <div class="lokasi-info-card">
            <div class="icon"><i data-lucide="map-pin"></i></div>
            <h4>Alamat</h4>
            <p>Komplek PDK, Jl. Perjuangan blok A No.34, Sunyaragi, Kec. Kesambi, Kota Cirebon, Jawa Barat 45132</p>
        </div>
        <div class="lokasi-info-card">
            <div class="icon"><i data-lucide="message-circle"></i></div>
            <h4>Whatsapp</h4>
            <p>0812-3458-7890</p>
        </div>
        <div class="lokasi-info-card">
            <div class="icon"><i data-lucide="clock"></i></div>
            <h4>Jam Operasional</h4>
            <p>Hari Kerja : 08.00 - 17.00<br>Hari Libur : 08.00 - 14.00</p>
        </div>
    </div>

    <!-- FASILITAS TERDEKAT -->
    <div class="nearby-section">

        <h2>Universitas:</h2>
        <div class="nearby-grid">
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>UIN Siber Syekh Nurjadi Cirebon</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Universitas Pariwisata</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Universitas 17 Agustus</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Sekolah Tinggi Menejemen Informatika Ilmu Komputer (STIKMI)</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Universitas Swadaya Gunung Jati Cirebon</h5>
                </div>
            </div>
        </div>

        <h2>Pusat Perbelanjaan:</h2>
        <div class="nearby-grid">
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Lotte</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>CSB Mall</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Alfamart</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Grage Mall</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Indomart</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Transmart</h5>
                </div>
            </div>
        </div>

        <h2>Tempat Olahraga dan Hiburan</h2>
        <div class="nearby-grid" style="margin-bottom: 0;">
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Stadion Bima</h5>
                </div>
            </div>
            <div class="nearby-item">
                <div class="nearby-icon"><i data-lucide="map-pin"></i></div>
                <div class="nearby-info">
                    <h5>Cipto Park</h5>
                </div>
            </div>
        </div>

    </div>

    <!-- CTA -->
    <div class="lokasi-cta">
        <h3>Ingin Berkunjung?</h3>
        <p>Hubungi kami untuk membuat janji survey kamar atau tanyakan lebih lanjut.</p>
        <a href="index.php?page=kontak" class="btn-lokasi">Hubungi Kami</a>
        <a href="https://maps.app.goo.gl/tY4fB8KgXP31P3gW8" target="_blank" class="btn-lokasi" style="background:#11a654;">Buka di Google Maps</a>
    </div>
</div>
