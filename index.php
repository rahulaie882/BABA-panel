<?php
// ==========================================
// FILE: index.php (3D Animated Login & Dashboard)
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
                perspective: 1000px;
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
                transform-style: preserve-3d;
                animation: floatCard 6s ease-in-out infinite;
                transition: transform 0.3s ease;
            }
            .login-card:hover {
                transform: translateY(-5px) rotateX(2deg) rotateY(-2deg);
            }
            @keyframes floatCard {
                0%, 100% { transform: translateY(0px) rotateX(0deg) rotateY(0deg); }
                50% { transform: translateY(-10px) rotateX(2deg) rotateY(2deg); }
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
                letter-spacing: 0.5px;
            }
            .logo-icon {
                font-size: 36px;
                animation: pulseIcon 2s infinite;
            }
            @keyframes pulseIcon {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); filter: drop-shadow(0 0 10px rgba(99, 102, 241, 0.5)); }
            }
            .input-group {
                position: relative;
                margin-bottom: 15px;
            }
            input {
                width: 100%;
                padding: 14px 16px;
                background: rgba(15, 17, 23, 0.8);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #fff;
                border-radius: 12px;
                font-size: 15px;
                outline: none;
                transition: all 0.3s ease;
            }
            input:focus {
                border-color: #6366f1;
                box-shadow: 0 0 15px rgba(99, 102, 241, 0.3);
                background: rgba(15, 17, 23, 1);
            }
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
                transition: all 0.3s ease;
                margin-top: 10px;
            }
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 15px 25px rgba(99, 102, 241, 0.5);
                background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
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

$admin_id = $_SESSION['admin_id'];
$total_users = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM pending_payments WHERE admin_id = $admin_id")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE admin_id = $admin_id AND status='pending'")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(amount) FROM pending_payments WHERE admin_id = $admin_id AND status='approved'")->fetchColumn() ?: 0;
$today_earning = $pdo->query("SELECT SUM(amount) FROM pending_payments WHERE admin_id = $admin_id AND status='approved' AND DATE(created_at) = DATE('now')")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BABA PANEL</title>
    <style>
        body { background: #0f1016; color: #fff; font-family: sans-serif; margin: 0; padding: 15px; }
        .card { background: #161821; border-radius: 12px; padding: 18px; margin-bottom: 15px; border: 1px solid #232634; }
        .nav { background: #161821; padding: 15px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #6366f1; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="nav">
        <span><b>BABA PANEL</b></span>
        <a href="?logout=true">Logout</a>
    </div>

    <h2>📊 Dashboard</h2>
    <div class="card">
        <h3>Total Revenue: ₹<?= $total_revenue ?></h3>
        <p>Pending Approvals: <?= $pending_count ?></p>
        <p>Today's Earning: ₹<?= $today_earning ?></p>
    </div>
</body>
</html>
