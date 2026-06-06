<?php
// scratch/sync_sidebars.php
// Syncs the sidebar across all admin files in backend/admin/ and subdirectories.

$baseDir = __DIR__ . '/../backend/admin';

function getAdminFiles($dir) {
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $files = array_merge($files, getAdminFiles($path));
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
    return $files;
}

$files = getAdminFiles($baseDir);

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'class="admin-sidebar"') === false) {
        // Skip files that don't have a sidebar
        continue;
    }
    
    // Determine depth: if it's directly in backend/admin/, depth is 1. If in a subfolder, depth is 2.
    $relativePath = str_replace(realpath($baseDir), '', realpath($file));
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $parts = explode('/', $relativePath);
    
    $prefix = '';
    $logout_prefix = '../';
    if (count($parts) > 1) {
        $prefix = '../';
        $logout_prefix = '../../';
    }
    
    // Define the correct active states
    $active_dashboard = (strpos($relativePath, 'dashboard.php') !== false) ? 'active' : '';
    $active_penghuni = (strpos($relativePath, 'kelola_penghuni/') !== false) ? 'active' : '';
    $active_user = (strpos($relativePath, 'kelola_user/') !== false) ? 'active' : '';
    $active_kamar = (strpos($relativePath, 'kelola_kamar/') !== false) ? 'active' : '';
    $active_tagihan = (strpos($relativePath, 'kelola_tagihan/') !== false) ? 'active' : '';
    $active_pengaduan = (strpos($relativePath, 'kelola_pengaduan/') !== false) ? 'active' : '';
    $active_booking = (strpos($relativePath, 'kelola_booking/') !== false) ? 'active' : '';
    $active_pengumuman = (strpos($relativePath, 'kelola_pengumuman/') !== false) ? 'active' : '';
    $active_ulasan = (strpos($relativePath, 'kelola_ulasan/') !== false) ? 'active' : '';
    $active_galeri = (strpos($relativePath, 'kelola_galeri/') !== false) ? 'active' : '';
    $active_pengaturan = (strpos($relativePath, 'pengaturan.php') !== false) ? 'active' : '';
    
    $sidebarMenu = '
    <ul class="sidebar-menu">
        <li class="sidebar-item"><a href="' . $prefix . 'dashboard.php" class="sidebar-link ' . $active_dashboard . '"><i data-lucide="layout-dashboard" class="sidebar-icon"></i> Dashboard</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'kelola_penghuni/list_penghuni.php" class="sidebar-link ' . $active_penghuni . '"><i data-lucide="users" class="sidebar-icon"></i> Penghuni Kost</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'kelola_user/list_user.php" class="sidebar-link ' . $active_user . '"><i data-lucide="user-cog" class="sidebar-icon"></i> Kelola User</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'kelola_kamar/list_kamar.php" class="sidebar-link ' . $active_kamar . '"><i data-lucide="box" class="sidebar-icon"></i> Menejemen Kamar</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'kelola_tagihan/list_tagihan.php" class="sidebar-link ' . $active_tagihan . '"><i data-lucide="receipt" class="sidebar-icon"></i> Tagihan & Pembayaran</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'kelola_pengaduan/list_pengaduan.php" class="sidebar-link ' . $active_pengaduan . '"><i data-lucide="alert-triangle" class="sidebar-icon"></i> Pengaduan</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'kelola_booking/list_booking.php" class="sidebar-link ' . $active_booking . '"><i data-lucide="calendar-check" class="sidebar-icon"></i> Kelola Booking</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'kelola_pengumuman/list_pengumuman.php" class="sidebar-link ' . $active_pengumuman . '"><i data-lucide="megaphone" class="sidebar-icon"></i> Pengumuman</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'kelola_ulasan/list_ulasan.php" class="sidebar-link ' . $active_ulasan . '"><i data-lucide="star" class="sidebar-icon"></i> Kelola Ulasan</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'kelola_galeri/list_galeri.php" class="sidebar-link ' . $active_galeri . '"><i data-lucide="image" class="sidebar-icon"></i> Kelola Galeri</a></li>
        <li class="sidebar-item"><a href="' . $prefix . 'pengaturan.php" class="sidebar-link ' . $active_pengaturan . '"><i data-lucide="settings" class="sidebar-icon"></i> Pengaturan</a></li>
    </ul>';
    
    $sidebarFooter = '
    <div class="sidebar-footer">
        <a href="' . $logout_prefix . 'logout.php" class="btn-keluar"><i data-lucide="log-out" class="sidebar-icon" style="color:#1f2937; margin-right:8px;"></i> Keluar</a>
    </div>';

    // Regex replacement for sidebar-menu
    $newContent = preg_replace('/<ul class="sidebar-menu">.*?<\/ul>/is', trim($sidebarMenu), $content);
    // Regex replacement for sidebar-footer
    $newContent = preg_replace('/<div class="sidebar-footer">.*?<\/div>/is', trim($sidebarFooter), $newContent);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "Updated sidebar in: " . $relativePath . "\n";
    }
}
echo "Sidebar sync complete.\n";
