<?php
require 'config/database.php';
require 'includes/functions.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['import_file_path']) || !file_exists($_SESSION['import_file_path'])) {
    $_SESSION['flash_error'] = 'Sesi import kadaluarsa, silakan upload ulang file.';
    header('Location: import_monitoring.php');
    exit;
}

$filePath  = $_SESSION['import_file_path'];
$fileName  = $_SESSION['import_file_name'] ?? basename($filePath);
$batchId   = generateBatchId();

try {
    $spreadsheet = IOFactory::load($filePath);
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'File Excel tidak dapat dibaca ulang: ' . $e->getMessage();
    header('Location: import_monitoring.php');
    exit;
}

/**
 * Ambil nilai cell, otomatis convert ke tanggal (Y-m-d) kalau cellnya bertipe tanggal.
 */
function cellValue(Cell $cell, $asDate = false) {
    $value = $cell->getValue();
    if ($value === null) return null;

    if ($asDate) {
        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($value);
                return $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                return trim((string)$value);
            }
        }
        // sudah berupa teks tanggal
        $ts = strtotime((string)$value);
        return $ts ? date('Y-m-d H:i:s', $ts) : trim((string)$value);
    }

    return is_string($value) ? trim($value) : trim((string)$value);
}

/**
 * Baca semua baris data (mulai baris 2) dari sebuah sheet, menggunakan
 * peta kolom hasil mapHeaderColumns() -- BUKAN posisi kolom tetap.
 * Return array of ['fieldKey' => Cell, ...] per baris (baris kosong dilewati).
 */
function readRowsByHeader($sheet, array $colMap) {
    $rows       = [];
    $highestRow = $sheet->getHighestRow();
    for ($r = 2; $r <= $highestRow; $r++) { // baris 1 = header
        $rowData = [];
        $isEmpty = true;
        foreach ($colMap as $fieldKey => $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $cell = $sheet->getCell($colLetter . $r);
            $val  = $cell->getValue();
            if (trim((string)$val) !== '') $isEmpty = false;
            $rowData[$fieldKey] = $cell;
        }
        if (!$isEmpty) $rows[] = $rowData;
    }
    return $rows;
}

$sheetPendaftar = $spreadsheet->getSheetByName('PENDAFTAR');
$sheetResponses = $spreadsheet->getSheetByName('RESPONSES');

// --- Validasi & pemetaan header (bukan asumsi posisi kolom tetap) ---
try {
    $pendaftarColMap = mapHeaderColumns($sheetPendaftar, pendaftarExpectedColumns(), 'PENDAFTAR');
    $responsesColMap = mapHeaderColumns($sheetResponses, responsesExpectedColumns(), 'RESPONSES');
} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: import_monitoring.php');
    exit;
}

$pendaftarRows = readRowsByHeader($sheetPendaftar, $pendaftarColMap);
$responsesRows = readRowsByHeader($sheetResponses, $responsesColMap);

$pdo->beginTransaction();
try {
    // ---------- Upsert tb_pendaftar (berdasarkan email_user) ----------
    // Kalau email_user sudah pernah ada di database, data lama di-UPDATE
    // (bukan insert baru) supaya file revisi/duplikat tidak bikin data dobel.
    // Baris tanpa email (kosong) selalu di-insert sebagai baris baru karena
    // tidak ada kunci unik untuk mencocokkannya.
    $stmtP = $pdo->prepare("
        INSERT INTO tb_pendaftar
        (id_batch, nama_lengkap, nip, jenis_kelamin, usia, jenjang_pendidikan, provinsi,
         kota_kabupaten, kecamatan, kelurahan, pekerjaan, instansi_pemerintahan, upt,
         jabatan, pangkat_golongan, email_user, batch_import)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            id_batch = VALUES(id_batch),
            nama_lengkap = VALUES(nama_lengkap),
            nip = VALUES(nip),
            jenis_kelamin = VALUES(jenis_kelamin),
            usia = VALUES(usia),
            jenjang_pendidikan = VALUES(jenjang_pendidikan),
            provinsi = VALUES(provinsi),
            kota_kabupaten = VALUES(kota_kabupaten),
            kecamatan = VALUES(kecamatan),
            kelurahan = VALUES(kelurahan),
            pekerjaan = VALUES(pekerjaan),
            instansi_pemerintahan = VALUES(instansi_pemerintahan),
            upt = VALUES(upt),
            jabatan = VALUES(jabatan),
            pangkat_golongan = VALUES(pangkat_golongan),
            batch_import = VALUES(batch_import)
    ");

    $countPendaftar = 0;
    foreach ($pendaftarRows as $row) {
        $stmtP->execute([
            cellValue($row['id_batch']),
            cellValue($row['nama_lengkap']),
            cellValue($row['nip']),
            cellValue($row['jenis_kelamin']),
            cellValue($row['usia']),
            cellValue($row['jenjang_pendidikan']),
            cellValue($row['provinsi']),
            cellValue($row['kota_kabupaten']),
            cellValue($row['kecamatan']),
            cellValue($row['kelurahan']),
            cellValue($row['pekerjaan']),
            cellValue($row['instansi_pemerintahan']),
            cellValue($row['upt']),
            cellValue($row['jabatan']),
            cellValue($row['pangkat_golongan']),
            emptyToNull(cellValue($row['email_user'])),
            $batchId,
        ]);
        $countPendaftar++;
    }

    // ---------- Upsert tb_responses (berdasarkan email_peserta + tema) ----------
    // Satu peserta bisa punya beberapa baris response (tema microskill berbeda),
    // jadi kunci pencocokan duplikat adalah kombinasi email + tema, bukan email saja.
    $stmtR = $pdo->prepare("
        INSERT INTO tb_responses
        (submit_form, nama_peserta, email_peserta, asal_instansi, tema_microskill,
         tanggal_penyelesaian, keterangan, sertifikat, batch_import)
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            submit_form = VALUES(submit_form),
            nama_peserta = VALUES(nama_peserta),
            asal_instansi = VALUES(asal_instansi),
            tanggal_penyelesaian = VALUES(tanggal_penyelesaian),
            keterangan = VALUES(keterangan),
            sertifikat = VALUES(sertifikat),
            batch_import = VALUES(batch_import)
    ");

    $countResponses = 0;
    foreach ($responsesRows as $row) {
        $tglSelesaiRaw = cellValue($row['tanggal_penyelesaian'], true);
        $tglSelesai    = $tglSelesaiRaw ? substr($tglSelesaiRaw, 0, 10) : null;

        $stmtR->execute([
            cellValue($row['submit_form'], true),
            cellValue($row['nama_peserta']),
            emptyToNull(cellValue($row['email_peserta'])),
            cellValue($row['asal_instansi']),
            cellValue($row['tema_microskill']),
            $tglSelesai,
            cellValue($row['keterangan']),
            cellValue($row['sertifikat']),
            $batchId,
        ]);
        $countResponses++;
    }

    // ---------- Log import ----------
    $stmtLog = $pdo->prepare("
        INSERT INTO tb_import_log (batch_import, nama_file, total_pendaftar, total_responses)
        VALUES (?,?,?,?)
    ");
    $stmtLog->execute([$batchId, $fileName, $countPendaftar, $countResponses]);

    $pdo->commit();

    unset($_SESSION['import_file_path'], $_SESSION['import_file_name']);
    $_SESSION['flash_success'] = "Import berhasil! $countPendaftar data pendaftar & $countResponses data responses diproses (baru ditambahkan atau data lama diperbarui jika email sudah pernah ada).";
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = 'Import gagal: ' . $e->getMessage();
    header('Location: import_monitoring.php');
    exit;
}
