<?php
/**
* Admin login handler
* Authenticates the admin user and creates a session
*/

session_start();
header('Content-Type: application/json');

try {
   // Get the configuration
   $config = include(__DIR__ . '/config.php');
   $hashed_password = $config['admin_password_hash'];
   $session_timeout = $config['session_timeout'];

   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       // Validate the password
       if (!isset($_POST['password']) || empty($_POST['password'])) {
           echo json_encode(['success' => false, 'message' => 'Password is required.']);
           exit;
       }
       
       $password = $_POST['password'];

       // Check if password is correct
       if (password_verify($password, $hashed_password)) {
           // Set session variables
           $_SESSION['loggedin'] = true;
           $_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time
           $_SESSION['CREATED'] = time();       // Record session creation time
           
           // Regenerate session ID on successful login for security
           session_regenerate_id(true);

           echo json_encode(['success' => true, 'message' => 'Logged in successfully.']);
       } else {
           echo json_encode(['success' => false, 'message' => 'Invalid password.']);
       }
   } else {
       echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
   }
} catch (Exception $e) {
   // Log the error and send a generic message
   error_log('Login error: ' . $e->getMessage());
   echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
exit;
