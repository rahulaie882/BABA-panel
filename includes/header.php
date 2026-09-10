<?php
if (!defined('BABA_PANEL')) {
    require_once __DIR__ . '/../config.php';
    requireLogin();
}

$current_page = basename($_SERVER['PHP_SELF']);
$primary   = getSetting('primary_color') ?: '#3b82f6';
$secondary = getSetting('secondary_color') ?: '#8b5cf6';
$panel_name = getSetting('panel_name') ?: 'BABA PANEL';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($panel_name) ?> | <?= $page_title ?? 'Dashboard' ?></title>
    <style>
        :root {
            --primary: <?= htmlspecialchars($primary) ?>;
            --secondary: <?= htmlspecialchars($secondary) ?>;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        body { background: #0a0a0f; color: #e2e8f0; min-height: 100vh; display: flex; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: #111118; border-right: 1px solid #1e1e2d; height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; z-index: 100; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid #1e1e2d; display: flex; align-items: center; gap: 12px; }
        .sidebar-logo { width: 42px; height: 42px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .sidebar-title { font-weight: 700; font-size: 16px; }
        .sidebar-title span { background: linear-gradient(90deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-sub { font-size: 11px; color: #64748b; }
        
        .nav { flex: 1; padding: 12px 10px; overflow-y: auto; }
        .nav a { display: flex; align-items: center; gap: 12px; padding: 11px 16px; color: #94a3b8; text-decoration: none; border-radius: 10px; margin-bottom: 3px; font-size: 14px; transition: 0.2s; }
        .nav a:hover { background: #1e1e2d; color: #fff; }
        .nav a.active { background: linear-gradient(90deg, color-mix(in srgb, var(--primary) 18%, transparent), color-mix(in srgb, var(--secondary) 18%, transparent)); color: var(--primary); border-left: 3px solid var(--primary); }
        .nav .badge { margin-left: auto; background: var(--primary); color: white; font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
        
        .logout { padding: 15px 10px; border-top: 1px solid #1e1e2d; }
        .logout a { color: #f87171 !important; }
        
        /* Main */
        .main { margin-left: 260px; flex: 1; min-height: 100vh; }
        .topbar { background: #111118; border-bottom: 1px solid #1e1e2d; padding: 14px 25px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 50; }
        .topbar-title { font-size: 18px; font-weight: 600; }
        .admin-badge { background: linear-gradient(135deg, var(--primary), var(--secondary)); width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; }
        
        .content { padding: 25px; }
        
        /* Cards */
        .card { background: #14141f; border: 1px solid #1e1e2d; border-radius: 16px; padding: 22px; margin-bottom: 20px; }
        .card h3 { margin-bottom: 16px; font-size: 16px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 25px; }
        .stat-card { background: #14141f; border: 1px solid #1e1e2d; border-radius: 16px; padding: 20px; }
        .stat-card .icon { font-size: 26px; margin-bottom: 10px; }
        .stat-card .value { font-size: 26px; font-weight: 700; margin-bottom: 4px; }
        .stat-card .label { font-size: 13px; color: #64748b; }
        
        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; border: none; cursor: pointer; font-size: 14px; font-weight: 500; text-decoration: none; transition: 0.2s; }
        .btn-primary { background: linear-gradient(90deg, var(--primary), var(--secondary)); color: white; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-danger { background: #7f1d1d; color: #fecaca; }
        .btn-success { background: #065f46; color: #a7f3d0; }
        .btn-secondary { background: #1e1e2d; color: #94a3b8; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        
        /* Forms */
        input, textarea, select { width: 100%; padding: 12px 14px; background: #0f0f17; border: 1px solid #1e1e2d; border-radius: 10px; color: white; font-size: 14px; outline: none; margin-bottom: 12px; }
        input:focus, textarea:focus, select:focus { border-color: var(--primary); }
        label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
        small { color: #64748b; font-size: 12px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #1e1e2d; font-size: 14px; }
        th { color: #64748b; font-weight: 500; font-size: 13px; }
        
        /* Badges */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-green { background: #065f46; color: #a7f3d0; }
        .badge-red { background: #7f1d1d; color: #fecaca; }
        .badge-yellow { background: #78350f; color: #fde68a; }
        .badge-blue { background: #1e3a5f; color: #93c5fd; }
        
        /* Alerts */
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: #065f46; color: #a7f3d0; }
        .alert-error { background: #7f1d1d; color: #fecaca; }
        .alert-info { background: #1e3a5f; color: #93c5fd; }
        
        /* Image preview */
        .img-preview { max-width: 180px; border-radius: 10px; border: 1px solid #1e1e2d; margin-top: 8px; }
        
        /* Grid helpers */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-title, .sidebar-sub, .nav a span:not(.badge) { display: none; }
            .main { margin-left: 70px; }
            .nav a { justify-content: center; padding: 12px; }
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">👑</div>
            <div>
                <div class="sidebar-title">BABA <span>PANEL</span></div>
                <div class="sidebar-sub">by Baba</div>
            </div>
        </div>
        <div class="nav">
            <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">📊 <span>Dashboard</span></a>
            <a href="start_message.php" class="<?= $current_page == 'start_message.php' ? 'active' : '' ?>">🎬 <span>Start Message</span></a>
            <a href="plans.php" class="<?= $current_page == 'plans.php' ? 'active' : '' ?>">📦 <span>Plans</span></a>
            <a href="pending.php" class="<?= $current_page == 'pending.php' ? 'active' : '' ?>">⏳ <span>Pending</span><?php
                $pc = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE status='pending'")->fetchColumn();
                if ($pc > 0) echo " <span class='badge'>$pc</span>";
            ?></a>
            <a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">👥 <span>Users</span></a>
            <a href="payment.php" class="<?= $current_page == 'payment.php' ? 'active' : '' ?>">💳 <span>Payment</span></a>
            <a href="broadcast.php" class="<?= $current_page == 'broadcast.php' ? 'active' : '' ?>">📢 <span>Broadcast</span></a>
            <a href="groups.php" class="<?= $current_page == 'groups.php' ? 'active' : '' ?>">🔗 <span>Groups</span></a>
            <a href="settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>">⚙️ <span>Settings</span></a>
            <a href="backup.php" class="<?= $current_page == 'backup.php' ? 'active' : '' ?>">💾 <span>Backup</span></a>
        </div>
        <div class="logout">
            <a href="logout.php">🚪 <span>Logout</span></a>
        </div>
    </div>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title"><?= $page_title ?? 'Dashboard' ?></div>
            <div class="admin-badge"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'B', 0, 1)) ?></div>
        </div>
        <div class="content">
