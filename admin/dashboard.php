<?php
require_once __DIR__ . '/auth_middleware.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Microskill Monitoring</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="glass-card">
        <h1 class="title">Admin Dashboard</h1>
        <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</p>
        <p>Ini adalah halaman admin. Tambahkan fitur admin di sini.</p>
        <a href="logout.php" class="btn-primary">Logout</a>
    </div>
</body>
</html>
