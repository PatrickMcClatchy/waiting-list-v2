<?php
// filepath: /Users/paddy/CODE/waiting-listv3.0/app-backend/api/get_open_message.php
/**
 * Get Open Message Handler
 * Returns the message displayed when the waiting list is open
 */

header('Content-Type: application/json');

try {
    // Ensure the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST is allowed.']);
        exit;
    }

    // Include the database connection helper
    require_once(__DIR__ . '/db_connect.php');
    
    // Connect to the database
    $db = db_connect();
    
    // Fetch the open message from the settings table
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'open_message'");
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $open_message = $row ? $row['value'] : 'The waiting list is currently open. Feel free to sign up!';

    // Replace the placeholder {{next_appointment}} dynamically
    if (strpos($open_message, '{{next_appointment}}') !== false) {
        $signupDate = new DateTime();
        $dayOfWeek = $signupDate->format('w'); // 0 (Sunday) to 6 (Saturday)

        if ($dayOfWeek <= 3) { // Sunday, Monday, Tuesday, or Wednesday
            $signupDate->modify('next Wednesday');
        } else { // Thursday, Friday, or Saturday
            $signupDate->modify('next Friday');
        }

        $formattedDate = $signupDate->format('l, F j, Y'); // Example: "Wednesday, March 15, 2023"
        $open_message = str_replace('{{next_appointment}}', $formattedDate, $open_message);
    }

    // Return the open message as JSON
    echo json_encode([
        'success' => true,
        'message' => $open_message,
    ]);
} catch (Exception $e) {
    // Handle any errors and return a failure response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching the open message.',
    ]);
}