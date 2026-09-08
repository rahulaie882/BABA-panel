<?php
define('BABA_PANEL', true);
require_once 'config.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$bot_token = getSetting('bot_token');
$upi_id = getSetting('upi_id');
$log_channel = getSetting('user_log_channel'); // सेटिंग्स से यूजर लॉग चैनल
$proof_channel = getSetting('proof_channel') ?: getSetting('chat_id'); // पेमेंट प्रूफ / इनवॉइस चैनल

// 1. टेक्स्ट मैसेज या कमांड हैंडलर
if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $username = $message['from']['username'] ?? 'NoUsername';
    $first_name = $message['from']['first_name'] ?? 'User';

    // /start कमांड
    if ($text === '/start') {
        // यूजर की डिटेल लॉग चैनल में भेजना
        if (!empty($log_channel)) {
            $log_msg = "🔔 *New User Started Bot!*\n\n";
            $log_msg .= "👤 Name: {$first_name}\n";
            $log_msg .= "🆔 User ID: `{$chat_id}`\n";
            $log_msg .= "🔗 Username: @{$username}\n";
            $log_msg .= "📅 Date: " . date('d M Y, h:i A');
            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id=" . urlencode($log_channel) . "&text=" . urlencode($log_msg) . "&parse_mode=Markdown");
        }

        // सेटिंग्स से Start Media वीडियो भेजना (अगर एडमिन ने सेट किया है)
        $start_videos = getSetting('start_videos');
        if (!empty($start_videos)) {
            $v_lines = explode("\n", trim($start_videos));
            foreach ($v_lines as $vid) {
                $vid = trim($vid);
                if (!empty($vid)) {
                    @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendVideo?chat_id={$chat_id}&video=" . urlencode($vid));
                }
            }
        }

        // प्लान्स फेच करना
        $plans = $pdo->query("SELECT * FROM plans")->fetchAll(PDO::FETCH_ASSOC);
        $keyboard = [];
        foreach ($plans as $p) {
            $keyboard[] = [[
                'text' => "📦 {$p['name']} - ₹{$p['price']}",
                'callback_data' => "plan_" . $p['id']
            ]];
        }

        // "How to Use" बटन जोड़ना अगर सेट है
        $keyboard[] = [['text' => '❓ How to Use', 'callback_data' => 'how_to_use']];
        $reply_markup = json_encode(['inline_keyboard' => $keyboard]);

        // वेलकम कैप्शन भेजना
        $welcome_caption = getSetting('start_caption') ?: "👋 *Welcome to " . getSetting('panel_name') . ", {$first_name}!*\n\nChoose a plan below:";
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($welcome_caption) . "&reply_markup=" . urlencode($reply_markup) . "&parse_mode=Markdown");
    }

    // पेमेंट स्क्रीनशॉट हैंडलर
    if (isset($message['photo'])) {
        $stmt = $pdo->prepare("SELECT * FROM users_state WHERE user_id = ? AND state = 'waiting_screenshot'");
        $stmt->execute([$chat_id]);
        $state_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($state_data) {
            $plan_id = $state_data['selected_plan'];
            $p_stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
            $p_stmt->execute([$plan_id]);
            $plan = $p_stmt->fetch(PDO::FETCH_ASSOC);

            if ($plan) {
                $photos = $message['photo'];
                $file_id = end($photos)['file_id'];

                $file_info_json = @file_get_contents("https://api.telegram.org/bot{$bot_token}/getFile?file_id={$file_id}");
                $file_info = json_decode($file_info_json, true);

                if (isset($file_info['result']['file_path'])) {
                    $file_path = $file_info['result']['file_path'];

                    // डेटाबेस में पेंडिंग पेमेंट सेव करना
                    ins_pending($chat_id, $username, $plan['name'], $plan['price'], $file_path);
                    $pdo->prepare("DELETE FROM users_state WHERE user_id = ?")->execute([$chat_id]);

                    $msg = "⏳ Payment screenshot received for *{$plan['name']}*! Please wait while admin verifies it.";
                    @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($msg) . "&parse_mode=Markdown");
                }
            }
        }
        exit;
    }
}

