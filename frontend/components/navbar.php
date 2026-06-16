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
        <div class="navbar-auth" style="display:flex; align-items:center; gap:12px;">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="#" class="login-link" onclick="openLoginModal(event)">Masuk</a>
                <span class="auth-separator">|</span>
                <a href="#" class="register-btn" onclick="openRegisterModal(event)">Daftar</a>
            <?php else: ?>
                <?php 
                    $fullName = $_SESSION['nama'] ?? 'User';
                    $firstName = explode(' ', trim($fullName))[0];
                    $userFoto = $_SESSION['foto'] ?? null;
                    $userRole = $_SESSION['role'] ?? '';
                ?>

                <?php if ($userRole === 'penghuni' || $userRole === 'admin'): ?>
                <!-- NOTIFIKASI BELL -->
                <div id="notifWrapper" style="position:relative; display:inline-block;">
                    <button id="notifBell" onclick="toggleNotifDropdown(event)" style="background:none; border:none; cursor:pointer; position:relative; padding:4px; display:flex; align-items:center; justify-content:center;" aria-label="Notifikasi">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span id="notifBadge" style="display:none; position:absolute; top:-2px; right:-2px; background:#ef4444; color:#fff; font-size:10px; font-weight:700; min-width:16px; height:16px; border-radius:999px; text-align:center; line-height:16px; padding:0 3px;">0</span>
                    </button>

                    <!-- DROPDOWN NOTIFIKASI -->
                    <div id="notifDropdown" style="display:none; position:absolute; right:0; top:calc(100% + 12px); width:320px; background:#fff; border-radius:14px; box-shadow:0 12px 35px rgba(0,0,0,0.15); z-index:1000; overflow:hidden;">
                        <div style="padding:14px 18px 10px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; justify-content:space-between;">
                            <span style="font-weight:700; font-size:14px; color:#111;">Notifikasi</span>
                            <span id="notifCount" style="font-size:11px; color:#888;">Memuat...</span>
                        </div>
                        <div id="notifList" style="max-height:280px; overflow-y:auto;">
                            <div style="padding:24px 18px; text-align:center; color:#aaa; font-size:13px;">Memuat notifikasi...</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="user-nav-dropdown" style="position: relative; display: inline-block;">
                    <a href="#" class="login-link" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                        <?php if (!empty($userFoto) && file_exists(__DIR__ . '/../../backend/uploads/profil/' . basename($userFoto))): ?>
                            <img src="backend/uploads/profil/<?= htmlspecialchars(basename($userFoto)) ?>" alt="Profile" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($firstName) ?>&background=random&color=fff&bold=true" alt="Profile" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                        <?php endif; ?>
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

// ===== NOTIFIKASI BELL =====
const notifTypeConfig = {
    warning: { icon: '⚠️', bg: '#fff8e1', border: '#f59e0b', color: '#92400e' },
    info:    { icon: 'ℹ️',  bg: '#e0f2fe', border: '#38bdf8', color: '#0369a1' },
    success: { icon: '✅', bg: '#dcfce7', border: '#4ade80', color: '#166534' },
};

function toggleNotifDropdown(e) {
    e.stopPropagation();
    const dd = document.getElementById('notifDropdown');
    if (!dd) return;
    const isVisible = dd.style.display === 'block';
    dd.style.display = isVisible ? 'none' : 'block';
    if (!isVisible) loadNotifikasi();
}

document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('notifWrapper');
    const dd = document.getElementById('notifDropdown');
    if (wrapper && dd && !wrapper.contains(e.target)) {
        dd.style.display = 'none';
    }
});

async function loadNotifikasi() {
    const list = document.getElementById('notifList');
    const badge = document.getElementById('notifBadge');
    const countEl = document.getElementById('notifCount');
    if (!list) return;

    try {
        const res = await fetch('backend/api/get_notifikasi.php', { credentials: 'same-origin' });
        const data = await res.json();

        if (!data.success) {
            list.innerHTML = '<div style="padding:20px 18px; text-align:center; color:#aaa; font-size:13px;">Gagal memuat notifikasi.</div>';
            return;
        }

        const notifs = data.notifikasi || [];

        // Update badge
        if (notifs.length > 0) {
            badge.textContent = notifs.length > 9 ? '9+' : notifs.length;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }

        // Update counter text
        if (countEl) countEl.textContent = notifs.length > 0 ? notifs.length + ' notifikasi' : 'Tidak ada notifikasi';

        // Render list
        if (notifs.length === 0) {
            list.innerHTML = `
                <div style="padding:28px 18px; text-align:center;">
                    <div style="font-size:32px; margin-bottom:8px;">🔔</div>
                    <div style="font-size:13px; color:#aaa;">Tidak ada notifikasi saat ini</div>
                </div>`;
            return;
        }

        list.innerHTML = notifs.map(n => {
            const cfg = notifTypeConfig[n.type] || notifTypeConfig.info;
            const linkAttr = n.link ? `href="index.php?page=${n.link}" ` : 'href="#" ';
            return `<a ${linkAttr}style="display:block; padding:12px 18px; border-bottom:1px solid #f5f5f5; text-decoration:none; transition:background 0.15s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <div style="width:36px; height:36px; border-radius:8px; background:${cfg.bg}; border:1px solid ${cfg.border}; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;">${cfg.icon}</div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:13px; font-weight:600; color:#111; line-height:1.4; margin-bottom:3px;">${n.isi}</div>
                        <div style="font-size:11px; color:#888;">${n.waktu}</div>
                    </div>
                </div>
            </a>`;
        }).join('');

    } catch(err) {
        console.error('Notifikasi error:', err);
        if (list) list.innerHTML = '<div style="padding:20px 18px; text-align:center; color:#aaa; font-size:13px;">Gagal memuat notifikasi.</div>';
    }
}

// Auto-load badge saat halaman siap
document.addEventListener('DOMContentLoaded', function() {
    const badge = document.getElementById('notifBadge');
    if (!badge) return;
    // Load pertama kali (hanya badge, dropdown belum terbuka)
    fetch('backend/api/get_notifikasi.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.notifikasi && data.notifikasi.length > 0) {
                badge.textContent = data.notifikasi.length > 9 ? '9+' : data.notifikasi.length;
                badge.style.display = 'block';
            }
        })
        .catch(() => {});

    // Refresh otomatis setiap 30 detik
    setInterval(() => {
        fetch('backend/api/get_notifikasi.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.notifikasi) {
                    const n = data.notifikasi.length;
                    badge.textContent = n > 9 ? '9+' : n;
                    badge.style.display = n > 0 ? 'block' : 'none';
                }
            })
            .catch(() => {});
    }, 30000);
});
</script>