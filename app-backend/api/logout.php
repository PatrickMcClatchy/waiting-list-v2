<?php
/**
* Admin logout handler
* Destroys the session and redirects to the logged out page
*/

session_start();

// Unset all session variables
$_SESSION = array();

// If a session cookie is used, destroy it
if (ini_get("session.use_cookies")) {
   $params = session_get_cookie_params();
   setcookie(session_name(), '', time() - 42000,
       $params["path"], $params["domain"],
       $params["secure"], $params["httponly"]
   );
}

// Destroy the session
session_destroy();

// Redirect to the logged out page in the admin folder
header('Location: /admin/logged_out.html');
exit;
