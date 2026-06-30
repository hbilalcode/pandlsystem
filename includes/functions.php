<?php
// includes/functions.php
// Shared calculations + validators + email helper

function total_sales(PDO $pdo, $from = null, $to = null) {
    $sql = "SELECT COALESCE(SUM(amount),0) FROM sales WHERE 1=1";
    $p = [];
    if ($from) { $sql .= " AND sale_date >= ?"; $p[] = $from; }
    if ($to)   { $sql .= " AND sale_date <= ?"; $p[] = $to; }
    $st = $pdo->prepare($sql); $st->execute($p);
    return (float)$st->fetchColumn();
}

function total_expenses(PDO $pdo, $from = null, $to = null, $type = null) {
    $sql = "SELECT COALESCE(SUM(amount),0) FROM expenses WHERE 1=1";
    $p = [];
    if ($from) { $sql .= " AND expense_date >= ?"; $p[] = $from; }
    if ($to)   { $sql .= " AND expense_date <= ?"; $p[] = $to; }
    if ($type) { $sql .= " AND expense_type = ?"; $p[] = $type; }
    $st = $pdo->prepare($sql); $st->execute($p);
    return (float)$st->fetchColumn();
}

function net_profit_loss(PDO $pdo, $from = null, $to = null) {
    return total_sales($pdo, $from, $to) - total_expenses($pdo, $from, $to);
}

function active_investors(PDO $pdo) {
    return $pdo->query("SELECT * FROM investors WHERE status='active' ORDER BY name")->fetchAll();
}

function total_share_percentage(PDO $pdo, $exclude_id = null) {
    $sql = "SELECT COALESCE(SUM(share_percentage),0) FROM investors WHERE status='active'";
    $p = [];
    if ($exclude_id) { $sql .= " AND id <> ?"; $p[] = $exclude_id; }
    $st = $pdo->prepare($sql); $st->execute($p);
    return (float)$st->fetchColumn();
}

function distribute_net(PDO $pdo, $net) {
    $rows = [];
    $type = $net >= 0 ? 'profit' : 'loss';
    foreach (active_investors($pdo) as $inv) {
        $amt = round($net * ((float)$inv['share_percentage'] / 100), 2);
        $rows[] = [
            'investor_id'      => $inv['id'],
            'name'             => $inv['name'],
            'email'            => $inv['email'],
            'share_percentage' => (float)$inv['share_percentage'],
            'investor_amount'  => $amt,
            'type'             => $type,
        ];
    }
    return $rows;
}

function save_distribution(PDO $pdo, $from, $to) {
    $sales = total_sales($pdo, $from, $to);
    $exp   = total_expenses($pdo, $from, $to);
    $net   = $sales - $exp;
    $rows  = distribute_net($pdo, $net);
    $st = $pdo->prepare("INSERT INTO profit_distributions
        (investor_id, period_start, period_end, total_sales, total_expenses, net_amount,
         share_percentage, investor_amount, type)
        VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($rows as $r) {
        $st->execute([
            $r['investor_id'], $from, $to, $sales, $exp, $net,
            $r['share_percentage'], $r['investor_amount'], $r['type'],
        ]);
    }
    return ['sales' => $sales, 'expenses' => $exp, 'net' => $net, 'rows' => $rows];
}

function expense_type_label($t) {
    return [
        'cashier' => 'Cashier Expense',
        'vendor'  => 'Vendor Expense',
        'salary'  => 'Salary Expense',
        'custom'  => 'Custom Expense',
    ][$t] ?? $t;
}

/* ---------------- Validators ---------------- */

/** Pakistan phone: 11 digits starting with 03 (e.g. 03001234567). */
function valid_pk_phone($s) {
    return $s !== '' && preg_match(PK_PHONE_REGEX, $s);
}

/** Email must be syntactically valid AND contain '@' (special char). */
function valid_email($s) {
    return $s !== '' && filter_var($s, FILTER_VALIDATE_EMAIL) && strpos($s, '@') !== false;
}

/* ---------------- Email helper ---------------- */

/**
 * Send an HTML email using PHP mail(). For production, configure SMTP at your
 * hosting provider, or replace this with PHPMailer.
 * Returns [bool ok, string error].
 */
function send_html_mail($to, $subject, $html) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
    $headers .= 'Reply-To: ' . MAIL_FROM . "\r\n";
    $ok = @mail($to, $subject, $html, $headers);
    return [$ok, $ok ? '' : (error_get_last()['message'] ?? 'mail() failed')];
}

/**
 * Build a styled monthly P&L email for one investor.
 * $period = ['start'=>YYYY-MM-DD,'end'=>YYYY-MM-DD,'label'=>'July 2026']
 */
function build_monthly_email_html($investor, $period, $totals, $myShare) {
    $type   = $totals['net'] >= 0 ? 'Profit' : 'Loss';
    $color  = $totals['net'] >= 0 ? '#16a34a' : '#dc2626';
    $cur    = CURRENCY . ' ';
    $fmt    = function($n) use ($cur){ return $cur . number_format((float)$n, 2); };
    $rest   = htmlspecialchars(RESTAURANT_NAME);
    $name   = htmlspecialchars($investor['name']);
    $label  = htmlspecialchars($period['label']);
    return '
    <div style="font-family:Arial,sans-serif;background:#f4f4f6;padding:24px">
      <div style="max-width:620px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb">
        <div style="background:#ff3b30;color:#fff;padding:18px 22px">
          <h2 style="margin:0;font-size:18px">'.$rest.' — Monthly P&amp;L Report</h2>
          <div style="opacity:.9;font-size:13px">Period: '.$label.'</div>
        </div>
        <div style="padding:22px">
          <p style="margin:0 0 12px">Dear <b>'.$name.'</b>,</p>
          <p style="margin:0 0 16px;color:#444">Please find your share of the '.$label.' performance below.</p>

          <table style="width:100%;border-collapse:collapse;font-size:14px">
            <tr><td style="padding:8px;border-bottom:1px solid #eee">Total Sales</td>
                <td style="padding:8px;border-bottom:1px solid #eee;text-align:right"><b>'.$fmt($totals['sales']).'</b></td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee">Total Expenses</td>
                <td style="padding:8px;border-bottom:1px solid #eee;text-align:right"><b>'.$fmt($totals['expenses']).'</b></td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee">Net '.$type.'</td>
                <td style="padding:8px;border-bottom:1px solid #eee;text-align:right;color:'.$color.'"><b>'.$fmt($totals['net']).'</b></td></tr>
            <tr><td style="padding:8px">Your Share</td>
                <td style="padding:8px;text-align:right"><b>'.number_format($investor['share_percentage'],2).'%</b></td></tr>
            <tr><td style="padding:8px;background:#fafafa;font-size:15px"><b>Your '.$type.'</b></td>
                <td style="padding:8px;background:#fafafa;text-align:right;color:'.$color.';font-size:15px"><b>'.$fmt($myShare).'</b></td></tr>
          </table>

          <p style="margin-top:20px;font-size:13px;color:#555">
            Log in to view the full report:<br>
            <a href="'.htmlspecialchars(APP_URL).'" style="color:#ff3b30">'.htmlspecialchars(APP_URL).'</a>
          </p>
          <p style="margin-top:18px;font-size:12px;color:#888">This is an automated message from '.$rest.'.</p>
        </div>
      </div>
    </div>';
}
