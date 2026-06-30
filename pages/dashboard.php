<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_admin();

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');

$todaySales   = total_sales($pdo, $today, $today);
$monthSales   = total_sales($pdo, $monthStart, $monthEnd);
$totalExp     = total_expenses($pdo);
$netPL        = $monthSales - total_expenses($pdo, $monthStart, $monthEnd);
$investorCount = (int)$pdo->query("SELECT COUNT(*) FROM investors")->fetchColumn();

// 30-day trend
$trend = $pdo->query("
    SELECT d.dt,
      COALESCE((SELECT SUM(amount) FROM sales WHERE sale_date = d.dt),0) AS sales,
      COALESCE((SELECT SUM(amount) FROM expenses WHERE expense_date = d.dt),0) AS expenses
    FROM (
      SELECT DATE_SUB(CURDATE(), INTERVAL n DAY) AS dt FROM (
        SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
        UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11
        UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17
        UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23
        UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
      ) x
    ) d ORDER BY d.dt
")->fetchAll();

$recentSales = $pdo->query("SELECT * FROM sales ORDER BY sale_date DESC, id DESC LIMIT 5")->fetchAll();
$recentExp   = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC, id DESC LIMIT 5")->fetchAll();
?>
<h1 class="h4 mb-3">Overview</h1>

<div class="card-row">
  <div class="card-stat"><div class="label">Today's Sales</div><div class="value"><?= money($todaySales) ?></div></div>
  <div class="card-stat"><div class="label">Monthly Sales</div><div class="value"><?= money($monthSales) ?></div></div>
  <div class="card-stat"><div class="label">Total Expenses (all-time)</div><div class="value"><?= money($totalExp) ?></div></div>
  <div class="card-stat"><div class="label">Net P/L (this month)</div>
    <div class="value <?= $netPL>=0?'pos':'neg' ?>"><?= money($netPL) ?></div></div>
  <div class="card-stat"><div class="label">Investors</div><div class="value"><?= $investorCount ?></div></div>
</div>

<div class="panel">
  <h2>Last 30 days</h2>
  <canvas id="trendChart" height="90"></canvas>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="panel">
      <h2>Recent Sales</h2>
      <table class="table table-sm">
        <thead><tr><th>Date</th><th>Amount</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach ($recentSales as $s): ?>
          <tr><td><?= e($s['sale_date']) ?></td><td><?= money($s['amount']) ?></td><td><?= e($s['notes']) ?></td></tr>
        <?php endforeach; if (!$recentSales): ?><tr><td colspan="3" class="text-muted">No sales yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="panel">
      <h2>Recent Expenses</h2>
      <table class="table table-sm">
        <thead><tr><th>Date</th><th>Type</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($recentExp as $x): ?>
          <tr><td><?= e($x['expense_date']) ?></td><td><?= e(expense_type_label($x['expense_type'])) ?></td><td><?= money($x['amount']) ?></td></tr>
        <?php endforeach; if (!$recentExp): ?><tr><td colspan="3" class="text-muted">No expenses yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const trend = <?= json_encode($trend) ?>;
new Chart(document.getElementById('trendChart'), {
  type:'line',
  data:{
    labels: trend.map(r => r.dt),
    datasets:[
      {label:'Sales', data: trend.map(r=>+r.sales), borderColor:'#ff3b30', backgroundColor:'rgba(255,59,48,.08)', tension:.3, fill:true},
      {label:'Expenses', data: trend.map(r=>+r.expenses), borderColor:'#111', backgroundColor:'rgba(17,17,17,.05)', tension:.3, fill:true},
      {label:'Profit', data: trend.map(r=>(+r.sales - +r.expenses)), borderColor:'#16a34a', tension:.3}
    ]
  },
  options:{plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}}}
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
