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
    .faq-wrapper { max-width: 1000px; margin: 0 auto; text-align: center; }
    .faq-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; text-align: left; margin-bottom: 60px; padding-top: 20px; }
    .faq-item { background: #f3efe6; border-radius: 16px; padding: 24px 32px; cursor: pointer; transition: all 0.3s ease; }
    .faq-question { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 14px; transition: margin-bottom 0.3s ease; }
    .faq-item.open .faq-question { margin-bottom: 16px; align-items: flex-start; }
    .faq-question h4 { font-size: 17px; font-weight: 800; color: #000; margin: 0; line-height: 1.4; flex: 1; }
    .faq-icon { width: 44px; height: 44px; border-radius: 50%; background: #1e293b; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: transform 0.3s ease; }
    .faq-item.open .faq-icon { transform: rotate(180deg); }
    .faq-icon svg { width: 22px; height: 22px; stroke-width: 2.5; }
    .faq-answer { font-size: 14.5px; color: #6b7280; line-height: 1.6; margin: 0; max-height: 0; overflow: hidden; opacity: 0; transition: all 0.3s ease; }
    .faq-item.open .faq-answer { max-height: 200px; opacity: 1; }
    @media (max-width: 768px) {
        .faq-grid { grid-template-columns: 1fr; }
    }
    .faq-cta { background: #eeeadf; border-radius: 24px; padding: 48px; text-align: center; margin: 56px 0; }
    .faq-cta h3 { font-weight: 800; font-size: 1.8rem; margin-bottom: 10px; }
    .faq-cta p { color: #6b7280; margin-bottom: 22px; }
    .btn-faq { background: #1f2937; color: #fff; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; display: inline-block; margin: 4px; }
    .btn-faq:hover { opacity: 0.85; color: #fff; }
    .btn-faq-out { border: 2px solid #1f2937; color: #1f2937; padding: 10px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; display: inline-block; margin: 4px; }
    .btn-faq-out:hover { background: #1f2937; color: #fff; }
</style>

<div class="gl-hero">
    <div class="container-custom">
        <p class="gl-hero-eyebrow">Kost Elmi Sarah</p>
        <h1 class="gl-hero-title">FAQ <span>Elmi Sarah</span></h1>
        <p class="gl-hero-sub">Temukan jawaban atas pertanyaan umum seputar Kost Elmi Sarah di sini.</p>
    </div>
</div>

<div class="container-custom" style="padding-bottom: 20px;">

    <div class="faq-wrapper">
        <div class="faq-grid">
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <h4>Apakah ada uang deposit?</h4>
                    <div class="faq-icon"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer">Ya, sebagai tanda jadi atau bukti pemesanan kamar</p>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <h4>Apakah pembayaran bisa bulanan?</h4>
                    <div class="faq-icon"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer">Ya. bisa per bulan, per enam bulan atau bisa langsung pertahun</p>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <h4>Metode pembayaran apa saja yang tersedia?</h4>
                    <div class="faq-icon"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer">Pembayaran bisa dilakukan melalui transfer bank atau e-wallet.</p>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <h4>Apakah sudah termasuk listrik?</h4>
                    <div class="faq-icon"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer">Tidak. listrik diluar dari pembayaran kos, jadi listrik bayar masing-masing sesuai kebutuhan</p>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <h4>Apakah kamar masih tersedia?</h4>
                    <div class="faq-icon"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer">Silakan cek di halaman "Daftar Kamar" atau hubungi admin untuk info terbaru</p>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <h4>Apakah bisa memilih kamar sendiri?</h4>
                    <div class="faq-icon"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer">Ya, penghuni dapat memilih kamar sesuai ketersediaan</p>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <h4>Kost ini dekat dengan apa saja?</h4>
                    <div class="faq-icon"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer">Dekat dengan beberapa kampus, minimarket, dan tempat makan.silahkan lihat bagian LOKASI</p>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <h4>Bagaimana jika ada kerusakan di kamar?</h4>
                    <div class="faq-icon"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer">Silakan laporkan melalui fitur pengaduan khusus untuk penghuni kost agar segera ditangani.</p>
            </div>
        </div>
    </div>

    <div class="faq-cta">
        <h3>Masih Ada Pertanyaan?</h3>
        <p>Jangan ragu untuk menghubungi kami langsung, kami siap membantu!</p>
        <a href="index.php?page=kontak" class="btn-faq">Hubungi Kami</a>
        <a href="index.php?page=booking" class="btn-faq-out">Booking Sekarang</a>
    </div>
</div>

<script>
function toggleFaq(item) {
    item.classList.toggle('open');
}
</script>
