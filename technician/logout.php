<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

if (isset($_SESSION['user_id'])) {

    // Log logout activity
    $logout_time = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
   
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}
header('Location: ../index.php');
exit();
?> 