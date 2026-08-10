<?php
// admin/auth_middleware.php
// Include this at the top of any admin page to enforce authentication and admin role.
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Not logged in or not admin – redirect to login page
    header('Location: ../login.php');
    exit;
}
?>
