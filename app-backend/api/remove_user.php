<?php
/**
* Remove user handler
* Removes a user from the waiting list
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

   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
       $id = intval($_POST['id']);

       // Get the user's position before deleting
       $stmt = $db->prepare("SELECT position FROM waiting_list WHERE id = :id");
       $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
       $result = $stmt->execute();
       $user = $result->fetchArray(SQLITE3_ASSOC);
       
       if (!$user) {
           echo json_encode(['success' => false, 'message' => 'User not found']);
           exit;
       }
       
       $position = $user['position'];
       
       // Delete the user
       $stmt = $db->prepare("DELETE FROM waiting_list WHERE id = :id");
       $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
       $stmt->execute();
       
       // Update positions of users after the deleted user
       $stmt = $db->prepare("UPDATE waiting_list SET position = position - 1 WHERE position > :position");
       $stmt->bindValue(':position', $position, SQLITE3_INTEGER);
       $stmt->execute();

       echo json_encode(['success' => true, 'message' => 'User removed successfully']);
   } else {
       echo json_encode(['success' => false, 'message' => 'Invalid request']);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
