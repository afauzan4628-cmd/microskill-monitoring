<?php
require_once 'config.php';
requireRoles(['admin','operator']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Sertifikat';

$uploadDir = __DIR__ . '/uploads/sertifikat';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Sesi tidak valid, silakan coba lagi.';
        header('Location: sertifikat.php');
        exit;
    }

    $responseId = (int)($_POST['response_id'] ?? 0);

    if ($responseId <= 0) {
        $_SESSION['flash_error'] = 'Data peserta tidak valid.';
        header('Location: sertifikat.php');
        exit;
    }

    if (!isset($_FILES['sertifikat']) || $_FILES['sertifikat']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash_error'] = 'Gagal mengunggah file. Pastikan file sudah dipilih.';
        header('Location: sertifikat.php');
        exit;
    }

    $file     = $_FILES['sertifikat'];
    $allowed  = ['pdf', 'jpg', 'jpeg', 'png'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $maxBytes = 5 * 1024 * 1024;

    if (!in_array($ext, $allowed, true)) {
        $_SESSION['flash_error'] = 'Format file harus PDF, JPG, atau PNG.';
        header('Location: sertifikat.php');
        exit;
    }
    if ($file['size'] > $maxBytes) {
        $_SESSION['flash_error'] = 'Ukuran file maksimal 5MB.';
        header('Location: sertifikat.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT nama_peserta FROM tb_responses WHERE id = ?');
    $stmt->execute([$responseId]);
    $namaPeserta = $stmt->fetchColumn();

    if ($namaPeserta === false) {
        $_SESSION['flash_error'] = 'Data response tidak ditemukan.';
        header('Location: sertifikat.php');
        exit;
    }

    $safeName = sanitizeFileName($namaPeserta, 'sertifikat');
    $fileName = 'sertifikat_' . $safeName . '_' . $responseId . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        $relativePath = 'uploads/sertifikat/' . $fileName;
        $stmt = $pdo->prepare('UPDATE tb_responses SET sertifikat = ? WHERE id = ?');
        $stmt->execute([$relativePath, $responseId]);
        logActivity($pdo, 'Upload sertifikat', "Response ID $responseId ($namaPeserta)");
        $_SESSION['flash_success'] = 'Sertifikat berhasil diunggah.';
    } else {
        $_SESSION['flash_error'] = 'Gagal menyimpan file ke server.';
    }
    header('Location: sertifikat.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where  = 'WHERE 1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (nama_peserta LIKE ? OR email_peserta LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filter === 'ada') {
    $where .= " AND sertifikat IS NOT NULL AND sertifikat != ''";
} elseif ($filter === 'belum') {
    $where .= " AND (sertifikat IS NULL OR sertifikat = '')";
}

$total = $pdo->prepare("SELECT COUNT(*) FROM tb_responses $where");
$total->execute($params);
$total = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT id, nama_peserta, email_peserta, tema_microskill, sertifikat FROM tb_responses $where ORDER BY nama_peserta ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$queryStringBase = 'sertifikat.php?' . http_build_query(['search' => $search, 'filter' => $filter]);

require 'includes/header.php';
?>

<div class="card">
  <h3 class="mt-0">Manajemen Sertifikat</h3>
  <p class="text-muted">Lihat, unduh, atau unggah manual sertifikat penyelesaian Microskill peserta.</p>

  <form method="GET" action="sertifikat.php" class="toolbar">
    <div class="form-group">
      <label>Cari</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Nama atau email...">
    </div>
    <div class="form-group">
      <label>Status Sertifikat</label>
      <select name="filter">
        <option value="">Semua</option>
        <option value="ada" <?= $filter=='ada'?'selected':'' ?>>Sudah Ada</option>
        <option value="belum" <?= $filter=='belum'?'selected':'' ?>>Belum Ada</option>
      </select>
    </div>
    <div class="form-group">
      <button type="submit" class="btn btn-sm">Filter</button>
      <a href="sertifikat.php" class="btn btn-outline btn-sm">Reset</a>
    </div>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama Peserta</th>
          <th>Email</th>
          <th>Tema Microskill</th>
          <th>Sertifikat</th>
          <th>Upload / Ganti</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="5" class="text-muted">Tidak ada data.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['nama_peserta']) ?></td>
            <td><?= e($r['email_peserta']) ?></td>
            <td><?= e($r['tema_microskill']) ?></td>
            <td>
              <?php if (!empty($r['sertifikat'])): ?>
                <a href="<?= e($r['sertifikat']) ?>" target="_blank" class="badge badge-sudah" style="text-decoration:none;">Lihat File</a>
              <?php else: ?>
                <span class="badge badge-belum">Belum Ada</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" action="sertifikat.php" enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center;">
                <?= csrfField() ?>
                <input type="hidden" name="response_id" value="<?= (int)$r['id'] ?>">
                <input type="file" name="sertifikat" accept=".pdf,.jpg,.jpeg,.png" required style="max-width:180px;">
                <button type="submit" class="btn btn-sm">Upload</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?= renderPagination($page, $totalPages, $queryStringBase) ?>
</div>

<?php require 'includes/footer.php'; ?>
