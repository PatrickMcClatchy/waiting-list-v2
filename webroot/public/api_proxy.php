<?php
/**
 * API Proxy for Public
 * 
 * This file forwards requests to the backend API and returns the response.
 * It acts as a bridge between the frontend and the backend.
 */

// Enable error reporting for debugging
ini_set('display_errors', 0); // Turn off HTML error display
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../app-backend/logs/error.log');

// Set content type to JSON
header('Content-Type: application/json');

// Define the path to the backend API folder
$apiPath = __DIR__ . '/../../app-backend/api';

// Get the full endpoint string including query parameters
$fullEndpoint = $_GET['endpoint'] ?? null;

// Debug information
$debug = [
    'requested_endpoint' => $fullEndpoint,
    'api_path' => $apiPath,
    'api_path_exists' => file_exists($apiPath) ? 'yes' : 'no',
    'current_dir' => __DIR__,
    'parent_dir' => dirname(__DIR__),
    'grandparent_dir' => dirname(dirname(__DIR__))
];

// Extract just the endpoint name without query parameters
$endpoint = $fullEndpoint;
if ($endpoint && strpos($endpoint, '?') !== false) {
    $endpoint = substr($endpoint, 0, strpos($endpoint, '?'));
}

// Check if the endpoint is provided and is a valid PHP file
if (!$endpoint || !preg_match('/^[\w\-]+\.php$/', $endpoint)) {
   echo json_encode(['success' => false, 'message' => 'Invalid endpoint specified.', 'debug' => $debug]);
   exit;
}

// Construct the full path to the target file in the api folder
$targetFile = $apiPath . '/' . $endpoint;
$debug['target_file'] = $targetFile;
$debug['target_file_exists'] = file_exists($targetFile) ? 'yes' : 'no';

// Check if the file exists
if (!file_exists($targetFile)) {
   echo json_encode(['success' => false, 'message' => 'Endpoint file not found.', 'debug' => $debug]);
   exit;
}

// Extract query parameters from the full endpoint
$queryParams = [];
if (strpos($fullEndpoint, '?') !== false) {
    $queryString = substr($fullEndpoint, strpos($fullEndpoint, '?') + 1);
    parse_str($queryString, $queryParams);
    
    // Add these parameters to $_GET so they're available to the included script
    foreach ($queryParams as $key => $value) {
        if ($key !== 'endpoint') {  // Don't override the endpoint parameter
            $_GET[$key] = $value;
        }
    }
}

$debug['extracted_query_params'] = $queryParams;

// For all endpoints, expect JSON responses
try {
   ob_start(); // Start output buffering
   include $targetFile;
   $output = ob_get_clean(); // Get everything that was output
   
   // Check if the output is valid JSON
   $jsonOutput = json_decode($output);
   if (json_last_error() !== JSON_ERROR_NONE) {
       // Not valid JSON, wrap it in a JSON response
       echo json_encode([
           'success' => false, 
           'message' => 'Invalid JSON response from endpoint', 
           'raw_output' => $output,
           'debug' => $debug
       ]);
   } else {
       // Valid JSON, pass it through
       echo $output;
   }
} catch (Exception $e) {
   echo json_encode([
       'success' => false, 
       'message' => 'Error executing endpoint: ' . $e->getMessage(),
       'debug' => $debug
   ]);
}
