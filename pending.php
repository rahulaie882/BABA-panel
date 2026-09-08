<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Pending Payments';

// Approve / Reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $pdo->prepare("UPDATE pending_payments SET status='approved' WHERE id=?")->execute([$id]);
        // Yahan Telegram bot ko message bhejne ka code aayega (baad mein)
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE pending_payments SET status='rejected' WHERE id=?")->execute([$id]);
    }
    header('Location: pending.php');
    exit;
}

$pending = $pdo->query("SELECT * FROM pending_payments WHERE status='pending' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$all = $pdo->query("SELECT * FROM pending_payments ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="card">
    <h3 style="margin-bottom:15px;">⏳ Pending Payments (<?= count($pending) ?>)</h3>
    
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
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $p): ?>
                <tr>
                    <td>#<?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['username'] ?: $p['user_id']) ?></td>
                    <td><?= htmlspecialchars($p['plan_name']) ?></td>
                    <td>₹<?= number_format($p['amount']) ?></td>
                    <td><?= date('d M H:i', strtotime($p['created_at'])) ?></td>
                    <td>
                        <a href="?action=approve&id=<?= $p['id'] ?>" class="btn btn-success btn-sm">✅ Approve</a>
                        <a href="?action=reject&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm">❌ Reject</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">📋 Recent Payments</h3>
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
                <td>₹<?= number_format($p['amount']) ?></td>
                <td>
                    <?php if ($p['status']=='approved'): ?>
                        <span class="badge badge-green">Approved</span>
                    <?php elseif ($p['status']=='rejected'): ?>
                        <span class="badge badge-red">Rejected</span>
                    <?php else: ?>
                        <span class="badge badge-yellow">Pending</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d M H:i', strtotime($p['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
