<?php $role = $_SESSION['user']['role'] ?? ''; ?>
<aside class="sidebar">
  <?php if ($role === 'super_admin'): ?>
    <div class="group">Overview</div>
    <a href="<?= base_url('pages/dashboard.php') ?>">Dashboard</a>

    <div class="group">Operations</div>
    <a href="<?= base_url('pages/sales/index.php') ?>">Sales</a>
    <a href="<?= base_url('pages/expenses/index.php') ?>">Expenses</a>
    <a href="<?= base_url('pages/investors/index.php') ?>">Investors</a>

    <div class="group">Insights</div>
    <a href="<?= base_url('reports/index.php') ?>">Reports</a>
    <a href="<?= base_url('pages/send_monthly.php') ?>">Send Monthly Email</a>

    <div class="group">Account</div>
    <a href="<?= base_url('pages/profile.php') ?>">My Profile</a>
  <?php else: ?>
    <div class="group">Investor</div>
    <a href="<?= base_url('investor/dashboard.php') ?>">My Dashboard</a>
    <a href="<?= base_url('reports/index.php?type=individual&self=1') ?>">My Reports</a>
  <?php endif; ?>
</aside>
