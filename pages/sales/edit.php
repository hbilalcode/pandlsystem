<?php
$pageTitle='Edit Sale';
require_once __DIR__ . '/../../includes/header.php';
require_admin();
$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT * FROM sales WHERE id=?"); $st->execute([$id]); $row=$st->fetch();
if(!$row){ flash('error','Sale not found'); header('Location: index.php'); exit; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $pdo->prepare("UPDATE sales SET sale_date=?, amount=?, notes=? WHERE id=?")
        ->execute([$_POST['sale_date'], (float)$_POST['amount'], trim($_POST['notes']??''), $id]);
    flash('success','Sale updated.');
    header('Location: index.php'); exit;
}
?>
<h1 class="h4 mb-3">Edit Sale</h1>
<form method="post" class="panel" style="max-width:520px">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="mb-3"><label>Date</label><input type="date" name="sale_date" class="form-control" value="<?= e($row['sale_date']) ?>" required></div>
  <div class="mb-3"><label>Total Sales Amount</label><input type="number" step="0.01" min="0" name="amount" class="form-control" value="<?= e($row['amount']) ?>" required></div>
  <div class="mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="3"><?= e($row['notes']) ?></textarea></div>
  <button class="btn btn-primary">Update</button>
  <a class="btn btn-outline" href="index.php">Cancel</a>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
