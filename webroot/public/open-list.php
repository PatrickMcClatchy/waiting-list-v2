<?php
/**
 * Trigger script for scheduled_open_waiting_list.php
 * 
 * Calls the backend script through api_proxy.php like the frontend does
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Triggering scheduled waiting list opener...\n";
echo str_repeat("-", 50) . "\n\n";

// Simulate the API proxy call by including it directly
$_GET['endpoint'] = 'cron_open_waiting_list.php';

// Capture the output
ob_start();
include(__DIR__ . '/api_proxy.php');
$response = ob_get_clean();

echo "Response:\n";

// Try to parse as JSON
$json_response = json_decode($response, true);
if ($json_response && isset($json_response['raw_output'])) {
    // Display the raw output from the cron script
    echo $json_response['raw_output'];
} else {
    // Display the full response if it's not wrapped
    echo $response . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n";
echo "Script execution completed.\n";
?>