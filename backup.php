<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();

$page_title = 'System Backup';

if (isset($_GET['download'])) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="database_backup.sqlite"');
    readfile(__DIR__ . '/database.sqlite');
    exit;
}

include 'header.php';
?>

<div class="card">
    <h3>Download Database Backup</h3>
    <p style="color: #94a3b8; margin: 10px 0;">You can download your entire SQLite database containing users, plans, and payment logs.</p>
    <a href="backup.php?download=true" class="btn btn-primary">Download Backup File (.sqlite)</a>
</div>

<?php include 'footer.php'; ?>
