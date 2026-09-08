<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();

$page_title = 'Payment Settings';

if (isset($_POST['save_payment'])) {
    $upi_id = $_POST['upi_id'];
    foreach(['upi_id' => $upi_id] as $key => $val) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE key = ?");
        $chk->execute([$key]);
        if($chk->fetchColumn() > 0) {
            $pdo->prepare("UPDATE settings SET value = ? WHERE key = ?")->execute([$val, $key]);
        } else {
            $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute([$key, $val]);
        }
    }
    header("Location: payment.php");
    exit;
}

$upi_id = getSetting('upi_id');

include 'header.php';
?>

<div class="card">
    <h3>Configure UPI / QR Gateway</h3>
    <form method="POST" style="margin-top: 15px;">
        <label>UPI ID (for automated QR generation)</label>
        <input type="text" name="upi_id" value="<?= htmlspecialchars($upi_id) ?>" placeholder="yourname@ybl" required>
        
        <button type="submit" name="save_payment" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<?php include 'footer.php'; ?>
