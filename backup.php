<?php
define('BABA_PANEL', true);
require_once 'config.php';
if (!isset($_SESSION['admin_logged'])) { header("Location: index.php"); exit; }

$file = __DIR__ . '/database.sqlite';
if (file_exists($file)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="baba_panel_backup.sqlite"');
    readfile($file);
    exit;
}
?>
