<?php /* lokasi-section.php - Komponen Lokasi untuk Beranda */ ?>
<section class="lokasi-section" id="lokasi-preview" style="padding: 60px 0; background: #fff;">
    <div class="container-custom">
        <h2 class="section-title" style="text-align: center; margin-bottom: 40px;">Lokasi Strategis</h2>
        
        <style>
            .lokasi-map-wrap-home { width: 100%; height: 350px; border-radius: 20px; overflow: hidden; background: #e5e7eb; margin-bottom: 30px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
            .lokasi-map-wrap-home iframe { width: 100%; height: 100%; border: none; display: block; }
            .lokasi-info-grid-home { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
            .lokasi-info-card-home { background: #f3efe6; border-radius: 16px; padding: 24px 20px; text-align: center; }
            .lokasi-info-card-home .icon { color: #a58145; margin-bottom: 16px; display: flex; justify-content: center; }
            .lokasi-info-card-home .icon svg { width: 40px; height: 40px; stroke-width: 1.5; }
            .lokasi-info-card-home h4 { font-size: 18px; font-weight: 800; color: #021f3a; margin-bottom: 8px; }
            .lokasi-info-card-home p { font-size: 14px; color: #021f3a; line-height: 1.6; margin: 0; }
        </style>

        <div class="lokasi-map-wrap-home">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d396.75969285597114!2d108.5342117!3d-6.7335593!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f1d595f6c29ad%3A0x1611318a8f504622!2sPondok%20ELMISARAH!5e0!3m2!1sid!2sid!4v1699999999999!5m2!1sid!2sid"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
        
        <div class="lokasi-info-grid-home">
            <div class="lokasi-info-card-home">
                <div class="icon"><i data-lucide="map-pin"></i></div>
                <h4>Alamat</h4>
                <p>Komplek PDK, Jl. Perjuangan blok A No.34, Sunyaragi, Kec. Kesambi, Kota Cirebon, Jawa Barat 45132</p>
            </div>
            <a href="https://wa.me/6285933675790" target="_blank" style="text-decoration: none; color: inherit; display: block;">
                <div class="lokasi-info-card-home">
                    <div class="icon"><i data-lucide="message-circle"></i></div>
                    <h4>Whatsapp</h4>
                    <p>0859-3367-5790</p>
                </div>
            </a>
        </div>
        
        <div style="text-align: center; margin-top: 32px;">
            <a href="index.php?page=lokasi" style="color: #1f2937; font-weight: 700; text-decoration: underline; font-size: 15px;">Lihat Detail Lokasi & Sekitarnya</a>
        </div>
    </div>
</section>
