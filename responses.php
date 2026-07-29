<?php
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Responses';

$search  = trim($_GET['search'] ?? '');
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (nama_peserta LIKE ? OR email_peserta LIKE ? OR tema_microskill LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$totalRows  = $pdo->prepare("SELECT COUNT(*) FROM tb_responses $where");
$totalRows->execute($params);
$totalRows  = $totalRows->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM tb_responses $where ORDER BY tanggal_penyelesaian DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$queryStringBase = 'responses.php?' . http_build_query(['search' => $search]);

require 'includes/header.php';
?>

<div class="card">
  <form method="GET" class="toolbar">
    <div class="form-group">
      <label>Cari Nama / Email / Tema</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Ketik untuk mencari...">
    </div>
    <div class="form-group">
      <button type="submit" class="btn">Cari</button>
      <a href="responses.php" class="btn btn-outline">Reset</a>
    </div>
  </form>

  <p class="text-muted">Menampilkan <?= count($rows) ?> dari <?= number_format($totalRows) ?> data responses</p>

  <div class="table-wrap">
    <table>
      <tr>
        <th>Nama Peserta</th>
        <th>Email</th>
        <th>Asal Instansi/OPD</th>
        <th>Tema Micro Skill</th>
        <th>Tgl Selesai</th>
        <th>Keterangan</th>
        <th>Sertifikat</th>
      </tr>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-muted" style="text-align:center;padding:20px;">Tidak ada data.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['nama_peserta']) ?></td>
          <td><?= e($r['email_peserta']) ?></td>
          <td><?= e($r['asal_instansi']) ?></td>
          <td><?= e($r['tema_microskill']) ?></td>
          <td><?= formatTanggalIndo($r['tanggal_penyelesaian']) ?></td>
          <td><?= e($r['keterangan']) ?></td>
          <td>
            <?php if (!empty($r['sertifikat'])): ?>
              <a href="<?= e($r['sertifikat']) ?>" target="_blank">Lihat</a>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <?= renderPagination($page, $totalPages, $queryStringBase) ?>
</div>

<?php require 'includes/footer.php'; ?>
