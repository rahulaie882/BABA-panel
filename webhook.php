<?php
// ==========================================
// FILE: webhook.php
// ==========================================
define('BABA_PANEL', true);
require_once 'config.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$incoming_token = $_GET['token'] ?? '';
if (empty($incoming_token)) {
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM admins WHERE bot_token = ?");
$stmt->execute([$incoming_token]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    exit;
}

$admin_id = $admin['id'];
$bot_token = $admin['bot_token'];
$upi_id = $admin['upi_id'];
$log_channel = $admin['user_log_channel'];
$proof_channel = $admin['proof_channel'] ?: $log_channel;
$group_link = $admin['group_link'];

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

        if (!empty($admin['start_videos'])) {
            foreach (explode("\n", trim($admin['start_videos'])) as $vid) {
                $vid = trim($vid);
                if (!empty($vid)) {
                    @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendVideo?chat_id={$chat_id}&video=" . urlencode($vid));
                }
            }
        }

        $plans_stmt = $pdo->prepare("SELECT * FROM plans WHERE admin_id = ?");
        $plans_stmt->execute([$admin_id]);
        $plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);

        $keyboard = [];
        foreach ($plans as $p) {
            $keyboard[] = [['text' => "📦 {$p['name']} - ₹{$p['price']}", 'callback_data' => "plan_" . $p['id']]];
        }
        $keyboard[] = [['text' => '❓ How to Use', 'callback_data' => 'how_to_use']];
        $reply_markup = json_encode(['inline_keyboard' => $keyboard]);

        $welcome_msg = $admin['start_caption'] ?: "👋 *Welcome, {$first_name}!*\n\nChoose a plan below:";
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($welcome_msg) . "&reply_markup=" . urlencode($reply_markup) . "&parse_mode=Markdown");
    }

    if (isset($message['photo'])) {
        $state_stmt = $pdo->prepare("SELECT * FROM users_state WHERE user_id = ? AND admin_id = ? AND state = 'waiting_screenshot'");
        $state_stmt->execute([$chat_id, $admin_id]);
        $state_data = $state_stmt->fetch(PDO::FETCH_ASSOC);

        if ($state_data) {
            $plan_stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
            $plan_stmt->execute([$state_data['selected_plan']]);
            $plan = $plan_stmt->fetch(PDO::FETCH_ASSOC);

            if ($plan) {
                $file_id = end($message['photo'])['file_id'];
                $file_info = json_decode(@file_get_contents("https://api.telegram.org/bot{$bot_token}/getFile?file_id={$file_id}"), true);

                if (isset($file_info['result']['file_path'])) {
                    $file_path = $file_info['result']['file_path'];
                    $ins = $pdo->prepare("INSERT INTO pending_payments (admin_id, user_id, username, plan_name, amount, screenshot, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                    $ins->execute([$admin_id, $chat_id, $username, $plan['name'], $plan['price'], $file_path]);

                    $pdo->prepare("DELETE FROM users_state WHERE user_id = ?")->execute([$chat_id]);
                    @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode("⏳ Payment screenshot received! Please wait for admin approval."));
                }
            }
        }
    }
}

if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];

    if ($data === 'how_to_use') {
        $cap = $admin['how_to_caption'] ?: "📖 Watch the guide video above.";
        if (!empty($admin['how_to_video'])) {
            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendVideo?chat_id={$chat_id}&video=" . urlencode($admin['how_to_video']) . "&caption=" . urlencode($cap) . "&parse_mode=Markdown");
        } else {
            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($cap) . "&parse_mode=Markdown");
        }
    }

    if (strpos($data, 'plan_') === 0) {
        $plan_id = str_replace('plan_', '', $data);
        $plan_stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ? AND admin_id = ?");
        $plan_stmt->execute([$plan_id, $admin_id]);
        $plan = $plan_stmt->fetch(PDO::FETCH_ASSOC);

        if ($plan) {
            $pdo->prepare("INSERT OR REPLACE INTO users_state (user_id, admin_id, state, selected_plan) VALUES (?, ?, 'waiting_screenshot', ?)")
                ->execute([$chat_id, $admin_id, $plan_id]);

            if (!empty($plan['video_ids'])) {
                foreach (explode("\n", trim($plan['video_ids'])) as $vid) {
                    $vid = trim($vid);
                    if (!empty($vid)) {
                        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendVideo?chat_id={$chat_id}&video=" . urlencode($vid));
                    }
                }
            }

            $upi_url = "upi://pay?pa={$upi_id}&pn=WangPanel&am={$plan['price']}&cu=INR";
            $qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_url);

            $caption = "💎 *Plan:* {$plan['name']}\n💰 *Price:* ₹{$plan['price']}\n⏳ *Validity:* {$plan['validity']} Days\n\nScan QR Code to pay:";
            $keyboard = json_encode(['inline_keyboard' => [[['text' => '✅ I Have Paid', 'callback_data' => 'paid']]]]);

            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendPhoto?chat_id={$chat_id}&photo=" . urlencode($qr_api) . "&caption=" . urlencode($caption) . "&parse_mode=Markdown&reply_markup=" . urlencode($keyboard));
        }
    }

    if ($data === 'paid') {
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode("📸 Please send your payment screenshot now."));
    }
}
?>
