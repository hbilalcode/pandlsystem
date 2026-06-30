<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$err = null; $ok = false;

$st = $pdo->prepare("SELECT * FROM users WHERE reset_token=? AND reset_expires > NOW() LIMIT 1");
$st->execute([$token]);
$user = $st->fetch();

if (!$user) {
    $err = 'This reset link is invalid or has expired. Please request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password2'] ?? '';
    if (strlen($p1) < 6)            $err = 'Password must be at least 6 characters.';
    elseif ($p1 !== $p2)            $err = 'Passwords do not match.';
    else {
        $pdo->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?")
            ->execute([password_hash($p1, PASSWORD_DEFAULT), $user['id']]);
        flash('success', 'Password updated. Please sign in.');
        header('Location: login.php'); exit;
    }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Reset Password · <?= e(RESTAURANT_NAME) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head><body>
<div class="login-wrap">
  <form class="login-card" method="post">
    <h1><span class="dot"></span><?= e(RESTAURANT_NAME) ?></h1>
    <p class="text-muted" style="margin-top:-6px">Set a New Password</p>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
    <?php if ($user): ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="mb-3"><label>New Password</label>
      <input type="password" name="password" class="form-control" minlength="6" required></div>
    <div class="mb-3"><label>Confirm Password</label>
      <input type="password" name="password2" class="form-control" minlength="6" required></div>
    <button class="btn btn-primary w-100">Update password</button>
    <?php else: ?>
    <a href="forgot_password.php" class="btn btn-outline w-100">Request a new link</a>
    <?php endif; ?>
    <div class="text-center mt-3">
      <a href="login.php" class="small" style="color:#ff3b30;text-decoration:none">← Back to login</a>
    </div>
  </form>
</div>
</body></html>
