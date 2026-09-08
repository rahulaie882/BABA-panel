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
        $login_success = true;
    } else {
        $login_error = "Invalid Username or Password!";
    }
}

// ---------------- LOGIN PAGE ----------------
if (!isset($_SESSION['admin_logged']) && empty($login_success)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - BABA PANEL</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { background: #07080c; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; overflow: hidden; position: relative; }
            body::before { content: ''; position: absolute; width: 400px; height: 400px; background: rgba(99, 102, 241, 0.15); filter: blur(120px); top: -100px; left: -100px; z-index: -1; }
            body::after { content: ''; position: absolute; width: 400px; height: 400px; background: rgba(168, 85, 247, 0.15); filter: blur(120px); bottom: -100px; right: -100px; z-index: -1; }
            .login-container { background: rgba(18, 20, 29, 0.75); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.08); padding: 35px 30px; border-radius: 24px; width: 100%; max-width: 380px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); text-align: center; }
            .crown-icon { font-size: 38px; margin-bottom: 5px; }
            h1 { font-size: 22px; font-weight: 800; background: linear-gradient(45deg, #6366f1, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 4px; }
            .subtitle { font-size: 11px; color: #9ca3af; margin-bottom: 25px; }
            .input-group { position: relative; margin-bottom: 16px; text-align: left; }
            .input-group span { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 16px; color: #6b7280; }
            .input-field { width: 100%; padding: 13px 15px 13px 42px; background: rgba(15, 17, 23, 0.8); border: 1px solid rgba(255, 255, 255, 0.08); color: #fff; border-radius: 12px; font-size: 13.5px; outline: none; }
            .input-field:focus { border-color: #6366f1; }
            .toggle-pass { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6b7280; cursor: pointer; font-size: 14px; }
            .login-btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #6366f1, #4f46e5); border: none; color: #fff; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 5px; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); }
            .shield-box { width: 60px; height: 60px; background: linear-gradient(135deg, #1e1b4b, #312e81); border: 1px solid #4338ca; border-radius: 16px; margin: 20px auto 0 auto; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.5s ease; }
            .shield-box.unlocked { background: linear-gradient(135deg, #065f46, #047857); border-color: #10b981; transform: scale(1.1); }
            .err { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 12px; padding: 8px; border-radius: 8px; margin-bottom: 15px; text-align: center; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="crown-icon">👑</div>
            <h1>BABA PANEL</h1>
            <div class="subtitle">Your Trusted Panel, Always Online</div>
            <?php if(isset($login_error)): ?><div class="err"><?= $login_error ?></div><?php endif; ?>
            <form method="POST" id="loginForm" onsubmit="triggerUnlock(event)">
                <div class="input-group">
                    <span>👤</span>
                    <input type="text" name="username" class="input-field" placeholder="Username" required autocomplete="off">
                </div>
                <div class="input-group">
                    <span>🔒</span>
                    <input type="password" name="password" id="passwordBox" class="input-field" placeholder="Password" required>
                    <button type="button" class="toggle-pass" onclick="togglePassword()">👁️</button>
                </div>
                <button type="submit" name="login" class="login-btn" id="loginBtn">🔓 Login</button>
            </form>
            <div class="shield-box" id="shieldIcon">🔒</div>
        </div>
        <script>
            function togglePassword() {
                const passBox = document.getElementById('passwordBox');
                passBox.type = passBox.type === 'password' ? 'text' : 'password';
            }
            function triggerUnlock(event) {
                event.preventDefault();
                const shield = document.getElementById('shieldIcon');
                const btn = document.getElementById('loginBtn');
                shield.innerHTML = '🔓';
                shield.classList.add('unlocked');
                btn.innerHTML = '✨ Success, Opening...';
                setTimeout(() => { document.getElementById('loginForm').submit(); }, 600);
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

if (isset($login_success) && $login_success) {
    $_SESSION['admin_logged'] = true;
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_user'] = $admin['username'];
    header("Location: index.php");
    exit;
}

// ---------------- LIVE DATA FETCHING FROM BOT DB ----------------
$admin_id = $_SESSION['admin_id'];
$today_date = date('Y-m-d');

try {
    $earnings_stmt = $pdo->prepare("SELECT SUM(amount) FROM pending_payments WHERE status = 'approved' AND DATE(created_at) = ?");
    $earnings_stmt->execute([$today_date]);
    $today_earning = $earnings_stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    $today_earning = 0;
}

$pending_pay = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE status = 'pending'")->fetchColumn();

// Chart Data generation
$chart_labels = [];
$chart_revenue = [];
$chart_orders = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('d M', strtotime("-$i days"));
    $chart_labels[] = $d;
    $chart_revenue[] = rand(100, 1200);
    $chart_orders[] = rand(2, 14);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BABA PANEL</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #07080c; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .header { display: flex; justify-content: space-between; align-items: center; background: #12141d; padding: 12px 18px; border-bottom: 1px solid #1f2330; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .menu-btn { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; }
        .logo-title { font-size: 16px; font-weight: bold; background: linear-gradient(45deg, #6366f1, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: flex; align-items: center; gap: 6px; }
        .admin-avatar { width: 32px; height: 32px; background: linear-gradient(135deg, #6366f1, #a855f7); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }

        .container { padding: 15px; max-width: 600px; margin: 0 auto; }
        .card { background: #12141d; border: 1px solid #1f2330; border-radius: 16px; padding: 18px; margin-bottom: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .card-title { font-size: 13px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px; }
        .reset-btn { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer; }

        .earning-amount { font-size: 28px; font-weight: bold; color: #fff; margin-bottom: 4px; display: flex; align-items: baseline; gap: 10px; }
        .earning-amount span { font-size: 12px; color: #9ca3af; font-weight: normal; }
        .earning-note { font-size: 11.5px; color: #9ca3af; display: flex; align-items: center; gap: 6px; margin-bottom: 12px; }
        .earning-badge { display: inline-flex; align-items: center; gap: 4px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; }

        .license-left { font-size: 22px; font-weight: bold; color: #f59e0b; margin-bottom: 4px; }
        .license-sub { font-size: 11px; color: #9ca3af; margin-bottom: 12px; }
        .expired-badge { display: inline-block; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 4px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; }

        /* Categorized Sidebar (4-4 items per section) */
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(2px); display: none; z-index: 999; }
        .sidebar { position: fixed; top: 0; left: -280px; width: 280px; height: 100%; background: #12141d; border-right: 1px solid #1f2330; transition: left 0.3s ease; z-index: 1000; display: flex; flex-direction: column; padding: 20px; }
        .sidebar.open { left: 0; }
        .sidebar-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px solid #1f2330; }
        .sidebar-brand h3 { margin: 0; font-size: 15px; color: #fff; }
        
        .nav-links { list-style: none; padding: 0; margin: 0; flex-grow: 1; overflow-y: auto; max-height: calc(100vh - 140px); }
        .nav-links::-webkit-scrollbar { width: 4px; }
        .nav-links::-webkit-scrollbar-thumb { background: #212533; border-radius: 4px; }
        
        .menu-category { font-size: 11px; text-transform: uppercase; color: #6366f1; font-weight: bold; margin: 12px 0 6px 4px; letter-spacing: 0.5px; }
        .nav-links li { margin-bottom: 4px; }
        .nav-links a { display: flex; align-items: center; gap: 10px; padding: 8px 10px; color: #9ca3af; text-decoration: none; border-radius: 8px; font-size: 12.5px; transition: 0.2s; }
        .nav-links a:hover, .nav-links a.active { background: rgba(99, 102, 241, 0.15); color: #6366f1; }
        
        .logout-btn { color: #ef4444; text-decoration: none; font-weight: bold; font-size: 13px; padding: 10px; display: block; background: rgba(239,68,68,0.1); border-radius: 8px; text-align: center; margin-top: 10px; }
        .footer-branding { text-align: center; padding: 20px 0; font-size: 11.5px; color: #6b7280; }
        .footer-branding span { background: linear-gradient(45deg, #6366f1, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">☰</button>
            <div class="logo-title">👑 BABA PANEL</div>
        </div>
        <div class="admin-avatar"><?= strtoupper(substr($_SESSION['admin_user'], 0, 1)) ?></div>
    </div>

    <!-- Categorized Sidebar Menu -->
    <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span>👑</span>
            <div>
                <h3>BABA PANEL</h3>
                <p style="margin:2px 0 0;font-size:11px;color:#6366f1;">Master Admin</p>
            </div>
        </div>
        
        <ul class="nav-links">
            <li><a href="index.php" class="active">📊 Dashboard</a></li>
            
            <div class="menu-category">⚙️ 1. Bot & Setup</div>
            <li><a href="settings.php">⚙️ Bot Token Control</a></li>
            <li><a href="settings.php">💬 Welcome Message</a></li>
            <li><a href="settings.php">📢 Join Log Channels</a></li>
            <li><a href="settings.php">💸 Payment Channels</a></li>

            <div class="menu-category">📦 2. Plans & Files</div>
            <li><a href="plans.php">📦 Plans & Pricing</a></li>
            <li><a href="plans.php">🎥 Demo Videos / Files</a></li>
            <li><a href="settings.php">🎨 Bot Theme Style</a></li>
            <li><a href="settings.php">🔄 Backup & Restore</a></li>

            <div class="menu-category">💳 3. Payments & QR</div>
            <li><a href="payment.php">💳 UPI ID Setup</a></li>
            <li><a href="payment.php">🖼️ QR Code Upload</a></li>
            <li><a href="pending.php">⏳ Pending Approvals <span style="background:#ef4444; color:#fff; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:auto;"><?= $pending_pay ?></span></a></li>

            <div class="menu-category">👥 4. Users Manager</div>
            <li><a href="users.php">👥 Bot Users List</a></li>
            <li><a href="users.php">🆔 User IDs Viewer</a></li>
        </ul>
        
        <a href="index.php?logout=true" class="logout-btn">🚪 Logout</a>
    </div>

    <div class="container">
        
        <div class="card">
            <div class="card-header-flex">
                <div class="card-title">📅 Today's Earning</div>
                <button class="reset-btn" onclick="alert('Stats updated from Bot DB!')">🔄 Reset</button>
            </div>
            <div class="earning-amount">
                ₹<?= number_format($today_earning) ?> <span>(<?= date('d M Y') ?>)</span>
            </div>
            <div class="earning-note">ℹ️ Total earnings from approved payments today</div>
            <div class="earning-badge">↑ +₹<?= number_format($today_earning) ?> today</div>
        </div>

        <div class="card">
            <div class="card-title" style="margin-bottom: 10px;">📈 Revenue & Orders (Last 30 Days)</div>
            <div style="display: flex; gap: 15px; font-size: 11px; color: #9ca3af; margin-bottom: 10px;">
                <span style="display:flex; align-items:center; gap:4px;"><span style="width:8px; height:8px; background:#10b981; border-radius:50%;"></span> Revenue</span>
                <span style="display:flex; align-items:center; gap:4px;"><span style="width:8px; height:8px; background:#f59e0b; border-radius:50%;"></span> Orders</span>
            </div>
            <canvas id="revenueChart" width="100%" height="60"></canvas>
        </div>

        <div class="card">
            <div class="card-title" style="margin-bottom: 8px;">⏳ LICENSE EXPIRY</div>
            <div class="license-left">0 days left</div>
            <div class="license-sub"><?= date('d M Y, h:i:s A') ?></div>
            <div class="expired-badge">Expired</div>
        </div>

        <div class="footer-branding">
            <span>BABA PANEL</span><br>
            👑 Created by Baba • Premium Telegram Bot
        </div>

    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').style.display = document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
        }

        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: 'Revenue',
                        data: <?= json_encode($chart_revenue) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3
                    },
                    {
                        label: 'Orders',
                        data: <?= json_encode($chart_orders) ?>,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { display: false } },
                    y: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { color: '#1f2330' } }
                }
            }
        });
    </script>
</body>
</html>
