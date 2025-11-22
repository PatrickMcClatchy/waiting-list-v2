<?php
/**
* Scheduled open handler
* Checks if the waiting list should be opened based on scheduled times
*/

header('Content-Type: application/json');

// Set the time zone
date_default_timezone_set('Europe/Berlin');

try {
   // Include the database connection helper
   require_once(__DIR__ . '/db_connect.php');
   
   // Connect to the database
   $db = db_connect();

   // Fetch the scheduled open times from the database
   $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'scheduled_open_times'");
   $result = $stmt->execute();

   if (!$result) {
       throw new Exception('Error executing the query.');
   }

   $row = $result->fetchArray(SQLITE3_ASSOC);
   $scheduledOpenTimes = $row ? $row['value'] : '';

   if (empty($scheduledOpenTimes)) {
       echo json_encode(['success' => false, 'message' => 'No scheduled open times found.']);
       exit;
   }

   // Parse the scheduled open times
   $openTimes = explode(',', $scheduledOpenTimes);

   // Get the current day and time
   $currentDateTime = new DateTime();
   $currentDay = $currentDateTime->format('l'); // Full day name (e.g., 'Monday')
   $currentTimestamp = $currentDateTime->getTimestamp();

   // Check if the list was manually closed today
   $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'manual_close_date'");
   $result = $stmt->execute();
   $row = $result->fetchArray(SQLITE3_ASSOC);
   $manualCloseDate = $row ? $row['value'] : null;

   $currentDate = (new DateTime())->format('Y-m-d');

   if ($manualCloseDate === $currentDate) {
       echo json_encode(['success' => false, 'message' => 'The list was manually closed today. Automatic opening skipped.']);
       exit;
   }

   // Check if the list is already open
   $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'waiting_list_open'");
   $result = $stmt->execute();
   $row = $result->fetchArray(SQLITE3_ASSOC);
   $isCurrentlyOpen = $row ? (int)$row['value'] === 1 : false;

   if ($isCurrentlyOpen) {
       echo json_encode(['success' => true, 'message' => 'The waiting list is already open.']);
       exit;
   }

   $shouldOpen = false;

   foreach ($openTimes as $openTime) {
       $parts = explode(' ', trim($openTime));
       
       if (count($parts) !== 2) {
           continue;
       }
       
       list($day, $time) = $parts;

       // Skip if the day does not match
       if ($currentDay !== $day) {
           continue;
       }

       // Create DateTime object for scheduled time
       $scheduledDateTime = DateTime::createFromFormat('H:i', $time);
       if ($scheduledDateTime === false) {
           continue;
       }

       // Set the scheduled time to today's date
       $scheduledDateTime->setDate(
           $currentDateTime->format('Y'),
           $currentDateTime->format('m'),
           $currentDateTime->format('d')
       );

       // Compare timestamps - open list if the current time is exactly the same or later
       if ($currentTimestamp >= $scheduledDateTime->getTimestamp()) {
           $shouldOpen = true;
           break;
       }
   }

   // Toggle the waiting list if it should be open
   if ($shouldOpen) {
       // Begin transaction
       $db->exec('BEGIN TRANSACTION');
       
       try {
           // Commented out the backup and clearing logic
           /*
           // Create a backup of the waiting list before clearing it
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
           
           // Clear the waiting list
           $db->exec('DELETE FROM waiting_list');
           */

           // Open the waiting list
           $stmt = $db->prepare("UPDATE settings SET value = '1' WHERE key = 'waiting_list_open'");
           if (!$stmt->execute()) {
               throw new Exception('Failed to update the waiting list state.');
           }
           
           // Commit transaction
           $db->exec('COMMIT');
           
           echo json_encode([
               'success' => true, 
               'message' => 'The waiting list is now open.'
           ]);
       } catch (Exception $e) {
           // Rollback transaction on error
           $db->exec('ROLLBACK');
           throw $e;
       }
   } else {
       echo json_encode(['success' => false, 'message' => 'The waiting list remains closed.']);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
