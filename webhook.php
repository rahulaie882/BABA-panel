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
$log_channel = getSetting('log_channel'); // नए यूजर के लॉग के लिए चैनल
$proof_channel = getSetting('proof_channel'); // अप्रूव्ड इनवॉइस और पेमेंट प्रूफ के लिए चैनल

// 1. अगर यूजर ने टेक्स्ट मैसेज या कमांड भेजा है (/start या स्क्रीनशॉट)
if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $username = $message['from']['username'] ?? 'No Username';
    $first_name = $message['from']['first_name'] ?? 'User';

    // /start कमांड हैंडलर + न्यू यूजर लॉगिंग
    if ($text === '/start') {
        // नए यूजर की डिटेल लॉग चैनल पर भेजना
        if (!empty($log_channel)) {
            $log_msg = "🔔 *New User Started Bot!*\n\n";
            $log_msg .= "👤 Name: {$first_name}\n";
            $log_msg .= "🆔 User ID: `{$chat_id}`\n";
            $log_msg .= "🔗 Username: @{$username}\n";
            $log_msg .= "📅 Date: " . date('d M Y, h:i A');
            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id=" . urlencode($log_channel) . "&text=" . urlencode($log_msg) . "&parse_mode=Markdown");
        }

        // डेटाबेस (plans टेबल) से प्लान्स निकालना
        $plans = $pdo->query("SELECT * FROM plans")->fetchAll(PDO::FETCH_ASSOC);
        
        $keyboard = [];
        foreach ($plans as $p) {
            $keyboard[] = [[
                'text' => "📦 {$p['name']} - ₹{$p['price']}",
                'callback_data' => "plan_" . $p['id']
            ]];
        }

        $reply_markup = json_encode(['inline_keyboard' => $keyboard]);

        // वेलकम मैसेज भेजना
        $welcome_msg = "👋 *Welcome to " . getSetting('panel_name') . ", {$first_name}!*\n\nGet access to our exclusive content and VIP channels. Choose a plan below to get started:";
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($welcome_msg) . "&reply_markup=" . urlencode($reply_markup) . "&parse_mode=Markdown");

        // 5 डेमो वीडियो / प्रिव्यू भेजना
        $demo_text = "🎥 Here are your 5 Demo Videos/Previews:";
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($demo_text));
    }

    // अगर यूजर पेमेंट का स्क्रीनशॉट भेज रहा है
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

                    // पेंडिंग पेमेंट डेटाबेस में सेव करना
                    ins_pending($chat_id, $username, $plan['name'], $plan['price'], $file_path);

                    $pdo->prepare("DELETE FROM users_state WHERE user_id = ?")->execute([$chat_id]);

                    $msg = "⏳ Your payment screenshot for *{$plan['name']}* has been received! Please wait while admin verifies it.";
                    @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($msg) . "&parse_mode=Markdown");
                }
            }
        }
        exit;
    }
}

// 2. इनलाइन बटन क्लिक हैंडलर (Plans और Payment)
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];

    // जब यूजर किसी प्लान पर क्लिक करे
    if (strpos($data, 'plan_') === 0) {
        $plan_id = str_replace('plan_', '', $data);

        $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($plan) {
            // यूजर का स्टेट सेव करें ताकि स्क्रीनशॉट ट्रैक हो सके
            $pdo->prepare("INSERT OR REPLACE INTO users_state (user_id, state, selected_plan) VALUES (?, 'waiting_screenshot', ?)")
                ->execute([$chat_id, $plan_id]);

            // अगर प्लान में वीडियो फाइल IDs डली हैं, तो पहले वो वीडियो भेजें
            if (!empty($plan['video_ids'])) {
                $video_lines = explode("\n", trim($plan['video_ids']));
                foreach ($video_lines as $vid) {
                    $vid = trim($vid);
                    if (!empty($vid)) {
                        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendVideo?chat_id={$chat_id}&video=" . urlencode($vid));
                    }
                }
            }

            // UPI QR Code जनरेट करना (अमाउंट के साथ)
            $upi_url = "upi://pay?pa={$upi_id}&pn=BabaPanel&am={$plan['price']}&cu=INR";
            $qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_url);

            $caption = "💎 *Plan:* {$plan['name']}\n";
            $caption .= "💰 *Price:* ₹{$plan['price']}\n";
            $caption .= "⏳ *Validity:* {$plan['validity']} Days\n\n";
            $caption .= "📝 *Details:* {$plan['caption']}\n\n";
            $caption .= "⚡ *Scan the QR Code above to pay via any UPI App.*\n";
            $caption .= "*(UPI ID: `{$upi_id}`)*";

            // QR के नीचे "I Have Paid" और "Back" बटन
            $keyboard = json_encode([
                'inline_keyboard' => [
                    [['text' => '✅ I Have Paid (Send Screenshot)', 'callback_data' => 'paid_' . $plan_id]],
                    [['text' => '« Back to Plans', 'callback_data' => 'back_home']]
                ]
            ]);

            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendPhoto?chat_id={$chat_id}&photo=" . urlencode($qr_api) . "&caption=" . urlencode($caption) . "&parse_mode=Markdown&reply_markup=" . urlencode($keyboard));
        }
    }

    // जब यूजर "I Have Paid" बटन दबाए
    if (strpos($data, 'paid_') === 0) {
        $msg = "📸 Please send the screenshot of your payment right here in the chat. Our system is waiting for your proof!";
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($msg));
    }

    // होम मेनू पर वापस जाने के लिए
    if ($data === 'back_home') {
        $plans = $pdo->query("SELECT * FROM plans")->fetchAll(PDO::FETCH_ASSOC);
        $keyboard = [];
        foreach ($plans as $p) {
            $keyboard[] = [['text' => "📦 {$p['name']} - ₹{$p['price']}", 'callback_data' => "plan_" . $p['id']]];
        }
        $reply_markup = json_encode(['inline_keyboard' => $keyboard]);
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode("Please select a plan:") . "&reply_markup=" . urlencode($reply_markup));
    }
}

// पेंडिंग पेमेंट डेटाबेस में सेव करने का फंक्शन
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
