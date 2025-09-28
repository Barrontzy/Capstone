<?php
require_once 'includes/session.php';
require_once 'includes/db.php';


include 'logger.php';
logAdminAction($_SESSION['user_id'], $_SESSION['user_name'], "Logout", "Admin logged out");

if (isset($_SESSION['user_id'])) {
   
}

session_destroy();

header('Location: index.php');
exit();
?> 