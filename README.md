# Restaurant Profit & Loss Management System (PHP + MySQL)

A clean, beginner-friendly P&L system for restaurants. Track sales, expenses,
calculate profit/loss, and distribute results across investors by share %.

## Stack
PHP 8+, MySQL 5.7/8, PDO prepared statements, Bootstrap 5, Chart.js. No frameworks.

## Setup
1. Copy the `rpl/` folder into your web root (e.g. `htdocs/rpl/`).
2. Create the database & tables: import `database/schema.sql` in phpMyAdmin
   (or `mysql -u root -p < database/schema.sql`).
3. Edit `config/database.php` with your DB credentials and restaurant info.
4. Visit `http://localhost/rpl/install.php` once to create the Super Admin.
   Default credentials: **admin / admin123** (change after first login).
5. **Delete `install.php`** after the admin account is created.
6. Log in at `http://localhost/rpl/login.php`.

## Folder structure
```
rpl/
├── assets/           CSS + JS
├── config/           DB connection + brand constants
├── database/         schema.sql
├── includes/         auth, header, footer, sidebar, shared functions
├── investor/         Investor dashboard
├── pages/            Admin pages (sales, expenses, investors, dashboard)
├── reports/          Unified Reports hub (single menu, many filters)
├── index.php         Entry → redirects to dashboard or login
├── install.php       One-time admin bootstrap
├── login.php
└── logout.php
```

## Reports
A single **Reports** menu opens a unified hub (`reports/index.php`). Pick a
report type from the tab strip; only the relevant filters appear:

| Report                | Filters                    |
| --------------------- | -------------------------- |
| Overall Summary       | (none — all-time)          |
| Daily P&L             | Date                       |
| Monthly P&L           | Month                      |
| Yearly P&L            | Year                       |
| Custom Date Range P&L | From / To                  |
| Sales Detail          | From / To                  |
| Expense Detail        | From / To / Type           |
| Investor Distribution | From / To (+ Save snapshot)|
| Individual Investor   | Investor / From / To       |

Every report:
- Branded header (restaurant mark + name + address)
- KPI cards (Sales, Expenses, Net, Margin, etc.)
- Detail tables with category breakdowns where relevant
- Signature block + generated-by footer
- **Print/Save PDF** button — uses CSS `@media print` for a clean A4 output

## Security
- PDO prepared statements throughout (no SQL injection surface)
- `password_hash()` / `password_verify()` for credentials
- CSRF tokens on all POST forms
- Session-based auth with `require_login` / `require_admin` / `require_investor` guards
- Investors are scoped to their own data on every report

## Default Admin
- Username: `admin`
- Password: `admin123` (created by `install.php`)

Change the password immediately after first login.
