<?php
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Pendaftar';

$search  = trim($_GET['search'] ?? '');
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (nama_lengkap LIKE ? OR email_user LIKE ? OR nip LIKE ? OR instansi_pemerintahan LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

$totalRows  = $pdo->prepare("SELECT COUNT(*) FROM tb_pendaftar $where");
$totalRows->execute($params);
$totalRows  = $totalRows->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM tb_pendaftar $where ORDER BY nama_lengkap ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$queryStringBase = 'pendaftar.php?' . http_build_query(['search' => $search]);

require 'includes/header.php';
?>

<div class="card">
  <form method="GET" class="toolbar">
    <div class="form-group">
      <label>Cari Nama / Email / NIP / Instansi</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Ketik untuk mencari...">
    </div>
    <div class="form-group">
      <button type="submit" class="btn">Cari</button>
      <a href="pendaftar.php" class="btn btn-outline">Reset</a>
    </div>
  </form>

  <p class="text-muted">Menampilkan <?= count($rows) ?> dari <?= number_format($totalRows) ?> peserta</p>

  <div class="table-wrap">
    <table>
      <tr>
        <th>ID Batch</th>
        <th>Nama Lengkap</th>
        <th>NIP</th>
        <th>Jenis Kelamin</th>
        <th>Instansi</th>
        <th>Jabatan</th>
        <th>Email</th>
      </tr>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-muted" style="text-align:center;padding:20px;">Tidak ada data.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['id_batch']) ?></td>
          <td><?= e($r['nama_lengkap']) ?></td>
          <td><?= e($r['nip']) ?></td>
          <td><?= e($r['jenis_kelamin']) ?></td>
          <td><?= e($r['instansi_pemerintahan']) ?></td>
          <td><?= e($r['jabatan']) ?></td>
          <td><?= e($r['email_user']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <?= renderPagination($page, $totalPages, $queryStringBase) ?>
</div>

<?php require 'includes/footer.php'; ?>
