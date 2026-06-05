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
   TENTANG STYLES
═══════════════════════════════════════════════════ */
.tt-page {
    background-color: #fff;
    color: #1f2937;
    font-family: 'Inter', sans-serif;
    padding-bottom: 0;
}
.tt-container {
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 24px;
}
/* Section Titles */
.tt-section-title {
    font-size: 26px;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 28px;
}
.tt-text-muted {
    color: #6b7280;
    line-height: 1.8;
}

/* 1. HERO SECTION */
.tt-hero-wrap {
    padding: 30px 24px 80px;
    max-width: 1200px;
    margin: 0 auto;
}
.tt-hero {
    display: flex;
    border-radius: 60px 20px 20px 60px;
    overflow: hidden;
    height: 480px;
}
.tt-hero-left {
    flex: 1;
    background: #EEEADF;
    padding: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.tt-hero-title {
    font-size: 46px;
    font-weight: 900;
    line-height: 1.15;
    color: #000;
    margin: 0 0 16px;
}
.tt-hero-sub {
    font-size: 15px;
    color: #4b5563;
    margin: 0 0 32px;
    max-width: 380px;
    line-height: 1.6;
}
.tt-hero-btn {
    align-self: flex-start;
    background: #000;
    color: #fff;
    padding: 14px 32px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
    transition: background 0.2s;
}
.tt-hero-btn:hover { background: #333; color: #fff; }
.tt-hero-right {
    flex: 1;
}
.tt-hero-right img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* 2. FASILITAS */
.tt-fasilitas-section {
    padding: 20px 0 80px;
}
.tt-fas-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}
.tt-fas-card {
    background: #EEEADF;
    border-radius: 20px;
    padding: 24px;
}
.tt-fas-card.long {
    grid-column: span 2;
}
.tt-fas-card h3 {
    font-size: 16px;
    font-weight: 800;
    margin: 0 0 20px;
    color: #1f2937;
}
.tt-fas-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.tt-fas-list.two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 20px;
}
.tt-fas-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #374151;
    font-weight: 500;
}
.tt-fas-item i {
    width: 18px;
    height: 18px;
    stroke-width: 2;
}

/* 3. CREAM WRAPPER (Keuntungan, Aturan, Testi) */
.tt-cream-wrapper {
    background: #EEEADF;
    border-top-right-radius: 120px;
    border-bottom-left-radius: 120px;
    padding: 100px 0;
    margin-bottom: 80px;
}

/* 4. KEUNTUNGAN TINGGAL */
.tt-keuntungan-section {
    margin-bottom: 100px;
}
.tt-keuntungan-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px 30px;
}
.tt-k-card {
    display: flex;
    flex-direction: column;
}
.tt-k-img {
    width: 100%;
    height: 180px;
    border-radius: 20px;
    object-fit: cover;
    margin-bottom: 16px;
}
.tt-k-card h4 {
    font-size: 17px;
    font-weight: 800;
    margin: 0 0 8px;
    color: #1f2937;
}
.tt-k-card p {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
    line-height: 1.6;
}

/* 5. ATURAN KOST */
.tt-aturan-section {
    margin-bottom: 100px;
}
.tt-aturan-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.tt-aturan-card {
    background: #e4dec8; /* Sedikit lebih gelap dari cream background */
    border-radius: 20px;
    padding: 32px 24px;
}
.tt-aturan-card h4 {
    font-size: 15px;
    font-weight: 800;
    margin: 0 0 16px;
    text-align: center;
    color: #1f2937;
}
.tt-aturan-card p, .tt-aturan-card li {
    font-size: 13px;
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 12px;
    text-align: justify;
}
.tt-aturan-card ul {
    padding-left: 18px;
    margin: 0;
}

/* 6. KATA PENGHUNI */
.tt-testi-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}
.tt-testi-img {
    width: 100%;
    border-radius: 30px;
    object-fit: cover;
    height: 340px;
}
.tt-testi-content h2 {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 20px;
}
.tt-testi-content p {
    font-size: 15px;
    color: #4b5563;
    line-height: 1.8;
    margin: 0 0 30px;
}
.tt-testi-user {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 30px;
}
.tt-testi-user img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}
.tt-testi-user-info h5 {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 700;
}
.tt-testi-user-info span {
    font-size: 13px;
    color: #6b7280;
}
.tt-testi-nav {
    display: flex;
    gap: 12px;
}
.tt-testi-btn {
    width: 44px;
    height: 44px;
    background: #1f2937;
    color: #fff;
    border: none;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}