// 2. इनलाइन बटन क्लिक्स
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];

    if ($data === 'how_to_use') {
        $how_video = getSetting('how_to_video');
        $how_caption = getSetting('how_to_caption') ?: "📖 *How to Purchase Guide:* Watch the video above to learn how to buy plans.";
        
        if (!empty($how_video)) {
            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendVideo?chat_id={$chat_id}&video=" . urlencode($how_video) . "&caption=" . urlencode($how_caption) . "&parse_mode=Markdown");
        } else {
            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($how_caption) . "&parse_mode=Markdown");
        }
    }

    if (strpos($data, 'plan_') === 0) {
        $plan_id = str_replace('plan_', '', $data);
        $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($plan) {
            $pdo->prepare("INSERT OR REPLACE INTO users_state (user_id, state, selected_plan) VALUES (?, 'waiting_screenshot', ?)")
                ->execute([$chat_id, $plan_id]);

            // अगर प्लान के साथ डेमो वीडियो अटैच हैं
            if (!empty($plan['video_ids'])) {
                $v_lines = explode("\n", trim($plan['video_ids']));
                foreach ($v_lines as $vid) {
                    $vid = trim($vid);
                    if (!empty($vid)) {
                        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendVideo?chat_id={$chat_id}&video=" . urlencode($vid));
                    }
                }
            }

            $upi_url = "upi://pay?pa={$upi_id}&pn=WangPanel&am={$plan['price']}&cu=INR";
            $qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_url);

            $caption = "💎 *Plan:* {$plan['name']}\n";
            $caption .= "💰 *Price:* ₹{$plan['price']}\n";
            $caption .= "⏳ *Validity:* {$plan['validity']} Days\n\n";
            $caption .= "📝 *Details:* {$plan['caption']}\n\n";
            $caption .= "⚡ *Scan QR Code above to pay via any UPI App.*\n*(UPI ID: `{$upi_id}`)*";

            $keyboard = json_encode([
                'inline_keyboard' => [
                    [['text' => '✅ I Have Paid (Send Screenshot)', 'callback_data' => 'paid_' . $plan_id]],
                    [['text' => '« Back to Plans', 'callback_data' => 'back_home']]
                ]
            ]);

            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendPhoto?chat_id={$chat_id}&photo=" . urlencode($qr_api) . "&caption=" . urlencode($caption) . "&parse_mode=Markdown&reply_markup=" . urlencode($keyboard));
        }
    }

    if (strpos($data, 'paid_') === 0) {
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode("📸 Please send your payment screenshot right here in chat."));
    }

    if ($data === 'back_home') {
        $plans = $pdo->query("SELECT * FROM plans")->fetchAll(PDO::FETCH_ASSOC);
        $keyboard = [];
        foreach ($plans as $p) {
            $keyboard[] = [['text' => "📦 {$p['name']} - ₹{$p['price']}", 'callback_data' => "plan_" . $p['id']]];
        }
        $keyboard[] = [['text' => '❓ How to Use', 'callback_data' => 'how_to_use']];
        $reply_markup = json_encode(['inline_keyboard' => $keyboard]);
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode("Please select a plan:") . "&reply_markup=" . urlencode($reply_markup));
    }
}

function ins_pending($user_id, $username, $plan_name, $amount, $screenshot) {
    global $pdo;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS pending_payments (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, username TEXT, plan_name TEXT, amount REAL, screenshot TEXT, status TEXT DEFAULT 'pending', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS users_state (user_id TEXT PRIMARY KEY, state TEXT, selected_plan TEXT)");
    } catch(Exception $e) {}

    $stmt = $pdo->prepare("INSERT INTO pending_payments (user_id, username, plan_name, amount, screenshot, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $username, $plan_name, $amount, $screenshot]);
}
?>
