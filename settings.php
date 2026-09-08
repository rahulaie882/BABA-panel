<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header("Location: index.php");
    exit;
}

// Simple JSON file based storage taaki Railway par database ka lafda hi khatam ho jaye aur data kabhi na ude!
$configFile = 'bot_config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : ['bot_token' => '', 'chat_id' => ''];

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_bot'])) {
        $config['bot_token'] = trim($_POST['bot_token']);
        $config['chat_id'] = trim($_POST['chat_id']);
        
        file_put_contents($configFile, json_encode($config));
        $msg = "✅ Bot Token aur Chat ID successfully save ho gaye!";
        $msgType = "success";
    }
    
    if (isset($_POST['set_webhook'])) {
        $token = $config['bot_token'];
        if (!empty($token)) {
            // Automatically current domain ka webhook URL bana lega
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "https";
            $domain = $_SERVER['HTTP_HOST'];
            $webhook_url = "$protocol://$domain/webhook.php"; // Yahan tera bot ka handler file hoga
            
            $api_url = "https://api.telegram.org/bot$token/setWebhook?url=" . urlencode($webhook_url);
            $response = @file_get_contents($api_url);
            $result = json_decode($response, true);
            
            if ($result && $result['ok']) {
                $msg = "🚀 Webhook successfully active ho gaya: $webhook_url";
                $msgType = "success";
            } else {
                $msg = "❌ Webhook set karne mein error aayi! Token check kar.";
                $msgType = "error";
            }
        } else {
            $msg = "⚠️ Pehle Bot Token daal kar Save kar bhai!";
            $msgType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot Token & Webhook - BABA PANEL</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #07080c; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: #12141d; border: 1px solid #1f2330; border-radius: 16px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
        h2 { font-size: 18px; margin-bottom: 20px; background: linear-gradient(45deg, #6366f1, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px; text-transform: uppercase; }
        input { width: 100%; padding: 12px 15px; background: #0a0c14; border: 1px solid #222634; color: #fff; border-radius: 10px; font-size: 13.5px; outline: none; }
        input:focus { border-color: #6366f1; }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 10px; font-weight: bold; font-size: 13.5px; cursor: pointer; margin-top: 10px; }
        .btn-save { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); }
        .btn-webhook { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .alert { padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 15px; text-align: center; }
        .alert.success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; }
        .alert.error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
        .back-link { display: inline-block; margin-top: 15px; color: #9ca3af; text-decoration: none; font-size: 12px; }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h2>⚙️ Bot Token & Webhook Setup</h2>
        
        <?php if($msg): ?>
            <div class="alert <?= $msgType ?>"><?= $msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>🤖 Telegram Bot Token</label>
                <input type="text" name="bot_token" value="<?= htmlspecialchars($config['bot_token']) ?>" placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ" required>
            </div>
            
            <div class="form-group">
                <label>👤 Admin / Owner Chat ID</label>
                <input type="text" name="chat_id" value="<?= htmlspecialchars($config['chat_id']) ?>" placeholder="1122334455" required>
            </div>

            <button type="submit" name="save_bot" class="btn btn-save">💾 Save Details</button>
        </form>

        <form method="POST" style="margin-top: 15px;">
            <button type="submit" name="set_webhook" class="btn btn-webhook">🚀 Set & Active Webhook</button>
        </form>

        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
