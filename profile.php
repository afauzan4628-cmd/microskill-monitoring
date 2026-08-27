<?php
require_once 'config.php';
requireLogin();
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Profil Saya';

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Sesi tidak valid, silakan coba lagi.';
        header('Location: profile.php');
        exit;
    }

    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'update_profile') {
        $nama  = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nama === '' || $email === '') {
            $_SESSION['flash_error'] = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Format email tidak valid.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE users SET nama = ?, email = ? WHERE id = ?');
                $stmt->execute([$nama, $email, $userId]);
                $_SESSION['nama'] = $nama;
                logActivity($pdo, 'Update profil', 'Mengubah nama/email sendiri');
                $_SESSION['flash_success'] = 'Profil berhasil diperbarui.';
            } catch (PDOException $ex) {
                $_SESSION['flash_error'] = str_contains($ex->getMessage(), 'Duplicate')
                    ? 'Email sudah dipakai user lain.'
                    : 'Gagal memperbarui profil.';
            }
        }
        header('Location: profile.php');
        exit;
    }

    if ($formAction === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($current, $hash)) {
            $_SESSION['flash_error'] = 'Password lama tidak sesuai.';
        } elseif (strlen($new) < 6) {
            $_SESSION['flash_error'] = 'Password baru minimal 6 karakter.';
        } elseif ($new !== $confirm) {
            $_SESSION['flash_error'] = 'Konfirmasi password baru tidak cocok.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$newHash, $userId]);
            logActivity($pdo, 'Ganti password', 'Mengganti password sendiri');
            $_SESSION['flash_success'] = 'Password berhasil diubah.';
        }
        header('Location: profile.php');
        exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$me = $stmt->fetch();

require 'includes/header.php';
?>

<div class="two-col">
  <div class="card">
    <h3 class="mt-0">Data Profil</h3>
    <form method="POST" action="profile.php">
      <?= csrfField() ?>
      <input type="hidden" name="form_action" value="update_profile">

      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" value="<?= e($me['nama'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Username</label>
        <input type="text" value="<?= e($me['username'] ?? '') ?>" disabled>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($me['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Role</label>
        <input type="text" value="<?= e(ucfirst($me['role'] ?? '')) ?>" disabled>
      </div>

      <button type="submit" class="btn">Simpan Profil</button>
    </form>
  </div>

  <div class="card">
    <h3 class="mt-0">Ganti Password</h3>
    <form method="POST" action="profile.php">
      <?= csrfField() ?>
      <input type="hidden" name="form_action" value="change_password">

      <div class="form-group">
        <label>Password Saat Ini</label>
        <input type="password" name="current_password" required>
      </div>
      <div class="form-group">
        <label>Password Baru</label>
        <input type="password" name="new_password" placeholder="Minimal 6 karakter" required>
      </div>
      <div class="form-group">
        <label>Konfirmasi Password Baru</label>
        <input type="password" name="confirm_password" required>
      </div>

      <button type="submit" class="btn">Ganti Password</button>
    </form>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
