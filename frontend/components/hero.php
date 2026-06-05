<section class="hero-section" id="top">
    <div class="hero-overlay"></div>

    <div class="hero-inner">
        <p class="hero-subtitle">Semester baru, Kost baru</p>

        <h1 class="hero-title">
            Hunian Kost Putri Nyaman, Aman,<br>
            dan Strategis untuk Aktivitas Anda
        </h1>

        <a href="#daftar-kamar" class="hero-btn">Cari Kamar</a>

        <div class="hero-social">
            <a href="#" aria-label="TikTok">
                <svg viewBox="0 0 24 24"><path d="M16.6 5.82c1.05.75 2.1 1.15 3.4 1.22v3.1c-1.25-.04-2.34-.3-3.4-.84v5.46c0 3.28-2.08 5.24-5.02 5.24-2.72 0-4.86-2.02-4.86-4.64 0-2.79 2.2-4.72 5.14-4.72.34 0 .62.03.9.08v3.16a3.1 3.1 0 0 0-.91-.13c-1.16 0-1.94.62-1.94 1.56 0 .9.72 1.5 1.67 1.5 1.1 0 1.72-.67 1.72-1.96V3.5h3.3v2.32Z"/></svg>
            </a>
            <a href="#" aria-label="Instagram">
                <svg viewBox="0 0 24 24"><path d="M7.75 2h8.5A5.76 5.76 0 0 1 22 7.75v8.5A5.76 5.76 0 0 1 16.25 22h-8.5A5.76 5.76 0 0 1 2 16.25v-8.5A5.76 5.76 0 0 1 7.75 2Zm0 2A3.75 3.75 0 0 0 4 7.75v8.5A3.75 3.75 0 0 0 7.75 20h8.5A3.75 3.75 0 0 0 20 16.25v-8.5A3.75 3.75 0 0 0 16.25 4h-8.5ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.25-2.2a1.05 1.05 0 1 1 0 2.1 1.05 1.05 0 0 1 0-2.1Z"/></svg>
            </a>
            <a href="#" aria-label="Facebook">
                <svg viewBox="0 0 24 24"><path d="M22 12.06C22 6.48 17.52 2 11.94 2S2 6.48 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.84c0-2.5 1.5-3.88 3.78-3.88 1.1 0 2.24.2 2.24.2v2.46H15.2c-1.24 0-1.63.77-1.63 1.56v1.88h2.77l-.44 2.91h-2.33V22C18.34 21.24 22 17.08 22 12.06Z"/></svg>
            </a>
        </div>

        <div class="gallery-card">
            <div class="gallery-thumb" id="heroGalleryThumb">
                <img src="frontend/assets/image/suasana lantai satu/IMG_3859.jpg" class="thumb-slide active" alt="Gallery preview">
                <img src="frontend/assets/image/suasana lantai dua/IMG_3898.jpg" class="thumb-slide" alt="Gallery preview">
                <img src="frontend/assets/image/fasilitas parkir/IMG_3887.jpg" class="thumb-slide" alt="Gallery preview">
                <img src="frontend/assets/image/fasilitas lainnya/IMG_3861.jpg" class="thumb-slide" alt="Gallery preview">
            </div>

            <div class="gallery-text">
                <h6>Gallery Elmi Sarah</h6>
                <p>Kamar, Fasilitas, Lokasi dll</p>
                <a href="index.php?page=gallery">Lihat Selengkapnya</a>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('#heroGalleryThumb .thumb-slide');
    if (slides.length > 1) {
        let currentSlide = 0;
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 3000);
    }
});
</script>