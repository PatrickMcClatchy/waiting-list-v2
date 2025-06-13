<?php
/**
* Get waiting list handler
* Returns all users in the waiting list
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
   
   // Check if the waiting_list table exists
   $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='waiting_list'");
   $tableExists = $tableCheck && $tableCheck->fetchArray();
   
   if (!$tableExists) {
       // Create the waiting_list table if it doesn't exist
       $db->exec("CREATE TABLE IF NOT EXISTS waiting_list (
           id INTEGER PRIMARY KEY AUTOINCREMENT,
           name TEXT NOT NULL,
           email_or_phone TEXT,
           comment TEXT,
           language TEXT,
           time INTEGER,
           confirmed INTEGER DEFAULT 0,
           position INTEGER NOT NULL
       )");
   }
   
   // Query all users in the waiting list, ordered by position
   $results = $db->query('SELECT * FROM waiting_list ORDER BY position ASC');
   
   // Check if query was successful
   if ($results === false) {
       throw new Exception('Failed to query waiting list: ' . $db->lastErrorMsg());
   }
   
   $waitingList = [];
   while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
       $waitingList[] = $row;
   }

   echo json_encode(['success' => true, 'data' => $waitingList]);
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
