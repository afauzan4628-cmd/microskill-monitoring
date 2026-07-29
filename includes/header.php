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
      <a href="index.php" class="<?= $current=='index.php' ? 'active' : '' ?>">🏠 Halaman Utama</a>
      <a href="dashboard.php" class="<?= $current=='dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
      <a href="import_monitoring.php" class="<?= in_array($current, ['import_monitoring.php','preview_monitoring.php','proses_import_monitoring.php']) ? 'active' : '' ?>">⬆️ Import Data</a>
      <a href="monitoring.php" class="<?= $current=='monitoring.php' ? 'active' : '' ?>">🧭 Monitoring</a>
      <a href="responses.php" class="<?= $current=='responses.php' ? 'active' : '' ?>">✅ Responses</a>
      <a href="pendaftar.php" class="<?= $current=='pendaftar.php' ? 'active' : '' ?>">👥 Pendaftar</a>
      <a href="laporan.php" class="<?= $current=='laporan.php' ? 'active' : '' ?>">📄 Laporan</a>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <h1><?= isset($page_title) ? htmlspecialchars($page_title) : 'Monitoring Microskill' ?></h1>
    </header>
    <main class="content">
      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
      <?php endif; ?>
