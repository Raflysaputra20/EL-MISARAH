<?php
require_once __DIR__ . "/../backend/config/database.php";
$userId = 6;
$stmt = $conn->prepare("
    SELECT k.nomor_kamar, k.tipe, k.harga,
           b.tanggal_masuk, b.durasi_bulan, b.status AS status_booking,
           p.status AS status_bayar,
           p.tanggal_bayar AS tgl_bayar,
           p.jumlah,
           p.metode, p.bukti_bayar,
           p.created_at AS tanggal_transaksi
    FROM pembayaran p
    JOIN booking b ON p.booking_id = b.id
    JOIN kamar k ON b.kamar_id = k.id
    WHERE b.user_id = ?
    ORDER BY p.id DESC
");
$stmt->execute([$userId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
?>
