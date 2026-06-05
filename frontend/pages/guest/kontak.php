<style>
    /* Override navbar style for this page if needed */
    .app-navbar { position: relative !important; background: #EEEADF !important; }
    .navbar-logo, .navbar-menu a, .login-link, .register-btn, .auth-separator { color: #1f2937 !important; }
    .nav-arrow { stroke: #1f2937 !important; }
    .mobile-toggle svg { stroke: #1f2937 !important; }
    .dropdown-menu { border: 1px solid #e5e7eb; }

    .hero-kontak {
        position: relative;
        height: 350px;
        border-radius: 20px;
        overflow: hidden;
        margin-top: 20px;
    }
    
    .hero-kontak img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .hero-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 0 20px;
    }

    .contact-card {
        background-color: #f1ebd9;
        border-radius: 12px;
        padding: 25px 15px;
        text-align: center;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .contact-icon {
        color: #b58150;
        margin-bottom: 15px;
        width: 32px;
        height: 32px;
    }

    .contact-title {
        font-weight: 700;
        font-size: 15px;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .contact-text {
        font-size: 13px;
        color: #4b5563;
        margin: 0;
        line-height: 1.5;
    }

    .form-input-kontak {
        border-radius: 8px !important;
        padding: 12px 15px !important;
        border: 1px solid #e5e7eb !important;
        font-size: 14px !important;
        color: #374151 !important;
        box-shadow: none !important;
    }
    .form-input-kontak::placeholder {
        color: #9ca3af;
    }
    
    .form-label-kontak {
        font-weight: 500;
        font-size: 13px;
        color: #374151;
        margin-bottom: 8px;
    }

    .btn-kirim {
        background-color: #b58150;
        color: white;
        font-weight: 600;
        border-radius: 8px;
        padding: 12px;
        border: none;
        transition: all 0.3s;
        font-size: 14px;
    }
    .btn-kirim:hover {
        background-color: #9c6c40;
        color: white;
    }
</style>

<div class="container mb-5 pb-5">
    
    <!-- HERO SECTION -->
    <section class="hero-kontak mb-5">
        <img src="frontend/assets/image/kontak.png" alt="Kontak Kami">
        <div class="hero-overlay">
            <h1 class="text-white fw-bold mb-3" style="font-size: 2.5rem;">Kontak Kami</h1>
            <p class="text-white" style="max-width: 600px; font-size: 14px; line-height: 1.6; font-weight: 300;">
                Jika Anda memiliki pertanyaan, saran, atau ingin bekerja sama, silakan hubungi kami melalui form di bawah ini. Kami akan dengan senang hati membantu Anda.
            </p>
        </div>
    </section>

    <!-- CONTENT SECTION -->
    <div class="row g-5">
        
        <!-- LEFT SIDE: CARDS & MAP -->
        <div class="col-lg-5">
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="contact-card">
                        <i data-lucide="phone" class="contact-icon"></i>
                        <h6 class="contact-title">Telepon</h6>
                        <p class="contact-text">0812-3456-7890</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="contact-card">
                        <i data-lucide="message-circle" class="contact-icon"></i>
                        <h6 class="contact-title">Whatsapp</h6>
                        <p class="contact-text">0812-3458-7890</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="contact-card">
                        <i data-lucide="mail" class="contact-icon"></i>
                        <h6 class="contact-title">Email</h6>
                        <p class="contact-text" style="font-size: 12px;">elmisarah79@gmail.com</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="contact-card">
                        <i data-lucide="clock" class="contact-icon"></i>
                        <h6 class="contact-title">Jam Operasional</h6>
                        <p class="contact-text" style="font-size: 11px;">
                            Hari Kerja : 08.00 - 17.00<br>
                            Hari Libur : 08.00 - 14.00
                        </p>
                    </div>
                </div>
            </div>

            <!-- MAP -->
            <div>
                <img src="frontend/assets/image/kontak_maps.png" alt="Map" class="w-100" style="border-radius: 12px; border: 1px solid #e5e7eb; object-fit: cover; height: 200px;">
            </div>
        </div>

        <!-- RIGHT SIDE: FORM -->
        <div class="col-lg-7">
            <h3 class="fw-bold mb-3" style="color: #1f2937;">Hubungi Kami</h3>
            <p class="mb-4" style="font-size: 14px; color: #6b7280; line-height: 1.6;">
                Silakan isi formulir berikut untuk mengirimkan pesan kepada kami. Tim kami akan segera merespons pertanyaan atau kebutuhan Anda.
            </p>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label-kontak">Nama</label>
                    <input type="text" class="form-control form-input-kontak" name="nama" placeholder="Nama" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label-kontak">Email</label>
                    <input type="email" class="form-control form-input-kontak" name="email" placeholder="Email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label-kontak">Subjek</label>
                    <input type="text" class="form-control form-input-kontak" name="subjek" placeholder="Subjek" required>
                </div>

                <div class="mb-4">
                    <label class="form-label-kontak">Pesan</label>
                    <textarea class="form-control form-input-kontak" name="pesan" rows="5" placeholder="Detail Pesan Anda" required></textarea>
                </div>

                <button type="submit" class="btn btn-kirim w-100">Kirim Pesan</button>
            </form>
        </div>

    </div>
</div>
