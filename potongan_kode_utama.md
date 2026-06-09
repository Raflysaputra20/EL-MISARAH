# POTONGAN KODE UTAMA (CRUCIAL CODE SNIPPETS)
## EL-MISARAH Kost Management System

Dokumen ini berisi potongan kode utama yang paling krusial pada platform **EL-MISARAH Kost Management System**. Bagian ini mencakup algoritma perhitungan harga sewa dan logika kontrol transaksi database untuk promosi role pengguna.

---

### 1. Algoritma Perhitungan Tarif Sewa Multi-Paket (`hitung_total_harga`)
Fungsi ini terletak pada file `backend/config/database.php`. Algoritma ini digunakan untuk menghitung total biaya sewa kamar secara otomatis berdasarkan kombinasi paket durasi bulan sewa (1 bulan, 3 bulan, 6 bulan, dan 1 tahun/12 bulan) agar memberikan efisiensi harga terbaik bagi penyewa.

#### Potongan Kode (Snippet):
```php
function hitung_total_harga($kamar, $durasi) {
    // 1. Inisialisasi harga dasar dari database
    $harga_bulanan = (float)$kamar['harga'];
    $harga_3_bulan = isset($kamar['harga_3_bulan']) && $kamar['harga_3_bulan'] > 0 ? (float)$kamar['harga_3_bulan'] : $harga_bulanan * 3;
    $harga_6_bulan = isset($kamar['harga_6_bulan']) && $kamar['harga_6_bulan'] > 0 ? (float)$kamar['harga_6_bulan'] : $harga_bulanan * 6;
    $harga_tahun = isset($kamar['harga_tahun']) && $kamar['harga_tahun'] > 0 ? (float)$kamar['harga_tahun'] : $harga_bulanan * 12;

    // 2. Pencocokan langsung jika durasi pas dengan paket dasar
    if ($durasi == 1) {
        return $harga_bulanan;
    } elseif ($durasi == 3) {
        return $harga_3_bulan;
    } elseif ($durasi == 6) {
        return $harga_6_bulan;
    } elseif ($durasi == 12) {
        return $harga_tahun;
    }

    // 3. Algoritma Greedy untuk durasi kustom (misal: 15 bulan, 20 bulan)
    $sisa = $durasi;
    $total = 0;
    
    // Pecah ke paket tahunan jika durasi >= 12 bulan
    if ($harga_tahun > 0) {
        $tahun = floor($sisa / 12);
        $total += $tahun * $harga_tahun;
        $sisa %= 12;
    }
    // Pecah ke paket 6 bulanan jika sisa durasi >= 6 bulan
    if ($harga_6_bulan > 0 && $sisa >= 6) {
        $total += $harga_6_bulan;
        $sisa -= 6;
    }
    // Pecah ke paket 3 bulanan jika sisa durasi >= 3 bulan
    if ($harga_3_bulan > 0 && $sisa >= 3) {
        $total += $harga_3_bulan;
        $sisa -= 3;
    }
    // Sisa bulan yang tidak masuk paket besar dihitung dengan harga bulanan biasa
    $total += $sisa * $harga_bulanan;
    
    return $total;
}
```

#### Penjelasan Logika Kode (Bahasa Manusia):
1. **Inisialisasi & Pengaman Nilai**: Sistem mengambil harga sewa dasar (bulanan) dari data kamar. Jika harga paket promo (3 bulanan, 6 bulanan, tahunan) tidak didefinisikan atau bernilai `0` di database, sistem secara otomatis menghitung harga paket tersebut dengan mengalikannya secara proporsional (misal: paket tahunan = harga bulanan $\times$ 12).
2. **Kondisi Dasar (Base Case)**: Jika pengguna memilih tepat durasi 1, 3, 6, atau 12 bulan, sistem langsung mengembalikan nilai harga paket tersebut dari database tanpa kalkulasi tambahan.
3. **Algoritma Komposisi Paket (Greedy Breakdown)**: Jika pengguna memilih durasi di luar paket standar (misalnya **15 bulan**), sistem akan memecahnya secara efisien:
   * Mengambil **1 unit Paket Tahunan** (12 bulan sewa) terlebih dahulu karena paket tahunan memiliki diskon lebih besar. Sisa waktu sewa menjadi 3 bulan.
   * Karena sisa waktu sewa adalah 3 bulan, sistem mengambil **1 unit Paket 3 Bulanan** daripada menghitungnya sebagai 3 kali harga bulanan biasa.
   * Hasil akhir dijumlahkan untuk mendapatkan total biaya sewa yang paling hemat bagi penyewa.

