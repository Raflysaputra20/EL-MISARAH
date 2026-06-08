<?php
/**
 * Penghuni Init - Auto-create missing columns & tables
 * Include this at the top of every penghuni page
 */

// Strict logout/checkout validation
if (isset($_SESSION["user_id"])) {
    $checkUserId = $_SESSION["user_id"];
    try {
        $stmtCheck = $conn->prepare("SELECT role, status, nama, foto FROM users WHERE id = ?");
        $stmtCheck->execute([$checkUserId]);
        $dbUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if (!$dbUser || $dbUser['role'] !== 'penghuni' || $dbUser['status'] !== 'aktif') {
            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            header("Location: ../api/auth/login.php?error=" . urlencode("Sesi Anda telah berakhir atau Anda telah checkout."));
            exit;
        } else {
            $_SESSION['nama'] = $dbUser['nama'];
            $_SESSION['foto'] = $dbUser['foto'];
        }
    } catch (Exception $e) {
        // Silent fallback
    }
}


// ===== FIX KOLOM users YANG KURANG =====
$userColumns = [
    "foto"                    => "ALTER TABLE users ADD COLUMN foto VARCHAR(255) DEFAULT NULL",
    "tanggal_lahir"           => "ALTER TABLE users ADD COLUMN tanggal_lahir DATE DEFAULT NULL",
    "pekerjaan"               => "ALTER TABLE users ADD COLUMN pekerjaan VARCHAR(100) DEFAULT NULL",
    "kontak_darurat_nama"     => "ALTER TABLE users ADD COLUMN kontak_darurat_nama VARCHAR(100) DEFAULT NULL",
    "kontak_darurat_hubungan" => "ALTER TABLE users ADD COLUMN kontak_darurat_hubungan VARCHAR(50) DEFAULT NULL",
    "kontak_darurat_hp"       => "ALTER TABLE users ADD COLUMN kontak_darurat_hp VARCHAR(20) DEFAULT NULL",
    "status"                  => "ALTER TABLE users ADD COLUMN status VARCHAR(30) DEFAULT 'aktif'",
];

try {
    $existingCols = [];
    $colResult = $conn->query("SHOW COLUMNS FROM users");
    foreach ($colResult->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $existingCols[] = $col['Field'];
    }
    foreach ($userColumns as $col => $sql) {
        if (!in_array($col, $existingCols)) {
            $conn->exec($sql);
        }
    }
} catch (Exception $e) { /* silent */ }

// ===== FIX KOLOM booking & pembayaran YANG KURANG =====
try {
    // 1. Tambah kolom alasan_penolakan di booking
    $existingBookingCols = [];
    $colBResult = $conn->query("SHOW COLUMNS FROM booking");
    foreach ($colBResult->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $existingBookingCols[] = $col['Field'];
    }
    if (!in_array('alasan_penolakan', $existingBookingCols)) {
        $conn->exec("ALTER TABLE booking ADD COLUMN alasan_penolakan TEXT DEFAULT NULL");
    }

    // 2. Tambah kolom durasi_bulan & jenis_pembayaran di pembayaran
    $existingPayCols = [];
    $colPResult = $conn->query("SHOW COLUMNS FROM pembayaran");
    foreach ($colPResult->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $existingPayCols[] = $col['Field'];
    }
    if (!in_array('durasi_bulan', $existingPayCols)) {
        $conn->exec("ALTER TABLE pembayaran ADD COLUMN durasi_bulan INT DEFAULT NULL");
    }
    if (!in_array('jenis_pembayaran', $existingPayCols)) {
        $conn->exec("ALTER TABLE pembayaran ADD COLUMN jenis_pembayaran VARCHAR(50) DEFAULT NULL");
    }

    // 3. Tambah kolom harga_3_bulan, harga_6_bulan, harga_tahun di kamar
    $existingKamarCols = [];
    $colKResult = $conn->query("SHOW COLUMNS FROM kamar");
    foreach ($colKResult->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $existingKamarCols[] = $col['Field'];
    }
    if (!in_array('harga_3_bulan', $existingKamarCols)) {
        $conn->exec("ALTER TABLE kamar ADD COLUMN harga_3_bulan INT DEFAULT NULL");
    }
    if (!in_array('harga_6_bulan', $existingKamarCols)) {
        $conn->exec("ALTER TABLE kamar ADD COLUMN harga_6_bulan INT DEFAULT NULL");
    }
    if (!in_array('harga_tahun', $existingKamarCols)) {
        $conn->exec("ALTER TABLE kamar ADD COLUMN harga_tahun INT DEFAULT NULL");
    }
} catch (Exception $e) { /* silent */ }

