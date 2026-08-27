<?php
require_once 'config.php';
requireRoles(['admin']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Log Aktivitas';

$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE username LIKE ? OR aksi LIKE ? OR keterangan LIKE ?';
    $params = ["%$search%", "%$search%", "%$search%"];
}

$total = (function() use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_activity_log $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
})();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM tb_activity_log $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$queryStringBase = 'activity_log.php?' . http_build_query(['search' => $search]);

require 'includes/header.php';
?>

<div class="card">
  <h3 class="mt-0">Log Aktivitas</h3>
  <p class="text-muted">Riwayat aksi penting yang dilakukan user di sistem: tambah/edit/hapus user, ubah role, dan lainnya.</p>

  <form method="GET" action="activity_log.php" class="toolbar">
    <div class="form-group">
      <label>Cari</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Username, aksi, atau keterangan...">
    </div>
    <button type="submit" class="btn btn-sm">Cari</button>
    <?php if ($search !== ''): ?>
      <a href="activity_log.php" class="btn btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Waktu</th>
          <th>User</th>
          <th>Aksi</th>
          <th>Keterangan</th>
          <th>IP</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="5" class="text-muted">Belum ada aktivitas tercatat.</td></tr>
        <?php else: foreach ($logs as $log): ?>
          <tr>
            <td><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
            <td><?= e($log['username'] ?? '-') ?></td>
            <td><?= e($log['aksi']) ?></td>
            <td><?= e($log['keterangan'] ?? '-') ?></td>
            <td class="text-muted"><?= e($log['ip_address'] ?? '-') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?= renderPagination($page, $totalPages, $queryStringBase) ?>
</div>

<?php require 'includes/footer.php'; ?>
