<?php
/**
* Move user handler
* Changes a user's position in the waiting list
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

   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['direction'])) {
       $id = intval($_POST['id']);
       $direction = $_POST['direction'];

       // Get the current user's position
       $stmt = $db->prepare("SELECT id, position FROM waiting_list WHERE id = :id");
       $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
       $result = $stmt->execute();
       $currentUser = $result->fetchArray(SQLITE3_ASSOC);

       if (!$currentUser) {
           echo json_encode(['success' => false, 'message' => 'User not found']);
           exit;
       }

       $currentPosition = $currentUser['position'];
       $newPosition = null;
       $swapUserId = null;

       // Determine the new position based on direction
       if ($direction === 'up') {
           if ($currentPosition <= 1) {
               echo json_encode(['success' => false, 'message' => 'User is already at the top']);
               exit;
           }
           $newPosition = $currentPosition - 1;
           
           // Find the user to swap with
           $stmt = $db->prepare("SELECT id FROM waiting_list WHERE position = :position");
           $stmt->bindValue(':position', $newPosition, SQLITE3_INTEGER);
           $result = $stmt->execute();
           $swapUser = $result->fetchArray(SQLITE3_ASSOC);
           
           if ($swapUser) {
               $swapUserId = $swapUser['id'];
           }
       } else if ($direction === 'down') {
           // Get the maximum position
           $result = $db->query("SELECT MAX(position) as max_position FROM waiting_list");
           $row = $result->fetchArray(SQLITE3_ASSOC);
           $maxPosition = $row['max_position'];
           
           if ($currentPosition >= $maxPosition) {
               echo json_encode(['success' => false, 'message' => 'User is already at the bottom']);
               exit;
           }
           $newPosition = $currentPosition + 1;
           
           // Find the user to swap with
           $stmt = $db->prepare("SELECT id FROM waiting_list WHERE position = :position");
           $stmt->bindValue(':position', $newPosition, SQLITE3_INTEGER);
           $result = $stmt->execute();
           $swapUser = $result->fetchArray(SQLITE3_ASSOC);
           
           if ($swapUser) {
               $swapUserId = $swapUser['id'];
           }
       } else {
           echo json_encode(['success' => false, 'message' => 'Invalid direction']);
           exit;
       }

       if ($swapUserId) {
           // Begin transaction
           $db->exec('BEGIN TRANSACTION');
           
           try {
               // Update the swap user's position
               $stmt = $db->prepare("UPDATE waiting_list SET position = :position WHERE id = :id");
               $stmt->bindValue(':position', $currentPosition, SQLITE3_INTEGER);
               $stmt->bindValue(':id', $swapUserId, SQLITE3_INTEGER);
               $stmt->execute();
               
               // Update the current user's position
               $stmt = $db->prepare("UPDATE waiting_list SET position = :position WHERE id = :id");
               $stmt->bindValue(':position', $newPosition, SQLITE3_INTEGER);
               $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
               $stmt->execute();
               
               // Commit transaction
               $db->exec('COMMIT');
               
               echo json_encode(['success' => true, 'message' => 'User moved successfully']);
           } catch (Exception $e) {
               // Rollback transaction on error
               $db->exec('ROLLBACK');
               throw $e;
           }
       } else {
           echo json_encode(['success' => false, 'message' => 'Cannot move user']);
       }
   } else {
       echo json_encode(['success' => false, 'message' => 'Invalid request']);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
