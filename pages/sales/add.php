<?php
$pageTitle='Add Sale';
require_once __DIR__ . '/../../includes/header.php';
require_admin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $date = $_POST['sale_date'] ?: date('Y-m-d');
    $amt  = (float)$_POST['amount'];
    $notes= trim($_POST['notes'] ?? '');
    $pdo->prepare("INSERT INTO sales (sale_date, amount, notes) VALUES (?,?,?)")->execute([$date,$amt,$notes]);
    flash('success','Sale added.');
    header('Location: index.php'); exit;
}
?>
<h1 class="h4 mb-3">Add Sale</h1>
<form method="post" class="panel" style="max-width:520px">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="mb-3"><label>Date</label><input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
  <div class="mb-3"><label>Total Sales Amount</label><input type="number" step="0.01" min="0" name="amount" class="form-control" required></div>
  <div class="mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
  <button class="btn btn-primary">Save</button>
  <a class="btn btn-outline" href="index.php">Cancel</a>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
