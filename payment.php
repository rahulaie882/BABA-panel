<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Payment Method';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_upi'])) {
        setSetting('upi_id', trim($_POST['upi_id'] ?? ''));
        $success = "UPI ID saved successfully!";
    }
}

$upi_id = getSetting('upi_id');

require_once 'includes/header.php';
?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-bottom:15px;">💳 UPI Setting</h3>
    <form method="POST">
        <label>Enter UPI ID (e.g. admin@upi)</label>
        <input type="text" name="upi_id" value="<?= htmlspecialchars($upi_id) ?>" placeholder="yourname@upi">
        <button type="submit" name="save_upi" class="btn btn-primary">💾 Save UPI</button>
    </form>
    
    <?php if ($upi_id): ?>
        <div style="margin-top:15px;padding:12px;background:#065f46;border-radius:10px;color:#a7f3d0;">
            ✅ UPI ID is saved and active: <strong><?= htmlspecialchars($upi_id) ?></strong>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">📷 QR Code</h3>
    <p style="color:#64748b;margin-bottom:12px;">Upload QR code image (feature ready for next update)</p>
    <input type="file" disabled>
    <small style="color:#64748b;">QR upload will be enabled in next version</small>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">🖼️ Waiting Photo</h3>
    <p style="color:#64748b;margin-bottom:12px;">This photo will be sent to user when they submit payment screenshot</p>
    <input type="file" disabled>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">❌ Reject Photo Settings</h3>
    <p style="color:#64748b;">Photo/message to send when payment is rejected (coming soon)</p>
</div>

<?php require_once 'includes/footer.php'; ?>
