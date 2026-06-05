<?php
/**
 * Migration: Create penghuni table & add alasan_penolakan to booking
 * Run once to set up missing tables/columns
 */
require_once __DIR__ . '/../config/database.php';

$migrations = [];

// 1. Create penghuni table
$migrations[] = "CREATE TABLE IF NOT EXISTS penghuni (
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
    UNIQUE KEY uniq_user_active (user_id, status),
    KEY idx_kamar (kamar_id),
    KEY idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// 2. Add alasan_penolakan column to booking if not exists
$migrations[] = "ALTER TABLE booking ADD COLUMN IF NOT EXISTS alasan_penolakan TEXT DEFAULT NULL";

// 3. Add durasi_bulan to pembayaran if not exists  
$migrations[] = "ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS durasi_bulan INT DEFAULT NULL";

// 4. Add jenis_pembayaran to pembayaran if not exists
$migrations[] = "ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS jenis_pembayaran VARCHAR(50) DEFAULT NULL";

// 5. Expand booking status enum to include all needed statuses
// MySQL requires full redefine of ENUM
try {
    $conn->exec("ALTER TABLE booking MODIFY COLUMN status ENUM('pending','menunggu_dp','disetujui','ditolak','selesai','aktif','dibatalkan','menunggu_batal') DEFAULT 'pending'");
    $migrations[] = "-- booking status enum already updated";
} catch (Exception $e) {
    $migrations[] = "-- booking status enum update skipped: " . $e->getMessage();
}

$success = 0;
$errors = [];

foreach ($migrations as $sql) {
    if (strpos($sql, '--') === 0) {
        $success++;
        continue;
    }
    try {
        $conn->exec($sql);
        $success++;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

echo "<h3>Migration Complete</h3>";
echo "<p>Success: $success</p>";
if (!empty($errors)) {
    echo "<p>Errors:</p><ul>";
    foreach ($errors as $err) {
        echo "<li>$err</li>";
    }
    echo "</ul>";
}
echo "<p><a href='dashboard.php'>Kembali ke Dashboard</a></p>";
