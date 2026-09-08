<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$admin_id = $_SESSION['admin_id'];
$users = $pdo->prepare("SELECT DISTINCT user_id FROM pending_payments WHERE admin_id = ?");
$users->execute([$admin_id]);
$all_users = $users->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - BABA PANEL</title>
    <style>
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 20px; }
        .back { color: #6366f1; text-decoration: none; display: inline-block; margin-bottom: 15px; }
        .card { background: #161922; border: 1px solid #212533; padding: 15px; border-radius: 8px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <a href="index.php" class="back">← Back to Dashboard</a>
    <h2>👥 Total Users</h2>
    <?php if(empty($all_users)): ?><p style="color: #6b7280;">No users found.</p><?php endif; ?>
    <?php foreach($all_users as $u): ?>
    <div class="card">User ID: <strong><?= htmlspecialchars($u['user_id']) ?></strong></div>
    <?php endforeach; ?>
</body>
</html>
