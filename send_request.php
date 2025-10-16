<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_SESSION['username'] ?? 'Unknown';
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === '') {
        echo "⚠️ No form type provided.";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO report_requests (user, form_type, status, date_requested) VALUES (?, ?, 'Pending', NOW())");
    $stmt->bind_param("ss", $user, $form_type);

    if ($stmt->execute()) {
        echo "✅ Request for '{$form_type}' has been sent and is waiting for admin approval.";
    } else {
        echo "❌ Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Invalid request method.";
}
?>
