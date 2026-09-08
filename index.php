<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid Username or Password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BABA PANEL | Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        body { background: #0a0a0f; min-height: 100vh; display: flex; align-items: center; justify-content: center; color: white; }
        .login-box { background: #14141f; border-radius: 24px; padding: 40px 30px; width: 90%; max-width: 380px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid #1f1f2e; }
        .logo { width: 70px; height: 70px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 32px; }
        h1 { font-size: 28px; font-weight: 700; margin-bottom: 6px; letter-spacing: 1px; }
        h1 span { background: linear-gradient(90deg, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .subtitle { color: #94a3b8; font-size: 13px; letter-spacing: 2px; margin-bottom: 8px; text-transform: uppercase; }
        .created { color: #64748b; font-size: 13px; margin-bottom: 30px; }
        .created span { color: #f59e0b; }
        .input-group { margin-bottom: 16px; position: relative; }
        .input-group input { width: 100%; padding: 14px 16px 14px 44px; background: #0f0f17; border: 1px solid #1e1e2d; border-radius: 12px; color: white; font-size: 15px; outline: none; transition: 0.3s; }
        .input-group input:focus { border-color: #3b82f6; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 16px; }
        .btn { width: 100%; padding: 15px; background: linear-gradient(90deg, #3b82f6, #8b5cf6); border: none; border-radius: 12px; color: white; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn:hover { opacity: 0.9; }
        .error { background: #7f1d1d; color: #fecaca; padding: 10px; border-radius: 10px; font-size: 13px; margin-bottom: 15px; }
        .footer { margin-top: 30px; font-size: 12px; color: #475569; }
        .footer span { color: #3b82f6; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">👑</div>
        <h1>BABA <span>PANEL</span></h1>
        <div class="subtitle">Administrator Access</div>
        <div class="created">⚡ Created by <span>Baba</span> ⚡</div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i>👤</i>
                <input type="text" name="username" placeholder="Username" required autocomplete="username">
            </div>
            <div class="input-group">
                <i>🔒</i>
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn">→ Access Panel</button>
        </form>

        <div class="footer">© 2026 BABA PANEL • <span>Premium Telegram Bot</span></div>
    </div>
</body>
</html>
