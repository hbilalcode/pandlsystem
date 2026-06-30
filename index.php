<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    $role = $_SESSION['user']['role'];
    header('Location: ' . ($role === 'super_admin' ? 'pages/dashboard.php' : 'investor/dashboard.php'));
} else {
    header('Location: login.php');
}
exit;
