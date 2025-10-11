<?php
/**
* Configuration file for the waiting list application
* This file contains sensitive information and should be stored outside the web root
*/

// Define database path
$dbPath = __DIR__ . '/../data/waiting_list.db';
$backupDir = __DIR__ . '/../backups/';

return [
   // Admin password hash - default password is "admin123"
   // Generated using password_hash() with PASSWORD_DEFAULT algorithm
   'admin_password_hash' => '$2y$12$BY2Z/eVcJiucT.7rK9Afu.30JDCjByCX5V7L0/e8HMVXs9FRkz6hG',
   
   // Database configuration
   'db_path' => $dbPath,
   
   // Backup directory
   'backup_dir' => $backupDir,
   
   // Session timeout in seconds (15 minutes)
   'session_timeout' => 900,
   
   // reCAPTCHA configuration
   'recaptcha' => [
       'site_key' => '6LeK9oIqAAAAAJQR2aXqxZiY-TDG4BQglyC1qTNq',
       'secret_key' => '6LeK9oIqAAAAAHGWBhQvZ90QvEe4y7Kc4chgc62h'
   ]
];
