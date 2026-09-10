<?php
/**
 * BABA PANEL - Telegram Bot Webhook
 * Version 2.0 Final
 */

require_once __DIR__ . '/../config.php';

// Get update
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    http_response_code(200);
    exit;
}

// ========== LICENSE CHECK ==========
if (!isLicenseValid()) {
    // License expired - bot stops working
    if (isset($update['message']['chat']['id'])) {
        $chat_id = $update['message']['chat']['id'];
        telegramApi('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "⛔ *Bot License Expired*\n\nPlease contact the admin to renew.",
            'parse_mode' => 'Markdown'
        ]);
    }
    http_response_code(200);
    exit;
}

// ========== HELPERS ==========
function sendMsg($chat_id, $text, $keyboard = null, $parse = 'Markdown') {
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse,
        'disable_web_page_preview' => true
    ];
    if ($keyboard) $params['reply_markup'] = json_encode($keyboard);
    return telegramApi('sendMessage', $params);
}

function sendVideo($chat_id, $file_id, $caption = '', $keyboard = null) {
    $params = [
        'chat_id' => $chat_id,
        'video' => $file_id,
        'caption' => $caption,
        'parse_mode' => 'Markdown'
    ];
    if ($keyboard) $params['reply_markup'] = json_encode($keyboard);
    return telegramApi('sendVideo', $params);
}

function sendPhoto($chat_id, $file_id_or_url, $caption = '', $keyboard = null) {
    $params = [
        'chat_id' => $chat_id,
        'photo' => $file_id_or_url,
        'caption' => $caption,
        'parse_mode' => 'Markdown'
    ];
    if ($keyboard) $params['reply_markup'] = json_encode($keyboard);
    return telegramApi('sendPhoto', $params);
}

function answerCallback($callback_id, $text = '') {
    telegramApi('answerCallbackQuery', [
        'callback_query_id' => $callback_id,
        'text' => $text,
        'show_alert' => false
    ]);
}

function getPlansKeyboard() {
    global $pdo;
    $plans = $pdo->query("SELECT * FROM plans WHERE status='active' OR status IS NULL ORDER BY sort_order ASC, price ASC")->fetchAll();
    $buttons = [];
    foreach ($plans as $p) {
        $buttons[] = [[
            'text' => "{$p['name']} • ₹" . number_format($p['price']),
            'callback_data' => 'plan_' . $p['id']
        ]];
    }
    if (empty($buttons)) {
        $buttons[] = [['text' => 'No plans available', 'callback_data' => 'none']];
    }
    return ['inline_keyboard' => $buttons];
}

// ========== MAIN LOGIC ==========

// Handle Callback Queries (button presses)
if (isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    $chat_id = $cb['message']['chat']['id'];
    $user_id = $cb['from']['id'];
    $data = $cb['data'];
    $cb_id = $cb['id'];

    answerCallback($cb_id);

    if ($data === 'show_plans') {
        $welcome = getSetting('welcome_message') ?: "Choose a plan:";
        sendMsg($chat_id, $welcome, getPlansKeyboard());
    }
    elseif (strpos($data, 'plan_') === 0) {
        $plan_id = intval(str_replace('plan_', '', $data));
        $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();

        if (!$plan) {
            sendMsg($chat_id, "❌ Plan not found.");
            exit;
        }

        $upi = getSetting('upi_id') ?: 'Not set';
        $qr  = getSetting('qr_image');

        $text = "🛒 *Selected Plan*\n\n";
        $text .= "📦 *{$plan['name']}*\n";
        $text .= "💰 Price: *₹" . number_format($plan['price']) . "*\n";
        $text .= "⏱ Validity: *{$plan['validity']} days*\n\n";
        if ($plan['description']) $text .= "_{$plan['description']}_\n\n";
        $text .= "━━━━━━━━━━━━━━━\n";
        $text .= "💳 *Payment Details*\n";
        $text .= "UPI: `{$upi}`\n\n";
        $text .= "1️⃣ UPI pe payment karo\n";
        $text .= "2️⃣ Payment ka screenshot bhejo\n";
        $text .= "3️⃣ Admin approve karega";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '✅ I have paid – Send Screenshot', 'callback_data' => 'paid_' . $plan_id]],
                [['text' => '« Back to Plans', 'callback_data' => 'show_plans']]
            ]
        ];

        // Send QR if available
        if ($qr && file_exists(UPLOAD_DIR . $qr)) {
            // For shared hosting we send as URL if possible, else just text
            // Better: upload QR to Telegram once and store file_id (future improvement)
            sendMsg($chat_id, $text, $keyboard);
            sendMsg($chat_id, "📷 *Scan QR to Pay*\n\nUPI: `{$upi}`");
        } else {
            sendMsg($chat_id, $text, $keyboard);
        }
    }
    elseif (strpos($data, 'paid_') === 0) {
        $plan_id = intval(str_replace('paid_', '', $data));
        // Mark that user is about to send screenshot
        // We'll handle photo in message handler with context (simple way: just tell them to send photo)
        sendMsg($chat_id, "📤 Ab apna *payment screenshot* bhejo (photo).\n\nScreenshot aate hi admin ko chala jayega.");
        // Store temporary state (simple file based or we can use a temp table)
        file_put_contents(sys_get_temp_dir() . "/baba_pending_{$user_id}.txt", $plan_id);
    }

    http_response_code(200);
    exit;
}

