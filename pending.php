<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_payment'])) {
    $pay_id = intval($_POST['payment_id']);
    $action = $_POST['action_type'];
    if ($action == 'approve') {
        $pdo->prepare("UPDATE pending_payments SET status = 'approved' WHERE id = ?")->execute([$pay_id]);
        $success = "Payment approved successfully!";
    } else {
        $pdo->prepare("UPDATE pending_payments SET status = 'rejected' WHERE id = ?")->execute([$pay_id]);
        $success = "Payment rejected!";
    }
}

$pending_list = $pdo->query("SELECT * FROM pending_payments WHERE status = 'pending' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments - BABA PANEL</title>
    <style>
        * { box-sizing: border-box; }
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #12141d; padding: 15px 20px; border-bottom: 1px solid #1f2330; }
        .container { padding: 20px; max-width: 600px; margin: 0 auto; }
        .card { background: #161922; border: 1px solid #212533; border-radius: 14px; padding: 20px; margin-bottom: 20px; }
        button { padding: 10px; border: none; color: #fff; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 13px; }
        .item-box { background: #0f1117; border: 1px solid #212533; padding: 15px; border-radius: 8px; margin-bottom: 12px; font-size: 13px; }
        .back-link { display: inline-block; color: #6366f1; text-decoration: none; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header"><h1>⏳ Pending Payment Approvals</h1><div>👤 <?= htmlspecialchars($_SESSION['admin_user']) ?></div></div>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <?php if($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>
        <div class="card">
            <?php if(empty($pending_list)): ?>
                <p style="color:#9ca3af; font-size:13px; text-align:center; margin:10px 0;">No pending payments right now.</p>
            <?php endif; ?>
            <?php foreach($pending_list as $pay): ?>
            <div class="item-box">
                <div style="margin-bottom:8px;">User ID: <code><?= htmlspecialchars($pay['user_id']) ?></code> | Amount: <strong>₹<?= $pay['amount'] ?></strong></div>
                <div style="display:flex; gap:10px;">
                    <form method="POST" style="flex:1; margin:0;">
                        <input type="hidden" name="payment_id" value="<?= $pay['id'] ?>">
                        <input type="hidden" name="action_type" value="approve">
                        <button type="submit" name="action_payment" style="background:#10b981;">Approve</button>
                    </form>
                    <form method="POST" style="flex:1; margin:0;">
                        <input type="hidden" name="payment_id" value="<?= $pay['id'] ?>">
                        <input type="hidden" name="action_type" value="reject">
                        <button type="submit" name="action_payment" style="background:#ef4444;">Reject</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
