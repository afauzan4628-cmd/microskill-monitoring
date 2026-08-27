<?php
require_once 'config.php';
requireRoles(['admin']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Ubah Role User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Sesi tidak valid, silakan coba lagi.';
        header('Location: change_role.php');
        exit;
    }

    $userId  = (int)($_POST['user_id'] ?? 0);
    $newRole = $_POST['role'] ?? '';
    $allowedRoles = ['admin', 'operator', 'user'];

    if (!in_array($newRole, $allowedRoles, true)) {
        $_SESSION['flash_error'] = 'Role tidak valid.';
        header('Location: change_role.php');
        exit;
    }

    if ($userId === (int)($_SESSION['user_id'] ?? 0) && $newRole !== 'admin') {
        $_SESSION['flash_error'] = 'Tidak bisa menurunkan role akun Anda sendiri.';
        header('Location: change_role.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT username, role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $target = $stmt->fetch();

    if (!$target) {
        $_SESSION['flash_error'] = 'User tidak ditemukan.';
    } elseif ($target['role'] === $newRole) {
        $_SESSION['flash_error'] = 'Role user sudah seperti itu.';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$newRole, $userId]);
        logActivity($pdo, 'Ubah role user', "{$target['username']}: {$target['role']} -> $newRole");
        $_SESSION['flash_success'] = "Role {$target['username']} berhasil diubah menjadi " . ucfirst($newRole) . '.';
    }
    header('Location: change_role.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$where  = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE nama LIKE ? OR username LIKE ?';
    $params = ["%$search%", "%$search%"];
}
$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY nama ASC");
$stmt->execute($params);
$users = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="card">
  <h3 class="mt-0">Ubah Role User</h3>
  <p class="text-muted">Ubah hak akses (role) tiap user. Perubahan berlaku setelah user login ulang.</p>

  <form method="GET" action="change_role.php" class="toolbar">
    <div class="form-group">
      <label>Cari</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Nama atau username...">
    </div>
    <button type="submit" class="btn btn-sm">Cari</button>
    <?php if ($search !== ''): ?>
      <a href="change_role.php" class="btn btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Username</th>
          <th>Role Saat Ini</th>
          <th>Ubah Ke</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="4" class="text-muted">Tidak ada data user.</td></tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['nama']) ?></td>
            <td><?= e($u['username']) ?></td>
            <td><span class="badge badge-sudah"><?= e(ucfirst($u['role'])) ?></span></td>
            <td>
              <form method="POST" action="change_role.php" style="display:flex;gap:8px;" onsubmit="return confirm('Ubah role <?= e($u['username']) ?> ?');">
                <?= csrfField() ?>
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <select name="role" style="width:auto;">
                  <?php foreach (['admin' => 'Admin', 'operator' => 'Operator', 'user' => 'User'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $u['role'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm">Simpan</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
