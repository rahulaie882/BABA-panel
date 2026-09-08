<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }

$admin_id = $_SESSION['admin_id'];
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $pdo->prepare("UPDATE pending_payments SET status = 'approved' WHERE id = ? AND admin_id = ?")->execute([$id, $admin_id]);
    header("Location: pending.php");
    exit;
}
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $pdo->prepare("UPDATE pending_payments SET status = 'rejected' WHERE id = ? AND admin_id = ?")->execute([$id, $admin_id]);
    header("Location: pending.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pending_payments WHERE admin_id = ? AND status = 'pending' ORDER BY id DESC");
$stmt->execute([$admin_id]);
$pendings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments - BABA PANEL</title>
    <style>
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 20px; }
        .back { color: #6366f1; text-decoration: none; display: inline-block; margin-bottom: 15px; }
        .card { background: #161922; border: 1px solid #212533; border-radius: 12px; padding: 15px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; }
        .btn-approve { background: #10b981; color: #fff; }
        .btn-reject { background: #ef4444; color: #fff; margin-left: 5px; }
    </style>
</head>
<body>
    <a href="index.php" class="back">← Back to Dashboard</a>
    <h2>⏳ Pending Payments</h2>
    <?php if(empty($pendings)): ?><p style="color: #6b7280;">No pending payments found.</p><?php endif; ?>
    <?php foreach($pendings as $p): ?>
    <div class="card">
        <div>
            <strong>User ID: <?= htmlspecialchars($p['user_id']) ?></strong><br>
            <span style="color: #10b981; font-weight: bold;">₹<?= $p['amount'] ?></span>
        </div>
        <div>
            <a href="?approve=<?= $p['id'] ?>" class="btn btn-approve">Approve</a>
            <a href="?reject=<?= $p['id'] ?>" class="btn btn-reject">Reject</a>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>
