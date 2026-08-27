<?php
require_once 'config.php';
requireRoles(['admin']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Manajemen User';

$action = $_GET['action'] ?? 'list';
$editUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Sesi tidak valid, silakan coba lagi.';
        header('Location: manage_users.php');
        exit;
    }

    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create' || $formAction === 'update') {
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'operator';
        $status   = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';
        $userId   = (int)($_POST['user_id'] ?? 0);

        $allowedRoles   = ['admin', 'operator', 'user'];
        $allowedStatus  = ['active', 'inactive'];
        if (!in_array($role, $allowedRoles, true))   $role = 'operator';
        if (!in_array($status, $allowedStatus, true)) $status = 'active';

        if ($nama === '' || $username === '' || $email === '') {
            $_SESSION['flash_error'] = 'Nama, username, dan email wajib diisi.';
            header('Location: manage_users.php');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Format email tidak valid.';
            header('Location: manage_users.php');
            exit;
        }

        try {
            if ($formAction === 'create') {
                if ($password === '' || strlen($password) < 6) {
                    $_SESSION['flash_error'] = 'Password minimal 6 karakter.';
                    header('Location: manage_users.php');
                    exit;
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (nama, username, email, password, role, status) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$nama, $username, $email, $hash, $role, $status]);
                logActivity($pdo, 'Tambah user', "Membuat akun baru: $username ($role)");
                $_SESSION['flash_success'] = 'User baru berhasil ditambahkan.';
            } else {
                if ($userId <= 0) {
                    $_SESSION['flash_error'] = 'User tidak ditemukan.';
                    header('Location: manage_users.php');
                    exit;
                }
                if ($userId === (int)($_SESSION['user_id'] ?? 0) && $role !== 'admin') {
                    $_SESSION['flash_error'] = 'Tidak bisa menurunkan role akun Anda sendiri dari sini.';
                    header('Location: manage_users.php');
                    exit;
                }
                if ($password !== '') {
                    if (strlen($password) < 6) {
                        $_SESSION['flash_error'] = 'Password minimal 6 karakter.';
                        header('Location: manage_users.php');
                        exit;
                    }
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET nama=?, username=?, email=?, role=?, status=?, password=? WHERE id=?');
                    $stmt->execute([$nama, $username, $email, $role, $status, $hash, $userId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET nama=?, username=?, email=?, role=?, status=? WHERE id=?');
                    $stmt->execute([$nama, $username, $email, $role, $status, $userId]);
                }
                logActivity($pdo, 'Edit user', "Mengubah data user ID $userId ($username)");
                $_SESSION['flash_success'] = 'Data user berhasil diperbarui.';
            }
        } catch (PDOException $ex) {
            $_SESSION['flash_error'] = (str_contains($ex->getMessage(), 'Duplicate'))
                ? 'Username atau email sudah dipakai user lain.'
                : 'Gagal menyimpan data user.';
        }
        header('Location: manage_users.php');
        exit;
    }

    if ($formAction === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['flash_error'] = 'Anda tidak bisa menghapus akun Anda sendiri.';
        } else {
            $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $targetUsername = $stmt->fetchColumn();

            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            logActivity($pdo, 'Hapus user', "Menghapus user ID $userId ($targetUsername)");
            $_SESSION['flash_success'] = 'User berhasil dihapus.';
        }
        header('Location: manage_users.php');
        exit;
    }

    if ($formAction === 'toggle_status') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['flash_error'] = 'Anda tidak bisa menonaktifkan akun Anda sendiri.';
        } else {
            $stmt = $pdo->prepare('SELECT status, username FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if ($row) {
                $newStatus = $row['status'] === 'active' ? 'inactive' : 'active';
                $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $userId]);
                logActivity($pdo, 'Ubah status user', "{$row['username']} -> $newStatus");
                $_SESSION['flash_success'] = 'Status user berhasil diubah.';
            }
        }
        header('Location: manage_users.php');
        exit;
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    $editUser = $stmt->fetch();
}

$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$where  = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE nama LIKE ? OR username LIKE ? OR email LIKE ?';
    $params = ["%$search%", "%$search%", "%$search%"];
}

$totalUsers = (function() use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
})();
$totalPages = max(1, (int)ceil($totalUsers / $perPage));

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

$queryStringBase = 'manage_users.php?' . http_build_query(['search' => $search]);

require 'includes/header.php';
?>

<div class="card">
  <h3 class="mt-0"><?= $editUser ? 'Edit User' : 'Tambah User Baru' ?></h3>
  <form method="POST" action="manage_users.php">
    <?= csrfField() ?>
    <input type="hidden" name="form_action" value="<?= $editUser ? 'update' : 'create' ?>">
    <?php if ($editUser): ?>
      <input type="hidden" name="user_id" value="<?= (int)$editUser['id'] ?>">
    <?php endif; ?>

    <div class="two-col">
      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" value="<?= e($editUser['nama'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" value="<?= e($editUser['username'] ?? '') ?>" required>
      </div>
    </div>

    <div class="two-col">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($editUser['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Password <?= $editUser ? '(kosongkan jika tidak diganti)' : '' ?></label>
        <input type="password" name="password" placeholder="<?= $editUser ? '••••••••' : 'Minimal 6 karakter' ?>">
      </div>
    </div>

    <div class="two-col">
      <div class="form-group">
        <label>Role</label>
        <select name="role">
          <?php foreach (['admin' => 'Admin', 'operator' => 'Operator', 'user' => 'User'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= (($editUser['role'] ?? 'operator') === $val) ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="active" <?= (($editUser['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Aktif</option>
          <option value="inactive" <?= (($editUser['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
    </div>

    <button type="submit" class="btn"><?= $editUser ? 'Simpan Perubahan' : 'Tambah User' ?></button>
    <?php if ($editUser): ?>
      <a href="manage_users.php" class="btn btn-outline">Batal</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <h3 class="mt-0">Daftar User</h3>

  <form method="GET" action="manage_users.php" class="toolbar">
    <div class="form-group">
      <label>Cari</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Nama, username, atau email...">
    </div>
    <button type="submit" class="btn btn-sm">Cari</button>
    <?php if ($search !== ''): ?>
      <a href="manage_users.php" class="btn btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Dibuat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="7" class="text-muted">Belum ada data user.</td></tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['nama']) ?></td>
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e(ucfirst($u['role'])) ?></td>
            <td>
              <span class="badge <?= $u['status'] === 'active' ? 'badge-sudah' : 'badge-belum' ?>">
                <?= $u['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td><?= formatTanggalIndo($u['created_at']) ?></td>
            <td>
              <a href="manage_users.php?action=edit&id=<?= (int)$u['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
              <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                <form method="POST" action="manage_users.php" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="form_action" value="toggle_status">
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm"><?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
                <form method="POST" action="manage_users.php" style="display:inline;" onsubmit="return confirm('Hapus user ini? Tindakan tidak bisa dibatalkan.');">
                  <?= csrfField() ?>
                  <input type="hidden" name="form_action" value="delete">
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <button type="submit" class="btn btn-sm" style="background:var(--red);">Hapus</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?= renderPagination($page, $totalPages, $queryStringBase) ?>
</div>

<?php require 'includes/footer.php'; ?>
