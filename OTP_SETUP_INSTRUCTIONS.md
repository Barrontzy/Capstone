# OTP Password Reset Setup Instructions

## Overview
The BSU Inventory Management System now includes OTP (One-Time Password) verification for password reset functionality. When users forget their password, they will receive a 6-digit verification code via email.

## Setup Requirements

### 1. Email Configuration
Edit the file `includes/email_config.php` and update the following settings:

```php
// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'g.batstate-u.edu.ph'); // Your BSU Gmail address
define('SMTP_PASSWORD', 'your-app-password'); // Your Gmail App Password
define('SMTP_FROM_EMAIL', 'g.batstate-u.edu.ph');
define('SMTP_FROM_NAME', 'BSU Inventory Management System');
```

### 2. Gmail App Password Setup
Since you're using `g.batstate-u.edu.ph`, you'll need to:

1. **Enable 2-Factor Authentication** on your BSU Gmail account
2. **Generate an App Password**:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate a new app password for "Mail"
   - Use this 16-character password in the `SMTP_PASSWORD` setting

### 3. Database Table
The OTP system requires a database table. If not already created, run:

```sql
CREATE TABLE IF NOT EXISTS password_reset_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email)
);
```

## How It Works

### 1. Forgot Password Flow
1. User enters email on `forgot_password.php`
2. System generates 6-digit OTP
3. OTP is stored in database with 15-minute expiration
4. Email with OTP is sent via PHPMailer
5. User is redirected to `otp.php`

### 2. OTP Verification
1. User enters 6-digit code on `otp.php`
2. System validates OTP against database
3. If valid, user is redirected to `reset_password.php`
4. User sets new password
5. OTP is deleted from database

### 3. Security Features
- OTP expires in 15 minutes
- Only one OTP per email address
- Secure password hashing
- Session-based verification flow

## Files Modified/Created

### New Files:
- `otp.php` - OTP verification page
- `reset_password.php` - Password reset form
- `includes/email_config.php` - Email configuration
- `OTP_SETUP_INSTRUCTIONS.md` - This file

### Modified Files:
- `forgot_password.php` - Updated to send OTP instead of generic message
- `database.sql` - Added OTP table schema

## Testing the System

1. **Configure Email Settings**: Update `includes/email_config.php` with your BSU Gmail credentials
2. **Test Forgot Password**: 
   - Go to login page
   - Click "Forgot Password"
   - Enter a valid email address
   - Check email for OTP code
3. **Test OTP Verification**:
   - Enter the 6-digit code
   - Verify redirect to password reset page
4. **Test Password Reset**:
   - Enter new password
   - Confirm password update

## Troubleshooting

### Email Not Sending
- Verify Gmail credentials in `email_config.php`
- Check if 2FA is enabled and App Password is correct
- Ensure SMTP settings are correct for your email provider

### OTP Not Working
- Check database table exists
- Verify OTP hasn't expired (15 minutes)
- Check for PHP errors in logs

### Database Issues
- Ensure database connection is working
- Verify table structure matches schema
- Check for SQL errors in logs

## Security Notes

- OTPs are only valid for 15 minutes
- Each email can only have one active OTP
- Used OTPs are deleted after successful password reset
- Password hashing uses PHP's `password_hash()` function
- All user inputs are properly sanitized

## Support

For technical support or issues with the OTP system, check:
1. PHP error logs
2. Email delivery logs
3. Database connection status
4. SMTP server connectivity
