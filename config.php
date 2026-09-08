<?php
// ==========================================
// FILE: config.php
// ==========================================
define('BABA_PANEL', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db_file = __DIR__ . '/database.sqlite';
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Admins / Multi-Tenant Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password TEXT,
        bot_token TEXT,
        upi_id TEXT,
        user_log_channel TEXT,
        proof_channel TEXT,
        group_link TEXT,
        start_videos TEXT,
        start_caption TEXT,
        how_to_video TEXT,
        how_to_caption TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Plans Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_id INTEGER,
        name TEXT,
        price REAL,
        validity INTEGER,
        caption TEXT,
        video_ids TEXT
    )");

    // Pending Payments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS pending_payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_id INTEGER,
        user_id TEXT,
        username TEXT,
        plan_name TEXT,
        amount REAL,
        screenshot TEXT,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Users State Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users_state (
        user_id TEXT PRIMARY KEY,
        admin_id INTEGER,
        state TEXT,
        selected_plan TEXT
    )");

    // Default Super Admin creation
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admins (username, password, bot_token, upi_id) VALUES ('admin', '$default_pass', '', 'yourupi@ibl')");
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

function getAdminSetting($admin_id, $key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT $key FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    return $stmt->fetchColumn() ?: '';
}

function updateAdminSetting($admin_id, $key, $val) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE admins SET $key = ? WHERE id = ?");
    $stmt->execute([$val, $admin_id]);
}
?>
