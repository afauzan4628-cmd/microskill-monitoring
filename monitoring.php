<?php
require_once 'config.php';
requireRoles(['admin','operator']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Monitoring';

$search   = trim($_GET['search'] ?? '');
$status   = trim($_GET['status'] ?? '');
$instansi = trim($_GET['instansi'] ?? '');

$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$baseSql = "
    FROM tb_pendaftar p
    LEFT JOIN tb_responses r
        ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $baseSql .= " AND (p.nama_lengkap LIKE ? OR p.email_user LIKE ? OR p.nip LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($instansi !== '') {
    $baseSql .= " AND p.instansi_pemerintahan LIKE ?";
    $params[] = "%$instansi%";
}

if ($status === 'sudah') {
    $baseSql .= " AND r.id IS NOT NULL";
} elseif ($status === 'belum') {
    $baseSql .= " AND r.id IS NULL";
}

$countStmt = $pdo->prepare("SELECT COUNT(*) $baseSql");
$countStmt->execute($params);
$totalRows  = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$sql = "
    SELECT p.id, p.nama_lengkap, p.nip, p.email_user, p.instansi_pemerintahan, p.jabatan,
           r.id AS response_id, r.tema_microskill, r.tanggal_penyelesaian, r.keterangan, r.sertifikat
    $baseSql
    ORDER BY p.nama_lengkap ASC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$instansiList = $pdo->query("SELECT DISTINCT instansi_pemerintahan FROM tb_pendaftar WHERE instansi_pemerintahan IS NOT NULL AND instansi_pemerintahan != '' ORDER BY instansi_pemerintahan")->fetchAll(PDO::FETCH_COLUMN);

$queryStringBase = 'monitoring.php?' . http_build_query([
    'search'   => $search,
    'status'   => $status,
    'instansi' => $instansi,
]);

require 'includes/header.php';
?>

<div class="card">
  <form method="GET" class="toolbar">
    <div class="form-group">
      <label>Cari Nama / Email / NIP</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Ketik untuk mencari...">
    </div>
    <div class="form-group">
      <label>Status</label>
      <select name="status">
        <option value="">Semua Status</option>
        <option value="sudah" <?= $status=='sudah'?'selected':'' ?>>Sudah</option>
        <option value="belum" <?= $status=='belum'?'selected':'' ?>>Belum</option>
      </select>
    </div>
    <div class="form-group">
      <label>Instansi</label>
      <select name="instansi">
        <option value="">Semua Instansi</option>
        <?php foreach ($instansiList as $ins): ?>
          <option value="<?= e($ins) ?>" <?= $instansi==$ins?'selected':'' ?>><?= e($ins) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <button type="submit" class="btn">Filter</button>
      <a href="monitoring.php" class="btn btn-outline">Reset</a>
    </div>
  </form>

  <p class="text-muted">Menampilkan <?= count($rows) ?> dari <?= number_format($totalRows) ?> peserta</p>

  <div class="table-wrap">
    <table>
      <tr>
        <th>Nama Lengkap</th>
        <th>NIP</th>
        <th>Email</th>
        <th>Instansi</th>
        <th>Status</th>
        <th>Tema Microskill</th>
        <th>Tgl Selesai</th>
        <th>Keterangan</th>
      </tr>
      <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="text-muted" style="text-align:center;padding:20px;">Tidak ada data ditemukan.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <?php $sudah = !is_null($r['response_id']); ?>
        <tr>
          <td><?= e($r['nama_lengkap']) ?></td>
          <td><?= e($r['nip']) ?></td>
          <td><?= e($r['email_user']) ?></td>
          <td><?= e($r['instansi_pemerintahan']) ?></td>
          <td>
            <?php if ($sudah): ?>
              <span class="badge badge-sudah">Sudah</span>
            <?php else: ?>
              <span class="badge badge-belum">Belum</span>
            <?php endif; ?>
          </td>
          <td><?= $sudah ? e($r['tema_microskill']) : '-' ?></td>
          <td><?= $sudah ? formatTanggalIndo($r['tanggal_penyelesaian']) : '-' ?></td>
          <td><?= $sudah ? e($r['keterangan']) : '-' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <?= renderPagination($page, $totalPages, $queryStringBase) ?>
</div>

<?php require 'includes/footer.php'; ?>
