<?php
// Simple test script to verify PHP is working
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'PHP is working correctly',
    'php_version' => phpversion(),
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'script_filename' => $_SERVER['SCRIPT_FILENAME'],
    'current_dir' => __DIR__,
    'parent_dir' => dirname(__DIR__),
    'sqlite_enabled' => extension_loaded('sqlite3') ? 'yes' : 'no'
]);
