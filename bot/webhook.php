<?php
/**
 * BABA PANEL - Telegram Bot Webhook
 * Version 2.1 Finalized
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

function sendMediaGroup($chat_id, $media_array) {
    $params = [
        'chat_id' => $chat_id,
        'media' => json_encode($media_array)
    ];
    return telegramApi('sendMediaGroup', $params);
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

        // 1. Send Demo Videos as Media Group (Album) if available
        if (!empty(trim($plan['demo_videos']))) {
            $lines = array_filter(array_map('trim', explode("\n", $plan['demo_videos'])));
            if (!empty($lines)) {
                $media_group = [];
                $i = 0;
                foreach ($lines as $vid) {
                    $media_group[] = [
                        'type' => 'video',
                        'media' => $vid,
                        'caption' => ($i === 0) ? "🎬 *Demo Videos for {$plan['name']}*" : '',
                        'parse_mode' => 'Markdown'
                    ];
                    $i++;
                    if (count($media_group) >= 10) break; // Telegram limit per album is 10
                }
                sendMediaGroup($chat_id, $media_group);
            }
        }

        $upi = getSetting('upi_id') ?: 'Not set';
        $qr  = trim($plan['qr_code']); // Plan specific QR

        $text = "🛒 *Selected Plan*\n\n";
        $text .= "📦 *{$plan['name']}*\n";
        $text .= "💰 Price: *₹" . number_format($plan['price']) . "*\n";
        $text .= "⏱ Validity: *{$plan['validity']} days*\n\n";
        if ($plan['description']) $text .= "_{$plan['description']}_\n\n";
        $text .= "━━━━━━━━━━━━━━━\n";
        $text .= "💳 *Payment Details*\n";
        $text .= "UPI: `{$upi}`\n\n";
        $text .= "1️⃣ Scan QR & Pay ₹" . number_format($plan['price']) . "\n";
        $text .= "2️⃣ Click 'I Have Paid' & send Screenshot";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '✅ I Have Paid – Send Screenshot', 'callback_data' => 'paid_' . $plan_id]],
                [['text' => '« Back to Plans', 'callback_data' => 'show_plans']]
            ]
        ];

        // Send Plan QR Code (Photo or URL or File ID)
        if (!empty($qr)) {
            sendPhoto($chat_id, $qr, $text, $keyboard);
        } else {
            sendMsg($chat_id, $text, $keyboard);
        }
    }
    elseif (strpos($data, 'paid_') === 0) {
        $plan_id = intval(str_replace('paid_', '', $data));
        sendMsg($chat_id, "📤 Ab apna *payment screenshot* (photo) yahan bhejo.\n\nScreenshot bhejte hi request admin ke paas chali jayegi.");
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

    // ===== FILE ID FEATURE (For Admin) =====
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
            sendMsg($chat_id, "✅ *Photo/QR File ID:*\n\n`{$fid}`");
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
        $plan_name = 'Unknown Plan';
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

        // Save to pending payments DB
        $pdo->prepare("INSERT INTO pending_payments (user_id, username, full_name, plan_id, plan_name, amount, screenshot, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')")
            ->execute([$user_id, $username, $full_name, $plan_id, $plan_name, $amount, $file_id]);

        $payment_db_id = $pdo->lastInsertId();

        // Notify Admin with Approve / Reject Buttons
        $admin = getSetting('admin_chat_id');
        if ($admin) {
            $notify = "💳 *New Payment Screenshot Received!*\n\n";
            $notify .= "👤 User: *{$full_name}*\n";
            $notify .= "🔗 Username: @" . ($username ?: 'N/A') . "\n";
            $notify .= "🆔 ID: `{$user_id}`\n";
            $notify .= "📦 Plan: *{$plan_name}*\n";
            $notify .= "💰 Amount: *₹" . number_format($amount) . "*\n\n";
            $notify .= "Neeche diye gaye buttons se action lein:";

            $admin_keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Approve', 'callback_data' => 'approve_' . $payment_db_id],
                        ['text' => '❌ Reject', 'callback_data' => 'reject_' . $payment_db_id]
                    ]
                ]
            ];

            sendPhoto($admin, $file_id, $notify, $admin_keyboard);
        }

        // Also to payment proof channel
        $proof_ch = getSetting('payment_proof_channel');
        if ($proof_ch) {
            sendPhoto($proof_ch, $file_id, "Payment from {$full_name} (@{$username}) - {$plan_name} - ₹{$amount}");
        }

        // Reply to user with waiting state
        $waiting_msg = "⏳ *Payment Screenshot Received!*\n\nAapka screenshot successfully admin ke paas bhej diya gaya hai.\nKripya thoda wait karein, verification ke baad plan activate kar diya jayega.";
        sendMsg($chat_id, $waiting_msg);

        http_response_code(200);
        exit;
    }

    // Default / Non-Start text handler (Clickable /start prompt)
    if ($text) {
        $start_keyboard = [
            'inline_keyboard' => [
                [['text' => '🚀 Click Here to Start Bot', 'callback_data' => 'show_plans']]
            ]
        ];
        sendMsg($chat_id, "⚠️ Kripya bot ko use karne ke liye neeche diye gaye button par click karein ya /start bhejein:", $start_keyboard);
    }
}

http_response_code(200);
?>
