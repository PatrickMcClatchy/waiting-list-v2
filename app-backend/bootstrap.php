<?php
/**
 * Application Bootstrap
 * 
 * This file initializes the application, loads configuration,
 * sets up error handling, and prepares the environment.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Define application constants
define('APP_ROOT', __DIR__);
define('CONFIG_PATH', APP_ROOT . '/config');
define('MODELS_PATH', APP_ROOT . '/src/Models');
define('CONTROLLERS_PATH', APP_ROOT . '/src/Controllers');
define('SERVICES_PATH', APP_ROOT . '/src/Services');
define('UTILS_PATH', APP_ROOT . '/src/Utils');

// Load configuration
require_once CONFIG_PATH . '/config.php';

// Set timezone
date_default_timezone_set(APP_CONFIG['timezone']);

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict'
    ]);
}

// Load utility functions
require_once UTILS_PATH . '/functions.php';

// Set up autoloading for classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = '';
    $base_dir = APP_ROOT . '/src/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separator with directory separator
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Create required directories if they don't exist
$directories = [
    APP_ROOT . '/logs',
    APP_ROOT . '/backups',
    APP_ROOT . '/data'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Initialize the database connection
require_once UTILS_PATH . '/Database.php';
$db = new Database(APP_CONFIG['database']['path']);

// Initialize the response handler
require_once UTILS_PATH . '/Response.php';
$response = new Response();

// Initialize the request handler
require_once UTILS_PATH . '/Request.php';
$request = new Request();

// Initialize the authentication service
require_once SERVICES_PATH . '/AuthService.php';
$auth = new AuthService($db);

// Set up error handler
set_exception_handler(function ($exception) use ($response) {
    error_log($exception->getMessage());
    $response->error('An unexpected error occurred', 500);
});