// Handle Messages
if (isset($update['message'])) {
    $msg = $update['message'];
    $chat_id = $msg['chat']['id'];
    $user_id = $msg['from']['id'];
    $username = $msg['from']['username'] ?? '';
    $full_name = trim(($msg['from']['first_name'] ?? '') . ' ' . ($msg['from']['last_name'] ?? ''));
    $text = $msg['text'] ?? '';

    // ===== FILE ID FEATURE =====
    // Agar admin video/photo bheje toh file_id return karo
    $admin_id = getSetting('admin_chat_id');
    if ($admin_id && (string)$user_id === (string)$admin_id) {
        if (isset($msg['video'])) {
            $fid = $msg['video']['file_id'];
            sendMsg($chat_id, "✅ *Video File ID:*\n\n`{$fid}`\n\nCopy karke Panel mein use karo.");
            http_response_code(200);
            exit;
        }
        if (isset($msg['photo'])) {
            $photos = $msg['photo'];
            $fid = end($photos)['file_id'];
            sendMsg($chat_id, "✅ *Photo File ID:*\n\n`{$fid}`");
            http_response_code(200);
            exit;
        }
        if (isset($msg['document'])) {
            $fid = $msg['document']['file_id'];
            sendMsg($chat_id, "✅ *Document File ID:*\n\n`{$fid}`");
            http_response_code(200);
            exit;
        }
    }

    // ===== /start =====
    if ($text === '/start' || strpos($text, '/start') === 0) {
        // Save / update user
        $pdo->prepare("INSERT INTO users (telegram_id, username, full_name, status, joined_at)
            VALUES (?, ?, ?, 'free', CURRENT_TIMESTAMP)
            ON CONFLICT(telegram_id) DO UPDATE SET
                username = excluded.username,
                full_name = excluded.full_name
        ")->execute([$user_id, $username, $full_name]);

        // Notify to User Log Channel
        $log_channel = getSetting('user_log_channel');
        if ($log_channel) {
            $log_text = "👤 *New User Started*\n\n";
            $log_text .= "Name: *{$full_name}*\n";
            $log_text .= "Username: @" . ($username ?: 'N/A') . "\n";
            $log_text .= "ID: `{$user_id}`\n";
            $log_text .= "Time: " . date('d M Y H:i');

            $dm_keyboard = [
                'inline_keyboard' => [[
                    ['text' => '💬 DM Now', 'url' => "tg://user?id={$user_id}"]
                ]]
            ];
            sendMsg($log_channel, $log_text, $dm_keyboard);
        }

        // Send Start Video + Welcome
        $start_video = getSetting('start_video_file_id');
        $welcome = getSetting('welcome_message') ?: "Welcome!\n\nChoose a plan below:";

        $keyboard = getPlansKeyboard();

        if ($start_video) {
            sendVideo($chat_id, $start_video, $welcome, $keyboard);
        } else {
            sendMsg($chat_id, $welcome, $keyboard);
        }

        http_response_code(200);
        exit;
    }

    // ===== Screenshot handling =====
    if (isset($msg['photo'])) {
        $state_file = sys_get_temp_dir() . "/baba_pending_{$user_id}.txt";
        $plan_id = null;
        if (file_exists($state_file)) {
            $plan_id = intval(file_get_contents($state_file));
            @unlink($state_file);
        }

        $photos = $msg['photo'];
        $file_id = end($photos)['file_id']; // highest quality

        // Get plan info
        $plan_name = 'Unknown';
        $amount = 0;
        if ($plan_id) {
            $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch();
            if ($plan) {
                $plan_name = $plan['name'];
                $amount = $plan['price'];
            }
        }

        // Save to pending
        $pdo->prepare("INSERT INTO pending_payments (user_id, username, full_name, plan_id, plan_name, amount, screenshot, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')")
            ->execute([$user_id, $username, $full_name, $plan_id, $plan_name, $amount, $file_id]);

        // Notify Admin
        $admin = getSetting('admin_chat_id');
        if ($admin) {
            $notify = "💳 *New Payment Screenshot*\n\n";
            $notify .= "User: *{$full_name}*\n";
            $notify .= "Username: @" . ($username ?: 'N/A') . "\n";
            $notify .= "ID: `{$user_id}`\n";
            $notify .= "Plan: *{$plan_name}*\n";
            $notify .= "Amount: *₹" . number_format($amount) . "*\n\n";
            $notify .= "Panel se Approve / Reject karo.";

            sendPhoto($admin, $file_id, $notify);
        }

        // Also to payment proof channel
        $proof_ch = getSetting('payment_proof_channel');
        if ($proof_ch) {
            sendPhoto($proof_ch, $file_id, "Payment from {$full_name} (@{$username}) - {$plan_name} - ₹{$amount}");
        }

        // Reply to user
        $waiting_msg = "⏳ *Payment Received!*\n\nAapka screenshot aa gaya hai.\nAdmin check karke approve karega.\nThoda wait kariye.";
        sendMsg($chat_id, $waiting_msg);

        http_response_code(200);
        exit;
    }

    // Default
    if ($text) {
        sendMsg($chat_id, "Use /start to see plans.");
    }
}

http_response_code(200);
?>
