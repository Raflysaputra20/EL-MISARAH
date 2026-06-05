<?php
require_once __DIR__ . "/../backend/config/database.php";
header('Content-Type: text/plain; charset=utf-8');

echo "=== BOOKING FLOW DIAGNOSTIC ===\n\n";

// 1. Check booking table
echo "--- RECENT BOOKINGS ---\n";
$stmt = $conn->query("SELECT id, user_id, status, alasan_penolakan, kamar_id FROM booking ORDER BY id DESC LIMIT 10");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($bookings)) {
    echo "(No bookings found)\n";
} else {
    foreach ($bookings as $b) {
        $alasan = $b['alasan_penolakan'] ?: '-';
        echo "  Booking #{$b['id']} | user={$b['user_id']} | status={$b['status']} | alasan={$alasan} | kamar={$b['kamar_id']}\n";
    }
}

// 2. Check pembayaran
echo "\n--- RECENT PAYMENTS ---\n";
$stmt2 = $conn->query("SELECT p.id, p.booking_id, p.bukti_bayar, p.status, p.jumlah, p.metode FROM pembayaran p ORDER BY p.id DESC LIMIT 10");
$payments = $stmt2->fetchAll(PDO::FETCH_ASSOC);
if (empty($payments)) {
    echo "(No payments found)\n";
} else {
    foreach ($payments as $p) {
        $bukti = $p['bukti_bayar'] ? 'YES(' . $p['bukti_bayar'] . ')' : 'NONE';
        echo "  Payment #{$p['id']} | booking={$p['booking_id']} | bukti={$bukti} | status={$p['status']} | jumlah={$p['jumlah']} | metode={$p['metode']}\n";
    }
}

// 3. Check column
echo "\n--- COLUMN CHECK ---\n";
$stmt3 = $conn->query("SHOW COLUMNS FROM booking LIKE 'alasan_penolakan'");
$col = $stmt3->fetch();
echo $col ? "alasan_penolakan EXISTS (Type: {$col['Type']})\n" : "alasan_penolakan MISSING!\n";

// 4. Admin visibility
echo "\n--- ADMIN VISIBILITY ---\n";
$stmt4 = $conn->query("
    SELECT booking.id, booking.status,
           (SELECT p.bukti_bayar FROM pembayaran p WHERE p.booking_id = booking.id ORDER BY p.id DESC LIMIT 1) as bukti_bayar
    FROM booking
    ORDER BY booking.id DESC LIMIT 10
");
$rows = $stmt4->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "(No bookings)\n";
} else {
    foreach ($rows as $r) {
        $bukti = $r['bukti_bayar'] ?: 'NONE';
        $hasBukti = !empty($r['bukti_bayar']);
        $visible = ($hasBukti || $r['status'] !== 'pending') ? 'VISIBLE' : 'HIDDEN (pending tanpa bukti)';
        echo "  Booking #{$r['id']} | status={$r['status']} | bukti={$bukti} | admin={$visible}\n";
    }
}

echo "\n=== DONE ===\n";
