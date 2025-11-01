<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
	if($_SESSION['user_role'] == 'admin'){
		header('Location: dashboard.php');
	} elseif($_SESSION['user_role'] == 'technician'){
		header('Location: technician/indet.php');
	} elseif($_SESSION['user_role'] == 'department_admin'){ 
		header('Location: depdashboard.php');
	}
	exit();
}

$error = '';
$lockout_info = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (!preg_match('/@g\.batstate-u\.edu\.ph$/', $email)) {
        $error = 'Email must be from @g.batstate-u.edu.ph ';
    } else {
        // Require captcha to be solved before attempting login
        if (empty($_POST['g-recaptcha-response'])) {
            $error = 'Please answer the captcha';
        } else {
            // Check if account is locked
            $lockout_info = isAccountLocked($email);
            
            if ($lockout_info && isset($lockout_info['locked']) && $lockout_info['locked'] && isset($lockout_info['remaining_seconds'])) {
                $remaining = $lockout_info['remaining_seconds'];
                $minutes = floor($remaining / 60);
                $seconds = $remaining % 60;
                
                if ($minutes > 0) {
                    $error = "Account temporarily locked. Please try again in {$minutes} minute(s) and {$seconds} second(s).";
                } else {
                    $error = "Account temporarily locked. Please try again in {$seconds} second(s).";
                }
            } else {
                $stmt = $conn->prepare("SELECT id, full_name, email, role, password FROM users WHERE email = ?");
                if ($stmt === false) {
                    $error = 'Database error: ' . $conn->error;
                } else {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
            
                    if ($result->num_rows == 1) {
                        $user = $result->fetch_assoc();
                        if (password_verify($password, $user['password'])) {
                            // Successful login - reset failed attempts
                            resetLoginAttempts($email);
                            
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['full_name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_role'] = $user['role'];
                            include 'logger.php';
                            
                            if($user['role'] == 'admin'){
                                logAdminAction($user['id'], $user['full_name'], "Login", "Admin logged in");
                                header('Location: dashboard.php');
                            } elseif($user['role'] == 'technician'){
                                header('Location: technician/indet.php');
                            } elseif($user['role'] == 'department_admin'){ 
                                header('Location: depdashboard.php');
                            }
                            exit();
                        } else {
                            // Failed login - increment attempts
                            $attempt_result = incrementLoginAttempts($email);
                            if ($attempt_result !== null) {
                                $attempts = $attempt_result['attempts'];
                                
                                if ($attempt_result['locked']) {
                                    $lockout_info = $attempt_result;
                                    $remaining = $attempt_result['lockout_duration'];
                                    $minutes = floor($remaining / 60);
                                    $seconds = $remaining % 60;
                                    
                                    if ($minutes > 0) {
                                        $error = "Incorrect password. Too many failed attempts. Account locked for {$minutes} minute(s) and {$seconds} second(s).";
                                    } else {
                                        $error = "Incorrect password. Too many failed attempts. Account locked for {$seconds} second(s).";
                                    }
                                } else {
                                    $error = "Invalid password. Failed attempts: {$attempts}";
                                }
                            } else {
                                $error = "Invalid password.";
                            }
                        }
                    } else {
                        $error = 'User not found';
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Management System - Login</title>
    <link rel="icon" href="assets/logo/bsutneu.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('BSU.jpg') center/cover no-repeat fixed;
        }

        .auth-modal {
            width: 90%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
            overflow: hidden;
        }

        .auth-header {
            padding: 22px 26px 0 26px;
            text-align: center;
        }

        .auth-title {
            margin: 0 0 16px 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .auth-body {
            padding: 0 28px 26px 28px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .input-group {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #f3f4f6;
            overflow: hidden;
        }

        .input-group .input-icon { padding: 12px 14px; color: #6b7280; }
        .input-group input {
            border: none;
            outline: none;
            background: transparent;
            padding: 12px 14px;
            font-size: 14px;
            width: 100%;
        }
        .input-group .toggle-visibility { padding: 10px 12px; color: #6b7280; cursor: pointer; user-select: none; }
        .input-group .toggle-visibility:hover { color: #dc3545; }
        .input-group + .form-group { margin-top: 14px; }

        .btn-login {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #dc3545;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-login:hover { background: #c82333; }
        .btn-login:disabled { background: #9ca3af; cursor: not-allowed; opacity: 0.6; }

        .recaptcha-container { margin: 16px 0 18px 0; display: flex; justify-content: center; }
        .alert { background: #fde8e8; color: #b91c1c; border: 1px solid #fecaca; padding: 10px 12px; border-radius: 10px; margin-bottom: 14px; font-size: 14px; }
        .lockout-message { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 14px; }
        .lockout-timer { font-weight: 700; font-size: 16px; color: #dc2626; }
        .forgot-password { text-align: center; margin-top: 12px; }
        .forgot-password a { color: #374151; text-decoration: underline; font-size: 14px; }
        .forgot-password a:hover { color: #dc3545; }
    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const emailInput = document.getElementById('email');
            const loginForm = document.querySelector('form');
            const loginBtn = document.querySelector('.btn-login');
            
            // Check for lockout on page load
            <?php if ($lockout_info && isset($lockout_info['locked']) && $lockout_info['locked'] && isset($lockout_info['remaining_seconds'])): ?>
            let remainingSeconds = <?php echo $lockout_info['remaining_seconds']; ?>;
            startLockoutTimer(remainingSeconds);
            <?php endif; ?>
            
            // Password toggle functionality
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function(){
                    const isText = passwordInput.getAttribute('type') === 'text';
                    passwordInput.setAttribute('type', isText ? 'password' : 'text');
                    this.innerHTML = isText ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                    this.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
                });
            }
            
            // Email validation
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    validateEmail(this);
                });
                
                emailInput.addEventListener('input', function() {
                    // Clear previous error styling
                    this.style.borderColor = '';
                    const errorMsg = document.querySelector('.email-error');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                });
            }
            
            // Form submission validation
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const email = emailInput.value.trim();
                    const password = passwordInput.value;
                    
                    if (!email || !password) {
                        e.preventDefault();
                        showError('Please fill in all fields');
                        return;
                    }
                    
                    if (!validateEmail(emailInput)) {
                        e.preventDefault();
                        return;
                    }
                });
            }
            
            function startLockoutTimer(seconds) {
                // Disable form inputs
                if (emailInput) emailInput.disabled = true;
                if (passwordInput) passwordInput.disabled = true;
                if (loginBtn) loginBtn.disabled = true;
                
                const lockoutDiv = document.querySelector('.lockout-message');
                const timerSpan = lockoutDiv ? lockoutDiv.querySelector('.lockout-timer') : null;
                
                const interval = setInterval(function() {
                    seconds--;
                    
                    if (seconds <= 0) {
                        clearInterval(interval);
                        
                        // Re-enable form inputs
                        if (emailInput) emailInput.disabled = false;
                        if (passwordInput) passwordInput.disabled = false;
                        if (loginBtn) loginBtn.disabled = false;
                        
                        // Remove lockout message
                        if (lockoutDiv) {
                            lockoutDiv.remove();
                        }
                    } else {
                        // Update timer display
                        if (timerSpan) {
                            const minutes = Math.floor(seconds / 60);
                            const secs = seconds % 60;
                            if (minutes > 0) {
                                timerSpan.textContent = minutes + ' minute(s) and ' + secs + ' second(s)';
                            } else {
                                timerSpan.textContent = secs + ' second(s)';
                            }
                        }
                    }
                }, 1000);
            }
            
            function validateEmail(input) {
                const email = input.value.trim();
                const emailRegex = /^[^\s@]+@g\.batstate-u\.edu\.ph$/;
                
                if (email && !emailRegex.test(email)) {
                    input.style.borderColor = '#dc3545';
                    showError('Email must be from @g.batstate-u.edu.ph', input);
                    return false;
                } else {
                    input.style.borderColor = '';
                    const errorMsg = document.querySelector('.email-error');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                    return true;
                }
            }
            
            function showError(message, targetElement = null) {
                // Remove existing error messages
                const existingError = document.querySelector('.alert');
                if (existingError) {
                    existingError.remove();
                }
                
                // Create new error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
                
                // Insert error message
                if (targetElement) {
                    targetElement.parentNode.insertBefore(errorDiv, targetElement.nextSibling);
                } else {
                    const form = document.querySelector('form');
                    form.insertBefore(errorDiv, form.firstChild);
                }
            }
        });
    </script>
    
</head>
<body>
    <div class="auth-modal" role="dialog" aria-modal="true">
        <div class="auth-header">
            <h2 class="auth-title">LOGIN</h2>
        </div>
        <div class="auth-body">
            <?php if ($lockout_info && isset($lockout_info['locked']) && $lockout_info['locked'] && isset($lockout_info['remaining_seconds'])): ?>
                <div class="lockout-message" role="alert">
                    <i class="fas fa-lock"></i> Account temporarily locked due to too many failed login attempts.
                    <div style="margin-top: 8px;">
                        Please try again in <span class="lockout-timer"><?php 
                            $remaining = $lockout_info['remaining_seconds'];
                            $minutes = floor($remaining / 60);
                            $seconds = $remaining % 60;
                            if ($minutes > 0) {
                                echo $minutes . ' minute(s) and ' . $seconds . ' second(s)';
                            } else {
                                echo $seconds . ' second(s)';
                            }
                        ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error && (!$lockout_info || !isset($lockout_info['locked']) || !$lockout_info['locked'])): ?>
                <div class="alert" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address:</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" placeholder="username@g.batstate-u.edu.ph" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="password" required>
                        <span class="toggle-visibility" id="togglePassword" aria-label="Show password"><i class="fas fa-eye"></i></span>
                    </div>
                </div>

                <div class="recaptcha-container">
                    <div class="g-recaptcha" data-sitekey="6LeGTvwrAAAAAHP4_YhvsnBqH6iRyliAh0Ed_U8E"></div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-arrow-right"></i>
                    Login
                </button>
            </form>

            <div class="forgot-password">
                <a href="forgot_password.php">Forgot password?</a>
            </div>
        </div>
    </div>
</body>
</html> 






