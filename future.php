<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }
$admin_id = $_SESSION['admin_id'];
$success = '';
$error = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Save Bot & General Settings (Token, Welcome Msg, UPI, Channels, Theme Style)
    if (isset($_POST['save_settings'])) {
        $settings = [
            'bot_token' => trim($_POST['bot_token']),
            'welcome_msg' => trim($_POST['welcome_msg']),
            'upi_id' => trim($_POST['upi_id']),
            'channel_user' => trim($_POST['channel_user']),
            'channel_payment' => trim($_POST['channel_payment']),
            'bot_theme_color' => trim($_POST['bot_theme_color'])
        ];

        // Handle QR Code Image Upload
        if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
            $filename = 'qr_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['qr_image']['tmp_name'], __DIR__ . '/' . $filename);
            $settings['qr_code_url'] = $filename;
        }

        foreach ($settings as $key => $val) {
            $pdo->prepare("DELETE FROM settings WHERE admin_id = ? AND key = ?")->execute([$admin_id, $key]);
            $pdo->prepare("INSERT INTO settings (admin_id, key, value) VALUES (?, ?, ?)")->execute([$admin_id, $key, $val]);
        }
        $success = "Settings and Bot configuration saved successfully!";
    }

    // 2. Delete QR Code
    if (isset($_POST['delete_qr'])) {
        $pdo->prepare("DELETE FROM settings WHERE admin_id = ? AND key = 'qr_code_url'")->execute([$admin_id]);
        $success = "QR Code deleted successfully!";
    }

    // 3. Add New Plan (Name, Price, Duration, Description, Video)
    if (isset($_POST['add_plan'])) {
        $name = trim($_POST['plan_name']);
        $price = floatval($_POST['plan_price']);
        $duration = intval($_POST['plan_duration']);
        $desc = trim($_POST['plan_desc']);
        $video = trim($_POST['plan_video']);

        $stmt = $pdo->prepare("INSERT INTO plans (admin_id, name, price, duration, description, demo_video) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$admin_id, $name, $price, $duration, $desc, $video]);
        $success = "New plan added successfully!";
    }

    // 4. Delete Plan
    if (isset($_POST['delete_plan'])) {
        $plan_id = intval($_POST['plan_id']);
        $pdo->prepare("DELETE FROM plans WHERE id = ? AND admin_id = ?")->execute([$plan_id, $admin_id]);
        $success = "Plan deleted successfully!";
    }

    // 5. Approve or Reject Pending Payment
    if (isset($_POST['action_payment'])) {
        $pay_id = intval($_POST['payment_id']);
        $action = $_POST['action_type'];
        if ($action == 'approve') {
            $pdo->prepare("UPDATE pending_payments SET status = 'approved' WHERE id = ?")->execute([$pay_id]);
            $success = "Payment approved!";
        } else {
            $pdo->prepare("UPDATE pending_payments SET status = 'rejected' WHERE id = ?")->execute([$pay_id]);
            $success = "Payment rejected!";
        }
    }
}

