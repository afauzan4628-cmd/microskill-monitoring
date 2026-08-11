<?php
require_once 'config.php';

$error = '';
$success = '';
$old = ['name' => '', 'username' => '', 'email' => ''];

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old['name']     = trim($_POST['name'] ?? '');
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email']    = trim($_POST['email'] ?? '');

    $name             = $old['name'];
    $username         = $old['username'];
    $email            = $old['email'];
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $honeypot         = $_POST['website'] ?? '';

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi form sudah kedaluwarsa. Silakan muat ulang halaman dan coba lagi.';
    } elseif ($honeypot !== '') {
        $error = 'Registrasi gagal. Silakan coba lagi.';
    } elseif ($name === '' || $username === '' || $email === '' || $password === '' || $confirm_password === '') {
        $error = 'Semua field harus diisi.';
    } elseif (strlen($name) < 3 || strlen($name) > 100) {
        $error = 'Nama harus antara 3-100 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        $error = 'Username hanya boleh berisi huruf, angka, dan underscore (4-20 karakter).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi tidak cocok.';
    } elseif (!isset($pdo) || !$pdo) {
        $error = 'Database tidak tersedia. Hubungi administrator.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        $exists = (int) $stmt->fetchColumn();

        if ($exists > 0) {
            $error = 'Username atau email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (nama, username, email, password, role, status) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$name, $username, $email, $hash, 'operator', 'active']);

            unset($_SESSION['csrf_token']);
            $_SESSION['flash_success'] = 'Akun berhasil dibuat. Silakan login.';
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi - Monitoring Microskill</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #6366f1;
        --primary-hover: #4f46e5;
        --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
        --bg: #f4f5f9;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border: #e2e8f0;
        --danger: #e11d48;
        --danger-bg: #fef2f2;
        --success: #059669;
        --success-bg: #ecfdf5;
    }
    * { box-sizing: border-box; }
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--bg);
        background-image: radial-gradient(circle at 15% 15%, rgba(99,102,241,0.12), transparent 45%),
                           radial-gradient(circle at 85% 85%, rgba(217,70,239,0.10), transparent 45%);
        min-height: 100vh;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }
    .auth-card {
        background: #fff;
        width: 100%;
        max-width: 430px;
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.10);
        border: 1px solid var(--border);
        padding: 40px 36px;
        animation: fadeIn .5s ease-out;
    }
    .brand { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 28px; }
    .brand-icon {
        width: 42px; height: 42px; border-radius: 12px;
        background: var(--gradient-primary);
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 15px;
    }
    .brand-text { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 16px; color: var(--text-main); line-height: 1.2; }
    h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 22px; font-weight: 700; text-align: center; color: var(--text-main); margin: 0 0 4px; }
    .subtitle { text-align: center; color: var(--text-muted); font-size: 14px; margin: 0 0 24px; }
    .alert { padding: 12px 14px; border-radius: 10px; font-size: 13.5px; margin-bottom: 18px; }
    .alert-error { background: var(--danger-bg); color: var(--danger); border: 1px solid #fecdd3; }
    .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid #a7f3d0; }
    .field { margin-bottom: 15px; }
    label { display: block; font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 6px; }
    .input-wrap { position: relative; }
    input[type="text"], input[type="email"], input[type="password"] {
        width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: 10px;
        font-size: 14.5px; font-family: inherit; color: var(--text-main); transition: border-color .2s, box-shadow .2s;
    }
    input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,0.12); }
    .toggle-pass {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 13px; padding: 4px;
    }
    .hint { font-size: 12px; color: var(--text-muted); margin-top: 5px; }
    .hint.match-ok { color: var(--success); }
    .hint.match-bad { color: var(--danger); }
    .website-field { position: absolute; left: -9999px; top: -9999px; opacity: 0; height: 0; overflow: hidden; }
    button[type="submit"] {
        width: 100%; padding: 13px; margin-top: 10px; border: none; border-radius: 10px;
        background: var(--gradient-primary); color: #fff; font-size: 15px; font-weight: 600;
        font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: transform .15s, box-shadow .15s;
    }
    button[type="submit"]:hover { transform: translateY(-1px); box-shadow: 0 10px 25px rgba(99,102,241,0.35); }
    .foot-link { text-align: center; margin-top: 22px; font-size: 13.5px; color: var(--text-muted); }
    .foot-link a { color: var(--primary); font-weight: 600; text-decoration: none; }
    .foot-link a:hover { text-decoration: underline; }
    .back-home { text-align: center; margin-top: 14px; }
    .back-home a { font-size: 12.5px; color: var(--text-muted); text-decoration: none; }
    .back-home a:hover { color: var(--primary); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
</style>
</head>
<body>
<div class="auth-card">
    <div class="brand">
        <span class="brand-icon">MS</span>
        <span class="brand-text">Monitoring<br>Microskill</span>
    </div>
    <h1>Buat Akun Baru</h1>
    <p class="subtitle">Daftar sebagai operator untuk mengelola data monitoring</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="registerForm" autocomplete="on">
        <?= csrfField() ?>

        <div class="website-field" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="field">
            <label for="name">Nama Lengkap</label>
            <input type="text" id="name" name="name" placeholder="Nama lengkap Anda" value="<?= htmlspecialchars($old['name'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Huruf, angka, underscore" pattern="[a-zA-Z0-9_]{4,20}" title="4-20 karakter: huruf, angka, underscore" value="<?= htmlspecialchars($old['username'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" required>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="nama@instansi.go.id" value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" autocomplete="new-password" minlength="6" required>
                <button type="button" class="toggle-pass" onclick="togglePassword('password', this)" tabindex="-1">Lihat</button>
            </div>
        </div>
        <div class="field">
            <label for="confirm_password">Konfirmasi Password</label>
            <div class="input-wrap">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password" autocomplete="new-password" minlength="6" required>
                <button type="button" class="toggle-pass" onclick="togglePassword('confirm_password', this)" tabindex="-1">Lihat</button>
            </div>
            <p class="hint" id="matchHint"></p>
        </div>
        <button type="submit">Buat Akun</button>
    </form>

    <p class="foot-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
    <div class="back-home"><a href="index.php">&larr; Kembali ke beranda</a></div>
</div>
<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Sembunyikan';
    } else {
        input.type = 'password';
        btn.textContent = 'Lihat';
    }
}

const pw = document.getElementById('password');
const confirmPw = document.getElementById('confirm_password');
const hint = document.getElementById('matchHint');

function checkMatch() {
    if (!confirmPw.value) { hint.textContent = ''; hint.className = 'hint'; return; }
    if (pw.value === confirmPw.value) {
        hint.textContent = 'Password cocok.';
        hint.className = 'hint match-ok';
    } else {
        hint.textContent = 'Password belum sama.';
        hint.className = 'hint match-bad';
    }
}
pw.addEventListener('input', checkMatch);
confirmPw.addEventListener('input', checkMatch);
</script>
</body>
</html>
