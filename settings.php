<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();

$page_title = 'Bot & Panel Settings';
$message = '';

if (isset($_POST['save_settings'])) {
    $bot_token = $_POST['bot_token'];
    $admin_chat = $_POST['admin_chat_id'];

    $data = ['bot_token' => $bot_token, 'chat_id' => $admin_chat];
    foreach($data as $key => $val) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE key = ?");
        $chk->execute([$key]);
        if($chk->fetchColumn() > 0) {
            $pdo->prepare("UPDATE settings SET value = ? WHERE key = ?")->execute([$val, $key]);
        } else {
            $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute([$key, $val]);
        }
    }
    $message = "Settings saved successfully!";
}

if (isset($_POST['set_webhook'])) {
    $bot_token = getSetting('bot_token');
    if (!empty($bot_token)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $current_path = dirname($_SERVER['PHP_SELF']);
        $webhook_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $current_path . "/webhook.php";

        $api_url = "https://api.telegram.org/bot{$bot_token}/setWebhook?url=" . urlencode($webhook_url);
        $response = @file_get_contents($api_url);
        $result = json_decode($response, true);

        if ($result && isset($result['ok']) && $result['ok'] === true) {
            $message = "Webhook successfully activated: " . $webhook_url;
        } else {
            $message = "Failed to set webhook. Check your Bot Token.";
        }
    } else {
        $message = "Please save a valid Bot Token first!";
    }
}

$bot_token = getSetting('bot_token');
$admin_chat = getSetting('chat_id');

include 'header.php';
?>

<div class="card">
    <h3>Telegram Bot Settings</h3>
    
    <?php if(!empty($message)): ?>
        <div style="margin-top: 15px; background: #065f46; color: #a7f3d0; padding: 10px; border-radius: 8px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="margin-top: 15px;">
        <label>Bot Token</label>
        <input type="text" name="bot_token" value="<?= htmlspecialchars($bot_token) ?>" placeholder="123456:ABC-DEF..." required>
        
        <label>Admin Telegram Chat ID (for notifications)</label>
        <input type="text" name="admin_chat_id" value="<?= htmlspecialchars($admin_chat) ?>" placeholder="123456789" required>
        
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
            <button type="submit" name="set_webhook" class="btn btn-success">Set Webhook Active</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
