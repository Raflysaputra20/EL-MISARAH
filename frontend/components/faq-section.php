<?php /* faq-section.php - Komponen FAQ untuk Beranda */ ?>
<section class="faq-section" id="faq-preview" style="padding: 60px 0; background: #f9f9f9;">
    <div class="container-custom">
        <h2 class="section-title" style="text-align: center; margin-bottom: 40px;">FAQ</h2>
        
        <style>
            .faq-grid-home { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; text-align: left; }
            .faq-item-home { background: #fff; border-radius: 16px; padding: 24px 32px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
            .faq-question-home { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 14px; transition: margin-bottom 0.3s ease; }
            .faq-item-home.open .faq-question-home { margin-bottom: 16px; align-items: flex-start; }
            .faq-question-home h4 { font-size: 17px; font-weight: 800; color: #000; margin: 0; line-height: 1.4; flex: 1; }
            .faq-icon-home { width: 44px; height: 44px; border-radius: 50%; background: #1e293b; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: transform 0.3s ease; }
            .faq-item-home.open .faq-icon-home { transform: rotate(180deg); }
            .faq-icon-home svg { width: 22px; height: 22px; stroke-width: 2.5; }
            .faq-answer-home { font-size: 14.5px; color: #6b7280; line-height: 1.6; margin: 0; max-height: 0; overflow: hidden; opacity: 0; transition: all 0.3s ease; }
            .faq-item-home.open .faq-answer-home { max-height: 200px; opacity: 1; }
            @media (max-width: 768px) { .faq-grid-home { grid-template-columns: 1fr; } }
        </style>

        <div class="faq-grid-home">
            <div class="faq-item-home" onclick="toggleFaqHome(this)">
                <div class="faq-question-home">
                    <h4>Apakah ada uang deposit?</h4>
                    <div class="faq-icon-home"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer-home">Ya, sebagai tanda jadi atau bukti pemesanan kamar</p>
            </div>
            <div class="faq-item-home" onclick="toggleFaqHome(this)">
                <div class="faq-question-home">
                    <h4>Apakah pembayaran bisa bulanan?</h4>
                    <div class="faq-icon-home"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer-home">Ya. bisa per bulan, per enam bulan atau bisa langsung pertahun</p>
            </div>
            <div class="faq-item-home" onclick="toggleFaqHome(this)">
                <div class="faq-question-home">
                    <h4>Apakah sudah termasuk listrik?</h4>
                    <div class="faq-icon-home"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer-home">Tidak. listrik diluar dari pembayaran kos, jadi listrik bayar masing-masing sesuai kebutuhan</p>
            </div>
            <div class="faq-item-home" onclick="toggleFaqHome(this)">
                <div class="faq-question-home">
                    <h4>Bagaimana jika ada kerusakan di kamar?</h4>
                    <div class="faq-icon-home"><i data-lucide="chevron-down"></i></div>
                </div>
                <p class="faq-answer-home">Silakan laporkan melalui fitur pengaduan khusus untuk penghuni kost agar segera ditangani.</p>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 32px;">
            <a href="index.php?page=faq" style="color: #1f2937; font-weight: 700; text-decoration: underline; font-size: 15px;">Lihat Semua FAQ</a>
        </div>
    </div>
</section>
<script>
function toggleFaqHome(item) {
    item.classList.toggle('open');
}
</script>
