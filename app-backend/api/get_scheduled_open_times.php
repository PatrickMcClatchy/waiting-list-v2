<?php
/**
* Get scheduled open times handler
* Returns the times when the waiting list is scheduled to open
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
   
   // Query the scheduled open times
   $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'scheduled_open_times'");
   $result = $stmt->execute();
   
   if (!$result) {
       throw new Exception('Error executing the query.');
   }
   
   $row = $result->fetchArray(SQLITE3_ASSOC);
   
   // If the setting doesn't exist, create it with default times
   if (!$row) {
       $defaultTimes = 'Monday 09:00,Thursday 14:00';
       
       $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES ('scheduled_open_times', :times)");
       $stmt->bindValue(':times', $defaultTimes, SQLITE3_TEXT);
       $stmt->execute();
       
       echo json_encode(['success' => true, 'scheduled_open_times' => $defaultTimes]);
   } else {
       echo json_encode(['success' => true, 'scheduled_open_times' => $row['value']]);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
