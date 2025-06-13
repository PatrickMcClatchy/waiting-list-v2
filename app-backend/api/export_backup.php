<?php
/**
 * Export Backup Handler
 * 
 * Creates a minimal, clean PDF export of a backup file
 */

session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get the configuration
    $config = include(__DIR__ . '/config.php');
    $backupDir = $config['backup_dir'];
    
    // Get the requested backup file
    $backupFile = isset($_GET['file']) ? $_GET['file'] : null;
    
    if (!$backupFile) {
        throw new Exception('No backup file specified.');
    }
    
    // Use a more permissive regex pattern for backup file validation
    if (!preg_match('/^waiting_list_backup_[\w\-\.]+\.db$/', $backupFile)) {
        throw new Exception('Invalid backup file format.');
    }
    
    $backupPath = $backupDir . $backupFile;
    
    if (!file_exists($backupPath)) {
        throw new Exception('Backup file not found.');
    }
    
    // Open the backup database
    $backupDb = new SQLite3($backupPath);
    
    if (!$backupDb) {
        throw new Exception('Failed to open backup database.');
    }
    
    // Query all users in the backup, ordered by position
    $results = $backupDb->query('SELECT * FROM waiting_list ORDER BY position ASC');
    
    $waitingList = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $waitingList[] = $row;
    }
    
    // Close the backup database
    $backupDb->close();
    
    // Get the backup date from the filename
    $backupDate = 'Unknown';
    if (preg_match('/waiting_list_backup_(\d{4}-\d{2}-\d{2})/', $backupFile, $matches)) {
        $backupDate = $matches[1];
    } else {
        // Use file modification time as fallback
        $backupDate = date('Y-m-d', filemtime($backupPath));
    }

    // Set headers for HTML
    header('Content-Type: text/html; charset=utf-8');
    
    // Create a printer-friendly HTML document
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SAGA Waiting List Backup - ' . htmlspecialchars($backupDate) . '</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.4;
            color: #333;
            background: #fff;
            padding: 15px;
            font-size: 10pt;
        }
        
        /* Date and count info */
        .info {
            font-size: 8pt;
            color: #777;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        
        /* Two-column layout */
        .columns {
            display: flex;
            gap: 15px;
        }
        
        .column {
            flex: 1;
        }
        
        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 8pt;
        }
        
        /* Print-specific styles */
        @media print {
            body {
                padding: 0;
            }
            
            @page {
                margin: 1cm;
                size: portrait;
            }
        }
    </style>
</head>
<body>
    <div class="info">
        <span>Backup date: ' . htmlspecialchars($backupDate) . '</span>
        <span>Total entries: ' . count($waitingList) . '</span>
    </div>';

    // Split the list into two columns
    $totalEntries = count($waitingList);
    $entriesPerColumn = ceil($totalEntries / 2);
    
    echo '<div class="columns">';
    
    // First column
    echo '<div class="column">
        <table>
            <thead>
                <tr>
                    <th>Pos</th>
                    <th>Name</th>
                    <th>Language</th>
                </tr>
            </thead>
            <tbody>';
    
    for ($i = 0; $i < $entriesPerColumn && $i < $totalEntries; $i++) {
        $user = $waitingList[$i];
        echo '<tr>
            <td>' . htmlspecialchars($user['position']) . '</td>
            <td>' . htmlspecialchars($user['name']) . '</td>
            <td>' . htmlspecialchars($user['language'] ?? '') . '</td>
        </tr>';
    }
    
    echo '</tbody>
        </table>
    </div>';
    
    // Second column
    echo '<div class="column">
        <table>
            <thead>
                <tr>
                    <th>Pos</th>
                    <th>Name</th>
                    <th>Language</th>
                </tr>
            </thead>
            <tbody>';
    
    for ($i = $entriesPerColumn; $i < $totalEntries; $i++) {
        $user = $waitingList[$i];
        echo '<tr>
            <td>' . htmlspecialchars($user['position']) . '</td>
            <td>' . htmlspecialchars($user['name']) . '</td>
            <td>' . htmlspecialchars($user['language'] ?? '') . '</td>
        </tr>';
    }
    
    echo '</tbody>
        </table>
    </div>';
    
    echo '</div>'; // End columns
    
    echo '<script>
        // Auto-print when loaded
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>';

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
