<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$admin_id = $_SESSION['admin_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_upi'])) {
        $upi = trim($_POST['upi_id']);
        $pdo->prepare("DELETE FROM settings WHERE admin_id = ? AND key = 'upi_id'")->execute([$admin_id]);
        $pdo->prepare("INSERT INTO settings (admin_id, key, value) VALUES (?, 'upi_id', ?)")->execute([$admin_id, $upi]);
        
        if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
            $filename = 'qr_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['qr_image']['tmp_name'], __DIR__ . '/' . $filename);
            $pdo->prepare("DELETE FROM settings WHERE admin_id = ? AND key = 'qr_code_url'")->execute([$admin_id]);
            $pdo->prepare("INSERT INTO settings (admin_id, key, value) VALUES (?, 'qr_code_url', ?)")->execute([$admin_id, $filename]);
        }
        $success = "UPI and QR Code updated successfully!";
    }

    if (isset($_POST['delete_qr'])) {
        $pdo->prepare("DELETE FROM settings WHERE admin_id = ? AND key = 'qr_code_url'")->execute([$admin_id]);
        $success = "QR Code deleted successfully!";
    }
}

$stmt = $pdo->prepare("SELECT key, value FROM settings WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$s = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment & QR - BABA PANEL</title>
    <style>
        * { box-sizing: border-box; }
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #12141d; padding: 15px 20px; border-bottom: 1px solid #1f2330; }
        .container { padding: 20px; max-width: 600px; margin: 0 auto; }
        .card { background: #161922; border: 1px solid #212533; border-radius: 14px; padding: 20px; margin-bottom: 20px; }
        label { font-size: 13px; color: #9ca3af; display: block; margin-bottom: 5px; margin-top: 10px; }
        input { width: 100%; padding: 12px; background: #0f1117; border: 1px solid #212533; color: #fff; border-radius: 8px; outline: none; font-size: 14px; }
        button { padding: 12px 20px; background: #6366f1; border: none; color: #fff; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 15px; width: 100%; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 13px; }
        .back-link { display: inline-block; color: #6366f1; text-decoration: none; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header"><h1>💳 Payment & QR Setup</h1><div>👤 <?= htmlspecialchars($_SESSION['admin_user']) ?></div></div>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <?php if($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <label>Merchant UPI ID</label>
                <input type="text" name="upi_id" value="<?= htmlspecialchars($s['upi_id'] ?? '') ?>" placeholder="merchant@upi" required>

                <label>Payment QR Code Image</label>
                <?php if(!empty($s['qr_code_url'])): ?>
                    <div style="margin: 10px 0;">
                        <img src="<?= htmlspecialchars($s['qr_code_url']) ?>" alt="QR" style="width:100px; height:100px; border-radius:8px; display:block; margin-bottom:8px;">
                        <button type="submit" name="delete_qr" style="background:#ef4444; width:auto; padding:6px 12px; font-size:12px; margin-top:0;">Delete QR Code</button>
                    </div>
                <?php endif; ?>
                <input type="file" name="qr_image" accept="image/*" style="margin-top:5px;">

                <button type="submit" name="save_upi">Save Payment Details</button>
            </form>
        </div>
    </div>
</body>
</html>
