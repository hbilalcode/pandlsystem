<?php
$pageTitle = 'Send Monthly Reports';
require_once __DIR__ . '/../includes/header.php';
require_admin();

// Default to previous month
$defStart = date('Y-m-01', strtotime('first day of last month'));
$defEnd   = date('Y-m-t',  strtotime('first day of last month'));

$from = $_POST['from'] ?? ($_GET['from'] ?? $defStart);
$to   = $_POST['to']   ?? ($_GET['to']   ?? $defEnd);
$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    verify_csrf();
    $sales = total_sales($pdo, $from, $to);
    $exp   = total_expenses($pdo, $from, $to);
    $net   = $sales - $exp;
    $label = date('F Y', strtotime($from));

    $results = [];
    foreach (active_investors($pdo) as $inv) {
        $myShare = round($net * ((float)$inv['share_percentage']/100), 2);
        $row = ['investor'=>$inv['name'], 'email'=>$inv['email'], 'amount'=>$myShare, 'status'=>'', 'error'=>''];

        if (!valid_email($inv['email'])) {
            $row['status'] = 'skipped'; $row['error'] = 'No valid email on file';
            $results[] = $row; continue;
        }
        // Duplicate guard
        $chk = $pdo->prepare("SELECT id FROM email_log WHERE investor_id=? AND period_start=? AND period_end=? AND status='sent'");
        $chk->execute([$inv['id'], $from, $to]);
        if ($chk->fetch()) {
            $row['status'] = 'already sent';
            $results[] = $row; continue;
        }

        $html = build_monthly_email_html($inv,
            ['start'=>$from,'end'=>$to,'label'=>$label],
            ['sales'=>$sales,'expenses'=>$exp,'net'=>$net],
            $myShare);
        $subject = RESTAURANT_NAME . " — Monthly P&L Report ($label)";
        [$ok, $errMsg] = send_html_mail($inv['email'], $subject, $html);

        $pdo->prepare("INSERT INTO email_log (investor_id,period_start,period_end,email_to,status,error)
                       VALUES (?,?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE status=VALUES(status), error=VALUES(error), sent_at=CURRENT_TIMESTAMP")
            ->execute([$inv['id'], $from, $to, $inv['email'], $ok?'sent':'failed', $ok?null:$errMsg]);

        $row['status'] = $ok ? 'sent' : 'failed';
        $row['error']  = $ok ? '' : $errMsg;
        $results[] = $row;
    }
    flash('success', 'Monthly emails processed for ' . $label . '.');
}

// Recent log
$log = $pdo->query("SELECT l.*, i.name AS investor_name FROM email_log l
                    JOIN investors i ON i.id=l.investor_id
                    ORDER BY l.sent_at DESC LIMIT 25")->fetchAll();
?>
<h1 class="h4 mb-3">Send Monthly P&amp;L Email</h1>

<div class="panel">
  <p class="text-muted small mb-3">
    Generates each active investor's share of the P&amp;L for the chosen period and emails the report.
    Re-sending the same period is automatically skipped (use the log below to audit).
  </p>
  <form method="post" class="row g-3 align-items-end">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="col-md-3"><label>From</label>
      <input type="date" name="from" class="form-control" value="<?= e($from) ?>" required></div>
    <div class="col-md-3"><label>To</label>
      <input type="date" name="to"   class="form-control" value="<?= e($to)   ?>" required></div>
    <div class="col-md-6">
      <button name="send" value="1" class="btn btn-primary">Send to active investors</button>
      <a href="dashboard.php" class="btn btn-outline">Back</a>
    </div>
  </form>
</div>

<?php if ($results !== null): ?>
<div class="panel">
  <h2 class="h6">Send Results</h2>
  <table class="table">
    <thead><tr><th>Investor</th><th>Email</th><th>Amount</th><th>Status</th><th>Notes</th></tr></thead>
    <tbody>
      <?php foreach ($results as $r): ?>
      <tr>
        <td><?= e($r['investor']) ?></td>
        <td><?= e($r['email'] ?: '—') ?></td>
        <td><?= money($r['amount']) ?></td>
        <td>
          <span class="badge bg-<?=
            $r['status']==='sent' ? 'success'
            : ($r['status']==='failed' ? 'danger'
            : ($r['status']==='already sent' ? 'secondary' : 'warning')) ?>">
            <?= e($r['status']) ?>
          </span>
        </td>
        <td class="text-muted small"><?= e($r['error']) ?></td>
      </tr>
      <?php endforeach; if(!$results): ?>
        <tr><td colspan="5" class="text-muted">No active investors to email.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="panel">
  <h2 class="h6">Recent Email Log</h2>
  <table class="table">
    <thead><tr><th>Sent At</th><th>Investor</th><th>Period</th><th>To</th><th>Status</th><th>Error</th></tr></thead>
    <tbody>
    <?php foreach($log as $l): ?>
      <tr>
        <td><?= e(date('d M Y H:i', strtotime($l['sent_at']))) ?></td>
        <td><?= e($l['investor_name']) ?></td>
        <td><?= e($l['period_start']) ?> → <?= e($l['period_end']) ?></td>
        <td><?= e($l['email_to']) ?></td>
        <td><span class="badge bg-<?= $l['status']==='sent'?'success':'danger' ?>"><?= e($l['status']) ?></span></td>
        <td class="text-muted small"><?= e($l['error']) ?></td>
      </tr>
    <?php endforeach; if(!$log): ?>
      <tr><td colspan="6" class="text-muted">No emails sent yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
