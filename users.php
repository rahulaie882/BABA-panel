<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();

$page_title = 'Registered Users';
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="card">
    <h3>Active & Registered Users</h3>
    <table>
        <tr>
            <th>Telegram ID</th>
            <th>Username</th>
            <th>Plan</th>
            <th>Expiry</th>
            <th>Status</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['telegram_id']) ?></td>
            <td>@<?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['plan']) ?></td>
            <td><?= htmlspecialchars($u['expiry']) ?></td>
            <td><span class="badge-green"><?= htmlspecialchars($u['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php include 'footer.php'; ?>
