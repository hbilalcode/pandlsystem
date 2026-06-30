<?php
$pageTitle='Investors';
require_once __DIR__ . '/../../includes/header.php';
require_admin();
$rows = $pdo->query("SELECT * FROM investors ORDER BY created_at DESC")->fetchAll();
$sumActive = total_share_percentage($pdo);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0">Investors</h1>
  <a class="btn btn-primary" href="add.php">+ New Investor</a>
</div>
<div class="panel">
  <p>Total active share %: <strong><?= number_format($sumActive,2) ?>%</strong>
    <?php if(abs($sumActive-100)>0.01 && $sumActive>0): ?>
      <span class="text-danger">(must equal 100%)</span>
    <?php endif; ?>
  </p>
  <table class="table">
    <thead><tr><th>Name</th><th>Username</th><th>Phone</th><th>Email</th><th>Investment</th><th>Share %</th><th>Status</th><th>Created</th><th style="width:230px">Actions</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['username']) ?></td>
        <td><?= e($r['phone']) ?></td>
        <td><?= e($r['email']) ?></td>
        <td><?= money($r['investment_amount']) ?></td>
        <td><?= number_format($r['share_percentage'],2) ?>%</td>
        <td><span class="badge bg-<?= $r['status']==='active'?'success':'secondary' ?>"><?= e($r['status']) ?></span></td>
        <td><?= e($r['created_at']) ?></td>
        <td>
          <a class="btn-outline" href="edit.php?id=<?= $r['id'] ?>">Edit</a>
          <a class="btn-outline" href="toggle.php?id=<?= $r['id'] ?>"><?= $r['status']==='active'?'Deactivate':'Activate' ?></a>
          <a class="btn-danger-soft" href="delete.php?id=<?= $r['id'] ?>" data-confirm="Remove this investor and their login?">Delete</a>
        </td>
      </tr>
    <?php endforeach; if(!$rows): ?><tr><td colspan="9" class="text-muted">No investors yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
