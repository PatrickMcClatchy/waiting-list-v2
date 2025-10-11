<?php
/**
 * Create Database Script for SQLite
 * 
 * This script initializes the SQLite database and creates all necessary tables
 * with the complete structure needed by the application.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection helper
require_once(__DIR__ . '/db_connect.php');

try {
    // Get configuration
    $config = include(__DIR__ . '/config.php');
    $db_path = $config['db_path'];
    
    echo "Creating SQLite database at: $db_path\n";
    
    // Create directory if it doesn't exist
    $dir = dirname($db_path);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
    
    // Connect to the database (this will create it if it doesn't exist)
    $db = db_connect();
    echo "Connected to SQLite database\n";
    
    // Create settings table
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key TEXT UNIQUE NOT NULL,
        value TEXT NOT NULL
    )");
    echo "Settings table created successfully\n";
    
    // Create waiting_list table
    $db->exec("CREATE TABLE IF NOT EXISTS waiting_list (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email_or_phone TEXT,
        comment TEXT,
        language TEXT,
        time INTEGER,
        confirmed INTEGER DEFAULT 0,
        position INTEGER NOT NULL
    )");
    echo "Waiting list table created successfully\n";
    
    // Default settings
    $defaultSettings = [
        'waiting_list_open' => '0',
        'scheduled_open_times' => 'Monday 09:00,Thursday 14:00',
        'closed_message' => 'The waiting list is currently closed. Please check back later.',
        'open_message' => 'The waiting list is currently open. Feel free to sign up!',
        'success_message' => 'You\'ve successfully signed up! Your position in the waiting list is: #{{position}}',
        'manual_close_date' => ''
    ];
    
    // Insert default settings if they don't exist
    $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)");
    foreach ($defaultSettings as $key => $value) {
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value, SQLITE3_TEXT);
        $stmt->execute();
        echo "Inserted default setting: $key\n";
    }
    
    echo "Database setup complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
