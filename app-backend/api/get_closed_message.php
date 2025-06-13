<?php
/**
* Get closed message handler
* Returns the message displayed when the waiting list is closed
*/

header('Content-Type: application/json');

try {
   // Include the database connection helper
   require_once(__DIR__ . '/db_connect.php');
   
   // Connect to the database
   $db = db_connect();
   
   // Query the closed message
   $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'closed_message'");
   $result = $stmt->execute();
   
   if (!$result) {
       throw new Exception('Error executing the query: ' . $db->lastErrorMsg());
   }
   
   $row = $result->fetchArray(SQLITE3_ASSOC);
   
   // If the setting doesn't exist, create it with a default message
   if (!$row) {
       $defaultMessage = 'The waiting list is currently closed. Please check back later.';
       
       $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('closed_message', :message)");
       $stmt->bindValue(':message', $defaultMessage, SQLITE3_TEXT);
       $stmt->execute();
       
       echo json_encode(['success' => true, 'message' => $defaultMessage]);
   } else {
       echo json_encode(['success' => true, 'message' => $row['value']]);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
