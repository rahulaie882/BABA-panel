<?php
// ==========================================
// FILE: index.php (Admin Login & Dashboard)
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
        <title>Login - Wang Panel</title>
        <style>
            body { background: #0f1016; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-card { background: #161821; padding: 30px; border-radius: 12px; width: 320px; border: 1px solid #232634; }
            input { width: 100%; padding: 12px; margin: 10px 0; background: #1a1d28; border: 1px solid #2d3246; color: #fff; border-radius: 8px; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background: #6366f1; border: none; color: #fff; border-radius: 8px; font-weight: bold; cursor: pointer; }
            .err { color: #ef4444; font-size: 13px; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class="login-card">
            <h2>👑 Wang Panel Login</h2>
            <?php if($error): ?><div class="err"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
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
    <title>Dashboard - Wang Panel</title>
    <style>
        body { background: #0f1016; color: #fff; font-family: sans-serif; margin: 0; padding: 15px; }
        .card { background: #161821; border-radius: 12px; padding: 18px; margin-bottom: 15px; border: 1px solid #232634; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .nav { background: #161821; padding: 15px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #6366f1; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="nav">
        <span><b>WANG PANEL</b></span>
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
