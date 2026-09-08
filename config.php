<?php
if (!defined('BABA_PANEL')) {
    define('BABA_PANEL', true);
}

$db_file = __DIR__ . '/database.sqlite';
try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Admins Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password TEXT,
        role TEXT DEFAULT 'admin'
    )");

    // Settings Table (Bot Token, Welcome Msg, UPI, QR, Channels, Theme)
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_id INTEGER,
        key TEXT,
        value TEXT
    )");

    // Plans Table (Name, Price, Duration, Description, Video/File ID)
    $pdo->exec("CREATE TABLE IF NOT EXISTS plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_id INTEGER,
        name TEXT,
        price REAL,
        duration INTEGER,
        description TEXT,
        demo_video TEXT
    )");

    // Users Table (Bot Users & IDs)
    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT UNIQUE,
        username TEXT,
        first_name TEXT,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Pending Payments Table (Screenshots Approval)
    $pdo->exec("CREATE TABLE IF NOT EXISTS pending_payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT,
        amount REAL,
        screenshot TEXT,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Default Admin (admin / admin123)
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admins (username, password, role) VALUES ('admin', '$hash', 'superadmin')");
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
