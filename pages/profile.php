<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_admin();

$uid = (int)$_SESSION['user']['id'];
$err = null;

$st = $pdo->prepare("SELECT * FROM users WHERE id=?");
$st->execute([$uid]);
$me = $st->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $full  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $cur   = $_POST['current_password'] ?? '';
    $new   = $_POST['new_password'] ?? '';
    $new2  = $_POST['new_password2'] ?? '';

    if ($full === '')                       $err = 'Full name is required.';
    elseif (!valid_email($email))           $err = 'Please enter a valid email (must contain @).';
    elseif ($phone !== '' && !valid_pk_phone($phone))
                                            $err = 'Phone must be a valid Pakistan number (11 digits starting with 03).';

    if (!$err && $new !== '') {
        if (!password_verify($cur, $me['password'])) $err = 'Current password is incorrect.';
        elseif (strlen($new) < 6)                    $err = 'New password must be at least 6 characters.';
        elseif ($new !== $new2)                      $err = 'New passwords do not match.';
    }

    if (!$err) {
        if ($new !== '') {
            $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, password=? WHERE id=?")
                ->execute([$full, $email, $phone, password_hash($new, PASSWORD_DEFAULT), $uid]);
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE id=?")
                ->execute([$full, $email, $phone, $uid]);
        }
        $_SESSION['user']['full_name'] = $full;
        flash('success', 'Profile updated successfully.');
        header('Location: profile.php'); exit;
    }
}
?>
<h1 class="h4 mb-3">My Profile</h1>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="row">
  <div class="col-lg-4 mb-3">
    <div class="panel">
      <div style="text-align:center">
        <div style="width:84px;height:84px;border-radius:50%;background:#ff3b30;color:#fff;
                    display:inline-flex;align-items:center;justify-content:center;font-size:32px;font-weight:600">
          <?= e(strtoupper(substr($me['full_name'] ?: $me['username'], 0, 1))) ?>
        </div>
        <h2 class="h5 mt-3 mb-0"><?= e($me['full_name'] ?: $me['username']) ?></h2>
        <div class="text-muted small">Super Admin</div>
        <hr>
        <div class="text-start small">
          <div class="mb-2"><b>Username:</b> <?= e($me['username']) ?></div>
          <div class="mb-2"><b>Email:</b> <?= e($me['email'] ?: '—') ?></div>
          <div class="mb-2"><b>Phone:</b> <?= e($me['phone'] ?: '—') ?></div>
          <div><b>Member since:</b> <?= e(date('d M Y', strtotime($me['created_at']))) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <form method="post" class="panel">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <h2 class="h6">Account Information</h2>
      <div class="row">
        <div class="col-md-6 mb-3"><label>Full Name *</label>
          <input name="full_name" class="form-control" required value="<?= e($me['full_name']) ?>"></div>
        <div class="col-md-6 mb-3"><label>Username</label>
          <input class="form-control" value="<?= e($me['username']) ?>" disabled></div>
        <div class="col-md-6 mb-3"><label>Email *</label>
          <input type="email" name="email" class="form-control" required
                 value="<?= e($me['email']) ?>" placeholder="you@example.com"></div>
        <div class="col-md-6 mb-3"><label>Phone (Pakistan)</label>
          <input name="phone" class="form-control" maxlength="11"
                 pattern="^03[0-9]{9}$" inputmode="numeric"
                 placeholder="03001234567" value="<?= e($me['phone']) ?>">
          <div class="form-text">11 digits, format: 03XXXXXXXXX</div>
        </div>
      </div>

      <hr>
      <h2 class="h6">Change Password <small class="text-muted">(optional)</small></h2>
      <div class="row">
        <div class="col-md-4 mb-3"><label>Current Password</label>
          <input type="password" name="current_password" class="form-control"></div>
        <div class="col-md-4 mb-3"><label>New Password</label>
          <input type="password" name="new_password" class="form-control" minlength="6"></div>
        <div class="col-md-4 mb-3"><label>Confirm New Password</label>
          <input type="password" name="new_password2" class="form-control" minlength="6"></div>
      </div>

      <button class="btn btn-primary">Save Changes</button>
      <a class="btn btn-outline" href="dashboard.php">Back to Dashboard</a>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
