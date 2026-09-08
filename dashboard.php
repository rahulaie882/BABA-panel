<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Dashboard';

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_subs = $pdo->query("SELECT COUNT(*) FROM users WHERE status='Active'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE status='pending'")->fetchColumn();

// Check if 'amount' column exists, otherwise handle safely
try {
    $total_revenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pending_payments WHERE status='approved'")->fetchColumn();
    $today_earning = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pending_payments WHERE status='approved' AND date(created_at)=date('now')")->fetchColumn();
} catch (Exception $e) {
    $total_revenue = 0;
    $today_earning = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BABA PANEL | Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        body { background: #0a0a0f; min-height: 100vh; color: white; display: flex; flex-direction: column; }
        .navbar { background: #14141f; border-bottom: 1px solid #1f1f2e; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .nav-brand { font-size: 20px; font-weight: 700; color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-brand span { background: linear-gradient(90deg, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-links { display: flex; gap: 15px; align-items: center; }
        .nav-links a { color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; transition: 0.2s; padding: 8px 12px; border-radius: 8px; }
        .nav-links a:hover, .nav-links a.active { color: white; background: #1f1f2e; }
        .logout-btn { color: #f87171 !important; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1) !important; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; width: 100%; flex: 1; }
        .welcome-banner { background: linear-gradient(135deg, #14141f, #1a1a2e); border: 1px solid #1f1f2e; border-radius: 20px; padding: 25px 30px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .welcome-banner h2 { font-size: 24px; margin-bottom: 5px; }
        .welcome-banner p { color: #94a3b8; font-size: 14px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #14141f; border: 1px solid #1f1f2e; border-radius: 20px; padding: 24px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .stat-card .icon { font-size: 28px; margin-bottom: 15px; background: #1f1f2e; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        .stat-card .value { font-size: 28px; font-weight: 700; margin-bottom: 5px; color: white; }
        .stat-card .label { color: #94a3b8; font-size: 14px; }

        .card { background: #14141f; border: 1px solid #1f1f2e; border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .footer { text-align: center; padding: 20px; color: #475569; font-size: 13px; border-top: 1px solid #1f1f2e; margin-top: auto; }
        .footer span { color: #3b82f6; }

        @media(max-width: 768px) {
            .navbar { flex-direction: column; gap: 15px; padding: 15px; }
            .nav-links { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">👑 BABA <span>PANEL</span></a>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="plans.php">Plans</a>
            <a href="pending.php">Pending</a>
            <a href="settings.php">Settings</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <div>
                <h2>Welcome back, Admin! 👋</h2>
                <p>Manage your Telegram bot subscription panel effortlessly.</p>
            </div>
            <div style="font-size: 36px;">⚡</div>
        </div>

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
            <h3 style="margin-bottom:15px; font-size: 18px;">📅 Today's Earning</h3>
            <div style="font-size:32px;font-weight:700;color:#34d399;">₹<?= number_format($today_earning) ?></div>
            <div style="color:#64748b;font-size:13px;margin-top:6px;">Total earnings from approved payments today (<?= date('d M Y') ?>)</div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:15px; font-size: 18px;">📈 Revenue & Orders (Last 30 Days)</h3>
            <div style="height:180px;display:flex;align-items:center;justify-content:center;color:#64748b;border:1px dashed #1e1e2d;border-radius:12px; font-size: 14px;">
                Graph will appear here when you have real payment data
            </div>
        </div>
    </div>

    <div class="footer">
        © 2026 BABA PANEL • <span>Premium Telegram Bot Dashboard</span>
    </div>

</body>
</html>
