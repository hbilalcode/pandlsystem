<?php
/**
 * reports/index.php
 * Unified Reports Hub.
 *
 * One screen, one menu. Pick a report type, set filters, view a polished
 * print-ready report below. Investors are locked to their own data.
 */
$pageTitle = 'Reports';
require_once __DIR__ . '/_report_header.php';
require_login();

$role        = $_SESSION['user']['role'];
$isAdmin     = $role === 'super_admin';
$selfInvId   = (int)($_SESSION['user']['investor_id'] ?? 0);

// ---------- Report types ----------
$types = [
    'summary'      => 'Overall Summary',
    'daily'        => 'Daily P&L',
    'monthly'      => 'Monthly P&L',
    'yearly'       => 'Yearly P&L',
    'range'        => 'Custom Date Range P&L',
    'sales'        => 'Sales Detail',
    'expenses'     => 'Expense Detail',
    'distribution' => 'Investor Distribution',
    'individual'   => 'Individual Investor',
];

// Investors see only a subset
if (!$isAdmin) {
    $types = ['individual' => 'My Statement'];
}

$type = $_GET['type'] ?? ($isAdmin ? 'summary' : 'individual');
if (!isset($types[$type])) { $type = array_key_first($types); }

// ---------- Filter defaults ----------
$today      = date('Y-m-d');
$monthVal   = $_GET['month']   ?? date('Y-m');
$yearVal    = (int)($_GET['year'] ?? date('Y'));
$dateVal    = $_GET['date']    ?? $today;
$fromVal    = $_GET['from']    ?? date('Y-m-01');
$toVal      = $_GET['to']      ?? date('Y-m-t');
$expType    = $_GET['exp_type'] ?? '';
$investorId = $isAdmin ? (int)($_GET['investor_id'] ?? 0) : $selfInvId;

// ---------- Save distribution snapshot (admin only) ----------
if ($isAdmin && isset($_GET['save_dist']) && !empty($_GET['from']) && !empty($_GET['to'])) {
    save_distribution($pdo, $fromVal, $toVal);
    flash('success', 'Distribution snapshot saved to history.');
    $qs = $_GET; unset($qs['save_dist']);
    header('Location: index.php?' . http_build_query($qs));
    exit;
}

// ---------- Compute period for the chosen report ----------
switch ($type) {
    case 'daily':
        $from = $to = $dateVal;
        $periodLabel = 'Date: ' . $dateVal;
        break;
    case 'monthly':
        $from = $monthVal . '-01';
        $to   = date('Y-m-t', strtotime($from));
        $periodLabel = 'Month: ' . date('F Y', strtotime($from));
        break;
    case 'yearly':
        $from = "$yearVal-01-01";
        $to   = "$yearVal-12-31";
        $periodLabel = 'Year: ' . $yearVal;
        break;
    case 'summary':
        $from = null; $to = null;
        $periodLabel = 'All time (as of ' . $today . ')';
        break;
    default:
        $from = $fromVal; $to = $toVal;
        $periodLabel = 'Period: ' . $from . ' → ' . $to;
}

$investors = $isAdmin
    ? $pdo->query("SELECT id,name FROM investors ORDER BY name")->fetchAll()
    : [];
?>

