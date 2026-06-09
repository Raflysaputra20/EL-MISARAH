# Dokumen Activity Diagram - EL-MISARAH Kost Management System

Dokumen ini berisi pemetaan alur aktivitas (**Activity Diagram**) dengan format **Swimlane (Aktor, Sistem, Database)** untuk sistem manajemen kost **EL-MISARAH**. Diagram di bawah didefinisikan menggunakan notasi **Mermaid** yang memisahkan tanggung jawab antara:
1. **Aktor** (Admin, Penghuni, atau Calon Penyewa)
2. **Sistem (Aplikasi)** (Logika backend dan frontend)
3. **Database** (Operasi penyimpanan, pembaruan, dan pengambilan data)

---

## Ringkasan Aktor dan Fitur Utama
Sistem ini membagi hak akses menjadi 3 jenis aktor utama:
1. **Admin**: Mengelola data master kost (kamar, user), memproses transaksi (booking, sewa, tagihan), dan menindaklanjuti pengaduan serta ulasan.
2. **Penghuni (Resident)**: Pengguna aktif kost yang melakukan pembayaran tagihan, pengaduan kerusakan fasilitas, dan menulis ulasan kost.
3. **User (Guest / Calon Penyewa)**: Pengguna umum yang mendaftar akun, mencari kamar tersedia, mengajukan booking sewa, dan membayar uang muka (DP).

---

## 1. Activity Diagram - Admin Per-Fitur

### 1.1. Kelola Data Kamar (CRUD Kamar)
Diagram ini menjelaskan alur admin saat mengelola data kamar kost pada menu `backend/admin/kelola_kamar`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Admin"]
        Start1_1([Mulai]) --> BukaMenu1_1[Buka Halaman Kelola Kamar]
        BukaMenu1_1 --> PilihAksi1_1{Pilih Aksi CRUD}
        
        %% Tambah Kamar
        PilihAksi1_1 -- Tambah Kamar --> FormTambah1_1[Isi Form Kamar & Upload Foto/Denah]
        FormTambah1_1 --> SubmitTambah1_1[Klik Simpan Kamar]
        
        %% Edit Kamar
        PilihAksi1_1 -- Edit Kamar --> PilihKamar1_1[Pilih Kamar dari Daftar]
        PilihKamar1_1 --> FormEdit1_1[Ubah Data & Upload Foto Baru]
        FormEdit1_1 --> SubmitEdit1_1[Klik Update Kamar]
        
        %% Hapus Kamar
        PilihAksi1_1 -- Hapus Kamar --> PilihKamarHapus1_1[Pilih Kamar]
        PilihKamarHapus1_1 --> KonfirmasiHapus1_1{Konfirmasi Hapus?}
        KonfirmasiHapus1_1 -- Batal --> BukaMenu1_1
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        BukaMenu1_1 --> TampilkanPage1_1[Memuat Form dan Daftar Kamar]
        SubmitTambah1_1 --> ValidasiTambah1_1{Validasi Input & Foto?}
        ValidasiTambah1_1 -- Invalid --> FormTambah1_1
        
        SubmitEdit1_1 --> ValidasiEdit1_1{Validasi Input?}
        ValidasiEdit1_1 -- Invalid --> FormEdit1_1
        
        KonfirmasiHapus1_1 -- Ya --> CekRelasi1_1{Apakah Kamar Terisi / Dibooking?}
        CekRelasi1_1 -- Ya --> GagalHapus1_1[Tampilkan Error: Kamar sedang aktif dihuni]
        GagalHapus1_1 --> BukaMenu1_1
        
        ValidasiTambah1_1 -- Valid --> UploadFoto1_1[Simpan Foto ke /uploads/kamar/]
        UploadFoto1_1 --> PemicuDBTambah1_1[Kirim Query Insert Kamar]
        
        ValidasiEdit1_1 -- Valid --> UploadFotoEdit1_1[Simpan Foto Baru jika Ada]
        UploadFotoEdit1_1 --> PemicuDBEdit1_1[Kirim Query Update Kamar]
        
        CekRelasi1_1 -- Tidak --> PemicuDBHapus1_1[Kirim Query Delete Kamar]
        
        ResponDB1_1[Proses Respon Database] --> SuksesPesan1_1[Tampilkan Pesan Sukses]
        SuksesPesan1_1 --> BukaMenu1_1
    end
    
    subgraph Database ["Database MySQL"]
        TampilkanPage1_1 --> SelectKamar1_1[("SELECT * FROM kamar")]
        PemicuDBTambah1_1 --> DBInsert1_1[("INSERT INTO kamar (...)")]
        PemicuDBEdit1_1 --> DBUpdate1_1[("UPDATE kamar SET ... WHERE id")]
        PemicuDBHapus1_1 --> DBDelete1_1[("DELETE FROM kamar WHERE id")]
        
        DBInsert1_1 --> ResponDB1_1
        DBUpdate1_1 --> ResponDB1_1
        DBDelete1_1 --> ResponDB1_1
    end
