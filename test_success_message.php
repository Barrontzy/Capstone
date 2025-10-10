<?php
require_once 'includes/session.php';

// Simulate the success message flow
$_SESSION['reset_email'] = 'test@example.com';
$_SESSION['email_sent_message'] = 'Verification code sent successfully to your email.';

echo "<h2>Success Message Test</h2>";
echo "<p>Simulating the flow from forgot password to OTP verification...</p>";

echo "<h3>1. Forgot Password Page Success:</h3>";
echo "<div style='background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 10px 12px; border-radius: 10px; margin: 10px 0;'>";
echo "<i class='fas fa-check-circle'></i> If this email is registered, a verification code has been sent.";
echo "</div>";

echo "<h3>2. OTP Verification Page Success:</h3>";
echo "<div style='background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 10px 12px; border-radius: 10px; margin: 10px 0;'>";
echo "<i class='fas fa-check-circle'></i> Verification code sent successfully to your email.";
echo "</div>";

echo "<h3>3. Email Info Display:</h3>";
echo "<div style='background: #f8f9fa; border-radius: 8px; padding: 12px; margin: 10px 0; text-align: center; font-size: 14px; color: #666;'>";
echo "<i class='fas fa-envelope'></i> Verification code sent to:<br>";
echo "<strong>test@example.com</strong>";
echo "</div>";

echo "<hr>";
echo "<p><a href='forgot_password.php'>← Test Forgot Password Flow</a></p>";
echo "<p><a href='otp.php'>← Test OTP Verification Page</a></p>";

// Clean up test data
unset($_SESSION['reset_email']);
unset($_SESSION['email_sent_message']);
?>
