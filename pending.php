if ($action === 'approve') {
    $pdo->prepare("UPDATE pending_payments SET status='approved' WHERE id=?")->execute([$id]);
    
    // अप्रूव्ड पेमेंट की डिटेल निकालें
    $stmt = $pdo->prepare("SELECT * FROM pending_payments WHERE id = ?");
    $stmt->execute([$id]);
    $pay_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pay_info) {
        $bot_token = getSetting('bot_token');
        $group_link = getSetting('group_link') ?? 'https://t.me/+your_group_link';
        $proof_channel = getSetting('proof_channel');

        // 1. यूजर को अप्रूवल बैनर और ग्रुप लिंक भेजना
        $user_msg = "🎉 *Congratulations!* Your payment has been **APPROVED**.\n\n✨ Here is your VIP Group Link:\n{$group_link}";
        @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$pay_info['user_id']}&text=" . urlencode($user_msg) . "&parse_mode=Markdown");

        // 2. प्रूफ चैनल पर ऑटोमैटिक इनवॉइस भेजना
        if (!empty($proof_channel)) {
            $current_date = date('d M Y, h:i A');
            $invoice_text = "🧾 *SECURE PAYMENT INVOICE* 🧾\n\n";
            $invoice_text .= "━━━━━━━━━━━━━━━━━━━\n";
            $invoice_text .= "👤 *Customer:* @{$pay_info['username']}\n";
            $invoice_text .= "📦 *Plan Name:* {$pay_info['plan_name']}\n";
            $invoice_text .= "💰 *Amount Paid:* ₹{$pay_info['amount']}\n";
            $invoice_text .= "📅 *Date & Time:* {$current_date}\n";
            $invoice_text .= "✅ *Status:* SUCCESSFUL (Verified)\n";
            $invoice_text .= "━━━━━━━━━━━━━━━━━━━\n";
            $invoice_text .= "🔥 *Get your VIP access today!*";

            $bot_username = getSetting('bot_username') ?? 'your_bot';
            $invoice_markup = json_encode([
                'inline_keyboard' => [
                    [['text' => '⚡ Buy Plan Now', 'url' => "https://t.me/{$bot_username}"]]
                ]
            ]);

            $url = "https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id=" . urlencode($proof_channel) . "&text=" . urlencode($invoice_text) . "&parse_mode=Markdown&reply_markup=" . urlencode($invoice_markup);
            @file_get_contents($url);
        }
    }
}