```

---

### 1.2. Kelola Booking & Pembayaran DP
Alur pemrosesan pengajuan sewa kamar oleh calon penyewa (status `pending` -> `menunggu_dp` -> `disetujui`) yang diakses pada menu `backend/admin/kelola_booking`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Admin"]
        Start1_2([Mulai]) --> BukaBooking1_2[Buka Menu Kelola Booking]
        BukaBooking1_2 --> PilihBooking1_2[Pilih Pengajuan Booking status 'pending']
        PilihBooking1_2 --> AksiBooking1_2{Aksi Persetujuan}
        
        %% Tolak
        AksiBooking1_2 -- Tolak --> FormTolak1_2[Masukkan Alasan Penolakan]
        FormTolak1_2 --> SubmitTolak1_2[Klik Kirim Penolakan]
        
        %% Terima
        AksiBooking1_2 -- Terima --> SubmitTerima1_2[Klik Setujui Awal]
        
        %% Verifikasi DP
        BukaPembayaran1_2[Buka Menu Kelola Pembayaran] --> PilihBukti1_2[Pilih Bukti DP Calon Penyewa]
        PilihBukti1_2 --> CekBukti1_2[Periksa Validitas Foto Bukti & Nominal]
        CekBukti1_2 --> VerifikasiDP1_2{Bukti DP Valid?}
        VerifikasiDP1_2 -- Tidak --> KlikTolakDP1_2[Klik Tolak Pembayaran DP]
        VerifikasiDP1_2 -- Ya --> KlikTerimaDP1_2[Klik Setujui Pembayaran DP]
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        BukaBooking1_2 --> TampilkanBooking1_2[Tampilkan Daftar Pemesanan Kamar]
        SubmitTolak1_2 --> ProsesTolak1_2[Set Status Ditolak & Kirim Notif]
        SubmitTerima1_2 --> ProsesTerima1_2[Set Status Menunggu DP & Kirim Invoice]
        
        ProsesTerima1_2 -.-> CalonPenyewaBayar[Calon Penyewa Transfer & Upload Bukti DP]
        CalonPenyewaBayar -.-> BukaPembayaran1_2
        
        KlikTolakDP1_2 --> ProsesTolakDP1_2[Update Pembayaran & Kirim Notif Upload Ulang]
        ProsesTolakDP1_2 --> BukaPembayaran1_2
        
        KlikTerimaDP1_2 --> ProsesTerimaDP1_2[Buat Akun Penghuni Baru & Set Kamar Terisi]
        ProsesTerimaDP1_2 --> SuksesVerif1_2[Tampilkan Sukses & Selesai]
    end
    
    subgraph Database ["Database MySQL"]
        TampilkanBooking1_2 --> QueryBooking1_2[("SELECT * FROM booking WHERE status='pending'")]
        ProsesTolak1_2 --> DBUpdateTolak1_2[("UPDATE booking SET status='ditolak'<br/>UPDATE kamar SET status='tersedia'")]
        ProsesTerima1_2 --> DBUpdateTerima1_2[("UPDATE booking SET status='menunggu_dp'<br/>UPDATE kamar SET status='dibooking'")]
        
        ProsesTolakDP1_2 --> DBUpdateTolakDP1_2[("UPDATE pembayaran SET status='tidak_valid'<br/>UPDATE booking SET status='menunggu_dp'")]
        ProsesTerimaDP1_2 --> DBUpdateTerimaDP1_2[("UPDATE pembayaran SET status='valid'<br/>UPDATE booking SET status='disetujui'<br/>UPDATE kamar SET status='terisi'<br/>INSERT INTO users (role='penghuni')")]
        
        DBUpdateTolak1_2 --> BukaBooking1_2
        DBUpdateTerima1_2 --> BukaBooking1_2
        DBUpdateTolakDP1_2 --> BukaPembayaran1_2
        DBUpdateTerimaDP1_2 --> SuksesVerif1_2
    end
```