---

### 2. Logika Transaksi Validasi & Promosi Status Penghuni (`jadikan_penghuni.php`)
File ini terletak di `backend/admin/kelola_booking/jadikan_penghuni.php`. Ini adalah pusat logika kontrol aliran data (*state machine*) saat admin menyetujui penyewa yang sudah melakukan pelunasan booking untuk masuk menjadi penghuni resmi kost.

#### Potongan Kode (Snippet):
```php
try {
    $conn->beginTransaction();

    // 1. Upgrade role user biasa menjadi 'penghuni'
    $updateUser = $conn->prepare("UPDATE users SET role = 'penghuni' WHERE id = ?");
    $updateUser->execute([$booking["user_id"]]);

    // 2. Ubah status kamar menjadi 'terisi'
    if (!empty($booking["kamar_id"])) {
        $updateKamar = $conn->prepare("UPDATE kamar SET status = 'terisi' WHERE id = ?");
        $updateKamar->execute([$booking["kamar_id"]]);
    }

    // 3. Ubah status pemesanan (booking) menjadi 'aktif'
    $updateBooking = $conn->prepare("UPDATE booking SET status = 'aktif' WHERE id = ?");
    $updateBooking->execute([$id]);

    // 4. Catat riwayat resmi ke dalam tabel penghuni
    $stmtInsertPenghuni = $conn->prepare("INSERT INTO penghuni (user_id, kamar_id, booking_id, tanggal_masuk, status) VALUES (?, ?, ?, ?, 'aktif')");
    $stmtInsertPenghuni->execute([
        $booking["user_id"],
        $booking["kamar_id"],
        $id,
        $booking["tanggal_masuk"] ?? date('Y-m-d')
    ]);

    // 5. AUTO-REJECT: Tolak booking lain yang memesan kamar yang sama secara otomatis
    $stmtRejectSameRoom = $conn->prepare("
        UPDATE booking 
        SET status = 'ditolak', 
            catatan = CONCAT(IFNULL(catatan,''), '\n[Otomatis ditolak: kamar sudah diisi penghuni lain]')
        WHERE kamar_id = ? 
          AND id != ? 
          AND status IN ('pending', 'disetujui', 'menunggu_dp')
    ");
    $stmtRejectSameRoom->execute([$booking["kamar_id"], $id]);

    // 6. AUTO-REJECT: Tolak pengajuan booking lain dari user yang sama
    $stmtRejectSameUser = $conn->prepare("
        UPDATE booking 
        SET status = 'ditolak',
            catatan = CONCAT(IFNULL(catatan,''), '\n[Otomatis ditolak: user sudah menjadi penghuni kamar lain]')
        WHERE user_id = ? 
          AND id != ? 
          AND status IN ('pending', 'disetujui', 'menunggu_dp')
    ");
    $stmtRejectSameUser->execute([$booking["user_id"], $id]);

    $conn->commit();

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $e;
}
```

#### Penjelasan Logika Kode (Bahasa Manusia):
1. **Mekanisme Transaksi Database (`ACID Properties`)**: 
   Skrip ini membungkus semua operasi SQL dalam blok `$conn->beginTransaction()` dan `$conn->commit()`. Jika terjadi kegagalan sistem pada salah satu dari 6 langkah pembaruan di atas, sistem akan memicu blok `$conn->rollBack()`. Hal ini menjamin bahwa tidak akan ada kesalahan konsistensi data (misalnya: status kamar berubah terisi tetapi role pengguna gagal di-upgrade).
2. **Promosi Role Otomatis**: 
   Ketika admin mengeklik tombol setujui, sistem secara otomatis menaikkan derajat akun pengguna dari `user` (pendaftar biasa) menjadi `penghuni` sehingga saat login berikutnya, pengguna tersebut dapat mengakses menu khusus penghuni seperti riwayat tagihan sewa dan menu pengaduan kerusakan kamar.
3. **Pencegahan Kamar Ganda & Konflik (Auto-Reject)**:
   * **Bentrokan Kamar**: Begitu kamar X dihuni oleh penyewa A, semua pengajuan sewa dari calon penghuni lain (B, C, dst.) untuk kamar X yang berstatus *pending* atau *menunggu DP* secara otomatis diubah menjadi `ditolak` dengan catatan sistem bahwa kamar sudah terisi.
   * **Bentrokan Akun**: Satu akun pengguna hanya diperkenankan menyewa satu kamar saja dalam satu periode aktif. Jika dia memiliki pengajuan sewa di kamar lain, pengajuan tersebut otomatis ditolak oleh sistem.
