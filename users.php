<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Users';

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="card">
    <h3 style="margin-bottom:15px;">👥 All Users (<?= count($users) ?>)</h3>
    
    <?php if (empty($users)): ?>
        <p style="color:#64748b;">No users yet. Users will appear here when they purchase plans through the bot.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Telegram ID</th>
                    <th>Username</th>
                    <th>Plan</th>
                    <th>Expiry</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['telegram_id']) ?></td>
                    <td>@<?= htmlspecialchars($u['username'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($u['plan']) ?></td>
                    <td><?= $u['expiry'] ?></td>
                    <td>
                        <?php if ($u['status']=='Active'): ?>
                            <span class="badge badge-green">Active</span>
                        <?php else: ?>
                            <span class="badge badge-red"><?= $u['status'] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
