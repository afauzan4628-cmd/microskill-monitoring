<?php
require_once 'config.php';
requireRoles(['admin','operator']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Laporan';

$totalPendaftar = $pdo->query("SELECT COUNT(*) FROM tb_pendaftar")->fetchColumn();
$totalResponses = $pdo->query("SELECT COUNT(*) FROM tb_responses")->fetchColumn();

$sudah = $pdo->query("
    SELECT COUNT(DISTINCT p.id)
    FROM tb_pendaftar p
    INNER JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
")->fetchColumn();
$belum    = max(0, $totalPendaftar - $sudah);
$progress = $totalPendaftar > 0 ? round(($sudah / $totalPendaftar) * 100, 1) : 0;

$topInstansi = $pdo->query("
    SELECT p.instansi_pemerintahan AS instansi, COUNT(DISTINCT p.id) AS jumlah
    FROM tb_pendaftar p
    INNER JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
    WHERE p.instansi_pemerintahan IS NOT NULL AND p.instansi_pemerintahan != ''
    GROUP BY p.instansi_pemerintahan
    ORDER BY jumlah DESC
    LIMIT 5
")->fetchAll();

$topTema = $pdo->query("
    SELECT tema_microskill AS tema, COUNT(*) AS jumlah
    FROM tb_responses
    WHERE tema_microskill IS NOT NULL AND tema_microskill != ''
    GROUP BY tema_microskill
    ORDER BY jumlah DESC
    LIMIT 5
")->fetchAll();

$belumList = $pdo->query("
    SELECT p.nama_lengkap, p.nip, p.email_user, p.instansi_pemerintahan
    FROM tb_pendaftar p
    LEFT JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
    WHERE r.id IS NULL
    ORDER BY p.nama_lengkap ASC
    LIMIT 50
")->fetchAll();

require 'includes/header.php';
?>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <div>
      <h2 class="mt-0" style="margin-bottom:2px;">Laporan Monitoring Microskill</h2>
      <p class="text-muted" style="margin:0;">Dicetak pada <?= date('d-m-Y H:i') ?></p>
    </div>
    <form action="export_pdf.php" method="GET" style="display:flex;gap:8px;align-items:center;">
      <input
        type="text"
        name="nama_file"
        placeholder="Nama file (opsional)"
        value="Laporan_Microskill_<?= date('Ymd_His') ?>"
        maxlength="100"
        style="padding:8px 10px;border:1px solid #ccc;border-radius:6px;min-width:220px;"
      >
      <button type="submit" class="btn">⬇️ Export ke PDF</button>
    </form>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card blue"><div class="label">Total Pendaftar</div><div class="value"><?= number_format($totalPendaftar) ?></div></div>
  <div class="stat-card green"><div class="label">Sudah Menyelesaikan</div><div class="value"><?= number_format($sudah) ?></div></div>
  <div class="stat-card red"><div class="label">Belum Menyelesaikan</div><div class="value"><?= number_format($belum) ?></div></div>
  <div class="stat-card blue"><div class="label">Progress</div><div class="value"><?= $progress ?>%</div></div>
</div>

<div class="two-col">
  <div class="card">
    <h3 class="mt-0">Top 5 Instansi (Penyelesaian Terbanyak)</h3>
    <table>
      <tr><th>Instansi</th><th>Jumlah</th></tr>
      <?php if (empty($topInstansi)): ?>
        <tr><td colspan="2" class="text-muted">Belum ada data.</td></tr>
      <?php endif; ?>
      <?php foreach ($topInstansi as $i): ?>
        <tr><td><?= e($i['instansi']) ?></td><td><?= (int)$i['jumlah'] ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <h3 class="mt-0">Top 5 Tema Micro Skill Terpopuler</h3>
    <table>
      <tr><th>Tema</th><th>Jumlah</th></tr>
      <?php if (empty($topTema)): ?>
        <tr><td colspan="2" class="text-muted">Belum ada data.</td></tr>
      <?php endif; ?>
      <?php foreach ($topTema as $t): ?>
        <tr><td><?= e($t['tema']) ?></td><td><?= (int)$t['jumlah'] ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<div class="card">
  <h3 class="mt-0">Daftar Peserta Belum Menyelesaikan <span class="text-muted">(maks. 50 ditampilkan, lengkap ada di PDF)</span></h3>
  <div class="table-wrap">
    <table>
      <tr><th>Nama</th><th>NIP</th><th>Email</th><th>Instansi</th></tr>
      <?php if (empty($belumList)): ?>
        <tr><td colspan="4" class="text-muted" style="text-align:center;padding:20px;">🎉 Semua peserta sudah menyelesaikan Microskill.</td></tr>
      <?php endif; ?>
      <?php foreach ($belumList as $b): ?>
        <tr>
          <td><?= e($b['nama_lengkap']) ?></td>
          <td><?= e($b['nip']) ?></td>
          <td><?= e($b['email_user']) ?></td>
          <td><?= e($b['instansi_pemerintahan']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
