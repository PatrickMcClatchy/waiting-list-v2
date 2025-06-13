<?php
/**
* Get backup info handler
* Returns information about the latest backup
*/

header('Content-Type: application/json');

try {
   // Get the configuration
   $config = include(__DIR__ . '/config.php');
   $backupDir = $config['backup_dir'];
   
   // Set timezone
   date_default_timezone_set('Europe/Berlin');
   
   // Get all backup files
   $backups = glob($backupDir . 'waiting_list_backup_*.db');

   if ($backups && count($backups) > 0) {
       // Sort backups by modification time (newest first)
       usort($backups, function ($a, $b) {
           return filemtime($b) - filemtime($a);
       });
       
       $latestBackup = $backups[0];
       $lastBackupDate = date("Y-m-d H:i:s", filemtime($latestBackup));
       
       echo json_encode(['success' => true, 'backup_date' => $lastBackupDate]);
   } else {
       echo json_encode([
           'success' => false,
           'message' => 'No backup files found.'
       ]);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
