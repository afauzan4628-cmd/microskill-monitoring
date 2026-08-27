<?php
require_once 'config.php';
requireRoles(['admin']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Pengaturan Sistem';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Sesi tidak valid, silakan coba lagi.';
        header('Location: settings.php');
        exit;
    }

    $appName  = trim($_POST['app_name'] ?? '');
    $email    = trim($_POST['kontak_email'] ?? '');
    $wa       = trim($_POST['kontak_wa'] ?? '');
    $perPage  = (int)($_POST['items_per_page'] ?? 10);

    if ($appName === '') {
        $_SESSION['flash_error'] = 'Nama aplikasi tidak boleh kosong.';
        header('Location: settings.php');
        exit;
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_error'] = 'Format email kontak tidak valid.';
        header('Location: settings.php');
        exit;
    }
    if ($perPage < 5 || $perPage > 100) $perPage = 10;

    setSetting($pdo, 'app_name', $appName);
    setSetting($pdo, 'kontak_email', $email);
    setSetting($pdo, 'kontak_wa', $wa);
    setSetting($pdo, 'items_per_page', (string)$perPage);

    logActivity($pdo, 'Ubah pengaturan sistem', "app_name=$appName");
    $_SESSION['flash_success'] = 'Pengaturan berhasil disimpan.';
    header('Location: settings.php');
    exit;
}

$appName    = getSetting($pdo, 'app_name', 'Monitoring Microskill Digital Talent');
$kontakEmail = getSetting($pdo, 'kontak_email', '');
$kontakWa    = getSetting($pdo, 'kontak_wa', '');
$itemsPerPage = getSetting($pdo, 'items_per_page', '10');

require 'includes/header.php';
?>

<div class="card" style="max-width:560px;">
  <h3 class="mt-0">Pengaturan Sistem</h3>
  <p class="text-muted">Pengaturan umum aplikasi. Perubahan berlaku langsung untuk semua user.</p>

  <form method="POST" action="settings.php">
    <?= csrfField() ?>

    <div class="form-group">
      <label>Nama Aplikasi</label>
      <input type="text" name="app_name" value="<?= e($appName) ?>" required>
    </div>

    <div class="form-group">
      <label>Email Kontak</label>
      <input type="email" name="kontak_email" value="<?= e($kontakEmail) ?>" placeholder="admin@instansi.go.id">
    </div>

    <div class="form-group">
      <label>Nomor WhatsApp Kontak</label>
      <input type="text" name="kontak_wa" value="<?= e($kontakWa) ?>" placeholder="08xxxxxxxxxx">
    </div>

    <div class="form-group">
      <label>Jumlah Data per Halaman (Tabel)</label>
      <input type="text" name="items_per_page" value="<?= e($itemsPerPage) ?>" placeholder="10">
    </div>

    <button type="submit" class="btn">Simpan Pengaturan</button>
  </form>
</div>

<?php require 'includes/footer.php'; ?>
