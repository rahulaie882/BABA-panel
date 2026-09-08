<?php
// ==========================================
// FILE: index.php (BABA PANEL - Full Dashboard & Animated Sidebar)
// ==========================================
define('BABA_PANEL', true);
require_once 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid Username or Password!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// ---------------------------------
// LOGIN PAGE (3D Animated Glassmorphism)
// ---------------------------------
if (!isset($_SESSION['admin_logged'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - BABA PANEL</title>
        <style>
            * { box-sizing: border-box; }
            body {
                background: #090a0f;
                background-image: 
                    radial-gradient(at 10% 20%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                    radial-gradient(at 90% 80%, rgba(16, 185, 129, 0.15) 0px, transparent 50%);
                color: #fff;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                overflow: hidden;
            }
            .login-card {
                background: rgba(22, 24, 33, 0.75);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                padding: 40px 30px;
                border-radius: 20px;
                width: 360px;
                border: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 40px rgba(99, 102, 241, 0.1);
            }
            .login-header {
                text-align: center;
                margin-bottom: 25px;
            }
            .login-header h2 {
                font-size: 24px;
                margin: 10px 0 0 0;
                background: linear-gradient(135deg, #fff 30%, #a5b4fc 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .logo-icon { font-size: 36px; }
            .input-group { margin-bottom: 15px; }
            input {
                width: 100%;
                padding: 14px 16px;
                background: rgba(15, 17, 23, 0.8);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #fff;
                border-radius: 12px;
                font-size: 15px;
                outline: none;
            }
            input:focus { border-color: #6366f1; box-shadow: 0 0 15px rgba(99, 102, 241, 0.3); }
            button {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                border: none;
                color: #fff;
                border-radius: 12px;
                font-weight: 600;
                font-size: 16px;
                cursor: pointer;
                box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
                margin-top: 10px;
            }
            .err {
                background: rgba(239, 68, 68, 0.1);
                color: #ef4444;
                font-size: 13px;
                padding: 10px;
                border-radius: 8px;
                margin-bottom: 15px;
                border: 1px solid rgba(239, 68, 68, 0.2);
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">👑</div>
                <h2>BABA PANEL</h2>
            </div>
            <?php if($error): ?><div class="err"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Username" required autocomplete="off">
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" name="login">Access Panel</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ---------------------------------
// DASHBOARD STATS FETCHING
// ---------------------------------
$admin_id = $_SESSION['admin_id'];
$total_users = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM pending_payments WHERE admin_id = $admin_id")->fetchColumn() ?: 0;
$active_subscribers = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE admin_id = $admin_id AND status='approved'")->fetchColumn() ?: 0;
$pending_count = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE admin_id = $admin_id AND status='pending'")->fetchColumn() ?: 0;
$total_revenue = $pdo->query("SELECT SUM(amount) FROM pending_payments WHERE admin_id = $admin_id AND status='approved'")->fetchColumn() ?: 0;
$today_earning = $pdo->query("SELECT SUM(amount) FROM pending_payments WHERE admin_id = $admin_id AND status='approved' AND DATE(created_at) = DATE('now')")->fetchColumn() ?: 0;
$current_date = date('d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BABA PANEL</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background-color: #0b0c10;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }
        /* Top Navigation Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #12141d;
            padding: 15px 20px;
            border-bottom: 1px solid #1f2330;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .menu-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 22px;
            cursor: pointer;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .user-avatar {
            background: linear-gradient(135deg, #6366f1, #a5b4fc);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.4);
        }

        /* Sidebar Overlay & Drawer */
        .sidebar-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(3px);
            display: none;
            z-index: 999;
        }
        .sidebar {
            position: fixed;
            top: 0; left: -280px; width: 280px; height: 100%;
            background: #12141d;
            border-right: 1px solid #1f2330;
            transition: left 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        .sidebar.open { left: 0; }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #1f2330;
        }
        .sidebar-brand span { font-size: 26px; }
        .sidebar-brand h3 { margin: 0; font-size: 16px; color: #fff; }
        .sidebar-brand p { margin: 2px 0 0 0; font-size: 11px; color: #6366f1; }
        
        .nav-links { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .nav-links li { margin-bottom: 8px; }
        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: #9ca3af;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .nav-links a:hover, .nav-links a.active {
            background: rgba(99, 102, 241, 0.15);
            color: #6366f1;
        }
        .logout-box {
            border-top: 1px solid #1f2330;
            padding-top: 15px;
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 10px;
            border-radius: 8px;
            background: rgba(239, 68, 68, 0.08);
        }

        /* Main Content Container */
        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Wang Panel Cards Style */
        .stat-card {
            background: #161922;
            border: 1px solid #212533;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }
        .stat-card:hover {
            border-color: #6366f1;
            transform: translateY(-2px);
        }
        .stat-card .icon-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        .stat-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
        }
        .stat-card .subtext {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
        .highlight-val { color: #10b981; }
    </style>
</head>
<body>

    <!-- Top Header -->
    <div class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">☰</button>
            <h1>BABA PANEL</h1>
        </div>
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['admin_user'], 0, 1)) ?></div>
    </div>

    <!-- Sidebar Menu Drawer -->
    <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span>👑</span>
            <div>
                <h3>BABA PANEL</h3>
                <p>Admin Dashboard</p>
            </div>
        </div>
        <ul class="nav-links">
            <li><a href="index.php" class="active">📊 Dashboard</a></li>
            <li><a href="plans.php">📦 Plans</a></li>
            <li><a href="pending.php">⏳ Pending</a></li>
            <li><a href="users.php">👥 Users</a></li>
            <li><a href="payment.php">💳 Payment</a></li>
            <li><a href="groups.php">🔗 Groups</a></li>
            <li><a href="backup.php">💾 Backup</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
        </ul>
        <div class="logout-box">
            <a href="?logout=true" class="logout-btn">🚪 Logout</a>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="container">
        
        <!-- 1. Total Users -->
        <div class="stat-card">
            <div class="icon-label">👥 Total Users</div>
            <div class="value"><?= $total_users ?></div>
        </div>

        <!-- 2. Active Subscribers -->
        <div class="stat-card">
            <div class="icon-label">✅ Active Subscribers</div>
            <div class="value"><?= $active_subscribers ?></div>
        </div>

        <!-- 3. Pending Payments -->
        <div class="stat-card">
            <div class="icon-label">⏳ Pending Payments</div>
            <div class="value" style="color: #f59e0b;"><?= $pending_count ?></div>
        </div>

        <!-- 4. Total Revenue -->
        <div class="stat-card">
            <div class="icon-label">💰 Total Revenue</div>
            <div class="value">₹<?= number_format($total_revenue) ?></div>
        </div>

        <!-- 5. Today's Earning -->
        <div class="stat-card">
            <div class="icon-label">📅 Today's Earning</div>
            <div class="value highlight-val">₹<?= number_format($today_earning) ?></div>
            <div class="subtext">Total earnings from approved payments today (<?= $current_date ?>)</div>
        </div>

    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').style.display = 
                document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
        }
    </script>
</body>
</html>
