# 🔧 Email Setup Guide - Fix "Failed to send verification email" Error

## 🚨 Problem
The error "Failed to send verification email. Please try again." occurs because the email configuration is not properly set up.

## 📋 Quick Fix Steps

### Step 1: Test Current Configuration
1. Open your browser and go to: `http://localhost/Caplit/test_email.php`
2. This will show you exactly what's wrong with the email configuration

### Step 2: Configure BSU Gmail Credentials

#### Option A: Using App Password (Recommended)
1. **Enable 2-Factor Authentication** on your BSU Gmail account:
   - Go to [Google Account Security](https://myaccount.google.com/security)
   - Turn on 2-Step Verification

2. **Generate App Password**:
   - In Google Account → Security → 2-Step Verification
   - Scroll down to "App passwords"
   - Click "Generate" → Select "Mail" → Generate
   - Copy the 16-character password (e.g., `abcd efgh ijkl mnop`)

3. **Update Configuration**:
   - Open `includes/email_config.php`
   - Replace `'your-app-password'` with your actual App Password:
   ```php
   define('SMTP_PASSWORD', 'abcdefghijklmnop'); // Your 16-character App Password
   ```

#### Option B: Using Regular Password (Less Secure)
If you can't use App Password:
1. Open `includes/email_config.php`
2. Replace the password:
   ```php
   define('SMTP_PASSWORD', 'your-actual-gmail-password');
   ```
3. **Enable "Less secure app access"** in Gmail settings (not recommended)

### Step 3: Test the Configuration
1. Go to `http://localhost/Caplit/test_email.php`
2. If successful, you'll see: ✅ SUCCESS: Email sent successfully!
3. Check your email inbox for the test message

### Step 4: Test Forgot Password
1. Go to `http://localhost/Caplit/forgot_password.php`
2. Enter a valid email address
3. Click "Send Verification Code"
4. Check your email for the OTP code

## 🔍 Troubleshooting

### Common Issues:

#### 1. "Authentication failed"
- **Cause**: Wrong password or username
- **Fix**: Double-check your Gmail credentials in `email_config.php`

#### 2. "Connection refused"
- **Cause**: Firewall or network blocking port 587
- **Fix**: Check firewall settings, try different network

#### 3. "SMTP connect() failed"
- **Cause**: Gmail security settings
- **Fix**: Enable 2FA and use App Password

#### 4. "Username and Password not accepted"
- **Cause**: Using regular password instead of App Password
- **Fix**: Generate and use App Password

## 📧 Alternative Email Providers

If Gmail doesn't work, you can use other providers:

### Outlook/Hotmail
```php
define('SMTP_HOST', 'smtp-mail.outlook.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@outlook.com');
define('SMTP_PASSWORD', 'your-password');
```

### Yahoo Mail
```php
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@yahoo.com');
define('SMTP_PASSWORD', 'your-app-password');
```

## ✅ Final Configuration Example

Your `includes/email_config.php` should look like this:

```php
<?php
// Email Configuration for PHPMailer
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'g.batstate-u.edu.ph');
define('SMTP_PASSWORD', 'abcdefghijklmnop'); // Your actual App Password
define('SMTP_FROM_EMAIL', 'g.batstate-u.edu.ph');
define('SMTP_FROM_NAME', 'BSU Inventory Management System');
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_DEBUG', false); // Set to false after testing
?>
```

## 🎯 Next Steps
1. Configure your email credentials
2. Test with `test_email.php`
3. Try the forgot password feature
4. Set `SMTP_DEBUG` to `false` once working

## 🆘 Still Having Issues?
1. Check PHP error logs in XAMPP
2. Verify Gmail account access
3. Try with a different email provider
4. Contact system administrator
