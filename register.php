<?php
require_once 'config.php';

$error = '';
$success = '';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    // Already logged in, redirect to dashboard
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name               = trim($_POST['name'] ?? '');
    $username           = trim($_POST['username'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $password           = $_POST['password'] ?? '';
    $confirm_password   = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi tidak cocok.';
    } else {
        // Check if username or email already exists
        if (isset($pdo) && $pdo) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            $exists = (int) $stmt->fetchColumn();
            if ($exists > 0) {
                $error = 'Username atau email sudah terdaftar.';
            } else {
                // Insert new operator account (role = operator)
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?,?,?,?)');
                $stmt->execute([$username, $email, $hash, 'operator']);
                $success = 'Akun berhasil dibuat. Silakan login.';
                if ($success) {
                    header('Location: login.php');
                    exit;
                }
            }
        } else {
            $error = 'Database tidak tersedia.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi</title>
    <style>
        :root { --primary: #4a90e2; --primary-dark: #357abd; --bg: #f4f7f6; --text: #333; --red: #e74c3c; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .register-box { background: rgba(255,255,255,0.85); padding: 40px; border-radius: 15px; box-shadow: 0 12px 30px rgba(0,0,0,0.15); width: 100%; max-width: 380px; backdrop-filter: blur(10px); animation: fadeIn 0.6s ease-out; }
        .register-box h2 { margin-bottom: 20px; text-align: center; color: var(--text); font-weight: 600; }
        .register-box input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; transition: border-color .3s, box-shadow .3s; }
        .register-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(74,144,226,0.2); outline: none; }
        .register-box button { width: 100%; padding: 12px; background: var(--primary); color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 12px; transition: background .3s, transform .2s; }
        .register-box button:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .msg { margin: 10px 0; text-align: center; font-size: 14px; }
        .error { color: var(--red); }
        .success { color: green; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
    </style>
</head>
<body>
<div class="register-box">
    <h2>Registrasi Akun</h2>
    <?php if ($error): ?>
        <p class="msg error"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($success): ?>
        <p class="msg success"><?php echo htmlspecialchars($success); ?></p>
        <p class="msg"><a href="login.php">Login Sekarang</a></p>
    <?php endif; ?>
    <form method="POST" action="register.php">
        <input type="text" name="name" placeholder="Nama" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Konfirmasi Password" required>
        <button type="submit">Buat Akun</button>
    </form>
</div>
</body>
</html>
