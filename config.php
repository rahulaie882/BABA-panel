<?php
session_start();

// Database (SQLite - easy hosting)
define('DB_FILE', __DIR__ . '/database.sqlite');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Create uploads folder if not exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

try {
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// ==================== CREATE TABLES ====================
$pdo->exec("
CREATE TABLE IF NOT EXISTS admin (
    id INTEGER PRIMARY KEY,
    username TEXT UNIQUE,
    password TEXT
);

CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    validity INTEGER NOT NULL DEFAULT 30,
    description TEXT,
    sort_order INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active',
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
    full_name TEXT,
    plan_id INTEGER,
    plan_name TEXT,
    amount REAL,
    screenshot TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telegram_id TEXT UNIQUE,
    username TEXT,
    full_name TEXT,
    plan TEXT,
    plan_id INTEGER,
    expiry DATE,
    status TEXT DEFAULT 'free',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS broadcasts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT,
    content TEXT,
    file_id TEXT,
    caption TEXT,
    status TEXT DEFAULT 'pending',
    sent_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

// ==================== DEFAULT ADMIN ====================
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)")
        ->execute(['baba', password_hash('baba123', PASSWORD_DEFAULT)]);
}

// ==================== DEFAULT SETTINGS ====================
$defaults = [
    // Bot
    'bot_token'              => '',
    'admin_chat_id'          => '',
    'webhook_set'            => '0',
    'webhook_url'            => '',

    // Channels
    'user_log_channel'       => '',
    'payment_proof_channel'  => '',

    // Start Message
    'start_video_file_id'    => '',
    'welcome_message'        => "Welcome to our Premium Bot!\n\nChoose a plan below:",

    // Payment
    'upi_id'                 => '',
    'qr_image'               => '',

    // Images
    'waiting_image'          => '',
    'approved_image'         => '',
    'rejected_image'         => '',
    'howto_video_file_id'    => '',

    // Theme
    'primary_color'          => '#3b82f6',
    'secondary_color'        => '#8b5cf6',
    'button_text_color'      => '#ffffff',

    // Panel
    'panel_name'             => 'BABA PANEL',
    'created_by'             => 'Baba',

    // License (for sharing bot with others)
    'license_expiry'         => '',      // Y-m-d format, empty = lifetime
    'license_owner'          => 'Baba',
    'license_note'           => '',
];

foreach ($defaults as $k => $v) {
    $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)")->execute([$k, $v]);
}

// ==================== HELPER FUNCTIONS ====================
function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return ($val !== false && $val !== null) ? $val : $default;
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

function money($amount) {
    return '₹' . number_format((float)$amount, 0);
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hrs ago';
    return date('d M Y', $time);
}

function isLicenseValid() {
    $expiry = getSetting('license_expiry');
    if (!$expiry || trim($expiry) === '') return true; // lifetime
    return strtotime($expiry) >= strtotime(date('Y-m-d'));
}

function licenseDaysLeft() {
    $expiry = getSetting('license_expiry');
    if (!$expiry || trim($expiry) === '') return -1; // lifetime
    $days = (int) floor((strtotime($expiry) - strtotime(date('Y-m-d'))) / 86400);
    return max(0, $days);
}

// Simple Telegram API call
function telegramApi($method, $params = []) {
    $token = getSetting('bot_token');
    if (!$token) return false;

    $url = "https://api.telegram.org/bot{$token}/{$method}";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>
