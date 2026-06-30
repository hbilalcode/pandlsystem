-- Restaurant Profit & Loss Management System
-- MySQL Schema (v3 - adds admin profile, password reset, email log)

CREATE DATABASE IF NOT EXISTS restaurant_pl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_pl;

-- Users table (login + roles + admin profile + password reset)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','investor') NOT NULL DEFAULT 'investor',
    investor_id INT NULL,
    full_name VARCHAR(100) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(20) NULL,
    reset_token VARCHAR(100) NULL,
    reset_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Investors table
CREATE TABLE IF NOT EXISTS investors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    investment_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    share_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE users
    ADD CONSTRAINT fk_users_investor
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date DATE NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    expense_type ENUM('cashier','vendor','salary','custom') NOT NULL,
    description VARCHAR(255) NULL,
    amount DECIMAL(14,2) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS profit_distributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_sales DECIMAL(14,2) NOT NULL,
    total_expenses DECIMAL(14,2) NOT NULL,
    net_amount DECIMAL(14,2) NOT NULL,
    share_percentage DECIMAL(5,2) NOT NULL,
    investor_amount DECIMAL(14,2) NOT NULL,
    type ENUM('profit','loss') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dist_investor FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Log of monthly report emails sent (prevents duplicate sends)
CREATE TABLE IF NOT EXISTS email_log (
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
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- MIGRATION (run if upgrading from v1/v2; safe to re-run)
-- ----------------------------------------------------------------
-- ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NULL AFTER investor_id;
-- ALTER TABLE users ADD COLUMN email VARCHAR(150) NULL AFTER full_name;
-- ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email;
-- ALTER TABLE users ADD COLUMN reset_token VARCHAR(100) NULL AFTER phone;
-- ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL AFTER reset_token;

-- Default Super Admin via /install.php (admin / admin123)
