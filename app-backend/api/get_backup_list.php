<?php
/**
* Get backup list handler
* Returns a list of available backups
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
   $backupDir = $config['backup_dir'];
   
   // Get all backup files
   $backups = glob($backupDir . 'waiting_list_backup_*.db');

   if ($backups && count($backups) > 0) {
       // Sort backups by modification time (newest first)
       usort($backups, function ($a, $b) {
           return filemtime($b) - filemtime($a);
       });

       // Get metadata for the three most recent backups
       $latestBackups = array_slice($backups, 0, 3);
       $backupInfo = array_map(function ($file) {
           return [
               'file' => basename($file),
               'date' => date("Y-m-d H:i:s", filemtime($file))
           ];
       }, $latestBackups);

       echo json_encode(['success' => true, 'backups' => $backupInfo]);
   } else {
       echo json_encode(['success' => false, 'message' => 'No backups found.']);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
