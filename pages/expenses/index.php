<?php
$pageTitle='Expenses';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$q=trim($_GET['q']??''); $from=$_GET['from']??''; $to=$_GET['to']??''; $type=$_GET['type']??'';
$page=max(1,(int)($_GET['page']??1)); $per=15; $off=($page-1)*$per;
$where="WHERE 1=1"; $p=[];
if($q!==''){ $where.=" AND (description LIKE ? OR notes LIKE ?)"; $p[]="%$q%"; $p[]="%$q%"; }
if($from!==''){ $where.=" AND expense_date >= ?"; $p[]=$from; }
if($to!==''){ $where.=" AND expense_date <= ?"; $p[]=$to; }
if($type!==''){ $where.=" AND expense_type = ?"; $p[]=$type; }

$st=$pdo->prepare("SELECT COUNT(*) FROM expenses $where"); $st->execute($p); $total=(int)$st->fetchColumn();
$st=$pdo->prepare("SELECT * FROM expenses $where ORDER BY expense_date DESC, id DESC LIMIT $per OFFSET $off");
$st->execute($p); $rows=$st->fetchAll();
$pages=max(1,(int)ceil($total/$per));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0">Expenses</h1>
  <a class="btn btn-primary" href="add.php">+ New Expense</a>
</div>
<form class="toolbar" method="get">
  <input class="form-control" style="max-width:220px" name="q" value="<?= e($q) ?>" placeholder="Search description / notes...">
  <select name="type" class="form-select" style="max-width:200px">
    <option value="">All types</option>
    <?php foreach (['cashier','vendor','salary','custom'] as $t): ?>
      <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= e(expense_type_label($t)) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" class="form-control" style="max-width:170px" name="from" value="<?= e($from) ?>">
  <input type="date" class="form-control" style="max-width:170px" name="to" value="<?= e($to) ?>">
  <button class="btn btn-outline">Filter</button>
  <a class="btn btn-outline" href="index.php">Reset</a>
</form>
<div class="panel">
  <table class="table">
    <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>Notes</th><th style="width:160px">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['expense_date']) ?></td>
        <td><?= e(expense_type_label($r['expense_type'])) ?></td>
        <td><?= e($r['description']) ?></td>
        <td><?= money($r['amount']) ?></td>
        <td><?= e($r['notes']) ?></td>
        <td>
          <a class="btn-outline" href="edit.php?id=<?= $r['id'] ?>">Edit</a>
          <a class="btn-danger-soft" href="delete.php?id=<?= $r['id'] ?>" data-confirm="Delete this expense?">Delete</a>
        </td>
      </tr>
    <?php endforeach; if(!$rows): ?><tr><td colspan="6" class="text-muted">No expenses found.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <?php if($pages>1): ?>
    <nav><ul class="pagination">
      <?php for($i=1;$i<=$pages;$i++): $qs=$_GET;$qs['page']=$i; ?>
        <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query($qs) ?>"><?= $i ?></a></li>
      <?php endfor; ?>
    </ul></nav>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
