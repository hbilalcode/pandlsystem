<?php
$pageTitle='Edit Investor';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM investors WHERE id=?"); $st->execute([$id]);
$row = $st->fetch();
if (!$row) { flash('error','Investor not found'); header('Location: index.php'); exit; }

$err = null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $amt   = (float)($_POST['investment_amount'] ?? 0);
    $share = (float)($_POST['share_percentage'] ?? 0);
    $status= $_POST['status'] ?? 'active';
    $pw    = $_POST['password'] ?? '';

    if ($name === '')                          $err = 'Name is required.';
    elseif (!valid_pk_phone($phone))           $err = 'Phone must be a valid Pakistan number (11 digits starting with 03).';
    elseif (!valid_email($email))              $err = 'Please enter a valid email (must contain @).';
    elseif ($amt < 0 || $share <= 0 || $share > 100) $err = 'Amount/share is invalid.';
    elseif ($pw !== '' && strlen($pw) < 6)     $err = 'New password must be at least 6 characters.';
    else {
        $existingActive = total_share_percentage($pdo, $id);
        if ($status==='active' && ($existingActive + $share) > 100.0001) {
            $err = "Total active share % would exceed 100 (other active investors: ".number_format($existingActive,2)."%).";
        }
    }

    if (!$err) {
        $pdo->prepare("UPDATE investors SET name=?, phone=?, email=?, investment_amount=?, share_percentage=?, status=? WHERE id=?")
            ->execute([$name,$phone,$email,$amt,$share,$status,$id]);
        // Sync investor's user row
        if ($pw !== '') {
            $pdo->prepare("UPDATE users SET password=?, full_name=?, email=?, phone=? WHERE investor_id=?")
                ->execute([password_hash($pw, PASSWORD_DEFAULT), $name, $email, $phone, $id]);
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE investor_id=?")
                ->execute([$name, $email, $phone, $id]);
        }
        flash('success','Investor updated.');
        header('Location: index.php'); exit;
    }
    // Re-show edited values on error
    $row['name']=$name; $row['phone']=$phone; $row['email']=$email;
    $row['investment_amount']=$amt; $row['share_percentage']=$share; $row['status']=$status;
}
?>
<h1 class="h4 mb-3">Edit Investor</h1>
<?php if($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="panel" style="max-width:760px">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="row">
    <div class="col-md-6 mb-3"><label>Full Name *</label>
      <input name="name" class="form-control" value="<?= e($row['name']) ?>" required></div>
    <div class="col-md-6 mb-3"><label>Username (read-only)</label>
      <input class="form-control" value="<?= e($row['username']) ?>" disabled></div>
    <div class="col-md-6 mb-3"><label>Reset Password (leave blank to keep)</label>
      <input type="password" name="password" class="form-control" minlength="6">
      <div class="form-text">Set a new password for this investor's login.</div></div>
    <div class="col-md-6 mb-3"><label>Status</label>
      <select name="status" class="form-select">
        <option value="active"   <?= $row['status']==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $row['status']==='inactive'?'selected':'' ?>>Inactive</option>
      </select></div>
    <div class="col-md-6 mb-3"><label>Phone (Pakistan) *</label>
      <input name="phone" class="form-control" required maxlength="11" inputmode="numeric"
             pattern="^03[0-9]{9}$" placeholder="03001234567" value="<?= e($row['phone']) ?>">
      <div class="form-text">11 digits starting with 03.</div></div>
    <div class="col-md-6 mb-3"><label>Email *</label>
      <input type="email" name="email" class="form-control" required value="<?= e($row['email']) ?>"
             placeholder="name@example.com">
      <div class="form-text">Must contain "@".</div></div>
    <div class="col-md-6 mb-3"><label>Investment Amount *</label>
      <input type="number" step="0.01" min="0" name="investment_amount" class="form-control" required
             value="<?= e($row['investment_amount']) ?>"></div>
    <div class="col-md-6 mb-3"><label>Share Percentage *</label>
      <input type="number" step="0.01" min="0.01" max="100" name="share_percentage" class="form-control" required
             value="<?= e($row['share_percentage']) ?>"></div>
  </div>
  <button class="btn btn-primary">Update</button>
  <a class="btn btn-outline" href="index.php">Cancel</a>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
