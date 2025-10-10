<?php
require_once 'vendor/autoload.php';
require_once 'includes/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Simple email test
echo "<h2>Email Configuration Test</h2>";
echo "<p>Testing email settings...</p>";

$mail = new PHPMailer(true);

try {
    echo "<p>✓ PHPMailer loaded successfully</p>";
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = SMTP_AUTH;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = SMTP_PORT;
    $mail->SMTPDebug  = SMTP_DEBUG ? 2 : 0; // Enable verbose debug output
    
    echo "<p>✓ SMTP settings configured</p>";
    echo "<p>Host: " . SMTP_HOST . "</p>";
    echo "<p>Port: " . SMTP_PORT . "</p>";
    echo "<p>Username: " . SMTP_USERNAME . "</p>";
    echo "<p>Password: " . (SMTP_PASSWORD === 'your-app-password' ? '<span style="color:red;">❌ NOT CONFIGURED</span>' : '<span style="color:green;">✓ Configured</span>') . "</p>";
    
    if (SMTP_PASSWORD === 'your-app-password') {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: #d32f2f; margin: 0 0 10px 0;'>❌ Email Configuration Required</h3>";
        echo "<p><strong>You need to configure your BSU Gmail credentials:</strong></p>";
        echo "<ol>";
        echo "<li>Go to <code>includes/email_config.php</code></li>";
        echo "<li>Replace <code>'your-app-password'</code> with your actual Gmail App Password</li>";
        echo "<li>Make sure 2-Factor Authentication is enabled on your BSU Gmail</li>";
        echo "<li>Generate an App Password from Google Account settings</li>";
        echo "</ol>";
        echo "</div>";
        exit;
    }
    
    // Recipients
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME); // Send test to yourself
    
    echo "<p>✓ Recipients configured</p>";
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'BSU Inventory System - Email Test';
    $mail->Body    = '<h3>Email Configuration Test</h3><p>If you receive this email, your SMTP configuration is working correctly!</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>';
    
    echo "<p>✓ Email content prepared</p>";
    echo "<p>Attempting to send email...</p>";
    
    $mail->send();
    echo '<p style="color: green; font-weight: bold;">✅ SUCCESS: Email sent successfully!</p>';
    echo '<p>Check your inbox at: ' . SMTP_USERNAME . '</p>';
    
} catch (Exception $e) {
    echo '<p style="color: red; font-weight: bold;">❌ ERROR: Email could not be sent.</p>';
    echo '<p><strong>Error details:</strong></p>';
    echo '<pre style="background: #ffebee; padding: 10px; border-radius: 5px; color: #d32f2f;">';
    echo "Mailer Error: {$mail->ErrorInfo}";
    echo '</pre>';
    
    echo '<div style="background: #fff3e0; border: 1px solid #ff9800; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3 style="color: #f57c00; margin: 0 0 10px 0;">🔧 Troubleshooting Steps:</h3>';
    echo '<ol>';
    echo '<li><strong>Check Gmail App Password:</strong> Make sure you\'re using the correct 16-character App Password (not your regular password)</li>';
    echo '<li><strong>Enable 2FA:</strong> Two-Factor Authentication must be enabled on your Gmail account</li>';
    echo '<li><strong>Allow Less Secure Apps:</strong> If using regular password, enable "Less secure app access" (not recommended)</li>';
    echo '<li><strong>Check Firewall:</strong> Make sure your firewall allows outbound connections on port 587</li>';
    echo '<li><strong>Gmail Settings:</strong> Check if Gmail has any security restrictions</li>';
    echo '</ol>';
    echo '</div>';
}

echo '<hr>';
echo '<p><a href="forgot_password.php">← Back to Forgot Password</a></p>';
echo '<p><a href="includes/email_config.php">Edit Email Configuration</a></p>';
?>
