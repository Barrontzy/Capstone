<?php
require_once 'includes/db.php';
require_once 'includes/session.php';

if (isset($_POST['approve'])) {
    $id = $_POST['id'];
    $status = 'Approved';
} elseif (isset($_POST['reject'])) {
    $id = $_POST['id'];
    $status = 'Rejected';
} else {
    exit('Invalid action');
}

$stmt = $conn->prepare("UPDATE report_requests SET status=?, date_updated=NOW() WHERE id=?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();
$stmt->close();

// Log it
addLog($conn, $_SESSION['user_id'], "Admin $status report request ID: $id");

header("Location: request.php");
exit();
?>
