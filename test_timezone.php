<?php
require_once 'includes/db.php';

echo "<h2>Timezone Test</h2>";
echo "<p><strong>PHP Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test MySQL timezone
$result = $conn->query("SELECT NOW() as mysql_time");
$row = $result->fetch_assoc();
echo "<p><strong>MySQL Time:</strong> " . $row['mysql_time'] . "</p>";

// Check current OTPs
echo "<h3>Current OTPs in Database:</h3>";
$result = $conn->query("SELECT email, otp_code, expires_at, created_at, 
    CASE 
        WHEN expires_at > NOW() THEN 'Valid'
        ELSE 'Expired'
    END as status
    FROM password_reset_otps 
    ORDER BY created_at DESC 
    LIMIT 5");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Email</th><th>OTP</th><th>Expires At</th><th>Created At</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['otp_code']) . "</td>";
        echo "<td>" . $row['expires_at'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "<td style='color: " . ($row['status'] == 'Valid' ? 'green' : 'red') . "; font-weight: bold;'>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No OTPs found in database.</p>";
}

// Clean up expired OTPs
echo "<h3>Cleanup Expired OTPs:</h3>";
$delete_result = $conn->query("DELETE FROM password_reset_otps WHERE expires_at < NOW()");
$affected_rows = $conn->affected_rows;
echo "<p>Deleted $affected_rows expired OTP(s).</p>";

echo "<hr>";
echo "<p><a href='forgot_password.php'>← Back to Forgot Password</a></p>";
echo "<p><a href='test_email.php'>Test Email Configuration</a></p>";
?>
