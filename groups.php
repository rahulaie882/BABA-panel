<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $channel = trim($_POST['channel_id']);
    $pdo->prepare("DELETE FROM settings WHERE admin_id = ? AND key = 'channel_id'")->execute([$admin_id]);
    $pdo->prepare("INSERT INTO settings (admin_id, key, value) VALUES (?, 'channel_id', ?)")->execute([$admin_id, $channel]);
    $success = "Channel/Group updated!";
}
$stmt = $pdo->prepare("SELECT value FROM settings WHERE admin_id = ? AND key = 'channel_id'");
$stmt->execute([$admin_id]);
$current_channel = $stmt->fetchColumn() ?: '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groups/Channels - BABA PANEL</title>
    <style>
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 20px; }
        .back { color: #6366f1; text-decoration: none; display: inline-block; margin-bottom: 15px; }
        input, button { width: 100%; padding: 12px; margin-bottom: 10px; background: #161922; border: 1px solid #212533; color: #fff; border-radius: 8px; }
        button { background: #6366f1; border: none; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <a href="index.php" class="back">← Back to Dashboard</a>
    <h2>🔗 Linked Groups / Channels</h2>
    <?php if(isset($success)): ?><p style="color: #10b981;"><?= $success ?></p><?php endif; ?>
    <form method="POST">
        <label>Telegram Channel/Group ID</label>
        <input type="text" name="channel_id" value="<?= htmlspecialchars($current_channel) ?>" placeholder="-100xxxxxxxxxx" required>
        <button type="submit">Save Channel</button>
    </form>
</body>
</html>
