<?php
require_once 'config.php';
requireRoles(['admin','operator']);
require 'config/database.php';
require 'includes/functions.php';
$page_title = 'Reminder Peserta';

$appName = getSetting($pdo, 'app_name', 'Monitoring Microskill Digital Talent');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Sesi tidak valid, silakan coba lagi.';
        header('Location: reminder.php');
        exit;
    }

    $emails  = $_POST['emails'] ?? [];
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($emails)) {
        $_SESSION['flash_error'] = 'Pilih minimal satu peserta.';
        header('Location: reminder.php');
        exit;
    }
    if ($subject === '' || $message === '') {
        $_SESSION['flash_error'] = 'Subjek dan isi pesan wajib diisi.';
        header('Location: reminder.php');
        exit;
    }

    $sukses = 0; $gagal = 0;
    $headers = "From: no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\nContent-Type: text/plain; charset=UTF-8";

    foreach ($emails as $rawEntry) {
        [$email, $nama] = array_pad(explode('|', $rawEntry, 2), 2, '');
        $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        if (!$email) { $gagal++; continue; }

        $personalized = str_replace('{nama}', $nama !== '' ? $nama : 'Peserta', $message);
        $sent = @mail($email, $subject, $personalized, $headers);

        $stmt = $pdo->prepare('INSERT INTO tb_reminder_log (email_user, nama_lengkap, sent_by, status) VALUES (?,?,?,?)');
        $stmt->execute([$email, $nama, $_SESSION['username'] ?? null, $sent ? 'sukses' : 'gagal']);

        if ($sent) { $sukses++; } else { $gagal++; }
    }

    logActivity($pdo, 'Kirim reminder', "Sukses: $sukses, Gagal: $gagal");

    if ($sukses > 0) {
        $_SESSION['flash_success'] = "Reminder terkirim ke $sukses peserta." . ($gagal > 0 ? " ($gagal gagal terkirim, cek konfigurasi SMTP server.)" : '');
    } else {
        $_SESSION['flash_error'] = 'Semua reminder gagal terkirim. Pastikan server sudah dikonfigurasi untuk mengirim email (SMTP).';
    }
    header('Location: reminder.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$where  = "WHERE r.id IS NULL AND p.email_user IS NOT NULL AND p.email_user != ''";
$params = [];
if ($search !== '') {
    $where .= " AND (p.nama_lengkap LIKE ? OR p.email_user LIKE ? OR p.instansi_pemerintahan LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$stmt = $pdo->prepare("
    SELECT p.nama_lengkap, p.email_user, p.instansi_pemerintahan,
           (SELECT MAX(created_at) FROM tb_reminder_log WHERE email_user = p.email_user) AS last_reminder
    FROM tb_pendaftar p
    LEFT JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
    $where
    ORDER BY p.nama_lengkap ASC
    LIMIT 300
");
$stmt->execute($params);
$belumList = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="card">
  <h3 class="mt-0">Kirim Reminder ke Peserta yang Belum Menyelesaikan</h3>
  <p class="text-muted">
    Pengiriman menggunakan fungsi email bawaan server (PHP <code>mail()</code>). Pastikan hosting/server Anda sudah dikonfigurasi SMTP,
    kalau tidak, email tidak akan benar-benar terkirim meski tercatat di sistem.
  </p>

  <form method="GET" action="reminder.php" class="toolbar">
    <div class="form-group">
      <label>Cari</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Nama, email, atau instansi...">
    </div>
    <button type="submit" class="btn btn-sm">Cari</button>
    <?php if ($search !== ''): ?>
      <a href="reminder.php" class="btn btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <form method="POST" action="reminder.php" id="reminderForm">
    <?= csrfField() ?>

    <div class="form-group">
      <label>Subjek Email</label>
      <input type="text" name="subject" value="Pengingat: Selesaikan Microskill Anda" required>
    </div>
    <div class="form-group">
      <label>Isi Pesan (gunakan <code>{nama}</code> untuk personalisasi nama peserta)</label>
      <textarea name="message" rows="5" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;" required>Halo {nama},

Kami informasikan bahwa Anda belum menyelesaikan program Microskill. Mohon segera diselesaikan sebelum batas waktu yang ditentukan.

Terima kasih,
<?= e($appName) ?></textarea>
    </div>

    <p class="text-muted">Terpilih: <span id="selectedCount">0</span> peserta</p>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox" id="checkAll"></th>
            <th>Nama</th>
            <th>Email</th>
            <th>Instansi</th>
            <th>Reminder Terakhir</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($belumList)): ?>
            <tr><td colspan="5" class="text-muted">Tidak ada peserta yang belum menyelesaikan (atau semua sudah punya email).</td></tr>
          <?php else: foreach ($belumList as $b): ?>
            <tr>
              <td><input type="checkbox" name="emails[]" class="row-check" value="<?= e($b['email_user'] . '|' . $b['nama_lengkap']) ?>"></td>
              <td><?= e($b['nama_lengkap']) ?></td>
              <td><?= e($b['email_user']) ?></td>
              <td><?= e($b['instansi_pemerintahan']) ?></td>
              <td class="text-muted"><?= $b['last_reminder'] ? date('d M Y H:i', strtotime($b['last_reminder'])) : 'Belum pernah' ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <button type="submit" class="btn" style="margin-top:14px;" onclick="return confirm('Kirim reminder ke peserta terpilih?');">📧 Kirim Reminder</button>
  </form>
</div>

<script>
const checkAll = document.getElementById('checkAll');
const rowChecks = document.querySelectorAll('.row-check');
const selectedCount = document.getElementById('selectedCount');

function updateCount() {
  selectedCount.textContent = document.querySelectorAll('.row-check:checked').length;
}
checkAll.addEventListener('change', () => {
  rowChecks.forEach(cb => cb.checked = checkAll.checked);
  updateCount();
});
rowChecks.forEach(cb => cb.addEventListener('change', updateCount));
</script>

<?php require 'includes/footer.php'; ?>
