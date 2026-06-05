<?php
require_once __DIR__ . "/../backend/config/database.php";
header('Content-Type: text/plain; charset=utf-8');

// Fix existing bookings yang sudah ada payment tapi masih pending
$stmt = $conn->query("
    SELECT b.id, b.status 
    FROM booking b 
    JOIN pembayaran p ON b.id = p.booking_id 
    WHERE b.status = 'pending'
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "Tidak ada booking pending yang perlu diperbaiki.\n";
} else {
    foreach ($rows as $r) {
        $conn->prepare("UPDATE booking SET status = 'menunggu_dp' WHERE id = ?")->execute([$r['id']]);
        echo "Fixed: Booking #{$r['id']} -> menunggu_dp\n";
    }
}

echo "\nDone!\n";
