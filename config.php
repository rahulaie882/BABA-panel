<?php
if (!defined('BABA_PANEL')) {
    define('BABA_PANEL', true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db_file = __DIR__ . '/database.sqlite';
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tables initialization if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS plans (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price TEXT, validity TEXT, caption TEXT, video_ids TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS pending_payments (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, username TEXT, plan_name TEXT, screenshot TEXT, status TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, telegram_id TEXT, username TEXT, plan TEXT, expiry TEXT, status TEXT)");
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

function getSetting($key) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn() ?: '';
    } catch (Exception $e) {
        return '';
    }
}

function requireLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}
?>
