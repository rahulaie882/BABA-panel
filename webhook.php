<?php
define('BABA_PANEL', true);
require_once 'config.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$bot_token = getSetting('bot_token');

if (isset($update['message']['photo'])) {
    $chat_id = $update['message']['chat']['id'];
    $username = $update['message']['from']['username'] ?? 'unknown';
    
    $photos = $update['message']['photo'];
    $file_id = end($photos)['file_id'];

    $file_info_json = @file_get_contents("https://api.telegram.org/bot{$bot_token}/getFile?file_id={$file_id}");
    $file_info = json_decode($file_info_json, true);

    if (isset($file_info['result']['file_path'])) {
        $file_path = $file_info['result']['file_path'];

        $stmt = $pdo->prepare("INSERT INTO pending_payments (user_id, username, plan_name, screenshot, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$chat_id, $username, 'VIP Subscription', $file_path]);

        $msg = "⏳ Your payment screenshot has been received and sent for verification. Please wait!";
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($msg));
    }
}
