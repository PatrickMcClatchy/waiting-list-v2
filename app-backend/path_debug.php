<?php
/**
 * Path Debug Tool
 * 
 * This script helps diagnose path-related issues on the server.
 * Access this file directly to see detailed path information.
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

// Function to check if a path exists and is readable
function check_path($path) {
    if (file_exists($path)) {
        if (is_readable($path)) {
            return "EXISTS and is readable";
        } else {
            return "EXISTS but is NOT readable";
        }
    } else {
        return "DOES NOT EXIST";
    }
}

// Function to check if a directory is writable
function check_dir_writable($path) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            return "WRITABLE";
        } else {
            return "NOT WRITABLE";
        }
    } else {
        return "NOT A DIRECTORY";
    }
}

// Get server information
echo "=== SERVER INFORMATION ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Current Script: " . __FILE__ . "\n\n";

// Check important directories
echo "=== DIRECTORY STRUCTURE ===\n";
$currentDir = __DIR__;
$parentDir = dirname($currentDir);
$webroot = $parentDir . '/webroot';
$dataDir = $currentDir . '/data';
$apiDir = $currentDir . '/api';
$backupsDir = $currentDir . '/backups';

echo "Current Directory: $currentDir - " . check_path($currentDir) . "\n";
echo "Parent Directory: $parentDir - " . check_path($parentDir) . "\n";
echo "Webroot Directory: $webroot - " . check_path($webroot) . "\n";
echo "Data Directory: $dataDir - " . check_path($dataDir) . " - " . check_dir_writable($dataDir) . "\n";
echo "API Directory: $apiDir - " . check_path($apiDir) . "\n";
echo "Backups Directory: $backupsDir - " . check_path($backupsDir) . " - " . check_dir_writable($backupsDir) . "\n\n";

// Check database file
echo "=== DATABASE FILE ===\n";
$dbPath = $dataDir . '/waiting_list.db';
echo "Database Path: $dbPath - " . check_path($dbPath) . "\n";

// Try alternative database paths
$altDbPaths = [
    dirname($currentDir) . '/data/waiting_list.db',
    $_SERVER['DOCUMENT_ROOT'] . '/../app-backend/data/waiting_list.db',
    dirname($_SERVER['DOCUMENT_ROOT']) . '/app-backend/data/waiting_list.db'
];

foreach ($altDbPaths as $index => $path) {
    echo "Alt DB Path $index: $path - " . check_path($path) . "\n";
}
echo "\n";

// Check API proxy files
echo "=== API PROXY FILES ===\n";
$adminProxyPath = $webroot . '/admin/api_proxy.php';
$publicProxyPath = $webroot . '/public/api_proxy.php';

echo "Admin API Proxy: $adminProxyPath - " . check_path($adminProxyPath) . "\n";
echo "Public API Proxy: $publicProxyPath - " . check_path($publicProxyPath) . "\n\n";

// Check important API files
echo "=== IMPORTANT API FILES ===\n";
$apiFiles = [
    'config.php',
    'db_connect.php',
    'get_waiting_list.php',
    'get_waiting_list_state.php',
    'login.php'
];

foreach ($apiFiles as $file) {
    $path = $apiDir . '/' . $file;
    echo "$file: $path - " . check_path($path) . "\n";
}
echo "\n";

// Check include paths
echo "=== PHP INCLUDE PATHS ===\n";
$includePaths = explode(PATH_SEPARATOR, get_include_path());
foreach ($includePaths as $path) {
    echo "$path - " . check_path($path) . "\n";
}
echo "\n";

// Check permissions
echo "=== FILE PERMISSIONS ===\n";
$filesToCheck = [
    $dbPath => "Database File",
    $dataDir => "Data Directory",
    $backupsDir => "Backups Directory",
    $apiDir => "API Directory",
    $adminProxyPath => "Admin API Proxy",
    $publicProxyPath => "Public API Proxy"
];

foreach ($filesToCheck as $path => $description) {
    if (file_exists($path)) {
        $perms = fileperms($path);
        $octal = substr(sprintf('%o', $perms), -4);
        echo "$description: $path - Permissions: $octal\n";
    } else {
        echo "$description: $path - DOES NOT EXIST\n";
    }
}
