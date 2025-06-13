<?php
/**
* Database connection helper
* Creates a connection to the SQLite database
*/

function db_connect() {
   $config = include(__DIR__ . '/config.php');
   $db_path = $config['db_path'];
   
   try {
       // Create directory if it doesn't exist
       $dir = dirname($db_path);
       if (!file_exists($dir)) {
           mkdir($dir, 0755, true);
       }
       
       $db = new SQLite3($db_path);
       
       if (!$db) {
           throw new Exception('Unable to connect to the database.');
       }
       
       // Enable foreign keys
       $db->exec('PRAGMA foreign_keys = ON');
       
       return $db;
   } catch (Exception $e) {
       throw new Exception('Database connection failed: ' . $e->getMessage());
   }
}
