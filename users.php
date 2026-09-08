<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }

$users_list = $pdo->query("SELECT * FROM bot_users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users List - BABA PANEL</title>
    <style>
        * { box-sizing: border-box; }
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #12141d; padding: 15px 20px; border-bottom: 1px solid #1f2330; }
        .container { padding: 20px; max-width: 600px; margin: 0 auto; }
        .card { background: #161922; border: 1px solid #212533; border-radius: 14px; padding: 20px; margin-bottom: 20px; }
        .item-box { background: #0f1117; border: 1px solid #212533; padding: 12px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .back-link { display: inline-block; color: #6366f1; text-decoration: none; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header"><h1>👥 Bot Users & IDs Viewer</h1><div>👤 <?= htmlspecialchars($_SESSION['admin_user']) ?></div></div>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <div class="card">
            <h3 style="font-size:15px; margin-top:0; color:#6366f1;">All Registered Users:</h3>
            <?php if(empty($users_list)): ?>
                <p style="color:#9ca3af; font-size:13px; text-align:center; margin:10px 0;">No users joined the bot yet.</p>
            <?php endif; ?>
            <?php foreach($users_list as $u): ?>
            <div class="item-box">
                <div>👤 <strong><?= htmlspecialchars($u['first_name'] ?? 'User') ?></strong> (@<?= htmlspecialchars($u['username'] ?? 'N/A') ?>)</div>
                <div>ID: <code><?= htmlspecialchars($u['user_id']) ?></code></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
