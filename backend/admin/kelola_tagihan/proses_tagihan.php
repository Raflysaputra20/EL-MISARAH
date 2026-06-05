<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../api/auth/login.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: list_tagihan.php"); exit;
}

$bulan = intval($_POST['bulan'] ?? date('n'));
$tahun = intval($_POST['tahun'] ?? date('Y'));
$generateAll = isset($_POST['generate_all']);
$tanggalBayar = sprintf('%04d-%02d-01', $tahun, $bulan);

try {
    if ($generateAll) {
        // Semua booking aktif yang belum punya tagihan bulan ini
        $stmt = $conn->prepare("
            SELECT b.id as booking_id, b.user_id, k.harga
            FROM booking b
            JOIN kamar k ON b.kamar_id = k.id
            WHERE b.status IN ('disetujui','aktif','selesai')
              AND b.id NOT IN (
                SELECT p.booking_id FROM pembayaran p
                WHERE MONTH(p.tanggal_bayar) = ? AND YEAR(p.tanggal_bayar) = ?
              )
        ");
        $stmt->execute([$bulan, $tahun]);
        $penghuniList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $bookingIds = array_map('intval', $_POST['booking_ids'] ?? $_POST['user_ids'] ?? []);
        $jumlahCustom = intval($_POST['jumlah_custom'] ?? 0);
        $durasiBulan = intval($_POST['durasi_bulan'] ?? 1);
        if ($durasiBulan < 1) $durasiBulan = 1;

        if (empty($bookingIds)) {
            header("Location: list_tagihan.php?bulan=$bulan&tahun=$tahun&error=Tidak ada yang dipilih"); exit;
        }
        
        // Handle if user_ids was passed instead of booking_ids
        $ph = implode(',', array_fill(0, count($bookingIds), '?'));
        if (isset($_POST['user_ids'])) {
            $stmt = $conn->prepare("
                SELECT b.id as booking_id, b.user_id, k.harga
                FROM booking b
                JOIN kamar k ON b.kamar_id = k.id
                WHERE b.user_id IN ($ph) AND b.status IN ('disetujui','aktif','selesai')
                  AND b.id NOT IN (
                    SELECT p.booking_id FROM pembayaran p
                    WHERE MONTH(p.tanggal_bayar) = ? AND YEAR(p.tanggal_bayar) = ?
                  )
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT b.id as booking_id, b.user_id, k.harga
                FROM booking b
                JOIN kamar k ON b.kamar_id = k.id
                WHERE b.id IN ($ph) AND b.status IN ('disetujui','aktif','selesai')
                  AND b.id NOT IN (
                    SELECT p.booking_id FROM pembayaran p
                    WHERE MONTH(p.tanggal_bayar) = ? AND YEAR(p.tanggal_bayar) = ?
                  )
            ");
        }
        $stmt->execute(array_merge($bookingIds, [$bulan, $tahun]));
        $penghuniListRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $penghuniList = [];
        foreach ($penghuniListRaw as $p) {
            if ($jumlahCustom > 0) {
                $p['harga'] = $jumlahCustom;
                $p['durasi'] = $durasiBulan;
            } else {
                $p['durasi'] = 1;
            }
            $penghuniList[] = $p;
        }
    }

    if (empty($penghuniList)) {
        header("Location: list_tagihan.php?bulan=$bulan&tahun=$tahun&error=Semua booking sudah punya tagihan bulan ini"); exit;
    }

    // Default harga
    $defaultHarga = 1000000;

    $ins = $conn->prepare("
        INSERT INTO pembayaran (booking_id, tanggal_bayar, jumlah, status, durasi_bulan)
        VALUES (?, ?, ?, 'menunggu_verifikasi', ?)
    ");
    $conn->beginTransaction();
    $count = 0;
    foreach ($penghuniList as $p) {
        $harga = (!empty($p['harga']) && $p['harga'] > 0) ? $p['harga'] : $defaultHarga;
        $durasi = $p['durasi'] ?? 1;
        $ins->execute([
            $p['booking_id'],
            $tanggalBayar,
            $harga,
            $durasi
        ]);
        $count++;
    }
    $conn->commit();

    $nmBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    header("Location: list_tagihan.php?bulan=$bulan&tahun=$tahun&success=" . urlencode("Berhasil buat $count tagihan {$nmBulan[$bulan]} $tahun"));
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    header("Location: list_tagihan.php?bulan=$bulan&tahun=$tahun&error=" . urlencode($e->getMessage()));
    exit;
}