.tt-testi-btn:hover { background: #111; }
.tt-testi-btn i { width: 18px; height: 18px; }

/* 7. SEKILAS & TARGET */
.tt-text-section {
    padding: 20px 0 60px;
}
.tt-text-content {
    max-width: 100%;
}
.tt-text-content p {
    margin-bottom: 16px;
    font-size: 14px;
    line-height: 1.8;
    color: #4b5563;
    text-align: justify;
}
.tt-target-list {
    counter-reset: target-counter;
    list-style: none;
    padding: 0;
    margin: 32px 0;
}
.tt-target-list li {
    position: relative;
    padding-left: 24px;
    margin-bottom: 24px;
}
.tt-target-list li::before {
    counter-increment: target-counter;
    content: counter(target-counter) ".";
    position: absolute;
    left: 0;
    top: 0;
    font-size: 15px;
    font-weight: 800;
    color: #1f2937;
}
.tt-target-list h5 {
    font-size: 15px;
    font-weight: 700;
    margin: 0 0 6px;
    color: #1f2937;
}
.tt-target-list p {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
    line-height: 1.6;
    text-align: left;
}

/* 8. BOTTOM CTA */
.tt-cta-section {
    padding: 0 0 80px;
}
.tt-cta-box {
    display: flex;
    background: #EEEADF;
    border-radius: 40px;
    overflow: hidden;
    height: 320px;
}
.tt-cta-img {
    flex: 1;
    height: 100%;
}
.tt-cta-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.tt-cta-content {
    flex: 1;
    padding: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
}
.tt-cta-content h2 {
    font-size: 32px;
    font-weight: 900;
    margin: 0 0 24px;
    line-height: 1.3;
    color: #1f2937;
}
.tt-cta-btn {
    background: #000;
    color: #fff;
    padding: 14px 32px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
    transition: opacity 0.2s;
}
.tt-cta-btn:hover { opacity: 0.85; color:#fff; }

/* RESPONSIVE */
@media (max-width: 900px) {
    .tt-hero { border-radius: 40px; }
    .tt-hero, .tt-cta-box, .tt-testi-section {
        flex-direction: column;
        height: auto;
    }
    .tt-hero-left, .tt-cta-content { padding: 40px; }
    .tt-hero-right, .tt-cta-img { height: 300px; }
    .tt-fas-grid { grid-template-columns: 1fr 1fr; }
    .tt-fas-card.long { grid-column: span 2; }
    .tt-keuntungan-grid, .tt-aturan-grid { grid-template-columns: 1fr 1fr; }
    .tt-testi-section { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .tt-fas-grid, .tt-keuntungan-grid, .tt-aturan-grid { grid-template-columns: 1fr; }
    .tt-fas-card.long { grid-column: span 1; }
    .tt-fas-list.two-col { grid-template-columns: 1fr; }
    .tt-cream-wrapper { border-top-right-radius: 60px; border-bottom-left-radius: 60px; padding: 60px 0; }
}
</style>

<div class="tt-page">

    <!-- 1. HERO SECTION -->
    <div class="tt-hero-wrap">
        <div class="tt-hero">
            <div class="tt-hero-left">
                <h1 class="tt-hero-title">Kost That Grows<br>With You</h1>
                <p class="tt-hero-sub">Mewujudkan hunian berkualitas dan terjangkau untuk semua orang, di setiap fase kehidupan.</p>
                <a href="?page=rooms" class="tt-hero-btn">Cari Hunian</a>
            </div>
            <div class="tt-hero-right">
                <img src="frontend/assets/image/tentang.png" alt="Kost Elmi Sarah">
            </div>
        </div>
    </div>

    <!-- 2. FASILITAS KOST -->
    <div class="tt-container tt-fasilitas-section">
        <h2 class="tt-section-title">Fasilitas Kost Elmi Sarah</h2>
        <div class="tt-fas-grid">
            
            <div class="tt-fas-card">
                <h3>Fasilitas Kamar</h3>
                <ul class="tt-fas-list">
                    <li class="tt-fas-item"><i data-lucide="bed-single"></i> Kasur</li>
                    <li class="tt-fas-item"><i data-lucide="door-closed"></i> Lemari Pakaian</li>
                    <li class="tt-fas-item"><i data-lucide="layout-grid"></i> Meja</li>
                    <li class="tt-fas-item"><i data-lucide="armchair"></i> Kursi</li>
                    <li class="tt-fas-item"><i data-lucide="air-vent"></i> AC </li>
                    <li class="tt-fas-item"><i data-lucide="bed"></i> Tempat Tidur</li>
                </ul>
            </div>

            <div class="tt-fas-card">
                <h3>Fasilitas Parkir</h3>
                <ul class="tt-fas-list">
                    <li class="tt-fas-item"><i data-lucide="car"></i> Mobil</li>
                    <li class="tt-fas-item"><i data-lucide="bike"></i> Motor / Sepeda</li>
                </ul>
            </div>

            <div class="tt-fas-card long">
                <h3>Fasilitas Bersama</h3>
                <ul class="tt-fas-list two-col">
                    <li class="tt-fas-item"><i data-lucide="sofa"></i> Ruang Tamu</li>
                    <li class="tt-fas-item"><i data-lucide="chef-hat"></i> Dapur</li>
                    <li class="tt-fas-item"><i data-lucide="sun"></i> Tempat Jemuran</li>
                    <li class="tt-fas-item"><i data-lucide="refrigerator"></i> Kulkas</li>
                    <li class="tt-fas-item"><i data-lucide="cctv"></i> CCTV</li>
                    <li class="tt-fas-item"><i data-lucide="shield-check"></i> Keamanan 24 Jam</li>
                    <li class="tt-fas-item"><i data-lucide="coffee"></i> Dispenser</li>
                    <li class="tt-fas-item"><i data-lucide="wifi"></i> Wi-Fi</li>
                    <li class="tt-fas-item"><i data-lucide="landmark"></i> Mushola</li>
                </ul>
            </div>

            <div class="tt-fas-card">
                <h3>Fasilitas Kamar Mandi</h3>
                <ul class="tt-fas-list">
                    <li class="tt-fas-item"><i data-lucide="bath"></i> Kloset Duduk</li>
                    <li class="tt-fas-item"><i data-lucide="droplet"></i> Ember</li>
                    <li class="tt-fas-item"><i data-lucide="droplets"></i> Gayung</li>
                </ul>
            </div>

        </div>
    </div>

    <!-- 3. CREAM WRAPPER (Extreme curves) -->
    <div class="tt-cream-wrapper">
        
        <!-- 4. KEUNTUNGAN TINGGAL -->
        <div class="tt-container tt-keuntungan-section">
            <h2 class="tt-section-title">Keuntungan Tinggal di Elmi Sarah</h2>
            <div class="tt-keuntungan-grid">
                
                <div class="tt-k-card">
                    <img src="frontend/assets/image/Suasana Kamar/IMG_4438.jpg" alt="Lingkungan Aman" class="tt-k-img">
                    <h4>Lingkungan Aman & Nyaman</h4>
                    <p>Hunian dilengkapi furniture lengkap termasuk AC, Wi-Fi, dan Kamar mandi dalam</p>
                </div>
                <div class="tt-k-card">
                    <img src="frontend/assets/image/suasana lantai satu/IMG_3859.jpg" alt="Lokasi Strategis" class="tt-k-img">
                    <h4>Lokasi Strategis</h4>
                    <p>Lokasi hunian yang dekat dengan beberapa kampus dan fasilitas umum</p>
                </div>
                <div class="tt-k-card">
                    <img src="frontend/assets/image/Suasana Kamar/IMG_4445.jpg" alt="Fasilitas Lengkap" class="tt-k-img">
                    <h4>Fasilitas Lengkap</h4>
                    <p>Tersedia berbagai fasilitas bersama yang mendukung kenyamanan Anda sehari-hari</p>
                </div>
                <div class="tt-k-card">
                    <img src="frontend/assets/image/suasana lantai satu/IMG_3869.jpg" alt="Pelayanan Responsif" class="tt-k-img">
                    <h4>Pelayanan Responsif</h4>
                    <p>Admin dan pengelola kost yang sigap membantu kebutuhan dan keluhan penghuni</p>
                </div>
                <div class="tt-k-card">
                    <img src="frontend/assets/image/Suasana Kamar/IMG_4441.jpg" alt="Harga Terjangkau" class="tt-k-img">
                    <h4>Harga Terjangkau</h4>
                    <p>Hunian berkualitas dengan harga sewa yang bersaing dan berbagai pilihan tipe kamar</p>
                </div>
                <div class="tt-k-card">
                    <img src="frontend/assets/image/suasana lantai dua/IMG_3898.jpg" alt="Suasana Tenang" class="tt-k-img">
                    <h4>Suasana Tenang & Damai</h4>
                    <p>Lingkungan kost yang kondusif, cocok untuk mahasiswa maupun pekerja yang butuh fokus</p>
                </div>

            </div>
        </div>

        <!-- 5. ATURAN KOST -->
        <div class="tt-container tt-aturan-section">
            <h2 class="tt-section-title">Aturan Kost Elmi Sarah</h2>
            <div class="tt-aturan-grid">
                
                <div class="tt-aturan-card">
                    <h4>Check-in/Out</h4>
                    <p>Proses check-in di Kos Elmisarah dapat dilakukan mulai pukul 08.00 WIB hingga 20.00 WIB. Calon penghuni diwajibkan untuk melapor terlebih dahulu kepada pengelola kos sebelum menempati kamar.</p>
                    <p>Sementara itu, proses check-out dilakukan paling lambat pukul 12.00 WIB pada hari terakhir masa sewa. Penghuni diharapkan memberitahukan kepada pengelola kos terlebih dahulu.</p>
                </div>
                
                <div class="tt-aturan-card">
                    <h4>Deposit</h4>
                    <p>Dalam proses pemesanan kamar di Kos Elmisarah, calon penghuni diwajibkan untuk membayar deposit sebesar 30% dari total biaya sewa kamar sebagai tanda jadi atau bukti pemesanan kamar.</p>
                    <p>Selanjutnya, pelunasan sisa pembayaran dapat dilakukan ketika penyewa kos sedang dalam perjalanan menuju lokasi kos ataupun pada saat penyewa telah tiba dan mulai menempati kamar.</p>
                </div>

                <div class="tt-aturan-card">
                    <h4>Informasi Pembatalan</h4>
                    <p>Dalam hal pembatalan pemesanan kamar, calon penghuni diharapkan untuk memberitahukan kepada pengelola kos terlebih dahulu.</p>
                    <ul>
                        <li>Apabila pembatalan dilakukan setelah pembayaran deposit, maka deposit yang telah dibayarkan tidak dapat dikembalikan.</li>
                        <li>Apabila terdapat kondisi tertentu yang tidak dapat dihindari, calon penghuni dapat melakukan komunikasi dengan pengelola kos.</li>
                    </ul>
                </div>

            </div>
        </div>

        <!-- 6. KATA PENGHUNI -->
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
                    'nama' => 'Elmi Sarah',
                    'komentar' => 'Kost ini sangat nyaman dan bersih. Fasilitasnya lengkap seperti AC, WiFi cepat, dan kamar yang sudah fully furnished, jadi tinggal bawa barang pribadi saja. Lokasinya juga strategis dekat kampus dan tempat makan.',
                    'foto' => '',
                    'foto_ulasan' => ''
                ]
            ];
        }
        ?>
        <?php 
        $ulasan_foto_url = !empty($ulasan_data[0]['foto_ulasan']) && file_exists(__DIR__ . '/../../../uploads/ulasan/' . $ulasan_data[0]['foto_ulasan']) 
            ? 'uploads/ulasan/' . htmlspecialchars($ulasan_data[0]['foto_ulasan']) 
            : 'frontend/assets/image/kost.png';
        ?>
        <div class="tt-container tt-testi-section">
            <img id="testi-ulasan-img" src="<?= $ulasan_foto_url ?>" alt="Kata Penghuni" class="tt-testi-img" style="object-fit: cover;">
            <div class="tt-testi-content">
                <h2>Kata Penghuni</h2>
                
                <div id="testi-container">
                    <p id="testi-text" style="min-height: 110px;">"<?= htmlspecialchars($ulasan_data[0]['komentar']) ?>"</p>
                    
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
                            <div id="testi-rating" style="color: #f59e0b; font-size: 14px; margin-top: 4px; letter-spacing: 2px;">
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

        <script>
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
        </script>
    </div> <!-- End Cream Wrapper -->

    <!-- 7. SEKILAS & TARGET -->
    <div class="tt-container tt-text-section">
        <h2 class="tt-section-title">Sekilas Tentang Elmi Sarah</h2>
        <div class="tt-text-content">
            <p>Nama "Elmi Sarah" diambil dari gabungan nama Bapak dan Ibu, pengelola dan pemilik kost ini. "Elmi" dari nama Bapak, sedangkan "Sarah" dari nama Ibu. Bapak dan Ibu mendirikan kost ini dengan tujuan memberikan tempat tinggal yang nyaman dan aman bagi para pendatang, khususnya mahasiswa dan pekerja yang merantau.</p>
            <p>Awal mula berdirinya kost ini dari sebuah rumah tua yang kami renovasi dan dikembangkan menjadi bangunan kost dengan beberapa kamar. Seiring berjalannya waktu, melihat antusiasme dan kebutuhan masyarakat akan tempat tinggal yang berkualitas, kami terus melakukan renovasi dan penambahan fasilitas.</p>
            <p>Kami berkomitmen untuk terus menjaga kualitas hunian kami, kebersihan, dan keamanan demi kenyamanan seluruh penghuni. Kami berharap Elmi Sarah bisa menjadi "rumah kedua" bagi siapapun yang tinggal di sini, memberikan kedamaian dan ketenangan untuk beristirahat setelah aktivitas padat sehari-hari.</p>
        </div>
    </div>

    <div class="tt-container tt-text-section">
        <h2 class="tt-section-title">Target Penghuni</h2>
        <div class="tt-text-content">
            <p>Kost Elmi Sarah Terbuka untuk semua kalangan, baik mahasiswa, pekerja, hingga mereka yang sedang mencari tempat persinggahan yang nyaman dan tenang.</p>
            
            <ul class="tt-target-list">
                <li>
                    <h5>Mahasiswa/i</h5>
                    <p>Lokasi kami strategis, dekat dengan beberapa kampus dan universitas di sekitar, sangat cocok bagi mahasiswa untuk menuntut ilmu.</p>
                </li>
                <li>
                    <h5>Dosen/Pegawai</h5>
                    <p>Ketenangan dan fasilitas lengkap di kost kami sangat mendukung produktivitas para pekerja.</p>
                </li>
                <li>
                    <h5>Karyawan/Pekerja</h5>
                    <p>Fasilitas parkir yang aman dan luas, serta akses 24 jam sangat fleksibel untuk rutinitas pekerja.</p>
                </li>
            </ul>

            <p>Dengan fasilitas lengkap dan lingkungan yang nyaman, kami berharap Elmi Sarah menjadi tempat yang ideal bagi siapa saja yang sedang mencari tempat tinggal. Datang dan rasakan sendiri kenyamanannya, kami siap menyambut dan memberikan pelayanan terbaik untuk Anda.</p>
        </div>
    </div>

    <!-- 8. BOTTOM CTA -->
    <div class="tt-container tt-cta-section">
        <div class="tt-cta-box">
            <div class="tt-cta-img">
                <img src="frontend/assets/image/tentang.png" alt="Yuk Cari Hunian">
            </div>
            <div class="tt-cta-content">
                <h2>Yuk cari Hunian<br>untukmu sekarang!</h2>
                <a href="?page=rooms" class="tt-cta-btn">Cari Hunian</a>
            </div>
        </div>
    </div>

</div> <!-- /tt-page -->

<script>
if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>