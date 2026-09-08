<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Dashboard';

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_subs = $pdo->query("SELECT COUNT(*) FROM users WHERE status='Active'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE status='pending'")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pending_payments WHERE status='approved'")->fetchColumn();
$today_earning = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pending_payments WHERE status='approved' AND date(created_at)=date('now')")->fetchColumn();

require_once 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon">👥</div>
        <div class="value"><?= number_format($total_users) ?></div>
        <div class="label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="icon">✅</div>
        <div class="value"><?= number_format($active_subs) ?></div>
        <div class="label">Active Subscribers</div>
    </div>
    <div class="stat-card">
        <div class="icon">⏳</div>
        <div class="value"><?= number_format($pending) ?></div>
        <div class="label">Pending Payments</div>
    </div>
    <div class="stat-card">
        <div class="icon">💰</div>
        <div class="value">₹<?= number_format($total_revenue) ?></div>
        <div class="label">Total Revenue</div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">📅 Today's Earning</h3>
    <div style="font-size:32px;font-weight:700;color:#34d399;">₹<?= number_format($today_earning) ?></div>
    <div style="color:#64748b;font-size:13px;margin-top:6px;">Total earnings from approved payments today (<?= date('d M Y') ?>)</div>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">📈 Revenue & Orders (Last 30 Days)</h3>
    <div style="height:200px;display:flex;align-items:center;justify-content:center;color:#64748b;border:1px dashed #1e1e2d;border-radius:12px;">
        Graph will appear here when you have real payment data
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
