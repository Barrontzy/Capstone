<?php
// Enhanced session management for BSU Inventory System
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    
    // Start session with custom name
    session_name('BSU_INVENTORY_SESSION');
    session_start();
}

// Set session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds

// Check if session has expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session has expired, destroy it
    session_unset();
    session_destroy();
    session_start();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to check if user is technician
function isTechnician() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'technician';
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: landing.php');
        exit();
    }
}

// Function to require admin access
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Function to get current user info
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }
    return null;
}

// Function to reset failed login attempts
function resetLoginAttempts($email) {
    global $conn;
    if (!isset($conn)) {
        return false;
    }
    $stmt = $conn->prepare("UPDATE users SET login_attempts = 0, lockout_until = NULL WHERE email = ?");
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
    return true;
}

// Function to increment failed login attempts
function incrementLoginAttempts($email) {
    global $conn;
    if (!isset($conn)) {
        return null;
    }
    
    // Get current attempts
    $stmt = $conn->prepare("SELECT login_attempts FROM users WHERE email = ?");
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $attempts = $user['login_attempts'] + 1;
        $stmt->close();
        
        // Calculate lockout duration based on attempts
        $lockout_duration = 0;
        if ($attempts == 3) {
            $lockout_duration = 30; // 30 seconds
        } elseif ($attempts == 5) {
            $lockout_duration = 60; // 1 minute
        } elseif ($attempts == 7) {
            $lockout_duration = 300; // 5 minutes
        } elseif ($attempts == 10) {
            $lockout_duration = 3600; // 1 hour
        } elseif ($attempts > 10) {
            $lockout_duration = 5400; // 1 hour 30 minutes
        }
        
        // Update attempts and lockout time if needed
        if ($lockout_duration > 0) {
            $lockout_until = date('Y-m-d H:i:s', time() + $lockout_duration);
            $stmt = $conn->prepare("UPDATE users SET login_attempts = ?, lockout_until = ? WHERE email = ?");
            $stmt->bind_param("iss", $attempts, $lockout_until, $email);
        } else {
            $stmt = $conn->prepare("UPDATE users SET login_attempts = ? WHERE email = ?");
            $stmt->bind_param("is", $attempts, $email);
        }
        $stmt->execute();
        $stmt->close();
        
        return [
            'attempts' => $attempts,
            'locked' => $lockout_duration > 0,
            'lockout_until' => isset($lockout_until) ? $lockout_until : null,
            'lockout_duration' => $lockout_duration,
            'remaining_seconds' => $lockout_duration
        ];
    }
    
    return null;
}

// Function to check if account is locked
function isAccountLocked($email) {
    global $conn;
    if (!isset($conn)) {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT lockout_until, login_attempts FROM users WHERE email = ?");
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user['lockout_until'] !== null) {
            $lockout_time = strtotime($user['lockout_until']);
            $current_time = time();
            
            // If lockout time has passed, clear the lockout but keep attempts count
            if ($current_time >= $lockout_time) {
                $stmt = $conn->prepare("UPDATE users SET lockout_until = NULL WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->close();
                return false;
            }
            
            return [
                'locked' => true,
                'lockout_until' => $user['lockout_until'],
                'remaining_seconds' => $lockout_time - $current_time,
                'attempts' => $user['login_attempts']
            ];
        }
    }
    
    return false;
}
?> 