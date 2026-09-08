<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Backup & Restore';

// Export
if (isset($_GET['export'])) {
    $data = [
        'plans' => $pdo->query("SELECT * FROM plans")->fetchAll(PDO::FETCH_ASSOC),
        'groups' => $pdo->query("SELECT * FROM groups")->fetchAll(PDO::FETCH_ASSOC),
        'settings' => $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_ASSOC),
        'exported_at' => date('Y-m-d H:i:s')
    ];
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="baba_panel_backup_'.date('Y-m-d').'.json"');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

require_once 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="value"><?= $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn() ?></div>
        <div class="label">PRODUCTS</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= $pdo->query("SELECT COUNT(*) FROM groups")->fetchColumn() ?></div>
        <div class="label">GROUPS</div>
    </div>
    <div class="stat-card">
        <div class="value" style="color:#34d399;">ON</div>
        <div class="label">SYSTEM</div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">⬇️ Download Backup</h3>
    <p style="color:#64748b;margin-bottom:12px;">Full setup export (Products, Settings, Groups)</p>
    <a href="?export=1" class="btn btn-primary">📥 Export Backup (JSON)</a>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">⬆️ Upload & Restore</h3>
    <p style="color:#64748b;margin-bottom:12px;">Import backup data (coming in next update)</p>
    <input type="file" disabled>
    <button class="btn btn-primary" style="margin-top:10px;" disabled>Restore</button>
    <div style="margin-top:12px;padding:10px;background:#78350f;border-radius:8px;color:#fde68a;font-size:13px;">
        ⚠️ This will OVERWRITE all existing products, settings, and groups.
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:15px;">👥 All User Backup</h3>
    <p style="color:#64748b;">Download CSV of all users (Coming Soon)</p>
</div>

<?php require_once 'includes/footer.php'; ?>
