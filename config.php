<?php
session_start();

// Database (SQLite - easy hosting)
define('DB_FILE', __DIR__ . '/database.sqlite');

try {
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Create tables if not exist
$pdo->exec("
CREATE TABLE IF NOT EXISTS admin (
    id INTEGER PRIMARY KEY,
    username TEXT UNIQUE,
    password TEXT
);

CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    price REAL,
    validity INTEGER,
    caption TEXT,
    video_ids TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    link TEXT,
    description TEXT,
    status TEXT DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);

CREATE TABLE IF NOT EXISTS pending_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT,
    username TEXT,
    plan_name TEXT,
    amount REAL,
    screenshot TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telegram_id TEXT,
    username TEXT,
    plan TEXT,
    expiry DATE,
    status TEXT DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

// Default admin
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)")
        ->execute(['baba', password_hash('baba123', PASSWORD_DEFAULT)]);
}

// Default settings
$defaults = [
    'bot_token' => '',
    'chat_id' => '',
    'upi_id' => '',
    'panel_name' => 'BABA PANEL',
    'created_by' => 'Baba',
    'primary_color' => '#3b82f6',
    'secondary_color' => '#8b5cf6'
];
foreach ($defaults as $k => $v) {
    $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)")->execute([$k, $v]);
}

function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn() ?: '';
}

function setSetting($key, $value) {
    global $pdo;
    $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)")->execute([$key, $value]);
}

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
