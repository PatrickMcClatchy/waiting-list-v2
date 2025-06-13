<?php
/**
* Update closed message handler
* Updates the message displayed when the waiting list is closed
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
       $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

       if (!$message) {
           echo json_encode(['success' => false, 'message' => 'Invalid message.']);
           exit;
       }

       // Check if the setting exists
       $stmt = $db->prepare("SELECT COUNT(*) as count FROM settings WHERE key = 'closed_message'");
       $result = $stmt->execute();
       $row = $result->fetchArray(SQLITE3_ASSOC);
       
       if ($row['count'] > 0) {
           // Update existing setting
           $stmt = $db->prepare("UPDATE settings SET value = :message WHERE key = 'closed_message'");
       } else {
           // Insert new setting
           $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES ('closed_message', :message)");
       }
       
       $stmt->bindValue(':message', $message, SQLITE3_TEXT);
       $stmt->execute();

       echo json_encode(['success' => true, 'message' => 'Closed message updated successfully.']);
   } else {
       echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
