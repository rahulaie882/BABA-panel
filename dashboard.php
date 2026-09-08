<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$admin_id = $_SESSION['admin_id'];

// Stats fetch kar rahe hain
$total_users = $pdo->query("SELECT COUNT(*) FROM bot_users")->fetchColumn();
$total_plans = $pdo->prepare("SELECT COUNT(*) FROM plans WHERE admin_id = ?");
$total_plans->execute([$admin_id]);
$active_plans = $total_plans->fetchColumn();

$pending_count = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BABA PANEL - Master Control</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #0b0c10; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow-x: hidden; }
        
        /* Top Header */
        .header { display: flex; justify-content: space-between; align-items: center; background: #12141d; padding: 15px 20px; border-bottom: 1px solid #1f2330; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .menu-btn { background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; transition: transform 0.2s; }
        .menu-btn:active { transform: scale(0.9); }
        .logo-title { font-size: 18px; font-weight: bold; letter-spacing: 0.5px; background: linear-gradient(45deg, #6366f1, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .admin-badge { background: #1f2330; padding: 6px 12px; border-radius: 20px; font-size: 13px; color: #9ca3af; border: 1px solid #2d3348; }

        /* Sidebar Drawer */
        .sidebar { position: fixed; top: 0; left: -280px; width: 280px; height: 100%; background: #12141d; border-right: 1px solid #1f2330; transition: left 0.3s ease-in-out; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; box-shadow: 5px 0 25px rgba(0,0,0,0.5); }
        .sidebar.active { left: 0; }
        .sidebar-header { padding: 0 20px 20px 20px; border-bottom: 1px solid #1f2330; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-menu { list-style: none; padding: 15px 0; overflow-y: auto; flex: 1; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #9ca3af; text-decoration: none; font-size: 14px; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { color: #fff; background: rgba(99, 102, 241, 0.1); border-left-color: #6366f1; }
        .sidebar-footer { padding: 15px 20px; border-top: 1px solid #1f2330; }
        .logout-btn { display: flex; align-items: center; gap: 10px; color: #ef4444; text-decoration: none; font-weight: 600; font-size: 14px; }

        /* Overlay */
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(2px); display: none; z-index: 900; }
        .overlay.active { display: block; }

        /* Main Container */
        .container { padding: 20px; max-width: 700px; margin: 0 auto; animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        /* Cards Style */
        .stat-card { background: #161922; border: 1px solid #212533; border-radius: 14px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .stat-title { font-size: 13px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .stat-value { font-size: 28px; font-weight: bold; color: #fff; }

        .action-card { background: linear-gradient(135deg, #161922 0%, #1f2330 100%); border: 1px solid #2d3348; border-radius: 16px; padding: 22px; margin-top: 20px; box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15); }
        .action-card h3 { font-size: 17px; margin-bottom: 10px; color: #fff; }
        .action-card p { font-size: 13px; color: #9ca3af; line-height: 1.5; margin-bottom: 18px; }
        
        .btn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .btn-item { display: block; padding: 12px; background: #0f1117; border: 1px solid #212533; color: #fff; text-align: center; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.2s; }
        .btn-item:hover { border-color: #6366f1; background: rgba(99, 102, 241, 0.1); }
    </style>
</head>
<body>

    <!-- Top Header -->
    <div class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">☰</button>
            <div class="logo-title">👑 BABA PANEL</div>
        </div>
        <div class="admin-badge">👤 <?= htmlspecialchars($_SESSION['admin_user']) ?></div>
    </div>

    <!-- Sidebar Drawer (All 13 Features Links) -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    <div class="sidebar" id="sidebar">
        <div>
            <div class="sidebar-header">
                <span style="font-weight:bold; font-size:15px; color:#6366f1;">Features Control</span>
                <button class="menu-btn" onclick="toggleSidebar()" style="font-size: 18px;">✕</button>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="settings.php">⚙️ 1. Bot Token Control</a></li>
                <li><a href="settings.php">💬 2. Welcome Message</a></li>
                <li><a href="plans.php">📦 3. Plans & Pricing</a></li>
                <li><a href="plans.php">🎥 4. Demo Videos / Files</a></li>
                <li><a href="payment.php">💳 5. UPI ID Setup</a></li>
                <li><a href="payment.php">🖼️ 6. QR Code Upload</a></li>
                <li><a href="pending.php">⏳ 7. Pending Approvals <span style="background:#ef4444; color:#fff; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:auto;"><?= $pending_count ?></span></a></li>
                <li><a href="users.php">👥 8. Bot Users List</a></li>
                <li><a href="users.php">🆔 9. User IDs Viewer</a></li>
                <li><a href="settings.php">📢 10. Join Log Channels</a></li>
                <li><a href="settings.php">💸 11. Payment Channels</a></li>
                <li><a href="settings.php">🎨 12. Bot Theme Style</a></li>
                <li><a href="settings.php">🔄 13. Backup & Restore</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </div>

    <!-- Main Dashboard Body -->
    <div class="container">
        <div class="stat-card">
            <div class="stat-title">Total Bot Users</div>
            <div class="stat-value" style="color: #6366f1;"><?= $total_users ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Active Plans</div>
            <div class="stat-value" style="color: #10b981;"><?= $active_plans ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Pending Payments</div>
            <div class="stat-value" style="color: #f59e0b;"><?= $pending_count ?></div>
        </div>

        <div class="action-card">
            <h3>⚡ Quick Feature Control Links</h3>
            <p>Yahan se direct click karke apne bot ke saare features control kar:</p>
            <div class="btn-grid">
                <a href="settings.php" class="btn-item">⚙️ Bot Token</a>
                <a href="settings.php" class="btn-item">💬 Welcome Msg</a>
                <a href="plans.php" class="btn-item">📦 Manage Plans</a>
                <a href="payment.php" class="btn-item">💳 UPI & QR</a>
                <a href="pending.php" class="btn-item">⏳ Pending Approvals</a>
                <a href="users.php" class="btn-item">👥 Bot Users</a>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        }
    </script>
</body>
</html>
