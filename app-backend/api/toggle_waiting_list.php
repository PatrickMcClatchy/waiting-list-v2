<?php
/**
* Toggle waiting list handler
* Changes the waiting list state between open and closed
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

   // Retrieve the state of the waiting list from the POST request
   $isOpen = isset($_POST['isOpen']) ? (int)$_POST['isOpen'] : null;

   // Validate if the isOpen parameter is provided
   if ($isOpen === null) {
       throw new Exception('The state of the waiting list (isOpen) is required.');
   }

   // Prepare the SQL statement to update the state of the waiting list
   $stmt = $db->prepare("UPDATE settings SET value = :value WHERE key = 'waiting_list_open'");
   
   // Check if the statement preparation is successful
   if (!$stmt) {
       throw new Exception('Failed to prepare the SQL statement.');
   }

   // Bind the value to the prepared statement
   $stmt->bindValue(':value', $isOpen, SQLITE3_INTEGER);

   // Execute the statement
   $result = $stmt->execute();

   // Check if the execution was successful
   if (!$result) {
       throw new Exception('Failed to execute the SQL statement.');
   }

   // If the list is manually closed, update the manual close date
   if ($isOpen == 0) {
       $currentDate = date('Y-m-d');
       $stmt = $db->prepare("UPDATE settings SET value = :value WHERE key = 'manual_close_date'");
       $stmt->bindValue(':value', $currentDate, SQLITE3_TEXT);
       $stmt->execute();
   } else {
       // Clear the manual close date if the list is opened
       $stmt = $db->prepare("UPDATE settings SET value = :value WHERE key = 'manual_close_date'");
       $stmt->bindValue(':value', '', SQLITE3_TEXT);
       $stmt->execute();
   }

   // Return a success message
   echo json_encode(['success' => true, 'message' => 'Waiting list state updated successfully.']);

} catch (Exception $e) {
   // Return error message
   echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
