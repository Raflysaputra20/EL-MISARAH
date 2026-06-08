# Dokumen Activity Diagram - EL-MISARAH Kost Management System

Dokumen ini berisi pemetaan alur aktivitas (**Activity Diagram**) untuk sistem manajemen kost **EL-MISARAH**. Diagram di bawah didefinisikan menggunakan notasi **Mermaid** yang dapat dirender secara visual. Alur aktivitas disesuaikan dengan database schema dan alur logika aplikasi yang ada di folder [backend/](file:///d:/laragon/www/EL-MISARAH-main/backend) dan [frontend/](file:///d:/laragon/www/EL-MISARAH-main/frontend).

---

## Ringkasan Aktor dan Fitur Utama
Sistem ini membagi hak akses menjadi 3 jenis aktor utama:
1. **Admin**: Bertanggung jawab mengelola seluruh data master kost (kamar, user, pengumuman), memproses transaksi (booking, pembayaran, tagihan), dan menindaklanjuti pengaduan serta ulasan.
2. **Penghuni (Resident)**: Pengguna yang sudah aktif menempati kamar kost. Fitur utamanya mencakup pembayaran tagihan bulanan, pengajuan pengaduan kerusakan fasilitas, melihat pengumuman internal, dan menulis ulasan kost.
3. **User (Guest / Calon Penyewa)**: Pengguna umum atau calon penyewa yang melakukan eksplorasi kamar, melakukan registrasi akun, mengajukan pemesanan (booking), serta mengunggah bukti pembayaran DP booking awal.

---

## 1. Activity Diagram - Admin Per-Fitur

### 1.1. Kelola Data Kamar (CRUD Kamar)
Diagram ini menjelaskan alur admin saat menambah, memperbarui, atau menghapus data kamar pada menu `backend/admin/kelola_kamar`.

```mermaid
flowchart TD
    Start([Mulai]) --> Login{Sudah Login Admin?}
    Login -- Tidak --> PageLogin[Halaman Login Admin] --> AuthAdmin[Autentikasi Kredensial] --> Login
    Login -- Ya --> MenuKamar[Buka Halaman Kelola Kamar]
    
    MenuKamar --> PilihAksi{Pilih Aksi}
    
    %% Tambah Kamar
    PilihAksi -- Tambah Kamar --> FormTambah[Isi Form Kamar & Upload Foto/Denah]
    FormTambah --> SubmitTambah[Klik Simpan]
    SubmitTambah --> ValidasiTambah{Validasi Input & File?}
    ValidasiTambah -- Invalid --> FormTambah
    ValidasiTambah -- Valid --> SimpanDB[Simpan Data Kamar Baru ke DB]
    SimpanDB --> UploadFile[Upload Foto ke /uploads/kamar/]
    UploadFile --> SuksesTambah[Tampilkan Pesan Sukses] --> MenuKamar
    
    %% Edit Kamar
    PilihAksi -- Edit Kamar --> PilihKamar[Pilih Kamar dari Daftar]
    PilihKamar --> FormEdit[Ubah Data & Upload Foto Baru jika ada]
    FormEdit --> SubmitEdit[Klik Update]
    SubmitEdit --> ValidasiEdit{Validasi Input?}
    ValidasiEdit -- Invalid --> FormEdit
    ValidasiEdit -- Valid --> UpdateDB[Update Database Kamar]
    UpdateDB --> SuksesEdit[Tampilkan Pesan Sukses] --> MenuKamar
    
    %% Hapus Kamar
    PilihAksi -- Hapus Kamar --> PilihKamarHapus[Pilih Kamar dari Daftar]
    PilihKamarHapus --> KonfirmasiHapus{Konfirmasi Hapus?}
    KonfirmasiHapus -- Batal --> MenuKamar
    KonfirmasiHapus -- Ya --> CekRelasi{Apakah Kamar Terisi / Dibooking?}
    CekRelasi -- Ya --> GagalHapus[Tampilkan Pesan: Kamar sedang aktif dihuni/dipesan] --> MenuKamar
    CekRelasi -- Tidak --> DeleteDB[Hapus Data Kamar dari DB]
    DeleteDB --> SuksesHapus[Tampilkan Pesan Sukses] --> MenuKamar
    
    PilihAksi -- Selesai --> End([Selesai])
```

---

### 1.2. Kelola Booking & Pembayaran DP
Alur pemrosesan pengajuan sewa kamar oleh calon penyewa (status `pending` -> `menunggu_dp` -> `disetujui`) yang diakses pada menu `backend/admin/kelola_booking` dan `backend/admin/kelola_pembayaran`.

```mermaid
flowchart TD
    Start([Mulai]) --> BukaBooking[Admin Buka Menu Kelola Booking]
    BukaBooking --> PilihBooking[Pilih Pengajuan Booking Status 'pending']
    PilihBooking --> AksiBooking{Aksi Admin}
    
    %% Tolak Booking
    AksiBooking -- Tolak --> FormTolak[Masukkan Alasan Penolakan]
    FormTolak --> UpdateTolak[Update Booking: status 'ditolak' & Kamar: status 'tersedia']
    UpdateTolak --> KirimNotifTolak[Kirim Status Penolakan ke Calon Penyewa] --> End([Selesai])
    
    %% Terima Booking Tahap 1
    AksiBooking -- Terima --> UpdateTerima[Update Booking: status 'menunggu_dp' & Kamar: status 'dibooking']
    UpdateTerima --> KirimNotifDP[Kirim Notifikasi ke Calon Penyewa untuk Bayar DP]
    
    %% Calon Penyewa Melakukan Pembayaran
    KirimNotifDP -.-> UserUploadDP[Calon Penyewa Upload Bukti Pembayaran DP]
    
    %% Verifikasi DP oleh Admin
    UserUploadDP --> BukaPembayaran[Admin Buka Menu Kelola Pembayaran]
    BukaPembayaran --> CekBukti[Periksa Validitas Foto Bukti & Jumlah Nominal]
    CekBukti --> Verifikasi{Bukti Pembayaran Valid?}
    
    Verifikasi -- Tidak Valid --> UpdateBayarGagal[Update Pembayaran: 'tidak_valid' & Booking: 'menunggu_dp']
    UpdateBayarGagal --> MintaBayarUlang[Notifikasi ke Penyewa untuk Upload Ulang] --> UserUploadDP
    
    Verifikasi -- Valid --> UpdateBayarSukses[Update Pembayaran: 'valid' & Booking: 'disetujui']
    UpdateBayarSukses --> BuatPenghuni[Sistem Otomatis Membuat Akun Penghuni Baru]
    BuatPenghuni --> KamarTerisi[Update Kamar: status 'terisi']
    KamarTerisi --> End
```

---

### 1.3. Kelola Pengaduan Kerusakan Fasilitas
Alur respon admin terhadap laporan kerusakan fasilitas yang dikirim oleh Penghuni di menu `backend/admin/kelola_pengaduan`.

```mermaid
flowchart TD
    Start([Mulai]) --> BukaPengaduan[Admin Buka Menu Kelola Pengaduan]
    BukaPengaduan --> DaftarAduan[Tampilkan Daftar Pengaduan dengan status 'masuk' / 'baru']
    DaftarAduan --> PilihAduan[Pilih Detail Pengaduan]
    
    PilihAduan --> UbahProses[Ubah Status Pengaduan: 'diproses']
    UbahProses --> UploadFotoProses[Upload Foto Progres Kerusakan Sedang Diperbaiki]
    UploadFotoProses --> ProsesPerbaikan[Petugas/Teknisi Memperbaiki Fasilitas]
    
    ProsesPerbaikan --> SelesaiPerbaikan[Ubah Status Pengaduan: 'selesai']
    SelesaiPerbaikan --> UploadFotoSelesai[Upload Foto Hasil Perbaikan Selesai]
    UploadFotoSelesai --> SimpanAduanDB[Simpan Status Terakhir di Database]
    SimpanAduanDB --> KirimNotif[Penghuni Menerima Status Pengaduan Selesai] --> End([Selesai])
```

---

### 1.4. Kelola Tagihan Bulanan & Verifikasi Pembayaran
Alur pembuatan tagihan periodik sewa kost dan verifikasi pembayarannya di menu `backend/admin/kelola_tagihan`.

```mermaid
flowchart TD
    Start([Mulai]) --> BukaTagihan[Admin Buka Menu Kelola Tagihan]
    BukaTagihan --> PilihPenghuni[Pilih Penghuni Aktif yang Akan Ditagih]
    PilihPenghuni --> InputForm[Input Jumlah Tagihan Sewa & Tanggal Jatuh Tempo]
    InputForm --> SimpanTagihan[Sistem Simpan Tagihan: status 'belum_bayar']
    SimpanTagihan --> KirimNotif[Penghuni Menerima Notifikasi Tagihan Baru]
    
    %% Proses Pembayaran oleh Penghuni
    KirimNotif -.-> PenghuniBayar[Penghuni Transfer & Upload Bukti Pembayaran Tagihan]
    
    %% Verifikasi Pembayaran oleh Admin
    PenghuniBayar --> BukaVerifikasi[Admin Buka Menu Kelola Pembayaran]
    BukaVerifikasi --> PilihPembayaran[Pilih Bukti Pembayaran Pembayaran Bulanan]
    PilihPembayaran --> CekValiditas{Bukti Pembayaran Valid?}
    
    CekValiditas -- Tidak --> SetTidakValid[Update Pembayaran: 'tidak_valid' & Tagihan: 'belum_bayar']
    SetTidakValid --> MintaReUpload[Penghuni Diminta Upload Ulang] --> PenghuniBayar
    
    CekValiditas -- Ya --> SetLunas[Update Pembayaran: 'valid' & Tagihan: 'lunas']
    SetLunas --> PerpanjangHuni[Perbarui Masa Huni / Tanggal Selesai Sewa]
    PerpanjangHuni --> End([Selesai])
```

---

## 2. Activity Diagram - Penghuni Per-Fitur

### 2.1. Pembayaran Tagihan Bulanan (Upload Bukti Bayar)
Alur bagi Penghuni untuk melihat dan melunasi tagihan kost bulanan melalui menu `backend/penghuni/pembayaran.php`.

```mermaid
flowchart TD
    Start([Mulai]) --> LoginPenghuni[Penghuni Login ke Akun]
    LoginPenghuni --> MenuPembayaran[Buka Halaman Pembayaran]
    MenuPembayaran --> LihatTagihan[Tampilkan Daftar Tagihan Aktif]
    LihatTagihan --> PilihTagihan[Pilih Tagihan status 'belum_bayar']
    
    PilihTagihan --> InfoBank[Sistem Tampilkan Informasi Rekening Kost]
    InfoBank --> MelakukanTransfer[Penghuni Transfer Dana via Bank/E-Wallet]
    MelakukanTransfer --> FormUpload[Isi Tanggal Bayar, Jumlah Transfer, & Upload Bukti Foto]
    FormUpload --> SubmitBukti[Klik Kirim Bukti Pembayaran]
    
    SubmitBukti --> ValidasiForm{File & Input Lengkap?}
    ValidasiForm -- Tidak --> FormUpload
    ValidasiForm -- Ya --> SimpanPembayaran[Sistem Simpan Pembayaran: status 'menunggu_verifikasi']
    SimpanPembayaran --> TungguAdmin[Menunggu Proses Verifikasi Admin] --> End([Selesai])
```

---

### 2.2. Pengajuan Pengaduan Kerusakan
Alur bagi Penghuni untuk melaporkan kerusakan fasilitas di dalam kamar atau area kost melalui menu `backend/penghuni/buat_pengaduan.php` dan memantau perkembangannya di `backend/penghuni/riwayat_pengaduan.php`.

```mermaid
flowchart TD
    Start([Mulai]) --> LoginPenghuni[Penghuni Login]
    LoginPenghuni --> MenuPengaduan[Buka Halaman Pengaduan]
    MenuPengaduan --> BuatAduan[Klik Tombol Buat Pengaduan Baru]
    
    BuatAduan --> IsiFormAduan[Isi Judul Keluhan, Isi Deskripsi, Prioritas, & Upload Foto Bukti]
    IsiFormAduan --> SubmitAduan[Klik Kirim Laporan]
    SubmitAduan --> ValidasiForm{Apakah Form Lengkap?}
    ValidasiForm -- Tidak --> IsiFormAduan
    
    ValidasiForm -- Ya --> SimpanAduan[Sistem Simpan Pengaduan: status 'baru' / 'masuk']
    SimpanAduan --> RiwayatAduan[Lihat Daftar Pengaduan & Pantau Progres Perbaikan]
    
    %% Alur Progres
    RiwayatAduan --> CekStatus{Status Pengaduan?}
    CekStatus -- diproses --> LihatFotoProses[Lihat Foto Proses Perbaikan dari Admin]
    CekStatus -- selesai --> LihatFotoSelesai[Lihat Foto Selesai Perbaikan & Fasilitas Kembali Normal]
    LihatFotoSelesai --> End([Selesai])
```

---

### 2.3. Pemberian Ulasan & Testimoni Kost
Alur bagi Penghuni yang aktif untuk memberikan rating dan tanggapan/testimoni terhadap pelayanan kost di menu `backend/penghuni/ulasan.php`.

```mermaid
flowchart TD
    Start([Mulai]) --> LoginPenghuni[Penghuni Login]
    LoginPenghuni --> MenuUlasan[Buka Halaman Ulasan]
    MenuUlasan --> CekUlasanLama{Sudah Pernah Memberi Ulasan?}
    
    %% Mengisi Ulasan Pertama
    CekUlasanLama -- Belum --> FormUlasan[Isi Rating Bintang 1-5, Komentar, & Upload Foto Ulasan]
    FormUlasan --> SubmitUlasan[Klik Kirim Ulasan]
    SubmitUlasan --> SimpanUlasanDB[Sistem Simpan Ulasan: default tampilkan = 0]
    
    %% Mengedit Ulasan yang Ada
    CekUlasanLama -- Sudah --> TampilkanUlasanLama[Tampilkan Ulasan yang Ada & Balasan Admin]
    TampilkanUlasanLama --> EditUlasan[Klik Edit Ulasan]
    EditUlasan --> FormUlasan
    
    SimpanUlasanDB --> TungguModerasi[Menunggu Admin Menyetujui Ulasan Ditampilkan di Landing Page] --> End([Selesai])
```

---

## 3. Activity Diagram - User (Guest / Calon Penyewa) Per-Fitur

### 3.1. Pendaftaran Akun & Autentikasi (Registrasi & Login)
Alur bagi pengunjung umum (Guest) untuk membuat akun di website agar dapat melakukan pemesanan kamar kost di `frontend/pages/guest/profil.php`.

```mermaid
flowchart TD
    Start([Mulai]) --> AksesWeb[Pengunjung Buka Landing Page EL-MISARAH]
    AksesWeb --> BukaModalAuth[Klik Login / Registrasi]
    BukaModalAuth --> PilihAksi{Pilih Opsi}
    
    %% Registrasi
    PilihAksi -- Registrasi Akun Baru --> FormDaftar[Isi Nama, Email, No HP, & Password]
    FormDaftar --> SubmitDaftar[Klik Daftar Sekarang]
    SubmitDaftar --> ValidasiEmail{Email Sudah Terdaftar?}
    ValidasiEmail -- Ya --> NotifEmailGanda[Tampilkan Pesan Error: Email Sudah Digunakan] --> FormDaftar
    ValidasiEmail -- Tidak --> SimpanUserDB[Simpan Akun Baru ke DB: role 'user', status 'aktif']
    SimpanUserDB --> SuksesDaftar[Tampilkan Pesan Registrasi Sukses] --> FormLogin
    
    %% Login
    PilihAksi -- Login Akun --> FormLogin[Masukkan Email & Password]
    FormLogin --> SubmitLogin[Klik Masuk]
    SubmitLogin --> Autentikasi{Kredensial Cocok & Akun Aktif?}
    Autentikasi -- Tidak --> NotifGagalLogin[Tampilkan Pesan Error: Kredensial Salah] --> FormLogin
    Autentikasi -- Ya --> SetSession[Sistem Setup Session: user_id, nama, role, status]
    
    SetSession --> RedirectRole[Arahkan User Berdasarkan Role]
    RedirectRole --> End([Selesai])
```

---

### 3.2. Eksplorasi Kamar & Pengajuan Booking
Alur bagi Calon Penyewa (User) untuk mengecek ketersediaan kamar dan melakukan booking melalui menu `frontend/pages/guest/rooms.php` dan `frontend/pages/guest/booking.php`.

```mermaid
flowchart TD
    Start([Mulai]) --> LoginUser[User Terdaftar Login]
    LoginUser --> BukaDaftarKamar[Buka Halaman Cari Kamar]
    BukaDaftarKamar --> FilterKamar[Eksplorasi Detail Kamar, Fasilitas, & Status Kamar]
    FilterKamar --> PilihKamar[Pilih Kamar dengan status 'tersedia']
    
    PilihKamar --> KlikBooking[Klik Tombol Booking Kamar]
    KlikBooking --> FormBooking[Isi Tanggal Rencana Masuk, Durasi Sewa, & Catatan Tambahan]
    FormBooking --> SubmitBooking[Klik Konfirmasi Pemesanan]
    
    SubmitBooking --> CekKetersediaan{Apakah Kamar Masih Tersedia di DB?}
    CekKetersediaan -- Tidak --> NotifKamarPenuh[Tampilkan Pesan: Kamar Baru Saja Dipesan Orang Lain] --> BukaDaftarKamar
    
    CekKetersediaan -- Ya --> SimpanBooking[Sistem Simpan Booking: status 'pending']
    SimpanBooking --> UpdateKamarStatus[Update Kamar: status 'dibooking']
    UpdateKamarStatus --> ArahkanMenunggu[Arahkan ke Halaman Menunggu Persetujuan Admin] --> End([Selesai])
```

---

### 3.3. Pembayaran DP Booking (Konfirmasi Pesanan)
Alur bagi Calon Penyewa untuk mengunggah bukti pembayaran DP setelah pengajuan booking disetujui oleh admin (status booking berubah menjadi `menunggu_dp`), melalui menu `frontend/pages/guest/pembayaran_booking.php`.

```mermaid
flowchart TD
    Start([Mulai]) --> BukaRiwayatBooking[User Buka Menu Riwayat Booking]
    BukaRiwayatBooking --> LihatStatus{Cek Status Booking}
    
    LihatStatus -- pending --> TungguPersetujuan[Menunggu Persetujuan Verifikasi Awal Admin] --> BukaRiwayatBooking
    
    LihatStatus -- menunggu_dp --> KlikBayar[Klik Tombol Pembayaran DP]
    KlikBayar --> HalamanBayar[Tampilkan Jumlah Tagihan DP & Rekening Pembayaran]
    HalamanBayar --> BayarTransfer[Penyewa Transfer Pembayaran]
    
    BayarTransfer --> FormUploadDP[Isi Form Pembayaran & Upload Foto Bukti Pembayaran DP]
    FormUploadDP --> SubmitDP[Klik Kirim Bukti DP]
    SubmitDP --> SimpanBuktiDB[Sistem Simpan Transaksi Pembayaran: status 'menunggu_verifikasi']
    
    SimpanBuktiDB --> HalamanSelesai[Arahkan ke Halaman Menunggu Konfirmasi Bukti Bayar] --> End([Selesai])
```
