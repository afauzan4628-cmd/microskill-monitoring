# Sistem Monitoring Penyelesaian Microskill Peserta Digital Talent

Sistem berbasis web untuk mentor memantau peserta Digital Talent yang sudah/belum
menyelesaikan Microskill — menggantikan cara manual pakai Excel.

## Teknologi

- PHP native (tanpa framework) + PDO MySQL
- MySQL / MariaDB
- XAMPP (Apache + MySQL)
- [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) — baca file Excel
- [Dompdf](https://github.com/dompdf/dompdf) — generate laporan PDF
- HTML + CSS murni (tanpa JS framework)

## Struktur Folder

```
microskill-monitoring/
├── config/
│   └── database.php          # koneksi PDO
├── database/
│   └── schema.sql             # struktur tabel MySQL
├── includes/
│   ├── header.php             # layout + sidebar
│   ├── footer.php
│   └── functions.php          # helper (pagination, format tanggal, dll)
├── assets/css/style.css
├── uploads/                   # file excel yang diupload
├── exports/                   # (opsional) cache pdf
├── index.php                  # redirect ke dashboard
├── dashboard.php
├── import_monitoring.php      # step 1: upload file
├── preview_monitoring.php     # step 2: validasi sheet & preview
├── proses_import_monitoring.php # step 3: insert ke database
├── monitoring.php             # LEFT JOIN + status Sudah/Belum
├── responses.php
├── pendaftar.php
├── laporan.php
├── export_pdf.php
└── composer.json
```

## Cara Install (XAMPP)

1. **Copy folder project**
   Taruh folder `microskill-monitoring` di `htdocs` XAMPP kamu, misal:
   `C:/xampp/htdocs/microskill-monitoring`

2. **Buat database**
   Buka phpMyAdmin, lalu import file `database/schema.sql`.
   Ini akan otomatis membuat database `db_microskill` beserta 3 tabel:
   `tb_pendaftar`, `tb_responses`, `tb_import_log`.

3. **Sesuaikan koneksi DB**
   Edit `config/database.php` kalau MySQL kamu pakai user/password custom
   (default XAMPP: user `root`, password kosong — biasanya tidak perlu diubah).

4. **Install dependency lewat Composer**
   Buka terminal di folder project, lalu jalankan:
   ```
   composer install
   ```
   Ini akan mengunduh PhpSpreadsheet dan Dompdf ke folder `vendor/`.

   > Kalau belum punya Composer, install dulu dari https://getcomposer.org

5. **Jalankan Apache & MySQL** dari XAMPP Control Panel.

6. **Buka di browser**
   ```
   http://localhost/microskill-monitoring/
   ```
   Otomatis diarahkan ke `dashboard.php`.

## Cara Pakai

1. Siapkan file Excel (`.xlsx`/`.xls`) dengan **dua sheet**:
   - **RESPONSES** — kolom: Submit Form, Nama Peserta, Email Peserta, Asal Instansi/OPD,
     Tema Micro Skill, Tanggal Penyelesaian, Keterangan, Sertifikat
   - **PENDAFTAR** — kolom: ID_BATCH, Nama Lengkap, NIP, Jenis Kelamin, Usia,
     Jenjang Pendidikan, Provinsi, Kota/Kabupaten, Kecamatan, Kelurahan, Pekerjaan,
     Instansi Pemerintahan, UPT, Jabatan, Pangkat Golongan, Email User

   > Import membaca kolom berdasarkan **nama header di baris 1**, jadi urutan kolom
   > boleh berbeda dari daftar di atas -- yang penting nama headernya ada dan sesuai
   > (tidak case-sensitive, dan boleh ada/tidak ada spasi di sekitar tanda `/`).
   > Kalau ada kolom wajib yang hilang atau namanya beda jauh, sistem akan menolak
   > import dan menampilkan pesan jelas kolom mana yang tidak ditemukan -- sebelum
   > data sempat masuk ke database.
   >
   > Kalau file yang diupload berisi peserta yang emailnya **sudah pernah** ada di
   > database (misal file revisi/duplikat dari mentor), data lama akan **diperbarui**
   > (bukan ditambah baru), jadi tidak akan dobel di halaman Monitoring/Pendaftar.
   > Baris tanpa email tetap disimpan sebagai baris baru karena tidak ada kunci untuk
   > mencocokkannya ke data lama.

2. Buka menu **Import Data** → upload file → sistem akan menampilkan **Preview**
   (jumlah baris tiap sheet, cuplikan datanya).

3. Klik **Import ke Database** → data masuk ke `tb_pendaftar` dan `tb_responses`.

4. Buka menu **Monitoring** untuk melihat status tiap peserta:
   - Sistem mencocokkan `Email User` (pendaftar) dengan `Email Peserta` (responses).
   - Email cocok → status **Sudah**. Tidak ditemukan → status **Belum**.
   - Tersedia search, filter status, filter instansi, dan pagination.

5. Menu **Laporan** menampilkan ringkasan (total, sudah, belum, progress,
   top instansi, top tema, daftar belum selesai) — bisa langsung di-**Export PDF**.

## Catatan Pengembangan Selanjutnya

Beberapa hal yang belum ada di versi ini dan bisa ditambahkan nanti:

- Login & hak akses (Admin / Mentor / Pimpinan)
- Grafik statistik interaktif di dashboard
- Filter berdasarkan rentang tanggal penyelesaian
- Export ke Excel (selain PDF)
- Notifikasi otomatis untuk peserta yang belum selesai
- Riwayat import (multi-batch) yang bisa dilihat & dibandingkan
- Fitur edit/hapus data langsung dari sistem

> **Catatan untuk database yang sudah pernah dibuat sebelum update ini:**
> jalankan `database/migration_v2_dedup.sql` satu kali lewat phpMyAdmin untuk
> menambahkan UNIQUE KEY yang dipakai fitur upsert (deteksi duplikat email).
> Instalasi baru cukup pakai `schema.sql` seperti biasa, tidak perlu migrasi.