---

### 1.3. Kelola Pengaduan Kerusakan Fasilitas
Alur respon admin terhadap laporan kerusakan fasilitas yang dikirim oleh Penghuni di menu `backend/admin/kelola_pengaduan`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Admin"]
        Start1_3([Mulai]) --> BukaPengaduan1_3[Buka Menu Kelola Pengaduan]
        BukaPengaduan1_3 --> PilihAduan1_3[Pilih Laporan Baru dengan status 'masuk']
        PilihAduan1_3 --> KlikProses1_3[Klik Mulai Proses Perbaikan]
        
        KlikProses1_3 --> PetugasPerbaikan1_3[Petugas Memperbaiki Fasilitas]
        PetugasPerbaikan1_3 --> UploadProgres1_3[Upload Foto Progres Perbaikan & Klik Update]
        UploadProgres1_3 --> KlikSelesai1_3[Klik Selesai & Upload Foto Hasil Perbaikan]
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        BukaPengaduan1_3 --> TampilkanAduan1_3[Tampilkan Daftar Laporan Kerusakan]
        KlikProses1_3 --> SetProses1_3[Ubah Status Laporan menjadi 'diproses']
        UploadProgres1_3 --> SaveProgres1_3[Simpan Foto Progres ke /uploads/pengaduan/]
        KlikSelesai1_3 --> SaveSelesai1_3[Simpan Foto Selesai ke /uploads/pengaduan/ & Set status 'selesai']
        SaveSelesai1_3 --> KirimNotif1_3[Tampilkan Status Selesai di Halaman Penghuni]
    end
    
    subgraph Database ["Database MySQL"]
        TampilkanAduan1_3 --> QueryAduan1_3[("SELECT * FROM pengaduan")]
        SetProses1_3 --> DBUpdateProses1_3[("UPDATE pengaduan SET status='diproses'")]
        SaveProgres1_3 --> DBUpdateFotoProgres1_3[("UPDATE pengaduan SET foto_progres=path")]
        SaveSelesai1_3 --> DBUpdateSelesai1_3[("UPDATE pengaduan SET status='selesai', foto_selesai=path")]
        
        DBUpdateProses1_3 --> BukaPengaduan1_3
        DBUpdateSelesai1_3 --> KirimNotif1_3
    end
```

---

### 1.4. Kelola Tagihan Bulanan & Verifikasi Pembayaran
Alur pembuatan tagihan periodik sewa kost dan verifikasi pembayarannya di menu `backend/admin/kelola_tagihan`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Admin"]
        Start1_4([Mulai]) --> BukaTagihan1_4[Buka Menu Kelola Tagihan]
        BukaTagihan1_4 --> PilihPenghuni1_4[Pilih Penghuni Kost Aktif]
        PilihPenghuni1_4 --> InputTagihan1_4[Input Jumlah Tagihan Sewa & Jatuh Tempo]
        InputTagihan1_4 --> KlikBuat1_4[Klik Kirim Laporan Tagihan]
        
        %% Verifikasi
        BukaVerifikasi1_4[Buka Menu Kelola Pembayaran Tagihan] --> PilihBayar1_4[Pilih Bukti Pembayaran Bulanan]
        PilihBayar1_4 --> CekBukti1_4[Periksa Bukti Transfer Bank/E-Wallet]
        CekBukti1_4 --> Validitas1_4{Bukti Pembayaran Valid?}
        Validitas1_4 -- Tidak --> KlikTolak1_4[Klik Tolak Pembayaran]
        Validitas1_4 -- Ya --> KlikTerima1_4[Klik Setujui & Lunas]
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        BukaTagihan1_4 --> TampilkanPenghuni1_4[Tampilkan Daftar Penghuni Aktif]
        KlikBuat1_4 --> ProsesBuat1_4[Buat Tagihan Baru & Notifikasi ke Penghuni]
        
        ProsesBuat1_4 -.-> PenghuniBayar1_4[Penghuni Transfer & Upload Bukti Pembayaran]
        PenghuniBayar1_4 -.-> BukaVerifikasi1_4
        
        KlikTolak1_4 --> ProsesTolak1_4[Update Tagihan & Minta Upload Bukti Ulang]
        KlikTerima1_4 --> ProsesTerima1_4[Set Tagihan Lunas & Perpanjang Tanggal Selesai Sewa]
        ProsesTerima1_4 --> TampilkanSukses1_4[Tampilkan Pesan Sukses Verifikasi]
    end
    
    subgraph Database ["Database MySQL"]
        TampilkanPenghuni1_4 --> QueryPenghuni1_4[("SELECT * FROM users JOIN kamar WHERE role='penghuni'")]
        ProsesBuat1_4 --> DBInsertTagihan1_4[("INSERT INTO tagihan (status='belum_bayar')")]
        
        ProsesTolak1_4 --> DBUpdateTolak1_4[("UPDATE pembayaran SET status='tidak_valid'<br/>UPDATE tagihan SET status='belum_bayar'")]
        ProsesTerima1_4 --> DBUpdateLunas1_4[("UPDATE pembayaran SET status='valid'<br/>UPDATE tagihan SET status='lunas'<br/>UPDATE penghuni SET tanggal_selesai=perpanjang")]
        
        DBInsertTagihan1_4 --> BukaTagihan1_4
        DBUpdateTolak1_4 --> BukaVerifikasi1_4
        DBUpdateLunas1_4 --> TampilkanSukses1_4
    end
```

