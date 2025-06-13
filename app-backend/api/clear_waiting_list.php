<?php
/**
* Clear waiting list handler
* Clears the waiting list and creates a backup
*/

session_start();
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
   echo json_encode(['success' => false, 'message' => 'Unauthorized']);
   exit;
}

try {
   // Get the configuration
   $config = include(__DIR__ . '/config.php');
   $dbPath = $config['db_path'];
   $backupDir = $config['backup_dir'];
   
   // Set timezone
   date_default_timezone_set('Europe/Berlin');

   // Generate a unique timestamp for the backup file
   $timestamp = date('Y-m-d_H-i-s') . '_' . str_replace('.', '', microtime(true));
   $backupFile = $backupDir . 'waiting_list_backup_' . $timestamp . '.db';

   // Ensure the backup directory exists
   if (!file_exists($backupDir)) {
       if (!mkdir($backupDir, 0777, true)) {
           throw new Exception("Failed to create backup directory: " . $backupDir);
       }
   }

   // Delete any existing backup files beyond the most recent three
   $existingBackups = glob($backupDir . 'waiting_list_backup_*.db');
   usort($existingBackups, function ($a, $b) {
       return filemtime($b) - filemtime($a); // Sort by last modified time (newest first)
   });

   // Keep only the three most recent backups
   if (count($existingBackups) > 3) {
       $backupsToDelete = array_slice($existingBackups, 3); // Get backups beyond the third most recent
       foreach ($backupsToDelete as $file) {
           if (!unlink($file)) {
               error_log("Failed to delete backup file: $file");
           }
       }
   }

   // Copy the main database file to create the new backup
   if (!copy($dbPath, $backupFile)) {
       $error = error_get_last();
       throw new Exception("Failed to create backup: " . $error['message']);
   }

   // Connect to the database
   $db = new SQLite3($dbPath);
   
   // Begin transaction
   $db->exec('BEGIN TRANSACTION');
   
   try {
       // Clear the waiting list
       $db->exec('DELETE FROM waiting_list');
       
       // Commit transaction
       $db->exec('COMMIT');
       
       echo json_encode([
           'success' => true, 
           'message' => 'Waiting list cleared successfully', 
           'backup_file' => basename($backupFile)
       ]);
   } catch (Exception $e) {
       // Rollback transaction on error
       $db->exec('ROLLBACK');
       throw $e;
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
