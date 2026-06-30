<?php
// config/database.php
// Database connection (PDO + prepared statements) and app config

define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_pl');
define('DB_USER', 'root');
define('DB_PASS', '');

// Restaurant brand info (used in UI / reports / emails)
define('RESTAURANT_NAME',    'My Restaurant');
define('RESTAURANT_ADDRESS', '123 Main Street, Karachi, Pakistan');
define('CURRENCY',           'Rs.');

// Public URL of the app (used in password-reset & monthly email links).
// Example: http://localhost/restaurant-pl  OR  https://myrestaurant.com
define('APP_URL', 'http://localhost/restaurant-pl');

// "From" address for system emails. Must be a real mailbox on your server
// for PHP mail() to deliver reliably (or configure SMTP at your host).
define('MAIL_FROM',      'no-reply@example.com');
define('MAIL_FROM_NAME', RESTAURANT_NAME . ' P&L System');

// Pakistan phone format: 03XXXXXXXXX (11 digits) — used for validation
define('PK_PHONE_REGEX', '/^03[0-9]{9}$/');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
