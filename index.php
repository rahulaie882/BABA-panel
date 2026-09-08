<?php
define('BABA_PANEL', true);
require_once 'config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$user]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($pass, $admin['password'])) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        header("Location: index.php");
        exit;
    } else {
        $login_error = "Invalid Username or Password!";
    }
}

if (!isset($_SESSION['admin_logged'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - BABA PANEL</title>
        <style>
            body { background: #0b0c10; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-card { background: #161922; border: 1px solid #212533; padding: 30px; border-radius: 14px; width: 320px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
            h2 { margin-top: 0; font-size: 20px; text-align: center; color: #6366f1; }
            label { font-size: 13px; color: #9ca3af; display: block; margin-bottom: 5px; }
            input { width: 100%; padding: 12px; background: #0f1117; border: 1px solid #212533; color: #fff; border-radius: 8px; margin-bottom: 15px; outline: none; }
            input:focus { border-color: #6366f1; }
            button { width: 100%; padding: 12px; background: #6366f1; border: none; color: #fff; border-radius: 8px; font-weight: bold; cursor: pointer; }
            .err { color: #ef4444; font-size: 12px; text-align: center; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class="login-card">
            <h2>👑 BABA PANEL</h2>
            <?php if(isset($login_error)): ?><div class="err"><?= $login_error ?></div><?php endif; ?>
            <form method="POST">
                <label>Username</label>
                <input type="text" name="username" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$admin_id = $_SESSION['admin_id'];
$total_users = $pdo->query("SELECT COUNT(*) FROM bot_users")->fetchColumn();
$total_plans = $pdo->prepare("SELECT COUNT(*) FROM plans WHERE admin_id = ?");
$total_plans->execute([$admin_id]);
$total_plans_count = $total_plans->fetchColumn();

$pending_pay = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BABA PANEL</title>
    <style>
        * { box-sizing: border-box; }
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #12141d; padding: 15px 20px; border-bottom: 1px solid #1f2330; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .menu-btn { background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; }
        .header h1 { font-size: 18px; margin: 0; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; z-index: 999; }
        .sidebar { position: fixed; top: 0; left: -280px; width: 280px; height: 100%; background: #12141d; border-right: 1px solid #1f2330; transition: left 0.3s ease; z-index: 1000; display: flex; flex-direction: column; padding: 20px; }
        .sidebar.open { left: 0; }
        .sidebar-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #1f2330; }
        .sidebar-brand h3 { margin: 0; font-size: 16px; }
        
        .nav-links { list-style: none; padding: 0; margin: 0; flex-grow: 1; overflow-y: auto; max-height: calc(100vh - 150px); }
        .nav-links::-webkit-scrollbar { width: 4px; }
        .nav-links::-webkit-scrollbar-thumb { background: #212533; border-radius: 4px; }
        .nav-links li { margin-bottom: 6px; }
        .nav-links a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #9ca3af; text-decoration: none; border-radius: 8px; font-size: 13px; transition: 0.2s; }
        .nav-links a:hover, .nav-links a.active { background: rgba(99, 102, 241, 0.15); color: #6366f1; }
        
        .logout-btn { color: #ef4444; text-decoration: none; font-weight: bold; font-size: 14px; padding: 10px; display: block; background: rgba(239,68,68,0.1); border-radius: 8px; text-align: center; margin-top: 10px; }
        .container { padding: 20px; max-width: 800px; margin: 0 auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #161922; border: 1px solid #212533; padding: 20px; border-radius: 14px; }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 14px; color: #9ca3af; }
        .stat-card p { margin: 0; font-size: 24px; font-weight: bold; color: #6366f1; }
        .card { background: #161922; border: 1px solid #212533; border-radius: 14px; padding: 20px; margin-bottom: 20px; }
        .btn-primary { display: block; width: 100%; padding: 14px; background: #6366f1; color: #fff; text-align: center; text-decoration: none; border-radius: 10px; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left"><button class="menu-btn" onclick="toggleSidebar()">☰</button><h1>BABA PANEL</h1></div>
        <div>👤 <?= htmlspecialchars($_SESSION['admin_user']) ?></div>
    </div>
    
    <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand"><span>👑</span><div><h3>BABA PANEL</h3><p style="margin:2px 0 0;font-size:11px;color:#6366f1;">Master Admin</p></div></div>
        
        <ul class="nav-links">
            <li><a href="index.php" class="active">📊 Dashboard</a></li>
            <li><a href="settings.php">⚙️ 1. Bot Token Control</a></li>
            <li><a href="settings.php">💬 2. Welcome Message</a></li>
            <li><a href="plans.php">📦 3. Plans & Pricing</a></li>
            <li><a href="plans.php">🎥 4. Demo Videos / Files</a></li>
            <li><a href="payment.php">💳 5. UPI ID Setup</a></li>
            <li><a href="payment.php">🖼️ 6. QR Code Upload</a></li>
            <li><a href="pending.php">⏳ 7. Pending Approvals <span style="background:#ef4444; color:#fff; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:auto;"><?= $pending_pay ?></span></a></li>
            <li><a href="users.php">👥 8. Bot Users List</a></li>
            <li><a href="users.php">🆔 9. User IDs Viewer</a></li>
            <li><a href="settings.php">📢 10. Join Log Channels</a></li>
            <li><a href="settings.php">💸 11. Payment Channels</a></li>
            <li><a href="settings.php">🎨 12. Bot Theme Style</a></li>
            <li><a href="settings.php">🔄 13. Backup & Restore</a></li>
        </ul>
        
        <a href="index.php?logout=true" class="logout-btn">🚪 Logout</a>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card"><h3>Total Bot Users</h3><p><?= $total_users ?></p></div>
            <div class="stat-card"><h3>Active Plans</h3><p><?= $total_plans_count ?></p></div>
            <div class="stat-card"><h3>Pending Payments</h3><p style="color:#f59e0b;"><?= $pending_pay ?></p></div>
        </div>

        <div class="card">
            <h2>🚀 Quick Setup & Management</h2>
            <p style="color:#9ca3af; font-size:14px; line-height:1.5;">Yahan se tu apne bot ke saare features (Bot Token, Welcome Message, Plans, QR Code, UPI ID, Channels, Pending Payments aur Backup) ek hi jagah par manage kar sakta hai.</p>
            <a href="features.php" class="btn-primary">Open Bot & Feature Control Panel →</a>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').style.display = document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
        }
    </script>
</body>
</html>
