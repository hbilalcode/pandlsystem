<?php
// Admin-only "forgot password" flow.
// Investors must ask the admin to reset their password from Investors → Edit.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!valid_email($email)) {
        $err = 'Please enter a valid email address (must contain @).';
    } else {
        $st = $pdo->prepare("SELECT * FROM users WHERE role='super_admin' AND email=? LIMIT 1");
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            $pdo->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?")
                ->execute([$token, $expires, $u['id']]);
            $link = rtrim(APP_URL, '/') . '/reset_password.php?token=' . $token;
            $html = '<div style="font-family:Arial,sans-serif;background:#f4f4f6;padding:24px">'
                  . '<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:22px">'
                  . '<h2 style="margin:0 0 10px;color:#ff3b30">Password Reset</h2>'
                  . '<p>Hi ' . e($u['full_name'] ?? $u['username']) . ',</p>'
                  . '<p>Click the link below to reset your admin password. The link is valid for 1 hour.</p>'
                  . '<p><a href="' . e($link) . '" style="display:inline-block;background:#ff3b30;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none">Reset Password</a></p>'
                  . '<p style="font-size:12px;color:#666">Or copy this URL: ' . e($link) . '</p>'
                  . '<p style="font-size:12px;color:#888">If you did not request this, ignore this email.</p>'
                  . '</div></div>';
            [$ok, $mailErr] = send_html_mail($email, RESTAURANT_NAME . ' — Reset your password', $html);
            // For local/dev usage where mail() is not configured, also expose the link to the admin.
            $msg = $ok
                ? 'A reset link has been emailed to you. Check your inbox.'
                : 'Mail server is not configured. Use this temporary reset link:<br><a href="' . e($link) . '">' . e($link) . '</a>';
        } else {
            // Don't reveal which emails are registered.
            $msg = 'If that email belongs to an admin account, a reset link has been sent.';
        }
    }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Forgot Password · <?= e(RESTAURANT_NAME) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head><body>
<div class="login-wrap">
  <form class="login-card" method="post">
    <h1><span class="dot"></span><?= e(RESTAURANT_NAME) ?></h1>
    <p class="text-muted" style="margin-top:-6px">Reset Admin Password</p>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert alert-info"><?= $msg /* contains safe HTML */ ?></div><?php endif; ?>
    <div class="mb-3"><label>Admin Email</label>
      <input type="email" class="form-control" name="email" required autofocus
             placeholder="you@example.com"></div>
    <button class="btn btn-primary w-100">Send reset link</button>
    <div class="text-center mt-3">
      <a href="login.php" class="small" style="color:#ff3b30;text-decoration:none">← Back to login</a>
    </div>
  </form>
</div>
</body></html>
