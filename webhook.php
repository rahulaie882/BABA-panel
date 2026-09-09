<?php
// ==========================================
// FILE: webhook.php (Updated for SQLite Database)
// ==========================================
define('BABA_PANEL', true);
require_once 'config.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

// Database se direct bot_token fetch karo jo settings table mein save hai
$stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'bot_token'");
$stmt->execute();
$token_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$token_row || empty($token_row['value'])) {
    exit;
}

$bot_token = trim($token_row['value']);

// Baaki settings jaise UPI ID aur Chat ID fetch karo
$settings_stmt = $pdo->query("SELECT key, value FROM settings");
$all_settings = [];
while ($row = $settings_stmt->fetch(PDO::FETCH_ASSOC)) {
    $all_settings[$row['key']] = $row['value'];
}

$upi_id = $all_settings['upi_id'] ?? '';
$log_channel = $all_settings['user_log_channel'] ?? '';

if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $username = $message['from']['username'] ?? 'NoUsername';
    $first_name = $message['from']['first_name'] ?? 'User';

    if ($text === '/start') {
        if (!empty($log_channel)) {
            $log_msg = "🔔 *New User Started Bot!*\n\n👤 Name: {$first_name}\n🆔 User ID: `{$chat_id}`\n🔗 Username: @{$username}\n📅 Date: " . date('d M Y, h:i A');
            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id=" . urlencode($log_channel) . "&text=" . urlencode($log_msg) . "&parse_mode=Markdown");
        }

        // Plans fetch karo database se
        $plans_stmt = $pdo->query("SELECT * FROM plans");
        $plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);

        $keyboard = [];
        foreach ($plans as $p) {
            $keyboard[] = [['text' => "📦 {$p['name']} - ₹{$p['price']}", 'callback_data' => "plan_" . $p['id']]];
        }
        $keyboard[] = [['text' => '❓ How to Use', 'callback_data' => 'how_to_use']];
        $reply_markup = json_encode(['inline_keyboard' => $keyboard]);

        $welcome_msg = "👋 *Welcome, {$first_name}!*\n\nChoose a plan below:";
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($welcome_msg) . "&reply_markup=" . urlencode($reply_markup) . "&parse_mode=Markdown");
    }
}

if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];

    if ($data === 'how_to_use') {
        $cap = "📖 Watch the guide video or contact admin.";
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($cap) . "&parse_mode=Markdown");
    }

    if (strpos($data, 'plan_') === 0) {
        $plan_id = str_replace('plan_', '', $data);
        $plan_stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
        $plan_stmt->execute([$plan_id]);
        $plan = $plan_stmt->fetch(PDO::FETCH_ASSOC);

        if ($plan) {
            $upi_url = "upi://pay?pa={$upi_id}&pn=BabaPanel&am={$plan['price']}&cu=INR";
            $qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_url);

            $caption = "💎 *Plan:* {$plan['name']}\n💰 *Price:* ₹{$plan['price']}\n\nScan QR Code to pay:";
            $keyboard = json_encode(['inline_keyboard' => [[['text' => '✅ I Have Paid', 'callback_data' => 'paid']]]]);

            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendPhoto?chat_id={$chat_id}&photo=" . urlencode($qr_api) . "&caption=" . urlencode($caption) . "&parse_mode=Markdown&reply_markup=" . urlencode($keyboard));
        }
    }

    if ($data === 'paid') {
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode("📸 Please send your payment screenshot now."));
    }
}
?>
