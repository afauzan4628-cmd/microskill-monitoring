<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>Monitoring Microskill</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="wrapper">

  <aside class="sidebar">
    <div class="brand">
      <span class="brand-icon">MS</span>
      <span class="brand-text">Monitoring<br>Microskill</span>
    </div>
    <nav class="menu">
<?php if (isLoggedIn()): ?>
        <a href="index.php">🏠 Halaman Utama</a>
        <a href="dashboard.php" class="<?= $current=='dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="import_monitoring.php" class="<?= in_array($current, ['import_monitoring.php','preview_monitoring.php','proses_import_monitoring.php']) ? 'active' : '' ?>">⬆️ Import Data</a>
        <a href="monitoring.php" class="<?= $current=='monitoring.php' ? 'active' : '' ?>">🧭 Monitoring peserta</a>
        <a href="responses.php" class="<?= $current=='responses.php' ? 'active' : '' ?>">✅ Responses</a>
        <a href="pendaftar.php" class="<?= $current=='pendaftar.php' ? 'active' : '' ?>">👤 Pendaftar</a>
        <a href="laporan.php" class="<?= $current=='laporan.php' ? 'active' : '' ?>">📄 Laporan</a>
        <a href="export.php" class="<?= $current=='export.php' ? 'active' : '' ?>">📤 Export laporan</a>
        <?php if (isAdmin()): ?>
            <a href="manage_users.php" class="<?= $current=='manage_users.php' ? 'active' : '' ?>">👥 Tambah/Edit/Hapus user</a>
            <a href="change_role.php" class="<?= $current=='change_role.php' ? 'active' : '' ?>">🔑 Ubah role user</a>
            <a href="activity_log.php" class="<?= $current=='activity_log.php' ? 'active' : '' ?>">📜 Lihat log aktivitas</a>
            <a href="settings.php" class="<?= $current=='settings.php' ? 'active' : '' ?>">⚙️ Pengaturan sistem</a>
        <?php endif; ?>
    <?php else: ?>
        <a href="login.php" class="<?= $current=='login.php' ? 'active' : '' ?>">🔑 Login</a>
        <a href="register.php" class="<?= $current=='register.php' ? 'active' : '' ?>">🛡️ Register</a>
    <?php endif; ?>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <h1><?= isset($page_title) ? htmlspecialchars($page_title) : 'Monitoring Microskill' ?></h1>
      <?php if (isLoggedIn()): ?>
        <div class="topbar-user">
          <div class="user-chip">
            <span class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></span>
            <span class="user-info">
              <span class="user-name"><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></span>
              <span class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </span>
          </div>
          <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?');">Logout</a>
        </div>
      <?php endif; ?>
    </header>
    <main class="content">
      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
      <?php endif; ?>
