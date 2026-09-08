<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['new_password'];
    if (!empty($new_pass)) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$hash, $admin_id]);
        $success = "Password updated successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - BABA PANEL</title>
    <style>
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 20px; }
        .back { color: #6366f1; text-decoration: none; display: inline-block; margin-bottom: 15px; }
        input, button { width: 100%; padding: 12px; margin-bottom: 10px; background: #161922; border: 1px solid #212533; color: #fff; border-radius: 8px; }
        button { background: #6366f1; border: none; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <a href="index.php" class="back">← Back to Dashboard</a>
    <h2>⚙️ Panel Settings</h2>
    <?php if(isset($success)): ?><p style="color: #10b981;"><?= $success ?></p><?php endif; ?>
    <form method="POST">
        <label>Change Admin Password</label>
        <input type="password" name="new_password" placeholder="New Password" required>
        <button type="submit">Update Password</button>
    </form>
</body>
</html>
