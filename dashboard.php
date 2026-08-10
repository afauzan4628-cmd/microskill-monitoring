<?php
require_once 'config.php';
requireRoles(['admin','operator']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Dashboard';

// Total pendaftar
$totalPendaftar = $pdo->query("SELECT COUNT(*) FROM tb_pendaftar")->fetchColumn();

// Total responses
$totalResponses = $pdo->query("SELECT COUNT(*) FROM tb_responses")->fetchColumn();

// Sudah menyelesaikan = pendaftar yang emailnya ada di tb_responses
$sudah = $pdo->query("
    SELECT COUNT(DISTINCT p.id)
    FROM tb_pendaftar p
    INNER JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
")->fetchColumn();

$belum = max(0, $totalPendaftar - $sudah);
$progress = $totalPendaftar > 0 ? round(($sudah / $totalPendaftar) * 100, 1) : 0;

// Import terakhir
$lastImport = $pdo->query("SELECT * FROM tb_import_log ORDER BY created_at DESC LIMIT 1")->fetch();

require 'includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-card blue">
    <div class="label">Total Pendaftar</div>
    <div class="value"><?= number_format($totalPendaftar) ?></div>
  </div>
  <div class="stat-card blue">
    <div class="label">Total Responses</div>
    <div class="value"><?= number_format($totalResponses) ?></div>
  </div>
  <div class="stat-card green">
    <div class="label">Sudah Menyelesaikan</div>
    <div class="value"><?= number_format($sudah) ?></div>
  </div>
  <div class="stat-card red">
    <div class="label">Belum Menyelesaikan</div>
    <div class="value"><?= number_format($belum) ?></div>
  </div>
</div>

<div class="card">
  <h3 class="mt-0">Progress Penyelesaian</h3>
  <div class="progress-bar">
    <div class="progress-bar-fill" style="width: <?= $progress ?>%;"></div>
  </div>
  <p class="text-muted" style="margin-top:8px;"><?= $progress ?>% peserta telah menyelesaikan Microskill</p>
</div>

<div class="two-col">
  <div class="card">
    <h3 class="mt-0">Import Terakhir</h3>
    <?php if ($lastImport): ?>
      <table>
        <tr><td>Nama File</td><td><?= e($lastImport['nama_file']) ?></td></tr>
        <tr><td>Batch</td><td><?= e($lastImport['batch_import']) ?></td></tr>
        <tr><td>Total Pendaftar</td><td><?= (int)$lastImport['total_pendaftar'] ?></td></tr>
        <tr><td>Total Responses</td><td><?= (int)$lastImport['total_responses'] ?></td></tr>
        <tr><td>Waktu</td><td><?= e($lastImport['created_at']) ?></td></tr>
      </table>
    <?php else: ?>
      <p class="text-muted">Belum ada data yang diimport. <a href="import_monitoring.php">Import sekarang</a>.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="mt-0">Aksi Cepat</h3>
    <p><a href="import_monitoring.php" class="btn">⬆️ Import Data Excel</a></p>
    <p><a href="monitoring.php" class="btn btn-outline">🧭 Lihat Monitoring</a></p>
    <p><a href="laporan.php" class="btn btn-outline">📄 Buat Laporan</a></p>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