---

## 2. Activity Diagram - Penghuni Per-Fitur

### 2.1. Pembayaran Tagihan Bulanan (Upload Bukti Bayar)
Alur bagi Penghuni untuk melihat dan melunasi tagihan sewa kost bulanan melalui menu `backend/penghuni/pembayaran.php`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Penghuni"]
        Start2_1([Mulai]) --> LoginPenghuni2_1[Login Akun Penghuni]
        LoginPenghuni2_1 --> BukaMenuBayar2_1[Buka Menu Pembayaran Sewa]
        BukaMenuBayar2_1 --> PilihTagihan2_1[Pilih Tagihan status 'belum_bayar']
        PilihTagihan2_1 --> TransferDana2_1[Transfer Dana ke Nomor Rekening Kost]
        TransferDana2_1 --> FormUpload2_1[Isi Form Tanggal & Upload Foto Bukti]
        FormUpload2_1 --> KlikKirim2_1[Klik Kirim Bukti Pembayaran]
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        LoginPenghuni2_1 --> CekSession2_1{Apakah Session Valid?}
        CekSession2_1 -- Tidak --> LoginPenghuni2_1
        CekSession2_1 -- Ya --> TampilkanDashboard2_1[Tampilkan Halaman Dashboard]
        
        BukaMenuBayar2_1 --> TampilkanTagihan2_1[Tampilkan Tagihan & Info Rekening]
        KlikKirim2_1 --> ValidasiForm2_1{File & Input Lengkap?}
        ValidasiForm2_1 -- Tidak --> FormUpload2_1
        ValidasiForm2_1 -- Ya --> UploadBukti2_1[Simpan Foto Bukti ke /uploads/pembayaran/]
        UploadBukti2_1 --> ProsesSimpan2_1[Set Status Pembayaran: 'menunggu_verifikasi']
        ProsesSimpan2_1 --> SuksesKirim2_1[Tampilkan Notif Menunggu Verifikasi Admin]
    end
    
    subgraph Database ["Database MySQL"]
        TampilkanTagihan2_1 --> QueryTagihan2_1[("SELECT * FROM tagihan WHERE user_id AND status='belum_bayar'")]
        ProsesSimpan2_1 --> DBInsertPembayaran2_1[("INSERT INTO pembayaran (...)<br/>UPDATE tagihan SET status='diproses'")]
        
        DBInsertPembayaran2_1 --> SuksesKirim2_1
    end
