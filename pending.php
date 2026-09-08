<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();

$page_title = 'Pending Payments';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM pending_payments WHERE id = ?");
    $stmt->execute([$id]);
    $pay_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pay_info) {
        $pdo->prepare("UPDATE pending_payments SET status = ? WHERE id = ?")->execute([$action, $id]);
        $bot_token = getSetting('bot_token');

        if ($action === 'approved') {
            $pdo->prepare("INSERT INTO users (telegram_id, username, plan, expiry, status) VALUES (?, ?, ?, date('now', '+30 days'), 'Active')")
                ->execute([$pay_info['user_id'], $pay_info['username'], $pay_info['plan_name']]);

            if ($bot_token && !empty($pay_info['user_id'])) {
                $msg = "🎉 Your payment for <b>{$pay_info['plan_name']}</b> has been <b>APPROVED</b>!";
                @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$pay_info['user_id']}&text=" . urlencode($msg) . "&parse_mode=HTML");
            }
        } else {
            if ($bot_token && !empty($pay_info['user_id'])) {
                $msg = "❌ Your payment for <b>{$pay_info['plan_name']}</b> was <b>REJECTED</b>.";
                @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$pay_info['user_id']}&text=" . urlencode($msg) . "&parse_mode=HTML");
            }
        }

        header("Location: pending.php");
        exit;
    }
}

$pending = $pdo->query("SELECT * FROM pending_payments WHERE status='pending' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="card">
    <h3>Pending Payments Approval</h3>
    <table>
        <tr>
            <th>User</th>
            <th>Plan</th>
            <th>Screenshot</th>
            <th>Action</th>
        </tr>
        <?php foreach ($pending as $item): ?>
        <tr>
            <td>@<?= htmlspecialchars($item['username']) ?><br><small><?= htmlspecialchars($item['user_id']) ?></small></td>
            <td><?= htmlspecialchars($item['plan_name']) ?></td>
            <td>
                <?php if (!empty($item['screenshot'])): ?>
                    <a href="https://api.telegram.org/file/bot<?= getSetting('bot_token') ?>/<?= $item['screenshot'] ?>" target="_blank" class="btn btn-sm btn-primary">View Proof</a>
                <?php else: ?>
                    No Image
                <?php endif; ?>
            </td>
            <td>
                <a href="pending.php?action=approved&id=<?= $item['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                <a href="pending.php?action=rejected&id=<?= $item['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($pending)): ?>
        <tr><td colspan="4" style="text-align: center; color: #64748b;">No pending payments found.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php include 'footer.php'; ?>
