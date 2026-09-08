<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();

$page_title = 'Dashboard Overview';

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_subs = $pdo->query("SELECT COUNT(*) FROM users WHERE status='Active'")->fetchColumn();
$pending_pay = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE status='pending'")->fetchColumn();
$total_plans = $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn();

include 'header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="value"><?= $total_users ?></div>
        <div class="label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= $active_subs ?></div>
        <div class="label">Active Subscriptions</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= $pending_pay ?></div>
        <div class="label">Pending Payments</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= $total_plans ?></div>
        <div class="label">Active Plans</div>
    </div>
</div>

<div class="card">
    <h3>🚀 Welcome to Baba Panel</h3>
    <p style="color: #94a3b8; margin-top: 8px;">Use the 3-line menu (☰) above to navigate through Plans, Pending Payments, Users, Settings, and more.</p>
</div>

<?php include 'footer.php'; ?>
