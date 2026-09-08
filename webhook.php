<?php
define('BABA_PANEL', true);
require_once 'config.php';

// टेलीग्राम से आने वाले JSON डेटा को पढ़ें
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

// यूजर का मैसेज या चैट आईडी निकालें
$chat_id = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? null;
$text = $update['message']['text'] ?? '';
$username = $update['message']['from']['username'] ?? '';
$first_name = $update['message']['from']['first_name'] ?? 'User';

if (!$chat_id) {
    exit;
}

// बॉट टोकन डेटाबेस से लाएं
$bot_token = getSetting('bot_token');
if (!$bot_token) {
    exit;
}

// अगर यूजर ने /start भेजा है
if ($text === '/start') {
    $welcome_msg = "👋 Hello <b>{$first_name}</b>!\n\nWelcome to our premium service bot.\n\nChoose an option below to get started:";
    
    // कीबोर्ड बटन बनाएं
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '🛍️ Buy Products / Plans', 'callback_data' => 'buy_plans']],
            [['text' => '👤 My Profile', 'callback_data' => 'my_profile']],
            [['text' => '📞 Support', 'url' => 'https://t.me/' . getSetting('chat_id')]] // एडमिन चैट या सपोर्ट लिंक
        ]
    ];
    
    sendTelegramMessage($bot_token, $chat_id, $welcome_msg, $keyboard);
}

// टेलीग्राम पर मैसेज भेजने का फंक्शन
function sendTelegramMessage($token, $chat_id, $message, $keyboard = null) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}
?>