```

---

### 2.2. Pengajuan Pengaduan Kerusakan
Alur bagi Penghuni untuk melaporkan kerusakan fasilitas di dalam kamar atau area kost melalui menu `backend/penghuni/buat_pengaduan.php`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Penghuni"]
        Start2_2([Mulai]) --> BukaAduan2_2[Buka Menu Pengaduan]
        BukaAduan2_2 --> KlikBuat2_2[Klik Buat Pengaduan Baru]
        KlikBuat2_2 --> IsiForm2_2[Isi Deskripsi, Prioritas, & Upload Foto Fasilitas Rusak]
        IsiForm2_2 --> KlikKirim2_2[Klik Kirim Laporan]
        KlikKirim2_2 --> PantauRiwayat2_2[Pantau Riwayat & Progres Perbaikan]
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        BukaAduan2_2 --> TampilkanDaftar2_2[Tampilkan Daftar Laporan Sebelumnya]
        KlikBuat2_2 --> TampilkanForm2_2[Tampilkan Form Input Pengaduan]
        KlikKirim2_2 --> ValidasiInput2_2{Input & Foto Lengkap?}
        ValidasiInput2_2 -- Tidak --> IsiForm2_2
        ValidasiInput2_2 -- Ya --> UploadFoto2_2[Simpan Foto Kerusakan ke /uploads/pengaduan/]
        UploadFoto2_2 --> SimpanAduan2_2[Set Status Pengaduan Baru: 'masuk']
        SimpanAduan2_2 --> SuksesKirim2_2[Tampilkan Pesan Berhasil & Refresh Daftar]
        SuksesKirim2_2 --> PantauRiwayat2_2
    end
    
    subgraph Database ["Database MySQL"]
        TampilkanDaftar2_2 --> QueryRiwayat2_2[("SELECT * FROM pengaduan WHERE user_id")]
        SimpanAduan2_2 --> DBInsertAduan2_2[("INSERT INTO pengaduan (status='masuk', ...)")]
        
        DBInsertAduan2_2 --> SuksesKirim2_2
    end
```

---

### 2.3. Pemberian Ulasan & Testimoni Kost
Alur bagi Penghuni yang aktif untuk memberikan rating dan ulasan/testimoni terhadap pelayanan kost di menu `backend/penghuni/ulasan.php`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Penghuni"]
        Start2_3([Mulai]) --> BukaUlasan2_3[Buka Menu Ulasan & Testimoni]
        BukaUlasan2_3 --> CekUlasanLama2_3{Sudah Pernah Mengulas?}
        
        %% Belum
        CekUlasanLama2_3 -- Belum --> FormBaru2_3[Isi Rating Bintang 1-5, Komentar, & Foto]
        FormBaru2_3 --> KlikKirim2_3[Klik Kirim Ulasan]
        
        %% Sudah
        CekUlasanLama2_3 -- Sudah --> EditUlasan2_3[Klik Edit Ulasan]
        EditUlasan2_3 --> FormEdit2_3[Ubah Rating & Teks Ulasan]
        FormEdit2_3 --> KlikSimpan2_3[Klik Update Ulasan]
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        BukaUlasan2_3 --> CekDBUlasan2_3[Kirim Request Pemeriksaan Ulasan]
        CekDBUlasan2_3 --> CekUlasanLama2_3
        
        KlikKirim2_3 --> ValidasiBaru2_3{Input Valid?}
        ValidasiBaru2_3 -- Tidak --> FormBaru2_3
        ValidasiBaru2_3 -- Ya --> SimpanUlasan2_3[Simpan Ulasan Baru dengan status tampilkan=0]
        
        KlikSimpan2_3 --> ValidasiEdit2_3{Input Valid?}
        ValidasiEdit2_3 -- Tidak --> FormEdit2_3
        ValidasiEdit2_3 -- Ya --> UpdateUlasan2_3[Update Ulasan & Set status tampilkan=0]
        
        SimpanUlasan2_3 --> SuksesUlas2_3[Tampilkan Notifikasi Sukses & Menunggu Moderasi Admin]
        UpdateUlasan2_3 --> SuksesUlas2_3
    end
    
    subgraph Database ["Database MySQL"]
        CekDBUlasan2_3 -.-> QueryCekUlasan2_3[("SELECT * FROM ulasan WHERE user_id")]
        SimpanUlasan2_3 --> DBInsertUlasan2_3[("INSERT INTO ulasan (tampilkan=0, ...)")]
        UpdateUlasan2_3 --> DBUpdateUlasan2_3[("UPDATE ulasan SET tampilkan=0, ... WHERE user_id")]
        
        DBInsertUlasan2_3 --> SuksesUlas2_3
        DBUpdateUlasan2_3 --> SuksesUlas2_3
    end