// ===== FIX TABEL YANG KURANG =====
$tables = [
    "booking" => "CREATE TABLE IF NOT EXISTS booking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        kamar_id INT NOT NULL,
        tanggal_masuk DATE DEFAULT NULL,
        tanggal_keluar DATE DEFAULT NULL,
        status ENUM('pending','disetujui','aktif','selesai','ditolak') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "kamar" => "CREATE TABLE IF NOT EXISTS kamar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nomor_kamar VARCHAR(10) NOT NULL,
        tipe VARCHAR(50) DEFAULT NULL,
        harga INT DEFAULT 0,
        harga_3_bulan INT DEFAULT NULL,
        harga_6_bulan INT DEFAULT NULL,
        harga_tahun INT DEFAULT NULL,
        status ENUM('tersedia','dihuni','maintenance') DEFAULT 'tersedia',
        fasilitas TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "pembayaran" => "CREATE TABLE IF NOT EXISTS pembayaran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        booking_id INT DEFAULT NULL,
        jumlah INT NOT NULL DEFAULT 0,
        status ENUM('belum_bayar','menunggu_verifikasi','valid','ditolak') DEFAULT 'belum_bayar',
        metode VARCHAR(100) DEFAULT NULL,
        bukti_bayar VARCHAR(255) DEFAULT NULL,
        jenis_pembayaran VARCHAR(50) DEFAULT NULL,
        tanggal_bayar DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "pengaduan" => "CREATE TABLE IF NOT EXISTS pengaduan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        judul VARCHAR(255) NOT NULL,
        isi TEXT NOT NULL,
        no_kamar VARCHAR(20) DEFAULT NULL,
        prioritas ENUM('rendah','sedang','tinggi') DEFAULT 'sedang',
        foto_bukti VARCHAR(255) DEFAULT NULL,
        status ENUM('baru','diproses','selesai','ditolak') DEFAULT 'baru',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "pengumuman" => "CREATE TABLE IF NOT EXISTS pengumuman (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(255) NOT NULL,
        isi TEXT NOT NULL,
        pinned TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "informasi_kost" => "CREATE TABLE IF NOT EXISTS informasi_kost (
        id INT AUTO_INCREMENT PRIMARY KEY,
        icon VARCHAR(50) DEFAULT 'info',
        judul VARCHAR(100) NOT NULL,
        deskripsi TEXT NOT NULL,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "ulasan" => "CREATE TABLE IF NOT EXISTS ulasan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        rating TINYINT NOT NULL DEFAULT 5,
        komentar TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ═══ ISSUE #6 & #9: Tabel penghuni untuk data penghuni ═══
    "penghuni" => "CREATE TABLE IF NOT EXISTS penghuni (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        kamar_id INT NOT NULL,
        booking_id INT DEFAULT NULL,
        tanggal_masuk DATE DEFAULT NULL,
        tanggal_keluar DATE DEFAULT NULL,
        status ENUM('aktif','selesai','keluar') DEFAULT 'aktif',
        catatan TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_user (user_id),
        KEY idx_kamar (kamar_id),
        KEY idx_booking (booking_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "pengaturan_penghuni" => "CREATE TABLE IF NOT EXISTS pengaturan_penghuni (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        notif_email TINYINT(1) DEFAULT 1,
        notif_tagihan TINYINT(1) DEFAULT 1,
        notif_pengumuman TINYINT(1) DEFAULT 1,
        notif_pengaduan TINYINT(1) DEFAULT 1,
        privasi_profil TINYINT(1) DEFAULT 0,
        sesi_aktif_notif TINYINT(1) DEFAULT 1,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "galeri" => "CREATE TABLE IF NOT EXISTS galeri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kategori VARCHAR(50) NOT NULL,
        tipe_file ENUM('foto', 'video') NOT NULL DEFAULT 'foto',
        file_path VARCHAR(255) NOT NULL,
        caption VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $sql) {
    try { $conn->exec($sql); } catch (Exception $e) { /* silent */ }
}

// ===== HELPER: ambil foto user =====
function getUserFoto($conn, $userId) {
    try {
        $s = $conn->prepare("SELECT foto FROM users WHERE id = ?");
        $s->execute([$userId]);
        return $s->fetchColumn();
    } catch (Exception $e) { return null; }
}

// ===== HELPER: format rupiah =====
function rupiah($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// ===== HELPER: format tanggal Indonesia =====
function tglIndo($date) {
    if (!$date) return '—';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($date);
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
