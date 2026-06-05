<nav class="app-navbar">
    <div class="nav-inner">

        <!-- LOGO -->
        <a href="index.php" class="navbar-logo">Elmi Sarah</a>

        <!-- MENU -->
        <div class="navbar-menu">

            <!-- DROPDOWN BERANDA -->
            <div class="nav-dropdown">
                <a href="index.php" class="nav-beranda">
                    Beranda
                    <svg class="nav-arrow" viewBox="0 0 24 24">
                        <path d="M6 9L12 15L18 9"></path>
                    </svg>
                </a>

                <div class="dropdown-menu">
                    <a href="index.php#daftar-kamar">Kamar</a>
                    <a href="index.php#gallery-preview">Gallery</a>
                    <a href="index.php?page=tentang">Ulasan</a>
                    <a href="index.php#ketentuan-preview">Ketentuan</a>
                    <a href="index.php#faq-preview">Faq</a>
                    <a href="index.php#lokasi-preview">Lokasi</a>
                </div>
            </div>

            <!-- MENU LAIN -->
            <a href="index.php?page=tentang">Tentang</a>
            <a href="index.php?page=booking">Booking</a>
            <a href="index.php?page=kontak">Kontak</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?page=riwayat_booking">Riwayat Booking</a>
                <?php if (($_SESSION['role'] ?? '') === 'penghuni'): ?>
                    <a href="index.php?page=dashboard">Dashboard Saya</a>
                <?php elseif (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="backend/admin/dashboard.php">Dashboard</a>
                <?php endif; ?>
            <?php endif; ?>

        </div>

        <!-- AUTH -->
        <div class="navbar-auth">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="#" class="login-link" onclick="openLoginModal(event)">Masuk</a>
                <span class="auth-separator">|</span>
                <a href="#" class="register-btn" onclick="openRegisterModal(event)">Daftar</a>
            <?php else: ?>
                <?php 
                    $fullName = $_SESSION['nama'] ?? 'User';
                    $firstName = explode(' ', trim($fullName))[0];
                ?>
                <div class="user-nav-dropdown" style="position: relative; display: inline-block;">
                    <a href="#" class="login-link" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($firstName) ?>&background=random&color=fff&bold=true" alt="Profile" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                        <span style="font-weight: 800; font-size: 15.5px; text-transform: capitalize;">Halo, <?= htmlspecialchars($firstName) ?></span>
                    </a>
                    <div class="user-dropdown-content" style="position: absolute; right: 0; top: 100%; min-width: 180px; background: white; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 8px 0; margin-top: 15px; opacity: 0; visibility: hidden; transition: 0.2s ease; transform: translateY(8px); z-index: 100;">
                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <a href="backend/admin/dashboard.php" style="display: block; padding: 10px 20px; color: #11a654; text-decoration: none; font-size: 14px; font-weight: 700;">Dashboard Admin</a>
                            <div style="height: 1px; background: #e5e7eb; margin: 4px 0;"></div>
                        <?php elseif (($_SESSION['role'] ?? '') === 'penghuni'): ?>
                            <a href="index.php?page=dashboard" style="display: block; padding: 10px 20px; color: #11a654; text-decoration: none; font-size: 14px; font-weight: 600;">Dashboard Saya</a>
                            <div style="height: 1px; background: #e5e7eb; margin: 4px 0;"></div>
                        <?php endif; ?>
                        
                        <a href="index.php?page=profil" style="display: block; padding: 10px 20px; color: #000; text-decoration: none; font-size: 14px;">Profil Anda</a>
                        <a href="index.php?page=riwayat_booking" style="display: block; padding: 10px 20px; color: #000; text-decoration: none; font-size: 14px;">Riwayat Booking</a>
                        <a href="index.php?page=pengaturan" style="display: block; padding: 10px 20px; color: #000; text-decoration: none; font-size: 14px;">Pengaturan</a>
                        <div style="height: 1px; background: #e5e7eb; margin: 4px 0;"></div>
                        <a href="index.php?action=logout" style="display: block; padding: 10px 20px; color: #ef4444; text-decoration: none; font-size: 14px;">Logout</a>
                    </div>
                </div>
                <style>
                    .user-nav-dropdown:hover .user-dropdown-content {
                        opacity: 1 !important;
                        visibility: visible !important;
                        transform: translateY(0) !important;
                    }
                    .user-dropdown-content a:hover {
                        background-color: #f3f4f6;
                    }
                </style>
            <?php endif; ?>
        </div>

        <!-- MOBILE TOGGLE BUTTON -->
        <button class="mobile-toggle" aria-label="Toggle Menu" onclick="toggleMobileMenu()">
            <svg viewBox="0 0 24 24" width="28" height="28" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

    </div>
</nav>

<!-- MOBILE MENU OVERLAY -->
<div class="mobile-menu-overlay" id="mobileMenu">
    <div class="mobile-menu-content">
        <button class="mobile-close" aria-label="Close Menu" onclick="toggleMobileMenu()">
            <svg viewBox="0 0 24 24" width="28" height="28" stroke="#000" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        
        <a href="index.php">Beranda</a>
        <a href="index.php?page=tentang">Tentang</a>
        <a href="index.php?page=booking">Booking</a>
        <a href="index.php?page=kontak">Kontak</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="index.php?page=riwayat_booking">Riwayat Booking</a>
        <?php endif; ?>
        
        <div class="mobile-menu-divider"></div>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="#" class="mobile-auth-btn mobile-login" onclick="openLoginModal(event)">Masuk</a>
            <a href="#" class="mobile-auth-btn mobile-register" onclick="openRegisterModal(event)">Daftar</a>
        <?php else: ?>
            <div class="mobile-greeting">Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></div>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a href="backend/admin/dashboard.php">Dashboard Admin</a>
            <?php elseif (($_SESSION['role'] ?? '') === 'penghuni'): ?>
                <a href="index.php?page=dashboard">Dashboard Saya</a>
            <?php endif; ?>
            <a href="index.php?page=profil">Profil Saya</a>
            <a href="index.php?page=riwayat_booking">Riwayat Booking</a>
            <a href="index.php?action=logout" style="color: #ef4444;">Logout</a>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('active');
}
</script>