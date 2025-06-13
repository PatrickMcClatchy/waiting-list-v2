<?php
/**
* Update scheduled open times handler
* Updates the times when the waiting list is scheduled to open
*/

session_start();
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
   echo json_encode(['success' => false, 'message' => 'Unauthorized']);
   exit;
}

try {
   // Include the database connection helper
   require_once(__DIR__ . '/db_connect.php');
   
   // Connect to the database
   $db = db_connect();

   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       // Sanitize input
       $scheduledOpenTimes = filter_input(INPUT_POST, 'scheduled_open_times', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

       if (!$scheduledOpenTimes) {
           echo json_encode(['success' => false, 'message' => 'Invalid or missing scheduled open times.']);
           exit;
       }

       // Check if the setting exists
       $stmt = $db->prepare("SELECT COUNT(*) as count FROM settings WHERE key = 'scheduled_open_times'");
       $result = $stmt->execute();
       $row = $result->fetchArray(SQLITE3_ASSOC);
       
       if ($row['count'] > 0) {
           // Update existing setting
           $stmt = $db->prepare("UPDATE settings SET value = :value WHERE key = 'scheduled_open_times'");
       } else {
           // Insert new setting
           $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES ('scheduled_open_times', :value)");
       }
       
       $stmt->bindValue(':value', $scheduledOpenTimes, SQLITE3_TEXT);
       $stmt->execute();

       echo json_encode(['success' => true, 'message' => 'Scheduled open times updated successfully.']);
   } else {
       echo json_encode(['success' => false, 'message' => 'Invalid request method']);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
