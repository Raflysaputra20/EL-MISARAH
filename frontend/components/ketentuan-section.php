<?php /* ketentuan-section.php - Komponen Ketentuan untuk Beranda */ ?>
<section class="ketentuan-section" id="ketentuan-preview" style="padding: 60px 0; background: #fff;">
    <div class="container-custom">
        <h2 class="section-title" style="text-align: center; margin-bottom: 40px;">Ketentuan Elmi Sarah</h2>
        
        <style>
            .ket-grid {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 24px;
            }
            .ket-card {
                background: #f3efe6;
                padding: 24px;
                border-radius: 16px;
            }
            .ket-card h3 {
                font-size: 18px;
                font-weight: 800;
                color: #1f2937;
                margin-bottom: 12px;
            }
            .ket-card p {
                font-size: 14px;
                color: #4b5563;
                line-height: 1.6;
                text-align: justify;
                margin: 0;
            }
            @media (max-width: 768px) {
                .ket-grid { grid-template-columns: 1fr; }
            }
        </style>
        
        <div class="ket-grid">
            <div class="ket-card">
                <h3>Check-in/Out</h3>
                <p>Check-in dapat dilakukan mulai pukul 08.00 WIB hingga 20.00 WIB. Check-out paling lambat pukul 12.00 WIB pada hari terakhir masa sewa.</p>
            </div>
            <div class="ket-card">
                <h3>Deposit</h3>
                <p>Calon penghuni diwajibkan untuk membayar deposit sebesar 30% dari total biaya sewa kamar sebagai tanda jadi atau bukti pemesanan kamar.</p>
            </div>
            <div class="ket-card">
                <h3>Pembatalan</h3>
                <p>Apabila pembatalan dilakukan setelah pembayaran deposit, maka deposit yang telah dibayarkan tidak dapat dikembalikan, dianggap sebagai tanda jadi pemesanan.</p>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 32px;">
            <a href="index.php?page=ketentuan" style="color: #1f2937; font-weight: 700; text-decoration: underline; font-size: 15px;">Baca Seluruh Ketentuan</a>
        </div>
    </div>
</section>
