<?php
// config.php - Central configuration for the Microskill Monitoring application
// Database connection (MySQL/MariaDB) using PDO
// Adjust credentials as needed for your environment

$DB_HOST = 'localhost';
$DB_NAME = 'microskill_monitoring';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // In a demo environment we can continue without DB (fallback mode)
    $pdo = null;
    error_log('Database connection failed: ' . $e->getMessage());
}

// Use strict session settings for better security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax',
]);
// Start a secure session for authentication
session_start();
// Authentication helper functions
function isLoggedIn(): bool {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
function requireLogin(): void {
    if (!isLoggedIn()) {
        echo '<script>alert("Login terlebih dahulu!"); window.location.href="login.php";</script>';
        exit;
    }
}
function requireAdmin(): void {
    if (!isAdmin()) {
        echo '<script>alert("Akses admin diperlukan!"); window.location.href="login.php";</script>';
        exit;
    }
}
// Require user to have one of the allowed roles
function requireRoles(array $allowedRoles) {
    requireLogin();
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        echo '<script>alert("Akses ditolak: role tidak diizinkan!"); window.location.href="login.php";</script>';
        exit;
    }
}
?>