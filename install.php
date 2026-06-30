<?php
/**
 * One-time installer. Open in browser AFTER importing database/schema.sql.
 * - (Re)creates Super Admin (admin / admin123)
 * - Runs idempotent column migrations for v3 upgrades
 * DELETE this file after install for production safety.
 */
require_once __DIR__ . '/config/database.php';

function col_exists(PDO $pdo, $table, $col) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $st->execute([$table, $col]);
    return (int)$st->fetchColumn() > 0;
}

$migrations = [
    ['users', 'full_name',     "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NULL"],
    ['users', 'email',         "ALTER TABLE users ADD COLUMN email VARCHAR(150) NULL"],
    ['users', 'phone',         "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL"],
    ['users', 'reset_token',   "ALTER TABLE users ADD COLUMN reset_token VARCHAR(100) NULL"],
    ['users', 'reset_expires', "ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL"],
];
$ran = [];
foreach ($migrations as [$t, $c, $sql]) {
    if (!col_exists($pdo, $t, $c)) { $pdo->exec($sql); $ran[] = "$t.$c"; }
}

// email_log table (in case schema not re-imported)
$pdo->exec("CREATE TABLE IF NOT EXISTS email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    email_to VARCHAR(150) NOT NULL,
    status ENUM('sent','failed') NOT NULL,
    error TEXT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_period (investor_id, period_start, period_end),
    CONSTRAINT fk_log_investor FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE
) ENGINE=InnoDB");

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$st = $pdo->prepare("SELECT id FROM users WHERE username=?");
$st->execute([$username]);
if ($st->fetch()) {
    $pdo->prepare("UPDATE users SET password=?, role='super_admin', investor_id=NULL WHERE username=?")
        ->execute([$hash, $username]);
    $msg = "Super Admin password reset.";
} else {
    $pdo->prepare("INSERT INTO users (username,password,role,full_name,email)
                   VALUES (?,?, 'super_admin','Administrator','admin@example.com')")
        ->execute([$username, $hash]);
    $msg = "Super Admin created.";
}

echo "<pre style='font:14px monospace;padding:20px;background:#f7f7f8'>";
echo $msg . "\n";
if ($ran) echo "Migrations applied: " . implode(', ', $ran) . "\n";
echo "\nUsername: {$username}\nPassword: {$password}\n\n";
echo "IMPORTANT: Log in, open Profile, set your real email, then DELETE install.php.";
echo "</pre>";
