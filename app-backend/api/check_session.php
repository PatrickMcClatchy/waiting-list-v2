<?php
/**
* Session check handler
* Verifies if the admin is logged in and the session is valid
*/

session_start();
header('Content-Type: application/json');

// Get the configuration
$config = include(__DIR__ . '/config.php');
$session_timeout = $config['session_timeout'];

// Check if the user is logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
   // Check if the session has timed out
   if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
       // If session has expired, unset and destroy it
       session_unset();
       session_destroy();
       echo json_encode(['loggedIn' => false, 'message' => 'Session expired. Please log in again.']);
       exit;
   } else {
       // Update last activity time if session is still valid
       $_SESSION['LAST_ACTIVITY'] = time();
       echo json_encode(['loggedIn' => true]);
   }
} else {
   // If the user is not logged in, return loggedIn as false
   echo json_encode(['loggedIn' => false, 'message' => 'Unauthorized access.']);
}