```

---

## 3. Activity Diagram - User (Guest / Calon Penyewa) Per-Fitur

### 3.1. Pendaftaran Akun & Autentikasi (Registrasi & Login)
Alur bagi pengunjung umum (Guest) untuk membuat akun di website agar dapat melakukan pemesanan kamar kost di `frontend/pages/guest/profil.php`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Calon Penyewa"]
        Start3_1([Mulai]) --> AksesWeb3_1[Akses Landing Page EL-MISARAH]
        AksesWeb3_1 --> KlikAuth3_1[Klik Registrasi / Login]
        KlikAuth3_1 --> PilihOpsi3_1{Pilih Opsi}
        
        %% Registrasi
        PilihOpsi3_1 -- Daftar Baru --> FormDaftar3_1[Isi Nama, Email, No HP, & Password]
        FormDaftar3_1 --> KlikDaftar3_1[Klik Daftar Sekarang]
        
        %% Login
        PilihOpsi3_1 -- Masuk Akun --> FormLogin3_1[Masukkan Email & Password]
        FormLogin3_1 --> KlikMasuk3_1[Klik Masuk]
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        KlikAuth3_1 --> TampilkanModal3_1[Tampilkan Modal / Halaman Autentikasi]
        KlikDaftar3_1 --> CekDuplikasi3_1[Validasi Input & Ketersediaan Email]
        CekDuplikasi3_1 -- Terdaftar --> NotifGanda3_1[Tampilkan Error: Email Sudah Digunakan]
        NotifGanda3_1 --> FormDaftar3_1
        
        CekDuplikasi3_1 -- Tersedia --> HashPassword3_1[Enkripsi Password dengan password_hash]
        HashPassword3_1 --> BuatAkun3_1[Simpan Akun Baru Ke Database]
        BuatAkun3_1 --> SuksesDaftar3_1[Tampilkan Pesan Sukses Registrasi]
        SuksesDaftar3_1 --> FormLogin3_1
        
        KlikMasuk3_1 --> VerifikasiKredensial3_1[Autentikasi Email & Verifikasi Password Hash]
        VerifikasiKredensial3_1 -- Salah / Nonaktif --> NotifGagal3_1[Tampilkan Error: Kredensial Salah]
        NotifGagal3_1 --> FormLogin3_1
        
        VerifikasiKredensial3_1 -- Cocok --> SetSession3_1[Inisialisasi PHP Session: user_id, role]
        SetSession3_1 --> RedirectRole3_1[Redirect Pengguna Sesuai Role]
        RedirectRole3_1 --> End3_1([Selesai])
    end
    
    subgraph Database ["Database MySQL"]
        CekDuplikasi3_1 -.-> DBQueryEmail3_1[("SELECT email FROM users WHERE email=?")]
        BuatAkun3_1 --> DBInsertUser3_1[("INSERT INTO users (role='user', status='aktif', ...)")]
        VerifikasiKredensial3_1 -.-> DBQueryAuth3_1[("SELECT * FROM users WHERE email=?")]
        
        DBInsertUser3_1 --> SuksesDaftar3_1
    end
```

---

