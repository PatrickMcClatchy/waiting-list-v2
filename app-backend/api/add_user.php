<?php
/**
* Add user handler (public)
* Adds a new user to the waiting list from the public form
*/

header('Content-Type: application/json');

// Get the configuration
$config = include(__DIR__ . '/config.php');
$recaptcha_secret = $config['recaptcha']['secret_key'];

// Verify reCAPTCHA
if (!isset($_POST['g-recaptcha-response'])) {
   echo json_encode(['success' => false, 'message' => 'CAPTCHA not completed.']);
   exit;
}

$recaptchaResponse = $_POST['g-recaptcha-response'];

// Send POST request to Google's reCAPTCHA verification API
$verifyURL = 'https://www.google.com/recaptcha/api/siteverify';
$response = file_get_contents($verifyURL . '?secret=' . $recaptcha_secret . '&response=' . $recaptchaResponse);
$responseKeys = json_decode($response, true);

if (!$responseKeys['success']) {
   echo json_encode(['success' => false, 'message' => 'CAPTCHA verification failed.']);
   exit;
}

try {
   // Include the database connection helper
   require_once(__DIR__ . '/db_connect.php');
   
   // Connect to the database
   $db = db_connect();
   
   // Check if the waiting list is open
   $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'waiting_list_open'");
   $result = $stmt->execute();
   $row = $result->fetchArray(SQLITE3_ASSOC);
   
   if (!$row || $row['value'] != '1') {
       echo json_encode(['success' => false, 'message' => 'The waiting list is currently closed.']);
       exit;
   }

   // Check for required POST data
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
       // Sanitize input data
       $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : null;
       $comment = isset($_POST['comment']) ? filter_var($_POST['comment'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null;
       $language = isset($_POST['language']) ? filter_var($_POST['language'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null;
       $time = time();

       // Validate required fields
       if (empty($name)) {
           echo json_encode(['success' => false, 'message' => 'Name is required.']);
           exit;
       }

       // Get the next position number
       $result = $db->query('SELECT COALESCE(MAX(position), 0) AS max_position FROM waiting_list');
       $row = $result->fetchArray(SQLITE3_ASSOC);
       $position = $row['max_position'] + 1;

       // Insert new user
       $stmt = $db->prepare('INSERT INTO waiting_list (name, email_or_phone, comment, language, time, confirmed, position) VALUES (:name, :email, :comment, :language, :time, :confirmed, :position)');
       $stmt->bindValue(':name', $name, SQLITE3_TEXT);
       $stmt->bindValue(':email', $email, SQLITE3_TEXT);
       $stmt->bindValue(':comment', $comment, SQLITE3_TEXT);
       $stmt->bindValue(':language', $language, SQLITE3_TEXT);
       $stmt->bindValue(':time', $time, SQLITE3_INTEGER);
       $stmt->bindValue(':confirmed', 1, SQLITE3_INTEGER);
       $stmt->bindValue(':position', $position, SQLITE3_INTEGER);
       $stmt->execute();

       // Fetch the success message from the settings table
       $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'success_message'");
       $result = $stmt->execute();
       $row = $result->fetchArray(SQLITE3_ASSOC);
       $success_message = $row ? $row['value'] : 'You\'ve successfully signed up! Your position in the waiting list is: #{{position}}';

       // Replace the placeholder {{position}} with the actual position
       $success_message = str_replace('{{position}}', $position, $success_message);

       echo json_encode([
           'success' => true,
           'message' => $success_message,
           'position' => $position,
       ]);
   } else {
       echo json_encode(['success' => false, 'message' => 'Invalid request.']);
   }
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => 'Failed to add user: ' . $e->getMessage()]);
}
