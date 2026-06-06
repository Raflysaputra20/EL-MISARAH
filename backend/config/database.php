<?php

$host = "localhost";
$dbname = "kost_elmisarah_main";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

if (!function_exists('hitung_total_harga')) {
    function hitung_total_harga($kamar, $durasi) {
        $harga_bulanan = (float)$kamar['harga'];
        $harga_3_bulan = isset($kamar['harga_3_bulan']) && $kamar['harga_3_bulan'] > 0 ? (float)$kamar['harga_3_bulan'] : $harga_bulanan * 3;
        $harga_6_bulan = isset($kamar['harga_6_bulan']) && $kamar['harga_6_bulan'] > 0 ? (float)$kamar['harga_6_bulan'] : $harga_bulanan * 6;
        $harga_tahun = isset($kamar['harga_tahun']) && $kamar['harga_tahun'] > 0 ? (float)$kamar['harga_tahun'] : $harga_bulanan * 12;

        if ($durasi == 1) {
            return $harga_bulanan;
        } elseif ($durasi == 3) {
            return $harga_3_bulan;
        } elseif ($durasi == 6) {
            return $harga_6_bulan;
        } elseif ($durasi == 12) {
            return $harga_tahun;
        }

        // package composition logic for other durations (like 24, 36, 48, 60 etc.)
        $sisa = $durasi;
        $total = 0;
        
        if ($harga_tahun > 0) {
            $tahun = floor($sisa / 12);
            $total += $tahun * $harga_tahun;
            $sisa %= 12;
        }
        if ($harga_6_bulan > 0 && $sisa >= 6) {
            $total += $harga_6_bulan;
            $sisa -= 6;
        }
        if ($harga_3_bulan > 0 && $sisa >= 3) {
            $total += $harga_3_bulan;
            $sisa -= 3;
        }
        $total += $sisa * $harga_bulanan;
        
        return $total;
    }
}