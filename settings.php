<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$admin_id = $_SESSION['admin_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'bot_token' => trim($_POST['bot_token']),
        'welcome_msg' => trim($_POST['welcome_msg']),
        'channel_user' => trim($_POST['channel_user']),
        'channel_payment' => trim($_POST['channel_payment']),
        'bot_theme_color' => trim($_POST['bot_theme_color'])
    ];

    foreach ($settings as $key => $val) {
        $pdo->prepare("DELETE FROM settings WHERE admin_id = ? AND key = ?")->execute([$admin_id, $key]);
        $pdo->prepare("INSERT INTO settings (admin_id, key, value) VALUES (?, ?, ?)")->execute([$admin_id, $key, $val]);
    }
    $success = "Bot settings & core features updated successfully!";
}

$stmt = $pdo->prepare("SELECT key, value FROM settings WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$s = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot Settings - BABA PANEL</title>
    <style>
        * { box-sizing: border-box; }
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #12141d; padding: 15px 20px; border-bottom: 1px solid #1f2330; }
        .container { padding: 20px; max-width: 600px; margin: 0 auto; }
        .card { background: #161922; border: 1px solid #212533; border-radius: 14px; padding: 20px; margin-bottom: 20px; }
        label { font-size: 13px; color: #9ca3af; display: block; margin-bottom: 5px; margin-top: 10px; }
        input, textarea, select { width: 100%; padding: 12px; background: #0f1117; border: 1px solid #212533; color: #fff; border-radius: 8px; outline: none; font-size: 14px; }
        button { padding: 12px 20px; background: #6366f1; border: none; color: #fff; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 15px; width: 100%; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 13px; }
        .back-link { display: inline-block; color: #6366f1; text-decoration: none; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header"><h1>⚙️ Bot & Token Settings</h1><div>👤 <?= htmlspecialchars($_SESSION['admin_user']) ?></div></div>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <?php if($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>
        <div class="card">
            <form method="POST">
                <label>Telegram Bot Token (Backup & Restore Feature)</label>
                <input type="text" name="bot_token" value="<?= htmlspecialchars($s['bot_token'] ?? '') ?>" placeholder="123456789:ABCdef..." required>
                <small style="color:#6b7280; font-size:11px;">Bot delete hone par sirf token yahan daalte hi sara setup wahi se recover ho jayega.</small>

                <label>Welcome Message (/start)</label>
                <textarea name="welcome_msg" rows="3" placeholder="Welcome! Choose your plan:"><?= htmlspecialchars($s['welcome_msg'] ?? '') ?></textarea>

                <label>Channel ID: User Join / Logs</label>
                <input type="text" name="channel_user" value="<?= htmlspecialchars($s['channel_user'] ?? '') ?>" placeholder="-100xxxxxxxxxx">

                <label>Channel ID: Payment Proof / Screenshots</label>
                <input type="text" name="channel_payment" value="<?= htmlspecialchars($s['channel_payment'] ?? '') ?>" placeholder="-100xxxxxxxxxx">

                <label>Bot Button / Theme Style</label>
                <select name="bot_theme_color">
                    <option value="indigo" <?= ($s['bot_theme_color'] ?? '') == 'indigo' ? 'selected' : '' ?>>Indigo Blue Pro</option>
                    <option value="dark" <?= ($s['bot_theme_color'] ?? '') == 'dark' ? 'selected' : '' ?>>Dark Sleek</option>
                    <option value="green" <?= ($s['bot_theme_color'] ?? '') == 'green' ? 'selected' : '' ?>>Emerald Green</option>
                </select>

                <button type="submit">Save Bot Settings</button>
            </form>
        </div>
    </div>
</body>
</html>
