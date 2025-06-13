<?php
/**
 * Database Test Script
 * Tests the SQLite database connection
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Include the database connection helper
    require_once(__DIR__ . '/../app-backend/api/db_connect.php');
    
    // Connect to the database
    $db = db_connect();
    
    // Get database information
    $config = include(__DIR__ . '/../app-backend/api/config.php');
    $db_path = $config['db_path'];
    
    // Check if database file exists
    $db_exists = file_exists($db_path);
    
    // Get database size
    $db_size = $db_exists ? filesize($db_path) : 0;
    
    // Get SQLite version
    $version = $db->querySingle('SELECT sqlite_version()');
    
    // Get tables
    $tables = [];
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tables[] = $row['name'];
    }
    
    // Output database information
    echo json_encode([
        'success' => true,
        'database' => [
            'type' => 'SQLite',
            'version' => $version,
            'path' => $db_path,
            'exists' => $db_exists,
            'size' => $db_size,
            'tables' => $tables
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database test failed: ' . $e->getMessage()
    ]);
}
