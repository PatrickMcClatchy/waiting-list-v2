<?php
// filepath: /Users/paddy/CODE/waiting-listv3.0/app-backend/api/update_open_message.php
/**
 * Update Open Message Handler
 * Updates the message displayed when the waiting list is open
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST is allowed.']);
    exit;
}

try {
    require_once(__DIR__ . '/db_connect.php');
    $db = db_connect();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$message) {
            echo json_encode(['success' => false, 'message' => 'Invalid message.']);
            exit;
        }

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM settings WHERE key = 'open_message'");
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row['count'] > 0) {
            $stmt = $db->prepare("UPDATE settings SET value = :message WHERE key = 'open_message'");
        } else {
            $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES ('open_message', :message')");
        }

        $stmt->bindValue(':message', $message, SQLITE3_TEXT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Open message updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}