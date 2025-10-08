<?php
require_once 'db.php'; // make sure db.php connects to your database

/**
 * Log an action to the logs table.
 *
 * @param int $userId  The ID of the user performing the action
 * @param string $action  Description of what happened
 */
function logAction($userId, $action) {
    global $conn;

    if (!$conn) {
        error_log("Database connection failed in logs.php");
        return;
    }

    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $userId, $action);
    $stmt->execute();
    $stmt->close();
}
?>
