<?php
/**
 * Web-accessible database initialization script
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to capture all output
ob_start();

try {
    // Include the create_db script
    require_once(__DIR__ . '/../app-backend/api/create_db.php');
    
    // Get all output
    $output = ob_get_clean();
    
    // Display a nice HTML page with the results
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Database Initialization</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            h1 {
                color: #333;
            }
            .success {
                color: green;
                font-weight: bold;
            }
            .error {
                color: red;
                font-weight: bold;
            }
            pre {
                background-color: #f5f5f5;
                padding: 10px;
                border-radius: 5px;
                overflow-x: auto;
            }
        </style>
    </head>
    <body>
        <h1>Database Initialization</h1>";
    
    if (strpos($output, "Error:") !== false) {
        echo "<p class='error'>Database initialization failed!</p>";
    } else {
        echo "<p class='success'>Database initialized successfully!</p>";
    }
    
    echo "<h2>Details:</h2>
        <pre>" . htmlspecialchars($output) . "</pre>
        <p><a href='/admin/login.html'>Go to admin login</a></p>
        <p><a href='/public/index.html'>Go to public page</a></p>
    </body>
    </html>";
    
} catch (Exception $e) {
    // Get any output so far
    $output = ob_get_clean();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Database Initialization Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            h1 {
                color: #333;
            }
            .error {
                color: red;
                font-weight: bold;
            }
            pre {
                background-color: #f5f5f5;
                padding: 10px;
                border-radius: 5px;
                overflow-x: auto;
            }
        </style>
    </head>
    <body>
        <h1>Database Initialization Error</h1>
        <p class='error'>An error occurred during database initialization:</p>
        <pre>" . htmlspecialchars($e->getMessage()) . "</pre>
        
        <h2>Output before error:</h2>
        <pre>" . htmlspecialchars($output) . "</pre>
    </body>
    </html>";
}
