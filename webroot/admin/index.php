<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /admin/login.html');
    exit;
}

// Render the admin panel
include('index.html');