<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: buat_tagihan.php");
    exit;
}

$bulan = intval($_POST['bulan'] ?? date('n'));
$tahun = intval($_POST['tahun'] ?? date('Y'));
$generateAll = isset($_POST['generate_all']);

// Tanggal bayar: hari pertama bulan yang dipilih
$tanggalBayar = sprintf('%04d-%02d-01', $tahun, $bulan);

try {
    if ($generateAll) {
        // Ambil semua booking aktif yang belum punya tagihan bulan ini
        $stmt = $conn->query("
            SELECT b.id as booking_id, b.user_id, k.harga
            FROM booking b
            JOIN kamar k ON b.kamar_id = k.id
            WHERE b.status IN ('disetujui','aktif')
              AND b.id NOT IN (
                  SELECT booking_id FROM pembayaran
                  WHERE MONTH(tanggal_bayar) = $bulan AND YEAR(tanggal_bayar) = $tahun
              )
        ");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Hanya booking_id yang dikirim
        $bookingIds = array_map('intval', $_POST['booking_ids'] ?? []);
        if (empty($bookingIds)) {
            header("Location: buat_tagihan.php?bulan=$bulan&tahun=$tahun&error=Tidak ada tagihan yang dipilih");
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
        $stmt = $conn->prepare("
            SELECT b.id as booking_id, b.user_id, k.harga
            FROM booking b
            JOIN kamar k ON b.kamar_id = k.id
            WHERE b.id IN ($placeholders)
              AND b.status IN ('disetujui','aktif')
              AND b.id NOT IN (
                  SELECT booking_id FROM pembayaran
                  WHERE MONTH(tanggal_bayar) = ? AND YEAR(tanggal_bayar) = ?
              )
        ");
        $stmt->execute(array_merge($bookingIds, [$bulan, $tahun]));
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($bookings)) {
        header("Location: buat_tagihan.php?bulan=$bulan&tahun=$tahun&error=Semua penghuni yang dipilih sudah memiliki tagihan bulan ini");
        exit;
    }

    // Insert tagihan
    $stmtInsert = $conn->prepare("
        INSERT INTO pembayaran (booking_id, tanggal_bayar, jumlah, jenis_pembayaran, metode, status)
        VALUES (?, ?, ?, 'bulanan', 'Transfer', 'belum_bayar')
    ");

    $conn->beginTransaction();
    $count = 0;
    foreach ($bookings as $b) {
        $stmtInsert->execute([$b['booking_id'], $tanggalBayar, $b['harga']]);
        $count++;
    }
    $conn->commit();

    $namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

    $msg = urlencode("Berhasil membuat $count tagihan untuk " . $namaBulan[$bulan] . " $tahun");
    header("Location: buat_tagihan.php?bulan=$bulan&tahun=$tahun&success=$msg");
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $msg = urlencode("Gagal membuat tagihan: " . $e->getMessage());
    header("Location: buat_tagihan.php?bulan=$bulan&tahun=$tahun&error=$msg");
    exit;
}
