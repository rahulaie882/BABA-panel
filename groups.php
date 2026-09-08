<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();

$page_title = 'Telegram Groups & Channels';

if(isset($_POST['save_group'])) {
    $g_link = $_POST['group_link'];
    $chk = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE key = 'group_link'");
    $chk->execute();
    if($chk->fetchColumn() > 0) {
        $pdo->prepare("UPDATE settings SET value = ? WHERE key = 'group_link'")->execute([$g_link]);
    } else {
        $pdo->prepare("INSERT INTO settings (key, value) VALUES ('group_link', ?)")->execute([$g_link]);
    }
    header("Location: groups.php");
    exit;
}

$group_link = getSetting('group_link');

include 'header.php';
?>

<div class="card">
    <h3>Manage Telegram Channel / Group Link</h3>
    <form method="POST" style="margin-top: 15px;">
        <label>Invite Link</label>
        <input type="text" name="group_link" value="<?= htmlspecialchars($group_link) ?>" placeholder="https://t.me/+xyz...">
        <button type="submit" name="save_group" class="btn btn-primary">Save Link</button>
    </form>
</div>

<?php include 'footer.php'; ?>
