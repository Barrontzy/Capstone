<?php
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - BSU User System' : 'BSU User System'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .user-header {
            background: #dc3545;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .user-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .user-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .user-nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .user-nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .user-logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .user-logout-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
        }
        .user-welcome {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .user-nav {
                flex-direction: column;
                gap: 15px;
            }
            .user-nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="user-header">
        <nav class="user-nav">
            <a href="index.php" class="user-logo">
                <i class="fas fa-university"></i> BSU User System
            </a>
            
            <div class="user-nav-links">
                <?php if ($isLoggedIn): ?>
                    <span class="user-welcome">
                        <i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="index.php" class="user-nav-link">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a href="profile.php" class="user-nav-link">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>
                    <a href="logout.php" class="user-logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="user-nav-link">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="user-nav-link">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
                <a href="../landing.php" class="user-nav-link">
                    <i class="fas fa-arrow-left"></i> Main System
                </a>
            </div>
        </nav>
    </header> 