// Fetch Settings
$stmt = $pdo->prepare("SELECT key, value FROM settings WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$s = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch Plans
$plans = $pdo->prepare("SELECT * FROM plans WHERE admin_id = ?");
$plans->execute([$admin_id]);
$plans_list = $plans->fetchAll(PDO::FETCH_ASSOC);

// Fetch Users List
$users_list = $pdo->query("SELECT * FROM bot_users ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Pending Payments
$pending_list = $pdo->query("SELECT * FROM pending_payments WHERE status = 'pending' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Features - BABA PANEL</title>
    <style>
        * { box-sizing: border-box; }
        body { background-color: #0b0c10; color: #fff; font-family: sans-serif; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #12141d; padding: 15px 20px; border-bottom: 1px solid #1f2330; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .menu-btn { background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; }
        .header h1 { font-size: 18px; margin: 0; }
        .container { padding: 20px; max-width: 700px; margin: 0 auto; }
        .card { background: #161922; border: 1px solid #212533; border-radius: 14px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        h2 { font-size: 16px; margin-top: 0; color: #6366f1; border-bottom: 1px solid #212533; padding-bottom: 10px; }
        label { font-size: 13px; color: #9ca3af; display: block; margin-bottom: 5px; margin-top: 10px; }
        input, textarea, select { width: 100%; padding: 12px; background: #0f1117; border: 1px solid #212533; color: #fff; border-radius: 8px; outline: none; font-size: 14px; margin-bottom: 5px; }
        input:focus, textarea:focus { border-color: #6366f1; }
        button { padding: 12px 20px; background: #6366f1; border: none; color: #fff; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 10px; width: 100%; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; text-align: center; }
        .item-box { background: #0f1117; border: 1px solid #212533; padding: 12px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .back-link { display: inline-block; color: #6366f1; text-decoration: none; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left"><button class="menu-btn" onclick="window.location.href='index.php'">←</button><h1>Bot & Feature Settings</h1></div>
    </div>
    
    <div class="container">
        <a href="index.php" class="back-link">← Back to Dashboard</a>
        
        <?php if($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>

        <!-- 1, 6, 8, 11, 12, 13. Bot Token, Welcome Message, UPI, Channels, Backup & Theme Settings -->
        <div class="card">
            <h2>🤖 Bot Token, Welcome Msg & Channels Setup</h2>
            <form method="POST" enctype="multipart/form-data">
                <label>Telegram Bot Token (Backup & Restore Feature)</label>
                <input type="text" name="bot_token" value="<?= htmlspecialchars($s['bot_token'] ?? '') ?>" placeholder="123456789:ABCdef..." required>
                <small style="color:#6b7280; font-size:11px;">Agar bot delete ho jaye ya naya lagana pade, bas token dalo sara setup wahi se recover ho jayega.</small>

                <label>Welcome Message (On /start)</label>
                <textarea name="welcome_msg" rows="3" placeholder="Welcome to our service bot! Choose a plan below:"><?= htmlspecialchars($s['welcome_msg'] ?? '') ?></textarea>

                <label>Merchant UPI ID</label>
                <input type="text" name="upi_id" value="<?= htmlspecialchars($s['upi_id'] ?? '') ?>" placeholder="merchant@upi">

                <label>Payment QR Code Image</label>
                <?php if(!empty($s['qr_code_url'])): ?>
                    <div style="margin-bottom:10px;">
                        <img src="<?= htmlspecialchars($s['qr_code_url']) ?>" alt="QR" style="width:100px; height:100px; border-radius:8px; display:block; margin-bottom:5px;">
                        <button type="submit" name="delete_qr" style="background:#ef4444; width:auto; padding:6px 12px; font-size:12px;">Delete QR Code</button>
                    </div>
                <?php endif; ?>
                <input type="file" name="qr_image" accept="image/*">

                <label>Channel 1 ID (User Join / Details Log)</label>
                <input type="text" name="channel_user" value="<?= htmlspecialchars($s['channel_user'] ?? '') ?>" placeholder="-100xxxxxxxxxx">

                <label>Channel 2 ID (Payment Proof / Screenshot Log)</label>
                <input type="text" name="channel_payment" value="<?= htmlspecialchars($s['channel_payment'] ?? '') ?>" placeholder="-100xxxxxxxxxx">

                <label>Bot Button / Theme Primary Color Style</label>
                <select name="bot_theme_color">
                    <option value="indigo" <?= ($s['bot_theme_color'] ?? '') == 'indigo' ? 'selected' : '' ?>>Indigo / Blue Pro</option>
                    <option value="dark" <?= ($s['bot_theme_color'] ?? '') == 'dark' ? 'selected' : '' ?>>Dark Sleek</option>
                    <option value="green" <?= ($s['bot_theme_color'] ?? '') == 'green' ? 'selected' : '' ?>>Emerald Green</option>
                </select>

                <button type="submit" name="save_settings">Save All Configurations</button>
            </form>
        </div>

        <!-- 2. Plans Management (Name, Price, Duration, Description, Video) -->
        <div class="card">
            <h2>📦 Add Subscription Plan & Demo Video</h2>
            <form method="POST">
                <label>Plan Name</label>
                <input type="text" name="plan_name" placeholder="VIP Monthly" required>
                <label>Price (₹)</label>
                <input type="number" step="0.01" name="plan_price" placeholder="199" required>
                <label>Duration (Days)</label>
                <input type="number" name="plan_duration" placeholder="30" required>
                <label>Description / Details</label>
                <textarea name="plan_desc" placeholder="Plan features..."></textarea>
                <label>Demo Video URL / Telegram File ID</label>
                <input type="text" name="plan_video" placeholder="Video URL or File ID">
                <button type="submit" name="add_plan">Add Plan</button>
            </form>

            <h3 style="font-size:14px; margin-top:20px; color:#9ca3af;">Existing Plans:</h3>
            <?php foreach($plans_list as $p): ?>
            <div class="item-box">
                <div><strong><?= htmlspecialchars($p['name']) ?></strong> - ₹<?= $p['price'] ?> (<?= $p['duration'] ?> Days)</div>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="delete_plan" style="background:#ef4444; padding:5px 10px; font-size:11px; margin:0; width:auto;">Delete</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 8. Pending Payment Screenshots Approval -->
        <div class="card">
            <h2>⏳ Pending Payments & Screenshots Approval</h2>
            <?php if(empty($pending_list)): ?><p style="color:#9ca3af; font-size:13px; text-align:center;">No pending payments.</p><?php endif; ?>
            <?php foreach($pending_list as $pay): ?>
            <div class="item-box" style="flex-direction:column; align-items:flex-start; gap:8px;">
                <div>User ID: <code><?= htmlspecialchars($pay['user_id']) ?></code> | Amount: <strong>₹<?= $pay['amount'] ?></strong></div>
                <div style="display:flex; gap:10px; width:100%;">
                    <form method="POST" style="flex:1; margin:0;">
                        <input type="hidden" name="payment_id" value="<?= $pay['id'] ?>">
                        <input type="hidden" name="action_type" value="approve">
                        <button type="submit" name="action_payment" style="background:#10b981; margin:0; padding:8px;">Approve</button>
                    </form>
                    <form method="POST" style="flex:1; margin:0;">
                        <input type="hidden" name="payment_id" value="<?= $pay['id'] ?>">
                        <input type="hidden" name="action_type" value="reject">
                        <button type="submit" name="action_payment" style="background:#ef4444; margin:0; padding:8px;">Reject</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 7. Bot Users ID Viewer -->
        <div class="card">
            <h2>👥 Bot Users List (User IDs)</h2>
            <div style="max-height: 200px; overflow-y: auto;">
                <?php if(empty($users_list)): ?><p style="color:#9ca3af; font-size:13px; text-align:center;">No users joined yet.</p><?php endif; ?>
                <?php foreach($users_list as $u): ?>
                <div class="item-box">
                    <div>👤 <strong><?= htmlspecialchars($u['first_name'] ?? 'User') ?></strong> (@<?= htmlspecialchars($u['username'] ?? 'N/A') ?>)</div>
                    <div>ID: <code><?= htmlspecialchars($u['user_id']) ?></code></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
