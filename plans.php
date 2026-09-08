<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();

$page_title = 'Manage Plans';

if (isset($_POST['add_plan'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $validity = $_POST['validity'];
    $caption = $_POST['caption'];
    $video_ids = $_POST['video_ids'];

    $stmt = $pdo->prepare("INSERT INTO plans (name, price, validity, caption, video_ids) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $validity, $caption, $video_ids]);
    header("Location: plans.php");
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM plans WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: plans.php");
    exit;
}

$plans = $pdo->query("SELECT * FROM plans ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="card">
    <h3>Add New Plan</h3>
    <form method="POST" style="margin-top: 15px;">
        <label>Plan Name</label>
        <input type="text" name="name" required placeholder="e.g. VIP Monthly">
        
        <label>Price (₹)</label>
        <input type="number" name="price" required placeholder="199">
        
        <label>Validity (Days)</label>
        <input type="number" name="validity" required placeholder="30">
        
        <label>Caption / Description</label>
        <textarea name="caption" placeholder="Plan details..."></textarea>
        
        <label>Telegram Video File IDs (One per line)</label>
        <textarea name="video_ids" placeholder="BAACAgU..."></textarea>
        
        <button type="submit" name="add_plan" class="btn btn-primary">Add Plan</button>
    </form>
</div>

<div class="card">
    <h3>Existing Plans</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Price</th>
            <th>Validity</th>
            <th>Action</th>
        </tr>
        <?php foreach ($plans as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td>₹<?= htmlspecialchars($p['price']) ?></td>
            <td><?= htmlspecialchars($p['validity']) ?> Days</td>
            <td><a href="plans.php?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm">Delete</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php include 'footer.php'; ?>
