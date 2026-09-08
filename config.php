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

    // Tables initialization
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS plans (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price TEXT, validity TEXT, caption TEXT, video_ids TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS pending_payments (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, username TEXT, plan_name TEXT, screenshot TEXT, status TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, telegram_id TEXT, username TEXT, plan TEXT, expiry TEXT, status TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT)");

    // Default Admin Create (Username: admin, Password: password123) Agar koi admin nahi hai to
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin");
    if ($stmt->fetchColumn() == 0) {
        $default_pass = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)")->execute(['admin', $default_pass]);
    }

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

// Fixed isLoggedIn function to prevent errors
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}
?>
