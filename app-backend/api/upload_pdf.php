<?php
/**
* Upload PDF handler
* Uploads a new confirmation PDF
*/

session_start();
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
   echo json_encode(['success' => false, 'message' => 'Unauthorized']);
   exit;
}

try {
   // Check if a file was uploaded
   if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
       throw new Exception('File upload failed.');
   }

   // Ensure the uploaded file is a PDF
   $fileType = mime_content_type($_FILES['pdf']['tmp_name']);
   if ($fileType !== 'application/pdf') {
       throw new Exception('Only PDF files are allowed.');
   }

   // Move the uploaded file to the public directory
   $uploadDir = __DIR__ . '/../../webroot/public/';
   $uploadFile = $uploadDir . 'confirmation.pdf';

   if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $uploadFile)) {
       throw new Exception('Failed to move uploaded file.');
   }

   echo json_encode(['success' => true, 'message' => 'PDF uploaded successfully.']);
} catch (Exception $e) {
   echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
