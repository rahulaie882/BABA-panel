<?php
// ==========================================
// FILE: pending.php (Approval & Auto-Invoice)
// ==========================================
define('BABA_PANEL', true);
require_once 'config.php';

if (!isset($_SESSION['admin_logged'])) {
    header("Location: index.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $pdo->prepare("UPDATE pending_payments SET status='approved' WHERE id=? AND admin_id=?")->execute([$id, $admin_id]);
        
        $stmt = $pdo->prepare("SELECT * FROM pending_payments WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $admin_id]);
        $pay_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pay_info) {
            $admin_stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
            $admin_stmt->execute([$admin_id]);
            $adm = $admin_stmt->fetch(PDO::FETCH_ASSOC);

            $bot_token = $adm['bot_token'];
            $group_link = $adm['group_link'] ?? 'https://t.me/+group_link';
            $proof_channel = $adm['proof_channel'];

            // Send VIP Link to user
            $user_msg = "🎉 *Payment Approved!*\n\n✨ VIP Group Access Link:\n{$group_link}";
            @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$pay_info['user_id']}&text=" . urlencode($user_msg) . "&parse_mode=Markdown");

            // Send Invoice to Proof Channel
            if (!empty($proof_channel)) {
                $invoice_text = "🧾 *SECURE PAYMENT INVOICE*\n\n👤 *Customer:* @{$pay_info['username']}\n📦 *Plan:* {$pay_info['plan_name']}\n💰 *Amount:* ₹{$pay_info['amount']}\n✅ *Status:* SUCCESSFUL";
                
                $bot_info = json_decode(@file_get_contents("https://api.telegram.org/bot{$bot_token}/getMe"), true);
                $bot_username = $bot_info['result']['username'] ?? 'bot';

                $invoice_markup = json_encode(['inline_keyboard' => [[['text' => '⚡ Buy Plan Now', 'url' => "https://t.me/{$bot_username}"]]]]);
                @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id=" . urlencode($proof_channel) . "&text=" . urlencode($invoice_text) . "&parse_mode=Markdown&reply_markup=" . urlencode($invoice_markup));
            }
        }
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE pending_payments SET status='rejected' WHERE id=? AND admin_id=?")->execute([$id, $admin_id]);
    }
    header("Location: pending.php");
    exit;
}

$pending_list = $pdo->prepare("SELECT * FROM pending_payments WHERE admin_id = ? AND status='pending'");
$pending_list->execute([$admin_id]);
$pendings = $pending_list->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Payments</title>
    <style>
        body { background: #0f1016; color: #fff; font-family: sans-serif; padding: 15px; }
        .card { background: #161821; padding: 15px; border-radius: 10px; margin-bottom: 10px; border: 1px solid #232634; }
        .btn { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-success { background: #10b981; color: #fff; }
        .btn-danger { background: #ef4444; color: #fff; }
    </style>
</head>
<body>
    <h2>⏳ Pending Payments (<?= count($pendings) ?>)</h2>
    <?php foreach($pendings as $p): ?>
        <div class="card">
            <p><b>User:</b> @<?= $p['username'] ?> (ID: <?= $p['user_id'] ?>)</p>
            <p><b>Plan:</b> <?= $p['plan_name'] ?> - ₹<?= $p['amount'] ?></p>
            <?php if(!empty($p['screenshot'])): ?>
                <p><a href="https://api.telegram.org/file/botTOKEN/<?= $p['screenshot'] ?>" target="_blank" style="color:#6366f1;">View Screenshot</a></p>
            <?php endif; ?>
            <a href="pending.php?action=approve&id=<?= $p['id'] ?>" class="btn btn-success">Approve</a>
            <a href="pending.php?action=reject&id=<?= $p['id'] ?>" class="btn btn-danger">Reject</a>
        </div>
    <?php endforeach; ?>
</body>
</html>
