<?php
require_once 'config.php';
requireRoles(['admin','operator']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Import Data';
require 'includes/header.php';
?>

<div class="card" style="max-width:650px;">
  <h2 class="mt-0">Import Data Excel</h2>
  <p class="text-muted">
    Upload file <strong>.xlsx</strong> atau <strong>.xls</strong> yang berisi dua sheet:
    <strong>RESPONSES</strong> (peserta yang sudah mengisi form penyelesaian) dan
    <strong>PENDAFTAR</strong> (seluruh data peserta).
  </p>

  <form action="preview_monitoring.php" method="POST" enctype="multipart/form-data">
    <div class="form-group">
      <label>Pilih File Excel</label>
      <div class="dropzone">
        <input type="file" name="file_excel" accept=".xlsx,.xls" required>
        <p style="margin:10px 0 0;font-size:13px;">Format didukung: .xlsx, .xls</p>
      </div>
    </div>
    <button type="submit" class="btn">Lanjut ke Preview &rarr;</button>
  </form>
</div>

<div class="card" style="max-width:650px;">
  <h3 class="mt-0">Ketentuan Sheet</h3>
  <table>
    <tr><th>Sheet RESPONSES</th><td>Submit Form, Nama Peserta, Email Peserta, Asal Instansi/OPD, Tema Micro Skill, Tanggal Penyelesaian, Keterangan, Sertifikat</td></tr>
    <tr><th>Sheet PENDAFTAR</th><td>ID_BATCH, Nama Lengkap, NIP, Jenis Kelamin, Usia, Jenjang Pendidikan, Provinsi, Kota/Kabupaten, Kecamatan, Kelurahan, Pekerjaan, Instansi Pemerintahan, UPT, Jabatan, Pangkat Golongan, Email User</td></tr>
  </table>
</div>

<?php require 'includes/footer.php'; ?>
