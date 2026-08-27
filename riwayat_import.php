<?php
require_once 'config.php';
requireRoles(['admin']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Riwayat Import';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Sesi tidak valid, silakan coba lagi.';
        header('Location: riwayat_import.php');
        exit;
    }

    if (($_POST['form_action'] ?? '') === 'rollback') {
        $logId = (int)($_POST['log_id'] ?? 0);
        $batch = trim($_POST['batch_import'] ?? '');

        if ($logId <= 0 || $batch === '') {
            $_SESSION['flash_error'] = 'Data batch tidak valid.';
            header('Location: riwayat_import.php');
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('DELETE FROM tb_pendaftar WHERE batch_import = ?');
            $stmt->execute([$batch]);
            $deletedPendaftar = $stmt->rowCount();

            $stmt = $pdo->prepare('DELETE FROM tb_responses WHERE batch_import = ?');
            $stmt->execute([$batch]);
            $deletedResponses = $stmt->rowCount();

            $stmt = $pdo->prepare('DELETE FROM tb_import_log WHERE id = ?');
            $stmt->execute([$logId]);

            $pdo->commit();

            logActivity($pdo, 'Rollback import', "Batch $batch: hapus $deletedPendaftar pendaftar, $deletedResponses responses");
            $_SESSION['flash_success'] = "Rollback berhasil. $deletedPendaftar data pendaftar dan $deletedResponses data responses pada batch \"$batch\" telah dihapus.";
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Rollback gagal: ' . $ex->getMessage();
        }
        header('Location: riwayat_import.php');
        exit;
    }
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$total = (int)$pdo->query('SELECT COUNT(*) FROM tb_import_log')->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM tb_import_log ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$logs = $stmt->fetchAll();

$detailBatch = trim($_GET['detail'] ?? '');
$detailRows  = [];
if ($detailBatch !== '') {
    $stmt = $pdo->prepare('SELECT nama_lengkap, email_user, instansi_pemerintahan FROM tb_pendaftar WHERE batch_import = ? ORDER BY nama_lengkap ASC LIMIT 200');
    $stmt->execute([$detailBatch]);
    $detailRows = $stmt->fetchAll();
}

$queryStringBase = 'riwayat_import.php?' . http_build_query([]);

require 'includes/header.php';
?>

<div class="card">
  <h3 class="mt-0">Riwayat Import Data</h3>
  <p class="text-muted">Semua histori import Excel. Klik "Lihat Detail" untuk melihat data yang masuk pada satu batch, atau "Rollback" untuk membatalkan import (menghapus semua data pada batch tersebut).</p>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Waktu</th>
          <th>Nama File</th>
          <th>Batch</th>
          <th>Total Pendaftar</th>
          <th>Total Responses</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="6" class="text-muted">Belum ada riwayat import.</td></tr>
        <?php else: foreach ($logs as $log): ?>
          <tr>
            <td><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
            <td><?= e($log['nama_file']) ?></td>
            <td><?= e($log['batch_import']) ?></td>
            <td><?= (int)$log['total_pendaftar'] ?></td>
            <td><?= (int)$log['total_responses'] ?></td>
            <td style="white-space:nowrap;">
              <a href="riwayat_import.php?detail=<?= urlencode($log['batch_import']) ?>" class="btn btn-outline btn-sm">Lihat Detail</a>
              <form method="POST" action="riwayat_import.php" style="display:inline;" onsubmit="return confirm('Hapus SEMUA data pendaftar dan responses pada batch \'<?= e($log['batch_import']) ?>\'? Tindakan ini tidak bisa dibatalkan.');">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="rollback">
                <input type="hidden" name="log_id" value="<?= (int)$log['id'] ?>">
                <input type="hidden" name="batch_import" value="<?= e($log['batch_import']) ?>">
                <button type="submit" class="btn btn-sm" style="background:var(--red);">Rollback</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?= renderPagination($page, $totalPages, $queryStringBase) ?>
</div>

<?php if ($detailBatch !== ''): ?>
<div class="card">
  <h3 class="mt-0">Detail Batch: <?= e($detailBatch) ?></h3>
  <p class="text-muted">Menampilkan maksimal 200 baris pertama.</p>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Nama</th><th>Email</th><th>Instansi</th></tr>
      </thead>
      <tbody>
        <?php if (empty($detailRows)): ?>
          <tr><td colspan="3" class="text-muted">Tidak ada data pendaftar pada batch ini.</td></tr>
        <?php else: foreach ($detailRows as $d): ?>
          <tr>
            <td><?= e($d['nama_lengkap']) ?></td>
            <td><?= e($d['email_user']) ?></td>
            <td><?= e($d['instansi_pemerintahan']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