<div class="reports-shell">
  <!-- Header card: title + report-type picker -->
  <div class="panel reports-picker">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h1 class="h5 mb-1">Reports</h1>
        <div class="text-muted" style="font-size:13px">
          Generate, filter and print professional reports.
        </div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline btn-sm" onclick="window.location=window.location.pathname">Reset</button>
        <button class="btn btn-primary btn-sm" onclick="printReport()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          Print / Save PDF
        </button>
      </div>
    </div>

    <!-- Tab-like report selector -->
    <div class="report-tabs mt-3">
      <?php foreach ($types as $k => $label): ?>
        <a class="report-tab <?= $k === $type ? 'active' : '' ?>"
           href="?type=<?= e($k) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Dynamic filters (only the relevant inputs are shown) -->
    <form method="get" class="filters mt-3">
      <input type="hidden" name="type" value="<?= e($type) ?>">

      <?php if ($type === 'daily'): ?>
        <label>Date <input type="date" name="date" class="form-control" value="<?= e($dateVal) ?>"></label>
      <?php endif; ?>

      <?php if ($type === 'monthly'): ?>
        <label>Month <input type="month" name="month" class="form-control" value="<?= e($monthVal) ?>"></label>
      <?php endif; ?>

      <?php if ($type === 'yearly'): ?>
        <label>Year <input type="number" name="year" class="form-control" min="2000" max="2100" value="<?= e($yearVal) ?>"></label>
      <?php endif; ?>

      <?php if (in_array($type, ['range','sales','expenses','distribution','individual'])): ?>
        <label>From <input type="date" name="from" class="form-control" value="<?= e($fromVal) ?>"></label>
        <label>To <input type="date" name="to" class="form-control" value="<?= e($toVal) ?>"></label>
      <?php endif; ?>

      <?php if ($type === 'expenses'): ?>
        <label>Type
          <select name="exp_type" class="form-select">
            <option value="">All types</option>
            <?php foreach (['cashier','vendor','salary','custom'] as $t): ?>
              <option value="<?= $t ?>" <?= $expType===$t?'selected':'' ?>><?= e(expense_type_label($t)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endif; ?>

      <?php if ($type === 'individual' && $isAdmin): ?>
        <label>Investor
          <select name="investor_id" class="form-select">
            <option value="">— Select investor —</option>
            <?php foreach ($investors as $i): ?>
              <option value="<?= $i['id'] ?>" <?= $i['id']==$investorId?'selected':'' ?>><?= e($i['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endif; ?>

      <?php if ($type !== 'summary'): ?>
        <div class="filters-actions">
          <button class="btn btn-primary">Apply Filters</button>
          <?php if ($isAdmin && $type === 'distribution'): ?>
            <a class="btn btn-outline"
               data-confirm="Save this distribution snapshot to history?"
               href="?<?= e(http_build_query(array_merge($_GET, ['save_dist'=>1]))) ?>">
              Save Distribution
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- ============ REPORT OUTPUT ============ -->
  <div class="report" id="printable">
    <!-- Branded header -->
    <div class="rpt-header">
      <div class="rpt-brand">
        <div class="rpt-mark"><?= strtoupper(substr(RESTAURANT_NAME,0,1)) ?></div>
        <div>
          <div class="rpt-name"><?= e(RESTAURANT_NAME) ?></div>
          <div class="rpt-addr"><?= e(RESTAURANT_ADDRESS) ?></div>
        </div>
      </div>
      <div class="rpt-meta">
        <div class="rpt-title"><?= e($types[$type]) ?></div>
        <div class="rpt-sub"><?= e($periodLabel) ?></div>
        <div class="rpt-sub">Generated: <?= date('Y-m-d H:i') ?>
          &middot; By: <?= e($_SESSION['user']['username']) ?></div>
      </div>
    </div>

    <?php
    // ============================================================
    // SUMMARY
    // ============================================================
    if ($type === 'summary'):
        $salesAll = total_sales($pdo);
        $expAll   = total_expenses($pdo);
        $netAll   = $salesAll - $expAll;
        $monthS   = total_sales($pdo, date('Y-m-01'), date('Y-m-t'));
        $monthE   = total_expenses($pdo, date('Y-m-01'), date('Y-m-t'));
        $yearS    = total_sales($pdo, date('Y-01-01'), date('Y-12-31'));
        $yearE    = total_expenses($pdo, date('Y-01-01'), date('Y-12-31'));
        $invList  = $pdo->query("SELECT * FROM investors ORDER BY name")->fetchAll();
        $capital  = array_sum(array_column($invList,'investment_amount'));
        $countSales = (int)$pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
        $countExp   = (int)$pdo->query("SELECT COUNT(*) FROM expenses")->fetchColumn();
    ?>
      <div class="kpi-grid">
        <div class="kpi"><span>Total Sales</span><b><?= money($salesAll) ?></b></div>
        <div class="kpi"><span>Total Expenses</span><b><?= money($expAll) ?></b></div>
        <div class="kpi <?= $netAll>=0?'kpi-pos':'kpi-neg' ?>">
          <span>Net <?= $netAll>=0?'Profit':'Loss' ?></span><b><?= money(abs($netAll)) ?></b></div>
        <div class="kpi"><span>Investment Capital</span><b><?= money($capital) ?></b></div>
      </div>
      <h3 class="rpt-h">Period Performance</h3>
      <table class="rpt-table">
        <thead><tr><th>Period</th><th class="num">Sales</th><th class="num">Expenses</th><th class="num">Net</th></tr></thead>
        <tbody>
          <tr><td>This Month (<?= date('M Y') ?>)</td><td class="num"><?= money($monthS) ?></td>
              <td class="num"><?= money($monthE) ?></td><td class="num"><?= money($monthS-$monthE) ?></td></tr>
          <tr><td>This Year (<?= date('Y') ?>)</td><td class="num"><?= money($yearS) ?></td>
              <td class="num"><?= money($yearE) ?></td><td class="num"><?= money($yearS-$yearE) ?></td></tr>
          <tr class="totals"><td>All Time</td><td class="num"><?= money($salesAll) ?></td>
              <td class="num"><?= money($expAll) ?></td><td class="num"><?= money($netAll) ?></td></tr>
        </tbody>
      </table>
      <h3 class="rpt-h">Investors (<?= count($invList) ?>)</h3>
      <table class="rpt-table">
        <thead><tr><th>#</th><th>Name</th><th class="num">Investment</th><th class="num">Share %</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($invList as $i=>$v): ?>
            <tr><td><?= $i+1 ?></td><td><?= e($v['name']) ?></td>
                <td class="num"><?= money($v['investment_amount']) ?></td>
                <td class="num"><?= number_format($v['share_percentage'],2) ?>%</td>
                <td><span class="pill <?= $v['status']==='active'?'pill-ok':'pill-mute' ?>"><?= e($v['status']) ?></span></td></tr>
          <?php endforeach; if(!$invList): ?>
            <tr><td colspan="5" class="empty">No investors recorded.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <div class="rpt-foot-note">
        <span>Sales transactions: <b><?= $countSales ?></b></span>
        <span>Expense transactions: <b><?= $countExp ?></b></span>
      </div>

    <?php
    // ============================================================
    // DAILY / MONTHLY / RANGE  (P&L snapshot)
    // ============================================================
    elseif (in_array($type, ['daily','monthly','range'])):
        $sales = total_sales($pdo, $from, $to);
        $exp   = total_expenses($pdo, $from, $to);
        $net   = $sales - $exp;
        $dist  = distribute_net($pdo, $net);

        // expense breakdown for the period
        $bdStmt = $pdo->prepare("SELECT expense_type, SUM(amount) total
                                 FROM expenses WHERE expense_date BETWEEN ? AND ?
                                 GROUP BY expense_type");
        $bdStmt->execute([$from,$to]);
        $bd = $bdStmt->fetchAll();
    ?>
      <div class="kpi-grid">
        <div class="kpi"><span>Total Sales</span><b><?= money($sales) ?></b></div>
        <div class="kpi"><span>Total Expenses</span><b><?= money($exp) ?></b></div>
        <div class="kpi <?= $net>=0?'kpi-pos':'kpi-neg' ?>">
          <span>Net <?= $net>=0?'Profit':'Loss' ?></span><b><?= money(abs($net)) ?></b></div>
        <div class="kpi"><span>Margin</span>
          <b><?= $sales>0 ? number_format(($net/$sales)*100,1).'%' : '—' ?></b></div>
      </div>

      <h3 class="rpt-h">Expense Breakdown</h3>
      <table class="rpt-table">
        <thead><tr><th>Category</th><th class="num">Amount</th><th class="num">% of Expenses</th></tr></thead>
        <tbody>
          <?php foreach ($bd as $r): ?>
            <tr><td><?= e(expense_type_label($r['expense_type'])) ?></td>
                <td class="num"><?= money($r['total']) ?></td>
                <td class="num"><?= $exp>0?number_format(($r['total']/$exp)*100,1):'0' ?>%</td></tr>
          <?php endforeach; if(!$bd): ?>
            <tr><td colspan="3" class="empty">No expenses in this period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <h3 class="rpt-h">Investor Distribution</h3>
      <table class="rpt-table">
        <thead><tr><th>#</th><th>Investor</th><th class="num">Share %</th><th class="num">Amount</th><th>Type</th></tr></thead>
        <tbody>
          <?php foreach ($dist as $i=>$r): ?>
            <tr><td><?= $i+1 ?></td><td><?= e($r['name']) ?></td>
                <td class="num"><?= number_format($r['share_percentage'],2) ?>%</td>
                <td class="num"><?= money(abs($r['investor_amount'])) ?></td>
                <td><span class="pill <?= $r['type']==='profit'?'pill-ok':'pill-bad' ?>"><?= e($r['type']) ?></span></td></tr>
          <?php endforeach; if(!$dist): ?>
            <tr><td colspan="5" class="empty">No active investors to distribute to.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

    <?php
    // ============================================================
    // YEARLY  (month-by-month + distribution for the year)
    // ============================================================
    elseif ($type === 'yearly'):
        $salesY = total_sales($pdo, $from, $to);
        $expY   = total_expenses($pdo, $from, $to);
        $netY   = $salesY - $expY;
        $distY  = distribute_net($pdo, $netY);
        $mst = $pdo->prepare("
          SELECT z.m,
            COALESCE((SELECT SUM(amount) FROM sales WHERE YEAR(sale_date)=? AND MONTH(sale_date)=z.m),0) AS sales,
            COALESCE((SELECT SUM(amount) FROM expenses WHERE YEAR(expense_date)=? AND MONTH(expense_date)=z.m),0) AS expenses
          FROM (SELECT 1 m UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
                UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) z
          ORDER BY z.m");
        $mst->execute([$yearVal,$yearVal]); $months = $mst->fetchAll();
    ?>
      <div class="kpi-grid">
        <div class="kpi"><span>Year Sales</span><b><?= money($salesY) ?></b></div>
        <div class="kpi"><span>Year Expenses</span><b><?= money($expY) ?></b></div>
        <div class="kpi <?= $netY>=0?'kpi-pos':'kpi-neg' ?>">
          <span>Year Net <?= $netY>=0?'Profit':'Loss' ?></span><b><?= money(abs($netY)) ?></b></div>
        <div class="kpi"><span>Avg Monthly Net</span><b><?= money($netY/12) ?></b></div>
      </div>
      <h3 class="rpt-h">Month-by-Month</h3>
      <table class="rpt-table">
        <thead><tr><th>Month</th><th class="num">Sales</th><th class="num">Expenses</th><th class="num">Net</th></tr></thead>
        <tbody>
          <?php foreach ($months as $m): $n=$m['sales']-$m['expenses']; ?>
            <tr><td><?= date('F', mktime(0,0,0,$m['m'],1)) ?></td>
                <td class="num"><?= money($m['sales']) ?></td>
                <td class="num"><?= money($m['expenses']) ?></td>
                <td class="num <?= $n>=0?'pos':'neg' ?>"><?= money($n) ?></td></tr>
          <?php endforeach; ?>
          <tr class="totals"><td>Total <?= $yearVal ?></td>
              <td class="num"><?= money($salesY) ?></td><td class="num"><?= money($expY) ?></td>
              <td class="num"><?= money($netY) ?></td></tr>
        </tbody>
      </table>
      <h3 class="rpt-h">Year-End Distribution</h3>
      <table class="rpt-table">
        <thead><tr><th>#</th><th>Investor</th><th class="num">Share %</th><th class="num">Amount</th><th>Type</th></tr></thead>
        <tbody>
          <?php foreach ($distY as $i=>$r): ?>
            <tr><td><?= $i+1 ?></td><td><?= e($r['name']) ?></td>
                <td class="num"><?= number_format($r['share_percentage'],2) ?>%</td>
                <td class="num"><?= money(abs($r['investor_amount'])) ?></td>
                <td><span class="pill <?= $r['type']==='profit'?'pill-ok':'pill-bad' ?>"><?= e($r['type']) ?></span></td></tr>
          <?php endforeach; if(!$distY): ?>
            <tr><td colspan="5" class="empty">No active investors.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

    <?php
    // ============================================================
    // SALES detail
    // ============================================================
    elseif ($type === 'sales'):
        $st = $pdo->prepare("SELECT * FROM sales WHERE sale_date BETWEEN ? AND ? ORDER BY sale_date, id");
        $st->execute([$from,$to]); $rows = $st->fetchAll();
        $tot = array_sum(array_column($rows,'amount'));
        $days = max(1, (strtotime($to)-strtotime($from))/86400 + 1);
    ?>
      <div class="kpi-grid">
        <div class="kpi"><span>Total Sales</span><b><?= money($tot) ?></b></div>
        <div class="kpi"><span>Transactions</span><b><?= count($rows) ?></b></div>
        <div class="kpi"><span>Daily Avg</span><b><?= money($tot/$days) ?></b></div>
        <div class="kpi"><span>Per Transaction</span>
          <b><?= count($rows)?money($tot/count($rows)):money(0) ?></b></div>
      </div>
      <h3 class="rpt-h">Sales Transactions</h3>
      <table class="rpt-table">
        <thead><tr><th>#</th><th>Date</th><th class="num">Amount</th><th>Notes</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $i=>$r): ?>
            <tr><td><?= $i+1 ?></td><td><?= e($r['sale_date']) ?></td>
                <td class="num"><?= money($r['amount']) ?></td><td><?= e($r['notes']) ?></td></tr>
          <?php endforeach; if(!$rows): ?>
            <tr><td colspan="4" class="empty">No sales recorded in this period.</td></tr>
          <?php endif; ?>
          <?php if ($rows): ?>
            <tr class="totals"><td colspan="2">Total</td><td class="num"><?= money($tot) ?></td><td></td></tr>
          <?php endif; ?>
        </tbody>
      </table>

    <?php
    // ============================================================
    // EXPENSES detail
    // ============================================================
    elseif ($type === 'expenses'):
        $sql = "SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ?";
        $p = [$from,$to];
        if ($expType) { $sql .= " AND expense_type=?"; $p[] = $expType; }
        $sql .= " ORDER BY expense_date, id";
        $st = $pdo->prepare($sql); $st->execute($p); $rows = $st->fetchAll();
        $tot = array_sum(array_column($rows,'amount'));
        $bd = [];
        foreach ($rows as $r) { $bd[$r['expense_type']] = ($bd[$r['expense_type']] ?? 0) + $r['amount']; }
    ?>
      <div class="kpi-grid">
        <div class="kpi"><span>Total Expenses</span><b><?= money($tot) ?></b></div>
        <div class="kpi"><span>Transactions</span><b><?= count($rows) ?></b></div>
        <div class="kpi"><span>Categories</span><b><?= count($bd) ?></b></div>
        <div class="kpi"><span>Largest</span>
          <b><?= $rows ? money(max(array_column($rows,'amount'))) : money(0) ?></b></div>
      </div>
      <h3 class="rpt-h">Expense Transactions</h3>
      <table class="rpt-table">
        <thead><tr><th>#</th><th>Date</th><th>Type</th><th>Description</th><th class="num">Amount</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $i=>$r): ?>
            <tr><td><?= $i+1 ?></td><td><?= e($r['expense_date']) ?></td>
                <td><?= e(expense_type_label($r['expense_type'])) ?></td>
                <td><?= e($r['description']) ?></td>
                <td class="num"><?= money($r['amount']) ?></td></tr>
          <?php endforeach; if(!$rows): ?>
            <tr><td colspan="5" class="empty">No expenses in this period.</td></tr>
          <?php endif; ?>
          <?php if ($rows): ?>
            <tr class="totals"><td colspan="4">Total</td><td class="num"><?= money($tot) ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <?php if ($bd): ?>
        <h3 class="rpt-h">By Category</h3>
        <table class="rpt-table">
          <thead><tr><th>Category</th><th class="num">Amount</th><th class="num">% of Total</th></tr></thead>
          <tbody>
            <?php foreach ($bd as $k=>$v): ?>
              <tr><td><?= e(expense_type_label($k)) ?></td>
                  <td class="num"><?= money($v) ?></td>
                  <td class="num"><?= number_format(($v/$tot)*100,1) ?>%</td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

    <?php
    // ============================================================
    // DISTRIBUTION  (current period + history of saved snapshots)
    // ============================================================
    elseif ($type === 'distribution'):
        $sales = total_sales($pdo, $from, $to);
        $exp   = total_expenses($pdo, $from, $to);
        $net   = $sales - $exp;
        $dist  = distribute_net($pdo, $net);
        $hst   = $pdo->prepare("SELECT pd.*, i.name FROM profit_distributions pd
                                JOIN investors i ON i.id = pd.investor_id
                                WHERE pd.period_start >= ? AND pd.period_end <= ?
                                ORDER BY pd.period_end DESC, i.name");
        $hst->execute([$from,$to]); $history = $hst->fetchAll();
    ?>
      <div class="kpi-grid">
        <div class="kpi"><span>Sales</span><b><?= money($sales) ?></b></div>
        <div class="kpi"><span>Expenses</span><b><?= money($exp) ?></b></div>
        <div class="kpi <?= $net>=0?'kpi-pos':'kpi-neg' ?>">
          <span>Net to Distribute</span><b><?= money(abs($net)) ?></b></div>
        <div class="kpi"><span>Active Investors</span><b><?= count($dist) ?></b></div>
      </div>
      <h3 class="rpt-h">Current Distribution (live)</h3>
      <table class="rpt-table">
        <thead><tr><th>#</th><th>Investor</th><th class="num">Share %</th><th class="num">Amount</th><th>Type</th></tr></thead>
        <tbody>
          <?php foreach ($dist as $i=>$r): ?>
            <tr><td><?= $i+1 ?></td><td><?= e($r['name']) ?></td>
                <td class="num"><?= number_format($r['share_percentage'],2) ?>%</td>
                <td class="num"><?= money(abs($r['investor_amount'])) ?></td>
                <td><span class="pill <?= $r['type']==='profit'?'pill-ok':'pill-bad' ?>"><?= e($r['type']) ?></span></td></tr>
          <?php endforeach; if(!$dist): ?>
            <tr><td colspan="5" class="empty">No active investors.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <h3 class="rpt-h">Saved Snapshots in Range</h3>
      <table class="rpt-table">
        <thead><tr><th>Period</th><th>Investor</th><th class="num">Net</th>
                   <th class="num">Share %</th><th class="num">Amount</th><th>Type</th></tr></thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr><td><?= e($h['period_start']) ?> → <?= e($h['period_end']) ?></td>
                <td><?= e($h['name']) ?></td>
                <td class="num"><?= money($h['net_amount']) ?></td>
                <td class="num"><?= number_format($h['share_percentage'],2) ?>%</td>
                <td class="num"><?= money($h['investor_amount']) ?></td>
                <td><span class="pill <?= $h['type']==='profit'?'pill-ok':'pill-bad' ?>"><?= e($h['type']) ?></span></td></tr>
          <?php endforeach; if(!$history): ?>
            <tr><td colspan="6" class="empty">No saved snapshots yet. Use “Save Distribution” to record one.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

    <?php
    // ============================================================
    // INDIVIDUAL investor
    // ============================================================
    elseif ($type === 'individual'):
        $inv = null;
        if ($investorId) {
            $st = $pdo->prepare("SELECT * FROM investors WHERE id=?");
            $st->execute([$investorId]); $inv = $st->fetch();
        }
        if (!$inv):
    ?>
      <div class="empty-state">Select an investor from the filter above to view their statement.</div>
    <?php else:
        $hst = $pdo->prepare("SELECT * FROM profit_distributions
                              WHERE investor_id=? AND period_end BETWEEN ? AND ?
                              ORDER BY period_end");
        $hst->execute([$investorId,$from,$to]); $hist = $hst->fetchAll();
        $earn = 0; $loss = 0;
        foreach ($hist as $h) {
            if ($h['type']==='profit') $earn += $h['investor_amount'];
            else $loss += $h['investor_amount'];
        }
        // Live calc for the same period
        $pSales = total_sales($pdo, $from, $to);
        $pExp   = total_expenses($pdo, $from, $to);
        $pNet   = $pSales - $pExp;
        $share  = (float)$inv['share_percentage'];
        $liveAmt = round($pNet * ($share/100), 2);
    ?>
      <table class="rpt-info">
        <tr><th>Name</th><td><?= e($inv['name']) ?></td>
            <th>Investment</th><td><?= money($inv['investment_amount']) ?></td></tr>
        <tr><th>Share</th><td><?= number_format($share,2) ?>%</td>
            <th>Status</th><td><span class="pill <?= $inv['status']==='active'?'pill-ok':'pill-mute' ?>"><?= e($inv['status']) ?></span></td></tr>
      </table>

      <div class="kpi-grid mt-3">
        <div class="kpi"><span>Period Sales</span><b><?= money($pSales) ?></b></div>
        <div class="kpi"><span>Period Expenses</span><b><?= money($pExp) ?></b></div>
        <div class="kpi <?= $pNet>=0?'kpi-pos':'kpi-neg' ?>">
          <span>Net</span><b><?= money(abs($pNet)) ?></b></div>
        <div class="kpi <?= $liveAmt>=0?'kpi-pos':'kpi-neg' ?>">
          <span>Your <?= $liveAmt>=0?'Earning':'Loss' ?> (live)</span>
          <b><?= money(abs($liveAmt)) ?></b></div>
      </div>

      <h3 class="rpt-h">Saved Distribution History</h3>
      <table class="rpt-table">
        <thead><tr><th>Period</th><th class="num">Net</th><th class="num">Share %</th>
                   <th class="num">Amount</th><th>Type</th></tr></thead>
        <tbody>
          <?php foreach ($hist as $h): ?>
            <tr><td><?= e($h['period_start']) ?> → <?= e($h['period_end']) ?></td>
                <td class="num"><?= money($h['net_amount']) ?></td>
                <td class="num"><?= number_format($h['share_percentage'],2) ?>%</td>
                <td class="num"><?= money($h['investor_amount']) ?></td>
                <td><span class="pill <?= $h['type']==='profit'?'pill-ok':'pill-bad' ?>"><?= e($h['type']) ?></span></td></tr>
          <?php endforeach; if(!$hist): ?>
            <tr><td colspan="5" class="empty">No saved distributions in this range.</td></tr>
          <?php endif; ?>
          <?php if ($hist): ?>
            <tr class="totals">
              <td>Totals</td><td></td><td></td>
              <td class="num">Earn: <?= money($earn) ?> &nbsp; Loss: <?= money($loss) ?></td>
              <td class="num">Net: <?= money($earn-$loss) ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    <?php endif; endif; ?>

    <!-- Signatures -->
    <div class="rpt-sign">
      <div><span></span>Prepared By</div>
      <div><span></span>Authorized Signature</div>
    </div>
    <div class="rpt-page-foot">
      <?= e(RESTAURANT_NAME) ?> &middot; <?= e($types[$type]) ?>
      &middot; Generated <?= date('Y-m-d H:i') ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_report_footer.php'; ?>
