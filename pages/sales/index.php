<?php
$pageTitle = 'Sales';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$q     = trim($_GET['q'] ?? '');
$from  = $_GET['from'] ?? '';
$to    = $_GET['to'] ?? '';
$page  = max(1, (int)($_GET['page'] ?? 1));
$per   = 15; $off = ($page - 1) * $per;

$where = "WHERE 1=1"; $params = [];
if ($q !== '')   { $where .= " AND notes LIKE ?"; $params[] = "%$q%"; }
if ($from !== ''){ $where .= " AND sale_date >= ?"; $params[] = $from; }
if ($to !== '')  { $where .= " AND sale_date <= ?"; $params[] = $to; }

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM sales $where")->execute($params) ?:
         (int)$pdo->prepare("SELECT COUNT(*) FROM sales $where");
$st = $pdo->prepare("SELECT COUNT(*) FROM sales $where"); $st->execute($params);
$total = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT * FROM sales $where ORDER BY sale_date DESC, id DESC LIMIT $per OFFSET $off");
$st->execute($params);
$rows = $st->fetchAll();
$pages = max(1, (int)ceil($total / $per));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0">Sales</h1>
  <a class="btn btn-primary" href="add.php">+ New Sale</a>
</div>
<form class="toolbar" method="get">
  <input class="form-control" style="max-width:220px" name="q" value="<?= e($q) ?>" placeholder="Search notes...">
  <input type="date" class="form-control" style="max-width:170px" name="from" value="<?= e($from) ?>">
  <input type="date" class="form-control" style="max-width:170px" name="to" value="<?= e($to) ?>">
  <button class="btn btn-outline">Filter</button>
  <a class="btn btn-outline" href="index.php">Reset</a>
</form>
<div class="panel">
  <table class="table">
    <thead><tr><th>Date</th><th>Amount</th><th>Notes</th><th style="width:160px">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['sale_date']) ?></td>
        <td><?= money($r['amount']) ?></td>
        <td><?= e($r['notes']) ?></td>
        <td>
          <a class="btn-outline" href="edit.php?id=<?= $r['id'] ?>">Edit</a>
          <a class="btn-danger-soft" href="delete.php?id=<?= $r['id'] ?>" data-confirm="Delete this sale?">Delete</a>
        </td>
      </tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="4" class="text-muted">No sales found.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <?php if ($pages > 1): ?>
    <nav><ul class="pagination">
      <?php for ($i=1;$i<=$pages;$i++): $qs = $_GET; $qs['page']=$i; ?>
        <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query($qs) ?>"><?= $i ?></a></li>
      <?php endfor; ?>
    </ul></nav>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
