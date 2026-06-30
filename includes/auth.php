<?php
// includes/auth.php
// Session bootstrap + role guards

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function require_admin() {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
        header('Location: ' . base_url('investor/dashboard.php'));
        exit;
    }
}

function require_investor() {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'investor') {
        header('Location: ' . base_url('pages/dashboard.php'));
        exit;
    }
}

function base_url($path = '') {
    // Resolve project base URL (works in subfolders too)
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Walk up to the project root (folder that contains login.php)
    // Simple approach: trim trailing /pages/* or /investor/* or /reports/*
    $script = preg_replace('#/(pages|investor|reports)(/.*)?$#', '', $script);
    if ($script === '' || $script === '/') $script = '';
    return $script . '/' . ltrim($path, '/');
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function verify_csrf() {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        die('Invalid CSRF token');
    }
}

function flash($key, $msg = null) {
    if ($msg === null) {
        $v = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $v;
    }
    $_SESSION['flash'][$key] = $msg;
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n) { return CURRENCY . ' ' . number_format((float)$n, 2); }
