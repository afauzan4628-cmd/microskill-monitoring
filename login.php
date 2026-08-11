<?php
require_once 'config.php';

$error = '';
$success = '';
$lockedOut = false;

if (!empty($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi form sudah kedaluwarsa. Silakan coba lagi.';
    } elseif (tooManyLoginAttempts()) {
        $lockedOut = true;
        $wait = secondsUntilLoginRetry();
        $error = 'Terlalu banyak percobaan login gagal. Coba lagi dalam ' . ceil($wait / 60) . ' menit.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Harap isi semua field.';
        } else {
            $valid = false;
            $user  = null;

            if (isset($pdo) && $pdo) {
                $stmt = $pdo->prepare('SELECT id, nama, username, email, password, role, status FROM users WHERE username = ? OR email = ?');
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();

                if ($user && !empty($user['password'])) {
                    if ($user['status'] !== 'aktif') {
                        $error = 'Akun Anda tidak aktif. Hubungi administrator.';
                    } else {
                        $valid = password_verify($password, $user['password']);
                    }
                }
            } else {
                $error = 'Koneksi database tidak tersedia. Hubungi administrator.';
            }

            if ($valid) {
                session_regenerate_id(true);
                clearLoginAttempts();

                $_SESSION['loggedin'] = true;
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['nama']     = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                header('Location: index.php');
                exit;
            } elseif (!$error) {
                recordFailedLoginAttempt();
                $error = 'Username/email atau password salah.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Monitoring Microskill</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #6366f1;
        --primary-hover: #4f46e5;
        --primary-light: #818cf8;
        --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
        --bg: #f4f5f9;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border: #e2e8f0;
        --danger: #e11d48;
        --danger-bg: #fef2f2;
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
        max-width: 400px;
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
    .alert-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .field { margin-bottom: 16px; }
    label { display: block; font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 6px; }
    .input-wrap { position: relative; }
    input[type="text"], input[type="password"] {
        width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: 10px;
        font-size: 14.5px; font-family: inherit; color: var(--text-main); transition: border-color .2s, box-shadow .2s;
    }
    input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,0.12); }
    .toggle-pass {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 13px; padding: 4px;
    }
    button[type="submit"] {
        width: 100%; padding: 13px; margin-top: 6px; border: none; border-radius: 10px;
        background: var(--gradient-primary); color: #fff; font-size: 15px; font-weight: 600;
        font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: transform .15s, box-shadow .15s;
    }
    button[type="submit"]:hover { transform: translateY(-1px); box-shadow: 0 10px 25px rgba(99,102,241,0.35); }
    button[disabled] { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }
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
    <h1>Selamat Datang Kembali</h1>
    <p class="subtitle">Masuk untuk mengelola data monitoring peserta</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="on">
        <?= csrfField() ?>
        <div class="field">
            <label for="username">Username atau Email</label>
            <input type="text" id="username" name="username" placeholder="Masukkan username atau email" autocomplete="username" required autofocus <?= $lockedOut ? 'disabled' : '' ?>>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required <?= $lockedOut ? 'disabled' : '' ?>>
                <button type="button" class="toggle-pass" onclick="togglePassword()" tabindex="-1">Lihat</button>
            </div>
        </div>
        <button type="submit" <?= $lockedOut ? 'disabled' : '' ?>>Masuk</button>
    </form>

    <p class="foot-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    <div class="back-home"><a href="index.php">&larr; Kembali ke beranda</a></div>
</div>
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const btn = document.querySelector('.toggle-pass');
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Sembunyikan';
    } else {
        input.type = 'password';
        btn.textContent = 'Lihat';
    }
}
</script>
</body>
</html>
