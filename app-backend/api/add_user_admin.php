<?php
/**
* Add user handler (admin)
* Adds a new user to the waiting list from the admin panel
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
       // Sanitize input data
       $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : null;
       $comment = isset($_POST['comment']) ? filter_var($_POST['comment'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null;
       $language = isset($_POST['language']) ? filter_var($_POST['language'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null;

       // Validate required fields
       if (empty($name)) {
           echo json_encode(['success' => false, 'message' => 'Name is required.']);
           exit;
       }

       // Get the next position number
       $result = $db->query('SELECT COALESCE(MAX(position), 0) AS max_position FROM waiting_list');
       $row = $result->fetchArray(SQLITE3_ASSOC);
       $position = $row['max_position'] + 1;

       // Insert the new user
       $stmt = $db->prepare('INSERT INTO waiting_list (name, email_or_phone, comment, language, time, confirmed, position) VALUES (:name, :email, :comment, :language, :time, :confirmed, :position)');
       $stmt->bindValue(':name', $name, SQLITE3_TEXT);
       $stmt->bindValue(':email', $email, SQLITE3_TEXT);
       $stmt->bindValue(':comment', $comment, SQLITE3_TEXT);
       $stmt->bindValue(':language', $language, SQLITE3_TEXT);
       $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
       $stmt->bindValue(':confirmed', 1, SQLITE3_INTEGER);
       $stmt->bindValue(':position', $position, SQLITE3_INTEGER);
       $stmt->execute();

       echo json_encode(['success' => true, 'message' => 'User added successfully at position ' . $position]);
   } else {
       echo json_encode(['success' => false, 'message' => 'Invalid request method']);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
