<?php
$pageTitle='Add Investor';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$err = null;
$old = ['name'=>'','username'=>'','phone'=>'','email'=>'','investment_amount'=>'','share_percentage'=>'','status'=>'active'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $old = [
      'name'=>trim($_POST['name']??''),
      'username'=>trim($_POST['username']??''),
      'phone'=>trim($_POST['phone']??''),
      'email'=>trim($_POST['email']??''),
      'investment_amount'=>$_POST['investment_amount']??'',
      'share_percentage'=>$_POST['share_percentage']??'',
      'status'=>$_POST['status']??'active',
    ];
    $password = $_POST['password'] ?? '';
    $amt   = (float)$old['investment_amount'];
    $share = (float)$old['share_percentage'];

    if ($old['name']==='' || $old['username']==='')      $err = 'Name and username are required.';
    elseif (strlen($password) < 6)                       $err = 'Password must be at least 6 characters.';
    elseif (!valid_pk_phone($old['phone']))              $err = 'Phone must be a valid Pakistan number (11 digits starting with 03, e.g. 03001234567).';
    elseif (!valid_email($old['email']))                 $err = 'Please enter a valid email address (must contain @).';
    elseif ($amt < 0 || $share <= 0 || $share > 100)     $err = 'Investment amount and share percentage must be valid.';
    else {
        $existing = $old['status']==='active' ? total_share_percentage($pdo) : 0;
        if ($old['status']==='active' && ($existing + $share) > 100.0001) {
            $err = "Total active share % would exceed 100 (current active total: ".number_format($existing,2)."%).";
        }
    }

    if (!$err) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO investors (name,username,phone,email,investment_amount,share_percentage,status)
                VALUES (?,?,?,?,?,?,?)")
                ->execute([$old['name'],$old['username'],$old['phone'],$old['email'],$amt,$share,$old['status']]);
            $invId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO users (username,password,role,investor_id,full_name,email,phone)
                           VALUES (?,?, 'investor', ?, ?, ?, ?)")
                ->execute([$old['username'], password_hash($password, PASSWORD_DEFAULT), $invId,
                           $old['name'], $old['email'], $old['phone']]);
            $pdo->commit();
            flash('success','Investor created.');
            header('Location: index.php'); exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = 'Could not save: ' . $e->getMessage();
        }
    }
}
?>
<h1 class="h4 mb-3">Add Investor</h1>
<?php if($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="panel" style="max-width:760px">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="row">
    <div class="col-md-6 mb-3"><label>Full Name *</label>
      <input name="name" class="form-control" required value="<?= e($old['name']) ?>"></div>
    <div class="col-md-6 mb-3"><label>Username *</label>
      <input name="username" class="form-control" required value="<?= e($old['username']) ?>"></div>
    <div class="col-md-6 mb-3"><label>Password *</label>
      <input type="password" name="password" class="form-control" required minlength="6">
      <div class="form-text">Minimum 6 characters.</div></div>
    <div class="col-md-6 mb-3"><label>Status</label>
      <select name="status" class="form-select">
        <option value="active"   <?= $old['status']==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $old['status']==='inactive'?'selected':'' ?>>Inactive</option>
      </select></div>
    <div class="col-md-6 mb-3"><label>Phone (Pakistan) *</label>
      <input name="phone" class="form-control" required maxlength="11" inputmode="numeric"
             pattern="^03[0-9]{9}$" placeholder="03001234567" value="<?= e($old['phone']) ?>">
      <div class="form-text">11 digits starting with 03 (e.g. 03001234567).</div></div>
    <div class="col-md-6 mb-3"><label>Email *</label>
      <input type="email" name="email" class="form-control" required
             placeholder="name@example.com" value="<?= e($old['email']) ?>">
      <div class="form-text">Must contain "@" (e.g. name@example.com).</div></div>
    <div class="col-md-6 mb-3"><label>Investment Amount *</label>
      <input type="number" step="0.01" min="0" name="investment_amount" class="form-control" required
             value="<?= e($old['investment_amount']) ?>"></div>
    <div class="col-md-6 mb-3"><label>Share Percentage *</label>
      <input type="number" step="0.01" min="0.01" max="100" name="share_percentage" class="form-control" required
             value="<?= e($old['share_percentage']) ?>"></div>
  </div>
  <button class="btn btn-primary">Save</button>
  <a class="btn btn-outline" href="index.php">Cancel</a>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
