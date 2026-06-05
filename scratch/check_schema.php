<?php
require_once __DIR__ . '/../backend/config/database.php';

echo "=== BOOKING TABLE ===\n";
$r = $conn->query('SHOW COLUMNS FROM booking');
foreach($r as $row) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . ($row['Default'] ?? 'NULL') . "\n";
}

echo "\n=== PEMBAYARAN TABLE ===\n";
$r = $conn->query('SHOW COLUMNS FROM pembayaran');
foreach($r as $row) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . ($row['Default'] ?? 'NULL') . "\n";
}

echo "\n=== KAMAR TABLE ===\n";
$r = $conn->query('SHOW COLUMNS FROM kamar');
foreach($r as $row) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . ($row['Default'] ?? 'NULL') . "\n";
}

echo "\n=== ACTIVE BOOKINGS ===\n";
$r = $conn->query("SELECT b.id, b.user_id, b.kamar_id, b.tanggal_masuk, b.durasi_bulan, b.status, u.nama, k.nomor_kamar FROM booking b JOIN users u ON b.user_id = u.id JOIN kamar k ON b.kamar_id = k.id ORDER BY b.id DESC LIMIT 10");
foreach($r as $row) {
    echo "ID:{$row['id']} User:{$row['nama']} Kamar:{$row['nomor_kamar']} Status:{$row['status']} Masuk:{$row['tanggal_masuk']} Durasi:{$row['durasi_bulan']}\n";
}
