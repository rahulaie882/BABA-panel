<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Plans / Products';

// Add Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plan'])) {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $validity = intval($_POST['validity'] ?? 30);
    $caption = trim($_POST['caption'] ?? '');
    $video_ids = trim($_POST['video_ids'] ?? '');

    if ($name && $price > 0) {
        $pdo->prepare("INSERT INTO plans (name, price, validity, caption, video_ids) VALUES (?,?,?,?,?)")
            ->execute([$name, $price, $validity, $caption, $video_ids]);
        $success = "Product added successfully!";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM plans WHERE id = ?")->execute([intval($_GET['delete'])]);
    header('Location: plans.php');
    exit;
}

$plans = $pdo->query("SELECT * FROM plans ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-bottom:15px;">➕ Add Product</h3>
    <form method="POST">
        <label>Product Name</label>
        <input type="text" name="name" placeholder="Enter name" required>
        
        <label>Price (₹)</label>
        <input type="number" name="price" placeholder="Enter price" step="0.01" required>
        
        <label>Validity (Days)</label>
        <input type="number" name="validity" value="30" required>
        
        <label>Caption / Description</label>
        <textarea name="caption" rows="2" placeholder="Description"></textarea>
        
        <label>Video File IDs (One per line)</label>
        <textarea name="video_ids" rows="3" placeholder="Enter each video file ID on a new line"></textarea>
        <small style="color:#64748b;display:block;margin-bottom:12px;">Get file IDs by forwarding videos to bot and typing /getids</small>
        
        <button type="submit" name="add_plan" class="btn btn-primary">+ Add Product</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">📦 All Products (<?= count($plans) ?>)</h3>
    <?php if (empty($plans)): ?>
        <p style="color:#64748b;">No products added yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Validity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $p): ?>
                <tr>
                    <td>#<?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td>₹<?= number_format($p['price']) ?></td>
                    <td><?= $p['validity'] ?> days</td>
                    <td>
                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
