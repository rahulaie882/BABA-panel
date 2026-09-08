<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_bot'])) {
        setSetting('bot_token', trim($_POST['bot_token'] ?? ''));
        setSetting('chat_id', trim($_POST['chat_id'] ?? ''));
        $success = "Bot configuration saved!";
    }
    if (isset($_POST['save_theme'])) {
        $primary = trim($_POST['primary_color'] ?? '#3b82f6');
        $secondary = trim($_POST['secondary_color'] ?? '#8b5cf6');
        // Basic validation
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $primary) && preg_match('/^#[a-fA-F0-9]{6}$/', $secondary)) {
            setSetting('primary_color', $primary);
            setSetting('secondary_color', $secondary);
            $success = "Theme colours updated successfully!";
        } else {
            $error = "Invalid colour code. Use hex format like #3b82f6";
        }
    }
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT password FROM admin WHERE username = ?");
        $stmt->execute([$_SESSION['admin_name']]);
        $hash = $stmt->fetchColumn();
        
        if (password_verify($current, $hash)) {
            if ($new === $confirm && strlen($new) >= 6) {
                $pdo->prepare("UPDATE admin SET password = ? WHERE username = ?")
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_name']]);
                $success = "Password changed successfully!";
            } else {
                $error = "New password mismatch or too short (min 6 chars)";
            }
        } else {
            $error = "Current password is wrong";
        }
    }
}

$bot_token = getSetting('bot_token');
$chat_id = getSetting('chat_id');
$primary_color = getSetting('primary_color') ?: '#3b82f6';
$secondary_color = getSetting('secondary_color') ?: '#8b5cf6';

require_once 'includes/header.php';
?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-bottom:15px;">🤖 Bot Configuration</h3>
    <form method="POST">
        <label>Bot Token</label>
        <input type="text" name="bot_token" value="<?= htmlspecialchars($bot_token) ?>" placeholder="123456:ABC-DEF...">
        
        <label>Admin Chat ID</label>
        <input type="text" name="chat_id" value="<?= htmlspecialchars($chat_id) ?>" placeholder="Your Telegram Chat ID">
        
        <button type="submit" name="save_bot" class="btn btn-primary">💾 Save Configuration</button>
    </form>
    <small style="color:#64748b;display:block;margin-top:10px;">
        How to use: Click Edit → Make changes → Click Save Configuration
    </small>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">🔗 Webhook</h3>
    <p style="color:#64748b;margin-bottom:10px;">Status: <span class="badge badge-yellow">Not Set</span></p>
    <p style="font-size:13px;color:#64748b;word-break:break-all;">
        Webhook URL: https://api.telegram.org/bot<?= $bot_token ? substr($bot_token,0,20).'...' : 'YOUR_TOKEN' ?>/setWebhook
    </p>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">🎨 Theme & Button Colour</h3>
    <form method="POST">
        <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:15px;">
            <div>
                <label>Primary Colour</label>
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="color" name="primary_color" value="<?= htmlspecialchars($primary_color) ?>" style="width:60px;height:40px;padding:0;border:none;cursor:pointer;">
                    <input type="text" name="primary_color_text" value="<?= htmlspecialchars($primary_color) ?>" style="width:110px;margin:0;" onchange="this.form.primary_color.value=this.value">
                </div>
            </div>
            <div>
                <label>Secondary Colour</label>
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="color" name="secondary_color" value="<?= htmlspecialchars($secondary_color) ?>" style="width:60px;height:40px;padding:0;border:none;cursor:pointer;">
                    <input type="text" name="secondary_color_text" value="<?= htmlspecialchars($secondary_color) ?>" style="width:110px;margin:0;" onchange="this.form.secondary_color.value=this.value">
                </div>
            </div>
        </div>

        <div style="margin-bottom:15px;">
            <label>Quick Presets</label>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#3b82f6,#8b5cf6)" onclick="setColors('#3b82f6','#8b5cf6')">Blue Purple</button>
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#10b981,#059669)" onclick="setColors('#10b981','#059669')">Green</button>
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#f59e0b,#d97706)" onclick="setColors('#f59e0b','#d97706')">Orange</button>
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#ef4444,#dc2626)" onclick="setColors('#ef4444','#dc2626')">Red</button>
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#ec4899,#db2777)" onclick="setColors('#ec4899','#db2777')">Pink</button>
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#06b6d4,#0891b2)" onclick="setColors('#06b6d4','#0891b2')">Cyan</button>
            </div>
        </div>

        <div style="margin-bottom:15px;padding:15px;background:#0f0f17;border-radius:12px;border:1px solid #1e1e2d;">
            <label style="margin-bottom:8px;">Preview</label>
            <button type="button" class="btn btn-primary" id="previewBtn">Sample Button</button>
        </div>

        <button type="submit" name="save_theme" class="btn btn-primary">💾 Save Theme Colours</button>
    </form>
</div>

<script>
function setColors(primary, secondary) {
    document.querySelector('input[name="primary_color"]').value = primary;
    document.querySelector('input[name="secondary_color"]').value = secondary;
    document.querySelector('input[name="primary_color_text"]').value = primary;
    document.querySelector('input[name="secondary_color_text"]').value = secondary;
    updatePreview();
}
function updatePreview() {
    const p = document.querySelector('input[name="primary_color"]').value;
    const s = document.querySelector('input[name="secondary_color"]').value;
    document.getElementById('previewBtn').style.background = `linear-gradient(90deg, ${p}, ${s})`;
}
document.querySelector('input[name="primary_color"]').addEventListener('input', updatePreview);
document.querySelector('input[name="secondary_color"]').addEventListener('input', updatePreview);
</script>

<div class="card">
    <h3 style="margin-bottom:15px;">🔑 Change Password</h3>
    <form method="POST">
        <label>Current Password</label>
        <input type="password" name="current_password" required>
        
        <label>New Password (min 6)</label>
        <input type="password" name="new_password" required>
        
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>
        
        <button type="submit" name="change_password" class="btn btn-primary">🔒 Change Password</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">ℹ️ Panel Info</h3>
    <p style="color:#94a3b8;font-size:14px;">
        <strong>BABA PANEL</strong><br>
        Created by <span style="color:#f59e0b;">Baba</span><br>
        Version: 1.0<br>
        © 2026 Premium Telegram Bot Panel
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>
