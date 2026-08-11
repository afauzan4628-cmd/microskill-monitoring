<?php

$DB_HOST = 'localhost';
$DB_NAME = 'db_microskill';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    $pdo = null;
    error_log('Database connection failed: ' . $e->getMessage());
}

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
session_start();
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
function requireRoles(array $allowedRoles) {
    requireLogin();
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        echo '<script>alert("Akses ditolak: role tidak diizinkan!"); window.location.href="login.php";</script>';
        exit;
    }
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}
function verifyCsrfToken($token): bool {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function tooManyLoginAttempts(int $maxAttempts = 5, int $windowSeconds = 300): bool {
    $attempts = $_SESSION['login_attempts'] ?? [];
    $attempts = array_filter($attempts, fn($t) => $t > time() - $windowSeconds);
    $_SESSION['login_attempts'] = array_values($attempts);
    return count($_SESSION['login_attempts']) >= $maxAttempts;
}
function recordFailedLoginAttempt(): void {
    $_SESSION['login_attempts'][] = time();
}
function clearLoginAttempts(): void {
    unset($_SESSION['login_attempts']);
}
function secondsUntilLoginRetry(int $windowSeconds = 300): int {
    if (empty($_SESSION['login_attempts'])) return 0;
    $oldest = min($_SESSION['login_attempts']);
    $remaining = $windowSeconds - (time() - $oldest);
    return max(0, $remaining);
}
?>