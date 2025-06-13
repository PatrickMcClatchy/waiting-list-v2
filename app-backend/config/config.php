<?php
/**
 * Application Configuration
 * 
 * This file contains all application configuration settings.
 * In a production environment, sensitive values should be stored in environment variables.
 */

// Define the application configuration
$APP_CONFIG = [
    // Application settings
    'app_name' => 'SAGA Waiting List',
    'version' => '1.0.0',
    'timezone' => 'Europe/Berlin',
    'debug' => true,
    
    // Database settings
    'database' => [
        'type' => 'sqlite',
        'path' => APP_ROOT . '/data/waiting_list.db'
    ],
    
    // Authentication settings
    'auth' => [
        // Default admin password: admin123
        'admin_password_hash' => '$2y$12$B62GuydxUnzosK17zJ8Hruhhy5bdr8NzDkaWf0dqyYH./F0HpZYsu',
        'session_timeout' => 900, // 15 minutes
    ],
    
    // Backup settings
    'backup' => [
        'directory' => APP_ROOT . '/backups/',
        'max_backups' => 5,
    ],
    
    // File upload settings
    'uploads' => [
        'pdf_directory' => APP_ROOT . '/../webroot/public/',
        'allowed_types' => ['application/pdf'],
        'max_size' => 5 * 1024 * 1024, // 5MB
    ],
    
    // reCAPTCHA settings
    'recaptcha' => [
        'site_key' => '6LeK9oIqAAAAAJQR2aXqxZiY-TDG4BQglyC1qTNq',
        'secret_key' => '6LeK9oIqAAAAAHGWBhQvZ90QvEe4y7Kc4chgc62h',
        'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
    ],
    
    // API settings
    'api' => [
        'cors' => [
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'allowed_headers' => ['Content-Type', 'Authorization'],
        ],
    ],
];

// Make configuration globally available
define('APP_CONFIG', $APP_CONFIG);
