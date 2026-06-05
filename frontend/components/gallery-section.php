<?php /* gallery-section.php - Komponen Gallery untuk Beranda */ ?>
<section class="gallery-section" id="gallery-preview" style="padding: 60px 0; background: #f9f9f9;">
    <div class="container-custom">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 class="section-title" style="margin-bottom: 0;">Gallery Elmi Sarah</h2>
            <a href="index.php?page=gallery" class="btn-detail-gallery" style="background: #1f2937; color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 14px; transition: opacity 0.2s;">Lihat Selengkapnya</a>
        </div>
        
        <style>
            .gl-suasana-grid-home {
                display: grid;
                grid-template-columns: 1.3fr 1fr 1fr;
                grid-template-rows: 240px 240px;
                gap: 12px;
            }
            .gl-suasana-grid-home .ph {
                background: #e0ddd8;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .gl-suasana-grid-home .ph img, .gl-suasana-grid-home .ph video { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s ease; }
            .gl-suasana-grid-home .ph:hover img, .gl-suasana-grid-home .ph:hover video { transform: scale(1.05); }
            
            .gl-suasana-grid-home .ph-main  { grid-column: 1; grid-row: 1 / 3; border-radius: 16px; }
            .gl-suasana-grid-home .ph-tr-1  { grid-column: 2; grid-row: 1; }
            .gl-suasana-grid-home .ph-tr-2  { grid-column: 3; grid-row: 1; }
            .gl-suasana-grid-home .ph-br    { grid-column: 2 / 4; grid-row: 2; }
            
            @media (max-width: 768px) {
                .gl-suasana-grid-home { grid-template-columns: 1fr 1fr; grid-template-rows: 150px 120px 120px; }
                .gl-suasana-grid-home .ph-main { grid-column: 1 / 3; grid-row: 1; }
                .gl-suasana-grid-home .ph-tr-1 { grid-column: 1; grid-row: 2; }
                .gl-suasana-grid-home .ph-tr-2 { grid-column: 2; grid-row: 2; }
                .gl-suasana-grid-home .ph-br   { grid-column: 1 / 3; grid-row: 3; }
            }
            .btn-detail-gallery:hover { opacity: 0.85; }
        </style>
        
        <div class="gl-suasana-grid-home">
            <div class="ph ph-main"><img src="frontend/assets/image/suasana%20lantai%20satu/IMG_3859.jpg" alt="Suasana Lantai Satu"></div>
            <div class="ph ph-tr-1"><img src="frontend/assets/image/suasana%20lantai%20dua/IMG_3898.jpg" alt="Suasana Lantai Dua"></div>
            <div class="ph ph-tr-2"><img src="frontend/assets/image/Suasana%20Kamar/IMG_4438.jpg" alt="Suasana Kamar"></div>
            <div class="ph ph-br"><img src="frontend/assets/image/fasilitas%20parkir/IMG_3887.jpg" alt="Fasilitas Parkir"></div>
        </div>
    </div>
</section>