### 3.2. Eksplorasi Kamar & Pengajuan Booking
Alur bagi Calon Penyewa (User) untuk mengecek ketersediaan kamar dan melakukan booking melalui menu `frontend/pages/guest/rooms.php`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Calon Penyewa"]
        Start3_2([Mulai]) --> BukaKamar3_2[Buka Halaman Cari Kamar]
        BukaKamar3_2 --> FilterKamar3_2[Eksplorasi Spesifikasi & Status Kamar]
        FilterKamar3_2 --> PilihKamar3_2[Pilih Kamar status 'tersedia']
        PilihKamar3_2 --> FormBooking3_2[Isi Tanggal Masuk, Durasi, & Catatan]
        FormBooking3_2 --> KlikKonfirmasi3_2[Klik Konfirmasi Pemesanan]
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        BukaKamar3_2 --> TampilkanKamar3_2[Tampilkan Daftar Kamar Tersedia]
        PilihKamar3_2 --> TampilkanForm3_2[Tampilkan Form Booking]
        KlikKonfirmasi3_2 --> CekStatusKamar3_2[Periksa Ulang Status Kamar Terkini]
        CekStatusKamar3_2 --> CekTersedia3_2{Kamar Masih Tersedia?}
        CekTersedia3_2 -- Tidak --> NotifPenuh3_2[Tampilkan Error: Kamar Baru Saja Dibooking Orang Lain] --> BukaKamar3_2
        
        CekTersedia3_2 -- Ya --> SimpanBooking3_2[Simpan Booking dengan status 'pending']
        SimpanBooking3_2 --> UbahStatusKamar3_2[Set Status Kamar: 'dibooking']
        UbahStatusKamar3_2 --> RedirectRiwayat3_2[Arahkan ke Halaman Riwayat Pemesanan]
        RedirectRiwayat3_2 --> End3_2([Selesai])
    end
    
    subgraph Database ["Database MySQL"]
        TampilkanKamar3_2 --> QueryKamarTersedia3_2[("SELECT * FROM kamar WHERE status='tersedia'")]
        CekStatusKamar3_2 -.-> QueryKamarDB3_2[("SELECT status FROM kamar WHERE id=?")]
        SimpanBooking3_2 --> DBInsertBooking3_2[("INSERT INTO booking (status='pending', ...)")]
        UbahStatusKamar3_2 --> DBUpdateKamar3_2[("UPDATE kamar SET status='dibooking' WHERE id=?")]
        
        DBInsertBooking3_2 --> UbahStatusKamar3_2
        DBUpdateKamar3_2 --> RedirectRiwayat3_2
    end
```

---

### 3.3. Pembayaran DP Booking (Konfirmasi Pesanan)
Alur bagi Calon Penyewa untuk mengunggah bukti pembayaran DP setelah pengajuan booking disetujui oleh admin (status booking berubah menjadi `menunggu_dp`), melalui menu `frontend/pages/guest/pembayaran_booking.php`.

```mermaid
flowchart TB
    subgraph Aktor ["Aktor: Calon Penyewa"]
        Start3_3([Mulai]) --> BukaRiwayat3_3[Buka Menu Riwayat Booking]
        BukaRiwayat3_3 --> CekStatus3_3{Cek Status Booking}
        
        %% Pending
        CekStatus3_3 -- pending --> TungguPersetujuan3_3[Menunggu Verifikasi Awal Admin] --> BukaRiwayat3_3
        
        %% Menunggu DP
        CekStatus3_3 -- menunggu_dp --> KlikBayar3_3[Klik Tombol Pembayaran DP]
        KlikBayar3_3 --> TransferDP3_3[Transfer Dana Sesuai Nominal & Rekening Kost]
        TransferDP3_3 --> FormUploadDP3_3[Isi Form & Upload Bukti Pembayaran DP]
        FormUploadDP3_3 --> KlikKirim3_3[Klik Kirim Bukti DP]
    end
    
    subgraph Sistem ["Sistem (Aplikasi)"]
        BukaRiwayat3_3 --> TampilkanRiwayat3_3[Tampilkan Riwayat Pemesanan Kamar]
        KlikBayar3_3 --> TampilkanHalamanBayar3_3[Tampilkan Jumlah DP & Info Rekening]
        KlikKirim3_3 --> ValidasiForm3_3{Input & Foto Bukti Lengkap?}
        ValidasiForm3_3 -- Tidak --> FormUploadDP3_3
        ValidasiForm3_3 -- Ya --> UploadFoto3_3[Simpan Foto Bukti ke /uploads/pembayaran/]
        UploadFoto3_3 --> SimpanPembayaran3_3[Set Status Pembayaran DP: 'menunggu_verifikasi']
        SimpanPembayaran3_3 --> TampilkanSukses3_3[Tampilkan Halaman Sukses & Menunggu Verifikasi]
        TampilkanSukses3_3 --> End3_3([Selesai])
    end
    
    subgraph Database ["Database MySQL"]
        TampilkanRiwayat3_3 --> QueryBooking3_3[("SELECT * FROM booking WHERE user_id")]
        SimpanPembayaran3_3 --> DBInsertPembayaran3_3[("INSERT INTO pembayaran (status='menunggu_verifikasi', ...)<br/>UPDATE booking SET status='menunggu_verifikasi'")]
        
        DBInsertPembayaran3_3 --> TampilkanSukses3_3
    end
```
