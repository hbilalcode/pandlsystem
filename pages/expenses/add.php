<?php
$pageTitle='Add Expense';
require_once __DIR__ . '/../../includes/header.php';
require_admin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $pdo->prepare("INSERT INTO expenses (expense_date, expense_type, description, amount, notes) VALUES (?,?,?,?,?)")
        ->execute([
            $_POST['expense_date'] ?: date('Y-m-d'),
            $_POST['expense_type'],
            trim($_POST['description'] ?? ''),
            (float)$_POST['amount'],
            trim($_POST['notes'] ?? ''),
        ]);
    flash('success','Expense added.');
    header('Location: index.php'); exit;
}
?>
<h1 class="h4 mb-3">Add Expense</h1>
<form method="post" class="panel" style="max-width:560px">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="row">
    <div class="col-md-6 mb-3"><label>Date</label><input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
    <div class="col-md-6 mb-3"><label>Expense Type</label>
      <select name="expense_type" class="form-select" required>
        <?php foreach (['cashier','vendor','salary','custom'] as $t): ?>
          <option value="<?= $t ?>"><?= e(expense_type_label($t)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="mb-3"><label>Description</label><input name="description" class="form-control"></div>
  <div class="mb-3"><label>Amount</label><input type="number" step="0.01" min="0" name="amount" class="form-control" required></div>
  <div class="mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
  <button class="btn btn-primary">Save</button>
  <a class="btn btn-outline" href="index.php">Cancel</a>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
