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

$nmBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

try {
    if ($generateAll) {
        // Semua booking aktif yang belum punya tagihan bulan ini (status belum_bayar)
        $stmt = $conn->prepare("
            SELECT b.id as booking_id, b.user_id, k.harga
            FROM booking b
            JOIN kamar k ON b.kamar_id = k.id
            WHERE b.status IN ('disetujui','aktif','selesai')
              AND b.id NOT IN (
                SELECT DISTINCT p.booking_id FROM pembayaran p
                WHERE MONTH(p.tanggal_bayar) = ? AND YEAR(p.tanggal_bayar) = ?
                  AND p.status IN ('belum_bayar','menunggu_verifikasi','valid')
              )
        ");
        $stmt->execute([$bulan, $tahun]);
        $penghuniList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($penghuniList as &$p) { $p['durasi'] = 1; }
        unset($p);
    } else {
        // Generate untuk penghuni tertentu (melalui user_ids[] atau booking_ids[])
        $inputIds  = array_map('intval', $_POST['user_ids'] ?? $_POST['booking_ids'] ?? []);
        $jumlahCustom = intval($_POST['jumlah_custom'] ?? 0);
        $durasiBulan  = max(1, intval($_POST['durasi_bulan'] ?? 1));

        if (empty($inputIds)) {
            header("Location: list_tagihan.php?bulan=$bulan&tahun=$tahun&error=Tidak ada yang dipilih"); exit;
        }

        $ph = implode(',', array_fill(0, count($inputIds), '?'));
        $useUserId = isset($_POST['user_ids']);

        // Cari booking aktif untuk user/booking yang dipilih
        $colFilter = $useUserId ? 'b.user_id' : 'b.id';
        $stmtBook = $conn->prepare("
            SELECT b.id as booking_id, b.user_id, k.harga
            FROM booking b
            JOIN kamar k ON b.kamar_id = k.id
            WHERE $colFilter IN ($ph)
              AND b.status IN ('disetujui','aktif','selesai')
              AND b.id = (
                SELECT MAX(b2.id) FROM booking b2
                WHERE b2.user_id = b.user_id AND b2.status IN ('disetujui','aktif','selesai')
              )
        ");
        $stmtBook->execute($inputIds);
        $bookingRows = $stmtBook->fetchAll(PDO::FETCH_ASSOC);

        if (empty($bookingRows)) {
            header("Location: list_tagihan.php?bulan=$bulan&tahun=$tahun&error=Tidak ada booking aktif ditemukan untuk penghuni ini"); exit;
        }

        $penghuniList = [];
        foreach ($bookingRows as $b) {
            $b['harga']  = ($jumlahCustom > 0) ? $jumlahCustom : (int)$b['harga'];
            $b['durasi'] = $durasiBulan;
            $penghuniList[] = $b;
        }
    }

    if (empty($penghuniList)) {
        header("Location: list_tagihan.php?bulan=$bulan&tahun=$tahun&error=Semua penghuni sudah punya tagihan {$nmBulan[$bulan]} $tahun"); exit;
    }

    $defaultHarga = 1000000;
    $count = 0;
    $updated = 0;

    $conn->beginTransaction();

    foreach ($penghuniList as $p) {
        $harga  = (!empty($p['harga']) && $p['harga'] > 0) ? (int)$p['harga'] : $defaultHarga;
        $durasi = (int)($p['durasi'] ?? 1);
        $bookingId = (int)$p['booking_id'];

        // Cek apakah sudah ada tagihan bulan ini untuk booking ini (status apapun)
        $stmtExist = $conn->prepare("
            SELECT id, status FROM pembayaran
            WHERE booking_id = ?
              AND MONTH(tanggal_bayar) = ? AND YEAR(tanggal_bayar) = ?
              AND NOT (metode LIKE '%Perpanjangan%' AND status = 'valid')
            LIMIT 1
        ");
        $stmtExist->execute([$bookingId, $bulan, $tahun]);
        $existing = $stmtExist->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Sudah ada — update jumlah/durasi jika masih belum_bayar
            if ($existing['status'] === 'belum_bayar') {
                $stmtUpd = $conn->prepare("
                    UPDATE pembayaran SET jumlah = ?, durasi_bulan = ?, tanggal_bayar = ?
                    WHERE id = ?
                ");
                $stmtUpd->execute([$harga, $durasi, $tanggalBayar, $existing['id']]);
                $updated++;
            }
            // Jika sudah menunggu_verifikasi/valid — biarkan
            continue;
        }

        // Belum ada — buat baru
        $stmtIns = $conn->prepare("
            INSERT INTO pembayaran (booking_id, tanggal_bayar, jumlah, status, durasi_bulan)
            VALUES (?, ?, ?, 'belum_bayar', ?)
        ");
        $stmtIns->execute([$bookingId, $tanggalBayar, $harga, $durasi]);
        $count++;
    }

    $conn->commit();

    $msg = "";
    if ($count > 0)   $msg .= "Berhasil buat $count tagihan. ";
    if ($updated > 0) $msg .= "$updated tagihan diperbarui. ";
    if ($msg === '')  $msg  = "Semua tagihan sudah ada dan tidak perlu diperbarui.";

    header("Location: list_tagihan.php?bulan=$bulan&tahun=$tahun&success=" . urlencode(trim($msg)));
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    header("Location: list_tagihan.php?bulan=$bulan&tahun=$tahun&error=" . urlencode($e->getMessage()));
    exit;
}
