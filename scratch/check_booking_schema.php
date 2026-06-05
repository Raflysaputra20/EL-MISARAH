<?php
require_once __DIR__ . '/../backend/config/database.php';

echo "=== BOOKING TABLE COLUMNS ===\n";
$cols = $conn->query("SHOW COLUMNS FROM booking")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . " | " . $c['Type'] . " | " . $c['Null'] . " | " . $c['Default'] . "\n";
}

echo "\n=== PEMBAYARAN TABLE ===\n";
try {
    $cols2 = $conn->query("SHOW COLUMNS FROM pembayaran")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols2 as $c) {
        echo $c['Field'] . " | " . $c['Type'] . " | " . $c['Null'] . " | " . $c['Default'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== ACTIVE BOOKINGS ===\n";
$bookings = $conn->query("SELECT b.id, b.status, b.kamar_id, b.user_id, k.status as kamar_status FROM booking b LEFT JOIN kamar k ON b.kamar_id = k.id WHERE b.status IN ('pending','menunggu_dp','disetujui','aktif')")->fetchAll(PDO::FETCH_ASSOC);
foreach ($bookings as $b) {
    echo "Booking #{$b['id']}: status={$b['status']}, kamar_id={$b['kamar_id']}, kamar_status={$b['kamar_status']}\n";
}

echo "\n=== TEST SETUJUI FLOW ===\n";
foreach ($bookings as $b) {
    if ($b['status'] === 'menunggu_dp') {
        echo "Testing setujui for booking #{$b['id']}...\n";
        $stmtPay = $conn->prepare("SELECT bukti_bayar FROM pembayaran WHERE booking_id = ? ORDER BY id DESC LIMIT 1");
        $stmtPay->execute([$b['id']]);
        $bukti = $stmtPay->fetchColumn();
        echo "  bukti_bayar: " . ($bukti ?: '(empty)') . "\n";
    }
}
