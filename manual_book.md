# Panduan Pengguna & Instalasi (Manual Book) - EL-MISARAH Kost Management System

Dokumen ini merupakan panduan lengkap penggunaan aplikasi **EL-MISARAH Kost Management System**, baik dari sudut pandang pengguna (Guest, Penghuni, Admin) maupun dari aspek teknis instalasi dan deployment (hosting).

---

## DAFTAR ISI
1. [Pendahuluan](#1-pendahuluan)
2. [Panduan Calon Penyewa (User / Guest)](#2-panduan-calon-penyewa-user--guest)
   - [Registrasi & Login Akun](#registrasi--login-akun)
   - [Mencari & Booking Kamar](#mencari--booking-kamar)
   - [Pembayaran DP Booking](#pembayaran-dp-booking)
3. [Panduan Penghuni (Resident)](#3-panduan-penghuni-resident)
   - [Melihat & Membayar Tagihan Bulanan](#melihat--membayar-tagihan-bulanan)
   - [Mengajukan Pengaduan Kerusakan](#mengajukan-pengaduan-kerusakan)
   - [Mengisi Ulasan / Testimoni Kost](#mengisi-ulasan--testimoni-kost)
4. [Panduan Admin / Pengelola Kost](#4-panduan-admin--pengelola-kost)
   - [Login Portal Admin](#login-portal-admin)
   - [Kelola Kamar Kost](#kelola-kamar-kost)
   - [Persetujuan Booking & Pembayaran](#persetujuan-booking--pembayaran)
   - [Pembuatan Tagihan Bulanan](#pembuatan-tagihan-bulanan)
   - [Tindak Lanjut Laporan Pengaduan](#tindak-lanjut-laporan-pengaduan)
   - [Moderasi Ulasan](#moderasi-ulasan)
5. [Panduan Instalasi Lokal & Hosting](#5-panduan-instalasi-lokal--hosting)
   - [Instalasi Lokal (Laragon/XAMPP)](#instalasi-lokal-laragonxampp)
   - [Deployment Ke Hosting Gratis (InfinityFree)](#deployment-ke-hosting-gratis-infinityfree)

---

## 1. PENDAHULUAN
**EL-MISARAH Kost Management System** adalah platform berbasis web dinamis yang dirancang untuk mendigitalisasi proses administrasi dan manajemen kost. Sistem ini menghubungkan calon penyewa, penghuni kost aktif, dan admin/pengelola kost dalam satu platform terintegrasi.

---

## 2. PANDUAN CALON PENYEWA (USER / GUEST)

### Registrasi & Login Akun
1. Buka halaman utama website EL-MISARAH.
2. Klik tombol **Login / Registrasi** pada bagian menu atas.
3. Untuk pendaftaran baru, klik **Daftar Sekarang** lalu masukkan informasi nama, email aktif, nomor WhatsApp, serta password Anda.
4. Jika sudah mendaftar, masukkan email dan password untuk masuk ke dashboard user.

### Mencari & Booking Kamar
1. Buka menu **Kamar / Rooms** di halaman utama.
2. Jelajahi daftar tipe kamar yang tersedia. Anda dapat mengklik **Detail** untuk melihat fasilitas kamar, denah layout, foto, dan harga sewa.
3. Klik tombol **Booking Kamar** pada kamar yang Anda pilih.
4. Lengkapi formulir pengajuan sewa:
   - **Tanggal Mulai Masuk**: Rencana mulai menempati kost.
   - **Durasi Sewa**: Pilih paket durasi bulanan/tahunan.
   - **Catatan**: Informasi tambahan untuk admin (jika ada).
5. Klik **Konfirmasi Pemesanan**. Pengajuan sewa Anda sekarang berstatus **Pending** menunggu persetujuan awal dari pengelola kost.

### Pembayaran DP Booking
1. Setelah pengajuan booking Anda disetujui awal oleh admin, status pemesanan akan berubah menjadi **Menunggu DP**.
2. Masuk ke halaman **Riwayat Booking** di profil Anda.
3. Klik tombol **Bayar DP** untuk melihat jumlah nominal pembayaran dan nomor rekening tujuan transfer bank.
4. Lakukan transfer bank, lalu unggah foto bukti transfer di form yang disediakan.
5. Klik **Kirim Bukti Pembayaran**. Status akan berubah menjadi **Menunggu Konfirmasi** selagi admin memverifikasi transferan Anda.

---

## 3. PANDUAN PENGHUNI (RESIDENT)

### Melihat & Membayar Tagihan Bulanan
1. Setelah akun Anda aktif sebagai penghuni, setiap bulan admin akan mengirimkan tagihan sewa bulanan baru.
2. Buka menu **Pembayaran** di dashboard penghuni Anda.
3. Anda akan melihat daftar tagihan aktif berstatus **Belum Bayar**.
4. Klik tombol **Bayar** pada tagihan tersebut untuk melihat rincian jumlah transfer dan nomor rekening pembayaran.
5. Unggah berkas foto bukti transfer, isi tanggal pembayaran, lalu klik **Kirim Bukti**.
6. Status tagihan akan berubah menjadi **Menunggu Verifikasi** hingga admin mengonfirmasi pembayaran Anda menjadi **Lunas**.

### Mengajukan Pengaduan Kerusakan
1. Jika terdapat fasilitas kamar atau area kost yang rusak, pilih menu **Pengaduan** di dashboard Anda.
2. Klik **Buat Pengaduan Baru**.
3. Isi detail laporan:
   - **Judul**: Subjek keluhan (misal: "Kran Air Kamar Mandi Bocor").
   - **Isi**: Penjelasan rinci mengenai kendala fasilitas.
   - **Prioritas**: Pilih tingkat urgensi (Rendah / Sedang / Tinggi).
   - **Foto Bukti**: Unggah foto bagian fasilitas yang rusak.
4. Klik **Kirim Laporan**. Anda dapat memantau status pengerjaan laporan Anda (baru -> diproses -> selesai) langsung di menu riwayat pengaduan.

### Mengisi Ulasan / Testimoni Kost
1. Pilih menu **Ulasan** pada panel dashboard Anda.
2. Masukkan rating bintang (1 s.d 5) dan tulis ulasan atau testimoni pengalaman Anda selama menghuni kost EL-MISARAH.
3. Anda juga dapat menyertakan foto dokumentasi kamar Anda.
4. Klik **Simpan Ulasan**. Ulasan Anda akan diserahkan ke admin dan akan ditampilkan pada halaman beranda utama setelah disetujui.

### Melihat Pengumuman Kost
1. Pada dashboard utama atau menu **Pengumuman**, Anda dapat membaca berita, aturan kost terbaru, atau informasi jam malam kost yang diterbitkan secara resmi oleh pihak pengelola.

---

## 4. PANDUAN ADMIN / PENGELOLA KOST

### Login Portal Admin
1. Untuk masuk ke panel admin, buka folder admin di browser (atau gunakan link redirect menu admin).
2. Masukkan username: `admin` dan password: `admin123` (atau sesuai akun admin aktif Anda).

### Kelola Kamar Kost
1. Buka menu **Kelola Kamar**.
2. **Tambah Kamar**: Klik **Tambah Kamar Baru**, isi nomor kamar, tipe, harga sewa bulanan/paket diskon, list fasilitas, deskripsi singkat, lalu unggah foto kamar dan denah layout. Klik simpan.
3. **Edit Kamar**: Pilih kamar yang ingin diubah, lakukan pembaruan data, klik update.
4. **Hapus Kamar**: Klik tombol hapus. Sistem akan menolak penghapusan jika kamar tersebut masih aktif ditempati oleh penghuni.

### Persetujuan Booking & Pembayaran
1. **Kelola Booking**: Buka daftar transaksi booking masuk.
   - Klik **Setujui** untuk membolehkan calon penyewa melakukan pembayaran DP (status berubah ke *menunggu_dp*).
   - Klik **Tolak** dan isi alasan penolakan jika kamar sudah terlanjur dipesan secara offline atau data user tidak valid.
2. **Kelola Pembayaran**: Buka daftar konfirmasi pembayaran.
   - Periksa foto bukti bayar yang diunggah user.
   - Jika valid, klik **Verifikasi / Setujui**. Sistem secara otomatis akan memperbarui status booking menjadi **Disetujui / Aktif** dan memasukkan data user tersebut ke daftar penghuni kost aktif.

### Pembuatan Tagihan Bulanan
1. Buka menu **Kelola Tagihan**.
2. Klik tombol **Buat Tagihan Baru**.
3. Pilih nama penghuni aktif yang akan diberikan tagihan bulanan.
4. Masukkan nominal tagihan sewa dan tentukan tanggal batas waktu pembayaran (Jatuh Tempo).
5. Klik **Terbitkan Tagihan**. Sistem akan mengirim notifikasi tagihan secara otomatis ke dashboard penghuni terkait.

### Tindak Lanjut Laporan Pengaduan
1. Masuk ke menu **Kelola Pengaduan**.
2. Pilih pengaduan baru masuk yang akan dikerjakan, ubah status menjadi **Diproses** dan unggah foto bukti pengerjaan telah dimulai.
3. Setelah perbaikan fisik selesai dilakukan oleh teknisi, ubah status aduan menjadi **Selesai** dan unggah foto hasil perbaikan sebagai bukti untuk penghuni.

### Moderasi Ulasan
1. Buka menu **Kelola Ulasan**.
2. Anda akan melihat ulasan yang dikirimkan oleh para penghuni.
3. Klik tombol **Tampilkan** (agar ulasan muncul di landing page utama website) atau **Sembunyikan**.
4. Anda juga dapat memberikan komentar balasan terima kasih/tanggapan dari pengelola atas ulasan tersebut.

---

## 5. PANDUAN INSTALASI LOKAL & HOSTING

### Instalasi Lokal (Laragon/XAMPP)
Untuk menjalankan web aplikasi EL-MISARAH secara lokal pada komputer Anda:
1. Pastikan Anda memiliki server lokal seperti **Laragon** (sangat direkomendasikan) atau **XAMPP** yang sudah mendukung PHP 7.4 s.d 8.2 dan database MySQL.
2. Salin folder project `EL-MISARAH-main` ke dalam direktori server lokal Anda:
   - Jika **Laragon**: letakkan di dalam folder `C:\laragon\www\`
   - Jika **XAMPP**: letakkan di dalam folder `C:\xampp\htdocs\`
3. Jalankan Apache dan MySQL pada server lokal Anda.
4. Impor database:
   - Akses `http://localhost/phpmyadmin/` di browser.
   - Buat database baru dengan nama `kost_elmisarah_main`.
   - Pilih database tersebut, lalu buka menu **Import**, pilih berkas file database `kost_elmisarah_main.sql` yang berada di dalam folder project Anda, lalu klik **Go/Import**.
5. Sesuaikan konfigurasi database lokal pada file `backend/config/database.php`. Secara default, pengaturannya adalah:
   ```php
   $host = "localhost";
   $dbname = "kost_elmisarah_main";
   $username = "root";
   $password = "";
   ```
6. Buka aplikasi di browser Anda dengan mengetikkan alamat:
   - `http://localhost/EL-MISARAH-main/` atau `http://el-misarah.test/` (jika menggunakan auto-virtual host Laragon).

### Deployment Ke Hosting Gratis (InfinityFree)
Jika Anda ingin mempublikasikan aplikasi agar bisa diakses oleh publik secara online tanpa biaya:

1. **Daftarkan Akun Hosting**:
   - Daftarkan akun di [InfinityFree](https://infinityfree.com/).
   - Buat akun hosting baru dan pilih subdomain gratis Anda (misal: `elmisarah.infinityfreeapp.com`).
2. **Setup Database di Server**:
   - Masuk ke Client Area InfinityFree, klik **Control Panel** (VistaPanel).
   - Cari menu **MySQL Databases**, lalu buat database baru. Catat informasi Hostname database, Database Name, Username, dan Password yang diberikan.
   - Klik tautan **phpMyAdmin** di samping nama database baru Anda.
   - Pilih tab **Import**, unggah berkas file `.sql` Anda (`kost_elmisarah_main.sql`), lalu klik **Go** untuk memproses impor data.
3. **Upload File via FTP (FileZilla)**:
   - Unduh dan pasang aplikasi **FileZilla** di komputer Anda.
   - Dapatkan informasi koneksi FTP dari dashboard akun InfinityFree Anda (FTP Host, FTP Username, FTP Password).
   - Masukkan informasi tersebut di kolom paling atas FileZilla, lalu klik **Quickconnect**.
   - Di kolom sebelah kanan (Server), masuk ke folder **`htdocs`**.
   - Di kolom sebelah kiri (Lokal), cari folder project Anda `EL-MISARAH-main`, pilih seluruh file di dalamnya, lalu klik kanan dan pilih **Upload** untuk mentransfer file ke server hosting.
4. **Update Konfigurasi Database Online**:
   - Setelah semua proses upload di FileZilla selesai, buka file `backend/config/database.php` di server (Anda bisa klik kanan file tersebut di FileZilla lalu pilih *View/Edit*).
   - Ubah parameter database dengan konfigurasi database yang Anda dapatkan dari InfinityFree:
     ```php
     $host = "sqlxxx.epizy.com"; // Ganti dengan hostname database Anda di InfinityFree
     $dbname = "epiz_xxxxxx_kost_elmisarah"; // Ganti dengan nama database Anda
     $username = "epiz_xxxxxx"; // Ganti dengan username database Anda
     $password = "password_akun_anda"; // Ganti dengan password hosting Anda
     ```
   - Simpan perubahan file.
5. **Uji Coba Aplikasi**:
   - Buka domain gratis Anda di browser (misal: `http://elmisarah.infinityfreeapp.com`).
   - Coba lakukan registrasi user baru dan verifikasi halaman admin untuk memastikan seluruh sistem berjalan dengan normal.
