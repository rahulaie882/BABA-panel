<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $pdo->prepare("INSERT INTO plans (admin_id, name, price, duration) VALUES (?, ?, ?, ?)")->execute([$admin_id, $name, $price, $duration]);
    header("Location: plans.php");
    exit;
}
$plans = $pdo->prepare("SELECT * FROM plans WHERE admin_id = ?");
$plans->execute([$admin_id]);
$all_plans = $plans->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans - BABA PANEL</title>
    <style>
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 20px; }
        .back { color: #6366f1; text-decoration: none; display: inline-block; margin-bottom: 15px; }
        input, button { width: 100%; padding: 12px; margin-bottom: 10px; background: #161922; border: 1px solid #212533; color: #fff; border-radius: 8px; }
        button { background: #6366f1; border: none; font-weight: bold; cursor: pointer; }
        .card { background: #161922; border: 1px solid #212533; padding: 15px; border-radius: 8px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <a href="index.php" class="back">← Back to Dashboard</a>
    <h2>📦 Manage Plans</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Plan Name" required>
        <input type="number" name="price" placeholder="Price (₹)" required>
        <input type="number" name="duration" placeholder="Duration (Days)" required>
        <button type="submit">Add Plan</button>
    </form>
    <h3>Existing Plans</h3>
    <?php foreach($all_plans as $plan): ?>
    <div class="card">
        <strong><?= htmlspecialchars($plan['name']) ?></strong> - ₹<?= $plan['price'] ?> (<?= $plan['duration'] ?> Days)
    </div>
    <?php endforeach; ?>
</body>
</html>
