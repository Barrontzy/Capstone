<?php
session_start();
require_once '../includes/session.php';

if (isset($_SESSION['user_id'])) {
    require_once '../includes/db.php';

    // Log logout activity
    $logout_time = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $log_stmt = $conn->prepare("
        INSERT INTO activity_log (user_id, action, ip_address, user_agent)
        VALUES (?, 'Technician Logout', ?, ?)
    ");
    $log_stmt->bind_param("iss", $_SESSION['user_id'], $ip_address, $user_agent);
    $log_stmt->execute();
}

session_destroy();
header('Location: login.php');
exit();
?> 