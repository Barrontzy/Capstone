<?php
require_once 'includes/session.php';
require_once 'includes/db.php';


// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
   
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit();
?> 