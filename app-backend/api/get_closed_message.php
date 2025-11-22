<?php
/**
 * Get Closed Message Handler
 * Returns the message displayed when the waiting list is closed
 */

header('Content-Type: application/json');

try {
    // Include the database connection helper
    require_once(__DIR__ . '/db_connect.php');
    
    // Connect to the database
    $db = db_connect();
    
    // Fetch the closed message from the settings table
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'closed_message'");
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $closed_message = $row ? $row['value'] : 'The waiting list is currently closed.';

    // Replace the placeholder {{next_appointment}} dynamically
    if (strpos($closed_message, '{{next_appointment}}') !== false) {
        $signupDate = new DateTime();
        $dayOfWeek = $signupDate->format('w'); // 0 (Sunday) to 6 (Saturday)

        if ($dayOfWeek == 0 || $dayOfWeek < 3) { // Sunday, Monday, or Tuesday
            $signupDate->modify('next Wednesday');
        } elseif ($dayOfWeek == 3) { // Today is Wednesday
            // Keep today's date
        } elseif ($dayOfWeek == 4) { // Thursday
            $signupDate->modify('next Friday');
        } elseif ($dayOfWeek == 5) { // Today is Friday
            // Keep today's date
        } else { // Saturday
            $signupDate->modify('next Wednesday');
        }

        $formattedDate = $signupDate->format('l, F j, Y'); // Example: "Wednesday, March 15, 2023"
        $closed_message = str_replace('{{next_appointment}}', $formattedDate, $closed_message);
    }

    // Return the closed message as JSON
    echo json_encode([
        'success' => true,
        'message' => $closed_message,
    ]);
} catch (Exception $e) {
    // Handle any errors and return a failure response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching the closed message.',
    ]);
}