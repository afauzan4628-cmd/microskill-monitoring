<?php
require_once 'config.php';
requireRoles(['admin','operator']);
require 'config/database.php';
require 'includes/functions.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$totalPendaftar = $pdo->query("SELECT COUNT(*) FROM tb_pendaftar")->fetchColumn();
$totalResponses = $pdo->query("SELECT COUNT(*) FROM tb_responses")->fetchColumn();

$sudah = $pdo->query("
    SELECT COUNT(DISTINCT p.id)
    FROM tb_pendaftar p
    INNER JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
")->fetchColumn();
$belum    = max(0, $totalPendaftar - $sudah);
$progress = $totalPendaftar > 0 ? round(($sudah / $totalPendaftar) * 100, 1) : 0;

$rekapInstansi = $pdo->query("
    SELECT
        p.instansi_pemerintahan AS instansi,
        COUNT(DISTINCT p.id) AS total,
        COUNT(DISTINCT CASE WHEN r.id IS NOT NULL THEN p.id END) AS sudah
    FROM tb_pendaftar p
    LEFT JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
    WHERE p.instansi_pemerintahan IS NOT NULL AND p.instansi_pemerintahan != ''
    GROUP BY p.instansi_pemerintahan
    ORDER BY total DESC
")->fetchAll();

foreach ($rekapInstansi as &$row) {
    $row['belum']    = max(0, $row['total'] - $row['sudah']);
    $row['progress'] = $row['total'] > 0 ? round(($row['sudah'] / $row['total']) * 100, 2) : 0;
}
unset($row);

$opdTerbanyakSelesai = null;
if (!empty($rekapInstansi)) {
    $sortedBySelesai = $rekapInstansi;
    usort($sortedBySelesai, function ($a, $b) { return $b['sudah'] <=> $a['sudah']; });
    $opdTerbanyakSelesai = $sortedBySelesai[0];
}

$topTema = $pdo->query("
    SELECT tema_microskill AS tema, COUNT(*) AS jumlah
    FROM tb_responses
    WHERE tema_microskill IS NOT NULL AND tema_microskill != ''
    GROUP BY tema_microskill ORDER BY jumlah DESC LIMIT 5
")->fetchAll();

$BATAS_BELUM_DI_PDF = 50;
$belumList = $pdo->query("
    SELECT p.nama_lengkap, p.nip, p.email_user, p.instansi_pemerintahan
    FROM tb_pendaftar p
    LEFT JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
    WHERE r.id IS NULL
    ORDER BY p.nama_lengkap ASC
    LIMIT $BATAS_BELUM_DI_PDF
")->fetchAll();

$narasi = sprintf(
    'Berdasarkan hasil monitoring, terdapat %s peserta yang terdaftar. Sebanyak %s peserta (%s%%) telah menyelesaikan Microskill, sedangkan %s peserta belum menyelesaikan. %s',
    number_format($totalPendaftar, 0, ',', '.'),
    number_format($sudah, 0, ',', '.'),
    number_format($progress, 1, ',', '.'),
    number_format($belum, 0, ',', '.'),
    $progress >= 50
        ? 'Data ini menunjukkan bahwa lebih dari separuh peserta telah menyelesaikan program, namun masih diperlukan tindak lanjut kepada peserta yang belum menyelesaikan.'
        : 'Data ini menunjukkan bahwa tingkat penyelesaian masih di bawah separuh peserta, sehingga diperlukan tindak lanjut lebih lanjut agar target penyelesaian tercapai.'
);

ob_start();
?>
<html>
<head>
<style>
  body{font-family: DejaVu Sans, sans-serif; font-size:11px; color:#222;}
  h1{font-size:16px;margin-bottom:2px;}
  .subtitle{color:#666;font-size:10px;margin-bottom:14px;}
  table{width:100%;border-collapse:collapse;margin-bottom:16px;}
  th,td{border:1px solid #ccc;padding:5px 7px;text-align:left;}
  th{background:#f0f2fa;}
  .stat-table td{border:none;padding:4px 10px 4px 0;}
  .stat-table .label{color:#666;}
  .stat-table .value{font-weight:bold;font-size:13px;}
  h3{font-size:12.5px;margin:16px 0 6px;border-bottom:1px solid #ddd;padding-bottom:4px;}
  .narasi{margin:0 0 16px;line-height:1.6;text-align:justify;}
  .catatan{color:#666;font-size:10px;font-style:italic;margin-top:6px;}
  .kesimpulan{margin:0;padding-left:18px;line-height:1.8;}
  .text-right{text-align:right;}
  .page-break{page-break-before:always;}
</style>
</head>
<body>
  <h1>Laporan Monitoring Penyelesaian Microskill</h1>
  <div class="subtitle">Dicetak pada <?= date('d-m-Y H:i') ?></div>

  <table class="stat-table">
    <tr>
      <td><div class="label">Total Pendaftar</div><div class="value"><?= number_format($totalPendaftar, 0, ',', '.') ?></div></td>
      <td><div class="label">Sudah Menyelesaikan</div><div class="value"><?= number_format($sudah, 0, ',', '.') ?></div></td>
      <td><div class="label">Belum Menyelesaikan</div><div class="value"><?= number_format($belum, 0, ',', '.') ?></div></td>
      <td><div class="label">Progress</div><div class="value"><?= number_format($progress, 1, ',', '.') ?>%</div></td>
    </tr>
  </table>

  <p class="narasi"><?= e($narasi) ?></p>

  <h3>Rekap per OPD / Instansi</h3>
  <table>
    <tr>
      <th>Instansi</th>
      <th class="text-right">Total</th>
      <th class="text-right">Sudah</th>
      <th class="text-right">Belum</th>
      <th class="text-right">Progress</th>
    </tr>
    <?php foreach ($rekapInstansi as $i): ?>
      <tr>
        <td><?= e($i['instansi']) ?></td>
        <td class="text-right"><?= number_format($i['total'], 0, ',', '.') ?></td>
        <td class="text-right"><?= number_format($i['sudah'], 0, ',', '.') ?></td>
        <td class="text-right"><?= number_format($i['belum'], 0, ',', '.') ?></td>
        <td class="text-right"><?= number_format($i['progress'], 2, ',', '.') ?>%</td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rekapInstansi)): ?><tr><td colspan="5">Belum ada data.</td></tr><?php endif; ?>
  </table>

  <h3>Top 5 Tema Micro Skill Terpopuler</h3>
  <table>
    <tr><th>Tema</th><th class="text-right">Jumlah</th></tr>
    <?php foreach ($topTema as $t): ?>
      <tr><td><?= e($t['tema']) ?></td><td class="text-right"><?= number_format($t['jumlah'], 0, ',', '.') ?></td></tr>
    <?php endforeach; ?>
    <?php if (empty($topTema)): ?><tr><td colspan="2">Belum ada data.</td></tr><?php endif; ?>
  </table>

  <h3>Kesimpulan</h3>
  <ol class="kesimpulan">
    <li>Total peserta terdaftar sebanyak <?= number_format($totalPendaftar, 0, ',', '.') ?> orang.</li>
    <li>Sebanyak <?= number_format($sudah, 0, ',', '.') ?> peserta telah menyelesaikan Microskill.</li>
    <li>Tingkat penyelesaian saat ini mencapai <?= number_format($progress, 1, ',', '.') ?>%.</li>
    <li>Masih terdapat <?= number_format($belum, 0, ',', '.') ?> peserta yang belum menyelesaikan.</li>
    <?php if ($opdTerbanyakSelesai): ?>
      <li>OPD dengan jumlah penyelesaian terbanyak adalah <?= e($opdTerbanyakSelesai['instansi']) ?> (<?= number_format($opdTerbanyakSelesai['sudah'], 0, ',', '.') ?> peserta selesai).</li>
    <?php endif; ?>
  </ol>

  <div class="page-break"></div>
  <h3>Lampiran: Daftar Peserta Belum Menyelesaikan</h3>
  <p class="catatan">
    Menampilkan <?= count($belumList) ?> dari total <?= number_format($belum, 0, ',', '.') ?> peserta yang belum menyelesaikan.
    Daftar lengkap dapat dilihat pada menu Monitoring di aplikasi.
  </p>
  <table>
    <tr><th>Nama</th><th>NIP</th><th>Email</th><th>Instansi</th></tr>
    <?php foreach ($belumList as $b): ?>
      <tr>
        <td><?= e($b['nama_lengkap']) ?></td>
        <td><?= e($b['nip']) ?></td>
        <td><?= e($b['email_user']) ?></td>
        <td><?= e($b['instansi_pemerintahan']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($belumList)): ?><tr><td colspan="4">Semua peserta sudah menyelesaikan.</td></tr><?php endif; ?>
  </table>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$defaultName = 'Laporan_Microskill_' . date('Ymd_His');
$fileName    = sanitizeFileName($_GET['nama_file'] ?? '', $defaultName) . '.pdf';

$dompdf->stream($fileName, ['Attachment' => true]);
exit;
