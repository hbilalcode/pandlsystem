<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();
$pageTitle = $pageTitle ?? 'Dashboard';

// Pull live profile info for the topbar (admin only needs full_name)
$u = $_SESSION['user'] ?? [];
$displayName = $u['full_name'] ?? null;
if (!$displayName) {
    $st = $pdo->prepare("SELECT full_name FROM users WHERE id=?");
    $st->execute([(int)$u['id']]);
    $displayName = $st->fetchColumn() ?: $u['username'];
    $_SESSION['user']['full_name'] = $displayName;
}
$initial = strtoupper(substr($displayName, 0, 1));
$isAdmin = ($u['role'] ?? '') === 'super_admin';
$profileUrl = $isAdmin ? base_url('pages/profile.php') : '#';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(RESTAURANT_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="topbar">
  <div class="brand"><span class="dot"></span> <?= e(RESTAURANT_NAME) ?></div>
  <div class="user">
    <span class="role-pill"><?= e(str_replace('_',' ', $u['role'])) ?></span>
    <?php if ($isAdmin): ?>
      <a href="<?= e($profileUrl) ?>" class="user-chip" title="View profile">
        <span class="avatar"><?= e($initial) ?></span>
        <span class="uname"><?= e($displayName) ?></span>
      </a>
    <?php else: ?>
      <span class="user-chip">
        <span class="avatar"><?= e($initial) ?></span>
        <span class="uname"><?= e($displayName) ?></span>
      </span>
    <?php endif; ?>
    <a class="btn-logout" href="<?= base_url('logout.php') ?>">Logout</a>
  </div>
</nav>
<div class="layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="content">
    <?php if ($m = flash('success')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('error')):   ?><div class="alert alert-danger"><?= e($m) ?></div><?php endif; ?>
