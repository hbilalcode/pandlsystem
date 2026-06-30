<?php
/**
 * CRON: Auto-email previous month's P&L to each active investor.
 *
 * Schedule on the 1st of every month (cPanel / crontab):
 *   0 6 1 * *  /usr/bin/php /path/to/restaurant-pl/cron/send_monthly_reports.php
 *
 * Or trigger manually from admin sidebar → "Send Monthly Email".
 * Re-running is safe: each (investor, period) row in email_log is unique.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$from = date('Y-m-01', strtotime('first day of last month'));
$to   = date('Y-m-t',  strtotime('first day of last month'));
$label= date('F Y',    strtotime($from));

$sales = total_sales($pdo, $from, $to);
$exp   = total_expenses($pdo, $from, $to);
$net   = $sales - $exp;

$sent = 0; $skipped = 0; $failed = 0;
foreach (active_investors($pdo) as $inv) {
    if (!valid_email($inv['email'])) { $skipped++; continue; }

    $chk = $pdo->prepare("SELECT id FROM email_log WHERE investor_id=? AND period_start=? AND period_end=? AND status='sent'");
    $chk->execute([$inv['id'], $from, $to]);
    if ($chk->fetch()) { $skipped++; continue; }

    $myShare = round($net * ((float)$inv['share_percentage']/100), 2);
    $html    = build_monthly_email_html($inv,
        ['start'=>$from,'end'=>$to,'label'=>$label],
        ['sales'=>$sales,'expenses'=>$exp,'net'=>$net],
        $myShare);
    [$ok, $err] = send_html_mail($inv['email'],
        RESTAURANT_NAME . " — Monthly P&L Report ($label)", $html);

    $pdo->prepare("INSERT INTO email_log (investor_id,period_start,period_end,email_to,status,error)
                   VALUES (?,?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE status=VALUES(status), error=VALUES(error), sent_at=CURRENT_TIMESTAMP")
        ->execute([$inv['id'], $from, $to, $inv['email'], $ok?'sent':'failed', $ok?null:$err]);

    $ok ? $sent++ : $failed++;
}
echo "[".date('c')."] Monthly P&L emails for $label — sent:$sent skipped:$skipped failed:$failed\n";
