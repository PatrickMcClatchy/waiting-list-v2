<?php
/**
* Database initialization script
* Creates necessary tables if they don't exist
*/

header('Content-Type: application/json');

try {
    // Include the database connection helper
    require_once(__DIR__ . '/db_connect.php');
    
    // Connect to the database
    $db = db_connect();
    
    // Create settings table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key TEXT UNIQUE NOT NULL,
        value TEXT NOT NULL
    )");
    
    // Insert default settings if they don't exist
    $settings = [
        ['waiting_list_open', '0'],
        ['closed_message', 'The waiting list is currently closed.'],
        ['success_message', 'You have been added to the waiting list.']
    ];
    
    foreach ($settings as $setting) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)");
        $stmt->bindValue(':key', $setting[0], SQLITE3_TEXT);
        $stmt->bindValue(':value', $setting[1], SQLITE3_TEXT);
        $stmt->execute();
    }
    
    // Create users table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'admin'
    )");
    
    // Insert default admin user if no users exist
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($row['count'] == 0) {
        // Default password is 'admin' - should be changed immediately
        $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->bindValue(':username', 'admin', SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
        $stmt->bindValue(':role', 'admin', SQLITE3_TEXT);
        $stmt->execute();
    }
    
    // Create waiting_list table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS waiting_list (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        position INTEGER NOT NULL
    )");
    
    // Create scheduled_open_times table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS scheduled_open_times (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        open_time DATETIME NOT NULL,
        close_time DATETIME NOT NULL
    )");
    
    echo json_encode(['success' => true, 'message' => 'Database initialized successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
