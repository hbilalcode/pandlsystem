<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $st = $pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    $st->execute([$u]);
    $user = $st->fetch();
    if ($user && password_verify($p, $user['password'])) {
        if ($user['role'] === 'investor' && $user['investor_id']) {
            $inv = $pdo->prepare("SELECT status FROM investors WHERE id=?");
            $inv->execute([$user['investor_id']]);
            if (($inv->fetchColumn() ?? 'inactive') !== 'active') {
                $error = 'Account is inactive. Contact admin.';
            }
        }
        if (!$error) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = [
                'id' => $user['id'], 'username' => $user['username'],
                'role' => $user['role'], 'investor_id' => $user['investor_id'],
                'full_name' => $user['full_name'] ?? null,
            ];
            header('Location: ' . ($user['role'] === 'super_admin' ? 'pages/dashboard.php' : 'investor/dashboard.php'));
            exit;
        }
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Login · <?= e(RESTAURANT_NAME) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head><body>
<div class="login-wrap">
  <form class="login-card" method="post">
    <h1><span class="dot"></span><?= e(RESTAURANT_NAME) ?></h1>
    <p class="text-muted" style="margin-top:-6px">Profit &amp; Loss Management</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($m = flash('success')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>
    <div class="mb-3"><label>Username</label>
      <input class="form-control" name="username" required autofocus></div>
    <div class="mb-2"><label>Password</label>
      <input type="password" class="form-control" name="password" required></div>
    <div class="d-flex justify-content-end mb-3">
      <a href="forgot_password.php" class="small" style="color:#ff3b30;text-decoration:none">Forgot password?</a>
    </div>
    <button class="btn btn-primary w-100">Sign in</button>
    <p class="text-muted small mt-3 mb-0" style="text-align:center">
      Investors: ask the admin to reset your password.
    </p>
  </form>
</div>
</body></html>
