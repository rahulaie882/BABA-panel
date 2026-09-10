<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Pending Payments';

// Approve / Reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    $stmt = $pdo->prepare("SELECT * FROM pending_payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch();

    if ($payment) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE pending_payments SET status='approved' WHERE id=?")->execute([$id]);

            // Make user premium
            $plan = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
            $plan->execute([$payment['plan_id']]);
            $planData = $plan->fetch();

            $expiry = date('Y-m-d', strtotime('+' . ($planData['validity'] ?? 30) . ' days'));

            $pdo->prepare("INSERT INTO users (telegram_id, username, full_name, plan, plan_id, expiry, status, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'premium', CURRENT_TIMESTAMP)
                ON CONFLICT(telegram_id) DO UPDATE SET
                    plan = excluded.plan,
                    plan_id = excluded.plan_id,
                    expiry = excluded.expiry,
                    status = 'premium',
                    updated_at = CURRENT_TIMESTAMP
            ")->execute([
                $payment['user_id'],
                $payment['username'],
                $payment['full_name'],
                $payment['plan_name'],
                $payment['plan_id'],
                $expiry
            ]);

            // Notify user
            $approved_img = getSetting('approved_image');
            $caption = "✅ *Payment Approved!*\n\nYour plan *{$payment['plan_name']}* is now active.\nExpiry: `$expiry`\n\nThank you!";
            
            if ($approved_img && file_exists(UPLOAD_DIR . $approved_img)) {
                // For now send text (bot can handle photo later via file_id)
                telegramApi('sendMessage', [
                    'chat_id' => $payment['user_id'],
                    'text' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
            } else {
                telegramApi('sendMessage', [
                    'chat_id' => $payment['user_id'],
                    'text' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
            }

        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE pending_payments SET status='rejected' WHERE id=?")->execute([$id]);

            $caption = "❌ *Payment Rejected*\n\nYour payment for *{$payment['plan_name']}* was rejected.\nPlease contact admin or try again.";
            telegramApi('sendMessage', [
                'chat_id' => $payment['user_id'],
                'text' => $caption,
                'parse_mode' => 'Markdown'
            ]);
        }
    }
    header('Location: pending.php');
    exit;
}

$pending = $pdo->query("SELECT * FROM pending_payments WHERE status='pending' ORDER BY id DESC")->fetchAll();
$all = $pdo->query("SELECT * FROM pending_payments ORDER BY id DESC LIMIT 40")->fetchAll();

require_once 'includes/header.php';
?>

<div class="card">
    <h3>⏳ Pending Payments (<?= count($pending) ?>)</h3>

    <?php if (empty($pending)): ?>
        <p style="color:#64748b;">No pending payments right now.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Screenshot</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $p): ?>
                <tr>
                    <td>#<?= $p['id'] ?></td>
                    <td>
                        <div><?= htmlspecialchars($p['full_name'] ?: $p['username'] ?: 'Unknown') ?></div>
                        <small style="color:#64748b;">ID: <?= $p['user_id'] ?></small>
                    </td>
                    <td><?= htmlspecialchars($p['plan_name']) ?></td>
                    <td><?= money($p['amount']) ?></td>
                    <td>
                        <?php if ($p['screenshot']): ?>
                            <a href="<?= htmlspecialchars($p['screenshot']) ?>" target="_blank" class="btn btn-secondary btn-sm">🖼️ View</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= timeAgo($p['created_at']) ?></td>
                    <td>
                        <a href="?action=approve&id=<?= $p['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this payment?')">✅ Approve</a>
                        <a href="?action=reject&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this payment?')">❌ Reject</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>📋 Recent Payments</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all as $p): ?>
            <tr>
                <td>#<?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['username'] ?: $p['user_id']) ?></td>
                <td><?= htmlspecialchars($p['plan_name']) ?></td>
                <td><?= money($p['amount']) ?></td>
                <td>
                    <?php if ($p['status']=='approved'): ?>
                        <span class="badge badge-green">Approved</span>
                    <?php elseif ($p['status']=='rejected'): ?>
                        <span class="badge badge-red">Rejected</span>
                    <?php else: ?>
                        <span class="badge badge-yellow">Pending</span>
                    <?php endif; ?>
                </td>
                <td><?= timeAgo($p['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
