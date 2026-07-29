<?php
require 'config/database.php';
require 'includes/functions.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$page_title = 'Preview Import';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'File tidak ditemukan atau gagal diupload.';
    header('Location: import_monitoring.php');
    exit;
}

$file      = $_FILES['file_excel'];
$ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExt = ['xlsx', 'xls'];

if (!in_array($ext, $allowedExt)) {
    $_SESSION['flash_error'] = 'Format file tidak didukung. Hanya menerima .xlsx atau .xls.';
    header('Location: import_monitoring.php');
    exit;
}

// Simpan file ke folder uploads dengan nama unik
$newName  = 'import_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;
$destPath = __DIR__ . '/uploads/' . $newName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    $_SESSION['flash_error'] = 'Gagal menyimpan file.';
    header('Location: import_monitoring.php');
    exit;
}

// Baca file excel
try {
    $spreadsheet = IOFactory::load($destPath);
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'File Excel tidak dapat dibaca: ' . $e->getMessage();
    unlink($destPath);
    header('Location: import_monitoring.php');
    exit;
}

$sheetNames = $spreadsheet->getSheetNames();

$hasResponses = in_array('RESPONSES', $sheetNames);
$hasPendaftar = in_array('PENDAFTAR', $sheetNames);

if (!$hasResponses || !$hasPendaftar) {
    $missing = [];
    if (!$hasResponses) $missing[] = 'RESPONSES';
    if (!$hasPendaftar) $missing[] = 'PENDAFTAR';

    $_SESSION['flash_error'] = 'Sheet wajib tidak ditemukan: ' . implode(', ', $missing) .
        '. Sheet yang tersedia di file: ' . implode(', ', $sheetNames);
    unlink($destPath);
    header('Location: import_monitoring.php');
    exit;
}

// Validasi kolom wajib berdasarkan NAMA HEADER (bukan posisi kolom).
// Kalau ada kolom yang hilang/berganti nama, gagalnya di sini -- sebelum
// data sempat masuk ke database.
try {
    $pendaftarColMap = mapHeaderColumns($spreadsheet->getSheetByName('PENDAFTAR'), pendaftarExpectedColumns(), 'PENDAFTAR');
    $responsesColMap = mapHeaderColumns($spreadsheet->getSheetByName('RESPONSES'), responsesExpectedColumns(), 'RESPONSES');
} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    unlink($destPath);
    header('Location: import_monitoring.php');
    exit;
}

// Ambil data mentah tiap sheet untuk ditampilkan di preview, dibaca lewat
// peta kolom (header) supaya urutan kolom di file mentor tidak berpengaruh.
function readSheetRowsByHeader($spreadsheet, $sheetName, array $colMap) {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $highestRow = $sheet->getHighestRow();
    $rows = [];
    for ($r = 2; $r <= $highestRow; $r++) {
        $rowData = [];
        $isEmpty = true;
        foreach ($colMap as $fieldKey => $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $val = $sheet->getCell($colLetter . $r)->getValue();
            if (trim((string)$val) !== '') $isEmpty = false;
            $rowData[$fieldKey] = $val;
        }
        if (!$isEmpty) $rows[] = $rowData;
    }
    return $rows;
}

$responsesRows  = readSheetRowsByHeader($spreadsheet, 'RESPONSES', $responsesColMap);
$pendaftarRows  = readSheetRowsByHeader($spreadsheet, 'PENDAFTAR', $pendaftarColMap);

// simpan path file & nama asli di session buat dipakai proses_import
$_SESSION['import_file_path'] = $destPath;
$_SESSION['import_file_name'] = $file['name'];

require 'includes/header.php';
?>

<div class="card">
  <h2 class="mt-0">Preview Data Sebelum Import</h2>
  <p class="text-muted">File: <strong><?= e($file['name']) ?></strong></p>

  <div class="stat-grid">
    <div class="stat-card blue">
      <div class="label">Jumlah baris - Sheet PENDAFTAR</div>
      <div class="value"><?= count($pendaftarRows) ?></div>
    </div>
    <div class="stat-card blue">
      <div class="label">Jumlah baris - Sheet RESPONSES</div>
      <div class="value"><?= count($responsesRows) ?></div>
    </div>
  </div>

  <form action="proses_import_monitoring.php" method="POST">
    <button type="submit" class="btn">✅ Import ke Database</button>
    <a href="import_monitoring.php" class="btn btn-outline">Batal, Upload Ulang</a>
  </form>
</div>

<div class="card">
  <h3 class="mt-0">Preview Sheet PENDAFTAR <span class="text-muted">(10 baris pertama)</span></h3>
  <div class="table-wrap">
    <table>
      <tr>
        <th>ID_BATCH</th><th>Nama Lengkap</th><th>NIP</th><th>Jenis Kelamin</th><th>Instansi</th><th>Email User</th>
      </tr>
      <?php foreach (array_slice($pendaftarRows, 0, 10) as $row): ?>
        <tr>
          <td><?= e($row['id_batch'] ?? '') ?></td>
          <td><?= e($row['nama_lengkap'] ?? '') ?></td>
          <td><?= e($row['nip'] ?? '') ?></td>
          <td><?= e($row['jenis_kelamin'] ?? '') ?></td>
          <td><?= e($row['instansi_pemerintahan'] ?? '') ?></td>
          <td><?= e($row['email_user'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<div class="card">
  <h3 class="mt-0">Preview Sheet RESPONSES <span class="text-muted">(10 baris pertama)</span></h3>
  <div class="table-wrap">
    <table>
      <tr>
        <th>Submit Form</th><th>Nama Peserta</th><th>Email Peserta</th><th>Tema Micro Skill</th><th>Tanggal Penyelesaian</th>
      </tr>
      <?php foreach (array_slice($responsesRows, 0, 10) as $row): ?>
        <tr>
          <td><?= e($row['submit_form'] ?? '') ?></td>
          <td><?= e($row['nama_peserta'] ?? '') ?></td>
          <td><?= e($row['email_peserta'] ?? '') ?></td>
          <td><?= e($row['tema_microskill'] ?? '') ?></td>
          <td><?= e($row['tanggal_penyelesaian'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
