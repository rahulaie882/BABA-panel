<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$admin_id = $_SESSION['admin_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_plan'])) {
        $name = trim($_POST['plan_name']);
        $price = floatval($_POST['plan_price']);
        $duration = intval($_POST['plan_duration']);
        $desc = trim($_POST['plan_desc']);
        $video = trim($_POST['plan_video']);

        $stmt = $pdo->prepare("INSERT INTO plans (admin_id, name, price, duration, description, demo_video) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$admin_id, $name, $price, $duration, $desc, $video]);
        $success = "Plan added successfully!";
    }

    if (isset($_POST['delete_plan'])) {
        $plan_id = intval($_POST['plan_id']);
        $pdo->prepare("DELETE FROM plans WHERE id = ? AND admin_id = ?")->execute([$plan_id, $admin_id]);
        $success = "Plan deleted successfully!";
    }
}

$plans = $pdo->prepare("SELECT * FROM plans WHERE admin_id = ?");
$plans->execute([$admin_id]);
$plans_list = $plans->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans - BABA PANEL</title>
    <style>
        * { box-sizing: border-box; }
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #12141d; padding: 15px 20px; border-bottom: 1px solid #1f2330; }
        .container { padding: 20px; max-width: 600px; margin: 0 auto; }
        .card { background: #161922; border: 1px solid #212533; border-radius: 14px; padding: 20px; margin-bottom: 20px; }
        label { font-size: 13px; color: #9ca3af; display: block; margin-bottom: 5px; margin-top: 10px; }
        input, textarea { width: 100%; padding: 12px; background: #0f1117; border: 1px solid #212533; color: #fff; border-radius: 8px; outline: none; font-size: 14px; }
        button { padding: 12px 20px; background: #6366f1; border: none; color: #fff; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 15px; width: 100%; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 13px; }
        .item-box { background: #0f1117; border: 1px solid #212533; padding: 12px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .back-link { display: inline-block; color: #6366f1; text-decoration: none; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header"><h1>📦 Plans & Demo Video Setup</h1><div>👤 <?= htmlspecialchars($_SESSION['admin_user']) ?></div></div>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <?php if($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>
        
        <div class="card">
            <form method="POST">
                <label>Plan Name</label>
                <input type="text" name="plan_name" placeholder="VIP Monthly" required>
                <label>Price (₹)</label>
                <input type="number" step="0.01" name="plan_price" placeholder="199" required>
                <label>Duration (Days)</label>
                <input type="number" name="plan_duration" placeholder="30" required>
                <label>Description</label>
                <textarea name="plan_desc" placeholder="Plan features..."></textarea>
                <label>Demo Video URL / Telegram File ID</label>
                <input type="text" name="plan_video" placeholder="Video URL or Telegram File ID">
                <button type="submit" name="add_plan">Add Plan</button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-size:15px; margin-top:0; color:#6366f1;">Existing Plans:</h3>
            <?php if(empty($plans_list)): ?><p style="color:#9ca3af; font-size:13px; text-align:center;">No plans added yet.</p><?php endif; ?>
            <?php foreach($plans_list as $p): ?>
            <div class="item-box">
                <div><strong><?= htmlspecialchars($p['name']) ?></strong> - ₹<?= $p['price'] ?> (<?= $p['duration'] ?> Days)</div>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="delete_plan" style="background:#ef4444; padding:6px 12px; font-size:11px; margin:0; width:auto;">Delete</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
