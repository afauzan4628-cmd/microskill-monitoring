<?php
require_once 'config.php';
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Cek Status Peserta';

$result = null;
$error  = null;
$num1 = random_int(1, 9);
$num2 = random_int(1, 9);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyword  = trim($_POST['keyword'] ?? '');
    $captcha  = trim($_POST['captcha'] ?? '');
    $expected = (int)($_POST['captcha_expected'] ?? -1);

    if ($captcha === '' || (int)$captcha !== $expected) {
        $error = 'Jawaban verifikasi salah. Silakan coba lagi.';
    } elseif ($keyword === '') {
        $error = 'Masukkan email atau NIP terlebih dahulu.';
    } else {
        $stmt = $pdo->prepare("
            SELECT p.nama_lengkap, p.nip, p.email_user, p.instansi_pemerintahan,
                   r.tema_microskill, r.tanggal_penyelesaian, r.sertifikat
            FROM tb_pendaftar p
            LEFT JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
            WHERE p.email_user = ? OR p.nip = ?
            ORDER BY r.tanggal_penyelesaian DESC
        ");
        $stmt->execute([$keyword, $keyword]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            $error = 'Data tidak ditemukan. Pastikan email/NIP yang dimasukkan sesuai dengan saat pendaftaran.';
        } else {
            $result = $rows;
        }
    }
    $num1 = random_int(1, 9);
    $num2 = random_int(1, 9);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cek Status Peserta - Monitoring Microskill</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div style="max-width:640px;margin:40px auto;padding:0 20px;">
  <div class="card">
    <h2 class="mt-0">🔎 Cek Status Penyelesaian Microskill</h2>
    <p class="text-muted">Masukkan email atau NIP yang Anda gunakan saat mendaftar untuk melihat status penyelesaian Microskill Anda.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="cek_status.php">
      <div class="form-group">
        <label>Email atau NIP</label>
        <input type="text" name="keyword" value="<?= e($_POST['keyword'] ?? '') ?>" placeholder="contoh@email.com atau 198xxxxxxxxxxx" required>
      </div>
      <div class="form-group">
        <label>Verifikasi: berapa hasil <?= $num1 ?> + <?= $num2 ?> ?</label>
        <input type="text" name="captcha" placeholder="Jawaban" required>
        <input type="hidden" name="captcha_expected" value="<?= $num1 + $num2 ?>">
      </div>
      <button type="submit" class="btn">Cek Status</button>
    </form>
  </div>

  <?php if ($result): ?>
    <div class="card">
      <h3 class="mt-0">Halo, <?= e($result[0]['nama_lengkap']) ?> 👋</h3>
      <p class="text-muted">Instansi: <?= e($result[0]['instansi_pemerintahan'] ?: '-') ?></p>

      <div class="table-wrap">
        <table>
          <tr>
            <th>Tema Microskill</th>
            <th>Status</th>
            <th>Tanggal Selesai</th>
            <th>Sertifikat</th>
          </tr>
          <?php foreach ($result as $row): ?>
            <tr>
              <td><?= $row['tema_microskill'] ? e($row['tema_microskill']) : '-' ?></td>
              <td>
                <?php if (!empty($row['tema_microskill'])): ?>
                  <span class="badge badge-sudah">Sudah Selesai</span>
                <?php else: ?>
                  <span class="badge badge-belum">Belum Menyelesaikan</span>
                <?php endif; ?>
              </td>
              <td><?= $row['tanggal_penyelesaian'] ? formatTanggalIndo($row['tanggal_penyelesaian']) : '-' ?></td>
              <td>
                <?php if (!empty($row['sertifikat'])): ?>
                  <a href="<?= e($row['sertifikat']) ?>" target="_blank">Unduh</a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <p class="text-muted" style="text-align:center;margin-top:16px;">
    <a href="login.php">Login sebagai Admin/Operator</a>
  </p>
</div>
</body>
</html>
