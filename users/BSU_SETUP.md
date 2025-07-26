# BSU Folder Setup Guide

This guide will help you connect the BSU folder to access the users system.

## Option 1: Simple Redirect (Recommended)

### Step 1: Create BSU folder structure
```
C:\xampp\htdocs\BSU\
└── users\
    ├── index.php (redirect file)
    ├── login.php (redirect file)
    ├── register.php (redirect file)
    └── ... (other redirect files)
```

### Step 2: Create redirect files in BSU folder

Create these files in `C:\xampp\htdocs\BSU\users\`:

**index.php:**
```php
<?php
// Redirect to the actual users folder
header('Location: /users/');
exit();
?>
```

**login.php:**
```php
<?php
// Include the login file from users folder
include $_SERVER['DOCUMENT_ROOT'] . '/users/login.php';
?>
```

**register.php:**
```php
<?php
// Include the register file from users folder
include $_SERVER['DOCUMENT_ROOT'] . '/users/register.php';
?>
```

**tasks.php:**
```php
<?php
// Include the tasks file from users folder
include $_SERVER['DOCUMENT_ROOT'] . '/users/tasks.php';
?>
```

**qr.php:**
```php
<?php
// Include the QR file from users folder
include $_SERVER['DOCUMENT_ROOT'] . '/users/qr.php';
?>
```

**history.php:**
```php
<?php
// Include the history file from users folder
include $_SERVER['DOCUMENT_ROOT'] . '/users/history.php';
?>
```

**profile.php:**
```php
<?php
// Include the profile file from users folder
include $_SERVER['DOCUMENT_ROOT'] . '/users/profile.php';
?>
```

**logout.php:**
```php
<?php
// Include the logout file from users folder
include $_SERVER['DOCUMENT_ROOT'] . '/users/logout.php';
?>
```

## Option 2: Symbolic Links (Advanced)

If your system supports symbolic links:

1. Open Command Prompt as Administrator
2. Navigate to your BSU folder:
   ```cmd
   cd C:\xampp\htdocs\BSU
   ```
3. Create symbolic links:
   ```cmd
   mklink /D users C:\xampp\htdocs\users
   ```

## Option 3: Copy Files (Simple but requires maintenance)

1. Copy all files from `C:\xampp\htdocs\users\` to `C:\xampp\htdocs\BSU\users\`
2. Update all internal links to use `/BSU/users/` instead of `/users/`

## Testing

After setup, you should be able to access:

- `http://localhost/BSU/users/` - Main dashboard
- `http://localhost/BSU/users/login.php` - Login page
- `http://localhost/BSU/users/register.php` - Registration page
- `http://localhost/BSU/users/tasks.php` - Task management
- `http://localhost/BSU/users/qr.php` - QR scanner
- `http://localhost/BSU/users/history.php` - History
- `http://localhost/BSU/users/profile.php` - Profile

## Quick Setup Script

Create a file called `setup_bsu.php` in your users folder and run it:

```php
<?php
// Quick BSU setup script
$bsu_path = $_SERVER['DOCUMENT_ROOT'] . '/BSU/users/';

// Create BSU directory if it doesn't exist
if (!is_dir($bsu_path)) {
    mkdir($bsu_path, 0755, true);
}

// List of files to create redirects for
$files = ['index.php', 'login.php', 'register.php', 'tasks.php', 'qr.php', 'history.php', 'profile.php', 'logout.php'];

foreach ($files as $file) {
    $redirect_content = "<?php\n// Redirect to users folder\ninclude \$_SERVER['DOCUMENT_ROOT'] . '/users/$file';\n?>";
    file_put_contents($bsu_path . $file, $redirect_content);
}

echo "BSU setup complete! You can now access the system at http://localhost/BSU/users/";
?>
```

Run this script by visiting `http://localhost/users/setup_bsu.php` in your browser.

## Notes

- The redirect method (Option 1) is the easiest and most maintainable
- All assets (CSS, JS, images) will still be loaded from the original users folder
- Database configuration remains the same
- Session management works across both paths 