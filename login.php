<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $user = null;
        if (isset($pdo) && $pdo) {
            $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            // Verify password (supports both `password_hash` and legacy `password` columns)
            if ($user) {
                if (isset($user['password_hash'])) {
                    $valid = password_verify($password, $user['password_hash']);
                } elseif (isset($user['password'])) {
                    // Legacy plain-text or hashed password stored in `password`
                    $valid = password_verify($password, $user['password']);
                } else {
                    $valid = false;
                }
            } else {
                $valid = false;
            }
        }

        if ($valid) {
            // Authentication successful
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi semua field.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
    :root { --primary: #4a90e2; --primary-dark: #357abd; --bg: #f4f7f6; --text: #333; --red: #e74c3c; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: rgba(255,255,255,0.85); padding: 40px; border-radius: 15px; box-shadow: 0 12px 30px rgba(0,0,0,0.15); width: 100%; max-width: 350px; backdrop-filter: blur(10px); animation: fadeIn 0.6s ease-out; }
        .login-box h2 { margin-bottom: 20px; text-align: center; color: var(--text); font-weight: 600; }
        .login-box input { width: 100%; padding: 12px; margin: 12px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; transition: border-color .3s, box-shadow .3s; }
        .login-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(74,144,226,0.2); outline: none; }
        .login-box button { width: 100%; padding: 12px; background: var(--primary); color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 12px; transition: background .3s, transform .2s; }
        .login-box button:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .error { color: var(--red); font-size: 14px; text-align: center; margin-bottom: 12px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
    </style>
</head>
<body>
<div class="login-box">
    <h2>Login</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>