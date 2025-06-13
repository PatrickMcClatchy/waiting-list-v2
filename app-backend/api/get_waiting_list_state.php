<?php
/**
* Get waiting list state handler
* Returns whether the waiting list is currently open or closed
*/

header('Content-Type: application/json');

try {
   // Include the database connection helper
   require_once(__DIR__ . '/db_connect.php');
   
   // Connect to the database
   $db = db_connect();
   
   // Check if the settings table exists
   $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'");
   $tableExists = $tableCheck && $tableCheck->fetchArray();
   
   if (!$tableExists) {
       // Create the settings table if it doesn't exist
       $db->exec("CREATE TABLE IF NOT EXISTS settings (
           id INTEGER PRIMARY KEY AUTOINCREMENT,
           key TEXT UNIQUE NOT NULL,
           value TEXT NOT NULL
       )");
       
       // Insert default value
       $db->exec("INSERT INTO settings (key, value) VALUES ('waiting_list_open', '0')");
   }
   
   // Query the waiting list state
   $stmt = $db->prepare("SELECT value FROM settings WHERE key = :key");
   
   // Check if prepare statement was successful
   if ($stmt === false) {
       throw new Exception('Failed to prepare statement: ' . $db->lastErrorMsg());
   }
   
   $stmt->bindValue(':key', 'waiting_list_open', SQLITE3_TEXT);
   $result = $stmt->execute();
   
   if (!$result) {
       throw new Exception('Error executing the query: ' . $db->lastErrorMsg());
   }
   
   $row = $result->fetchArray(SQLITE3_ASSOC);

   if ($row) {
       echo json_encode(['success' => true, 'isOpen' => (int)$row['value']]);
   } else {
       // If no row was found, insert default value and return it
       $db->exec("INSERT INTO settings (key, value) VALUES ('waiting_list_open', '0')");
       echo json_encode(['success' => true, 'isOpen' => 0]);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
