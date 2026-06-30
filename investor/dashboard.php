<?php
$pageTitle='My Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_investor();

$invId = (int)$_SESSION['user']['investor_id'];
$st = $pdo->prepare("SELECT * FROM investors WHERE id=?"); $st->execute([$invId]); $inv=$st->fetch();
if (!$inv) { die('Investor profile not found.'); }

$share = (float)$inv['share_percentage'] / 100;

// Current month live P/L
$mStart = date('Y-m-01'); $mEnd = date('Y-m-t');
$monthNet   = net_profit_loss($pdo, $mStart, $mEnd);
$myMonth    = round($monthNet * $share, 2);

// LIVE all-time totals (always reflect data even when no distribution snapshots exist)
$allSales   = total_sales($pdo);
$allExp     = total_expenses($pdo);
$allNet     = $allSales - $allExp;
$liveEarn   = $allNet > 0 ? round($allNet * $share, 2) : 0.0;
$liveLoss   = $allNet < 0 ? round(abs($allNet) * $share, 2) : 0.0;

// Historical snapshot totals (admin-recorded distributions)
$st = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN type='profit' THEN investor_amount ELSE 0 END),0) AS earnings,
    COALESCE(SUM(CASE WHEN type='loss'   THEN investor_amount ELSE 0 END),0) AS losses
  FROM profit_distributions WHERE investor_id=?");
$st->execute([$invId]); $tot=$st->fetch();

$st = $pdo->prepare("SELECT * FROM profit_distributions WHERE investor_id=? ORDER BY period_end DESC, id DESC LIMIT 10");
$st->execute([$invId]); $hist=$st->fetchAll();
?>
<h1 class="h4 mb-1">Welcome, <?= e($inv['name']) ?></h1>
<p class="text-muted small mb-3">Snapshot of your investment and current returns.</p>

<div class="card-row">
  <div class="card-stat">
    <div class="label">Total Investment</div>
    <div class="value"><?= money($inv['investment_amount']) ?></div></div>
  <div class="card-stat">
    <div class="label">Your Share</div>
    <div class="value"><?= number_format($inv['share_percentage'],2) ?>%</div></div>
  <div class="card-stat">
    <div class="label">This Month P/L</div>
    <div class="value <?= $myMonth>=0?'pos':'neg' ?>"><?= money($myMonth) ?></div></div>
  <div class="card-stat">
    <div class="label">Total Earnings (live)</div>
    <div class="value pos"><?= money($liveEarn) ?></div>
    <div class="text-muted" style="font-size:11px">Your share of all-time net profit</div></div>
  <div class="card-stat">
    <div class="label">Total Losses (live)</div>
    <div class="value neg"><?= money($liveLoss) ?></div>
    <div class="text-muted" style="font-size:11px">Your share of all-time net loss</div></div>
</div>

<div class="panel">
  <div class="d-flex justify-content-between align-items-center">
    <h2 class="h6 m-0">Distribution History (admin snapshots)</h2>
    <div class="text-muted small">
      Recorded earnings: <b class="text-success"><?= money($tot['earnings']) ?></b> ·
      Recorded losses: <b class="text-danger"><?= money($tot['losses']) ?></b>
    </div>
  </div>
  <table class="table mt-2">
    <thead><tr><th>Period</th><th>Net</th><th>My Share %</th><th>My Amount</th><th>Type</th></tr></thead>
    <tbody>
    <?php foreach($hist as $h): ?>
      <tr>
        <td><?= e($h['period_start']) ?> → <?= e($h['period_end']) ?></td>
        <td><?= money($h['net_amount']) ?></td>
        <td><?= number_format($h['share_percentage'],2) ?>%</td>
        <td><?= money($h['investor_amount']) ?></td>
        <td><span class="badge bg-<?= $h['type']==='profit'?'success':'danger' ?>"><?= e($h['type']) ?></span></td>
      </tr>
    <?php endforeach; if(!$hist): ?>
      <tr><td colspan="5" class="text-muted">No distribution records yet. Your live totals above reflect current data.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
