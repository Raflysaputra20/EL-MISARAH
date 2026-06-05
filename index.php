<?php
// =========================================================================
// MAIN ROUTER & SESSION HANDLER (CLEAN VERSION)
// =========================================================================
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/backend/config/database.php";

// 1. REFRESH SESSION DARI DATABASE (Penting agar perubahan role otomatis ter-update)
if (isset($_SESSION['user_id'])) {
    try {
        $stmtSync = $conn->prepare("SELECT role, status FROM users WHERE id = ?");
        $stmtSync->execute([$_SESSION['user_id']]);
        $userSync = $stmtSync->fetch(PDO::FETCH_ASSOC);
        if ($userSync) {
            $_SESSION['role'] = $userSync['role'];
            $_SESSION['status'] = $userSync['status'];
        }
    } catch (Exception $e) {}
}

// 2. HANDLER LOGOUT
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// 2. HELPER FUNCTIONS
if (!function_exists('getFrontIcon')) {
    function getFrontIcon($f) {
        $map = [
            'single bed'       => 'bed-single',
            'double bed'       => 'bed-double',
            'ac'               => 'air-vent',
            'kamar mandi dalam'=> 'shower-head',
            'kamar mandi luar' => 'shower-head',
            'meja belajar'     => 'book-open',
            'lemari'           => 'package',
            'wifi'             => 'wifi',
            'tv'               => 'tv',
            'kulkas'           => 'refrigerator',
            'parkir'           => 'car',
            'mushola'          => 'landmark',
            '33m2'             => 'layout-grid',
            '30m2'             => 'layout-grid',
        ];
        $key = strtolower(trim($f));
        return $map[$key] ?? 'check';
    }
}

// 3. ROUTING LOGIC
$page = $_GET['page'] ?? 'home';

// Jika mencoba akses halaman admin via index.php, arahkan ke file aslinya
if (strpos($page, 'admin') === 0) {
    // Mapping sederhana untuk kompatibilitas link yang sudah ada
    $adminMap = [
        'admin-dashboard' => 'backend/admin/dashboard.php',
        'admin-penghuni'  => 'backend/admin/kelola_penghuni/list_penghuni.php',
        'admin-kamar'     => 'backend/admin/kelola_kamar/list_kamar.php',
        'admin-pembayaran'=> 'backend/admin/kelola_pembayaran/list_pembayaran.php',
        'admin-pengaduan' => 'backend/admin/kelola_pengaduan/list_pengaduan.php',
        'admin-booking'   => 'backend/admin/kelola_booking/list_booking.php',
        'admin-pengumuman'=> 'backend/admin/kelola_pengumuman/list_pengumuman.php',
        'admin-pengaturan'=> 'backend/admin/pengaturan.php',
    ];
    
    $target = $adminMap[$page] ?? 'backend/admin/dashboard.php';
    header("Location: " . $target);
    exit;
}

// Access Control Lists (ACL) untuk tamu
$protectedGuestPages = ['profil', 'riwayat_booking', 'booking', 'pembayaran_booking', 'menunggu_konfirmasi', 'pengaturan', 'batal_booking'];
if (in_array($page, $protectedGuestPages) && !isset($_SESSION['user_id'])) {
    header("Location: index.php?login_modal=1&msg=auth_required");
    exit;
}

// Routing Guest
switch ($page) {
    case 'rooms':
        $content = 'frontend/pages/guest/rooms.php';
        break;
    case 'gallery':
        $content = 'frontend/pages/guest/gallery.php';
        break;
    case 'tentang':
        $content = 'frontend/pages/guest/tentang.php';
        break;
    case 'booking':
        $content = 'frontend/pages/guest/booking.php';
        break;
    case 'kontak':
        $content = 'frontend/pages/guest/kontak.php';
        break;
    case 'ketentuan':
        $content = 'frontend/pages/guest/ketentuan.php';
        break;
    case 'faq':
        $content = 'frontend/pages/guest/faq.php';
        break;
    case 'lokasi':
        $content = 'frontend/pages/guest/lokasi.php';
        break;
    case 'detail_kamar':
        $content = 'frontend/pages/guest/detail_kamar.php';
        break;
    case 'konfirmasi_pesanan':
        $content = 'frontend/pages/guest/konfirmasi_pesanan.php';
        break;
    case 'pembayaran_booking':
        $content = 'frontend/pages/guest/pembayaran_booking.php';
        break;
    case 'menunggu_konfirmasi':
        $content = 'frontend/pages/guest/menunggu_konfirmasi.php';
        break;
    case 'profil':
        $content = 'frontend/pages/guest/profil.php';
        break;
    case 'pengaturan':
        $content = 'frontend/pages/guest/pengaturan.php';
        break;
    case 'riwayat_booking':
        $content = 'frontend/pages/guest/riwayat_booking.php';
        break;
    case 'batal_booking':
        $content = 'frontend/pages/guest/batal_booking.php';
        break;
    case 'dashboard':
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $_SESSION['role'] = $stmt->fetchColumn() ?: $_SESSION['role'];
            } catch (Exception $e) {}
        }
        // Smart Redirect based on role
        $role = $_SESSION['role'] ?? '';
        if ($role === 'admin') {
            header("Location: backend/admin/dashboard.php");
        } elseif ($role === 'penghuni') {
            header("Location: backend/penghuni/dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    default:
        $content = 'frontend/pages/guest/home.php';
}

// Ambil data kamar untuk layout utama, dikelompokkan berdasarkan tipe
try {
    $stmt = $conn->query("
        SELECT MIN(id) as id, tipe, harga, fasilitas, foto, COUNT(id) as total_kamar,
        SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as tersedia
        FROM kamar 
        GROUP BY tipe, harga, fasilitas, foto
        ORDER BY MIN(id) DESC
    ");
    $kamar = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $kamar = [];
}

include 'frontend/layouts/main.php';