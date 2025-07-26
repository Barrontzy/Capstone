<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSU Inventory Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            min-height: 100vh;
            overflow: hidden;
        }

        .loading-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInUp 1s ease-out;
        }

        .logo {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
            animation: pulse 2s infinite;
        }

        .logo i {
            font-size: 50px;
            color: white;
        }

        .system-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .system-subtitle {
            color: #cccccc;
            font-size: 1.2rem;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .loading-bar {
            width: 300px;
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 20px;
            position: relative;
        }

        .loading-progress {
            height: 100%;
            background: linear-gradient(90deg, #dc3545, #ff6b6b);
            border-radius: 3px;
            width: 0%;
            animation: loading 3s ease-in-out;
            position: relative;
        }

        .loading-progress::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 1.5s infinite;
        }

        .loading-text {
            color: #cccccc;
            font-size: 1rem;
            margin-bottom: 40px;
            animation: fadeIn 2s ease-out;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            animation: fadeInUp 1.5s ease-out;
        }

        .btn-landing {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary-landing {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-primary-landing:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            color: white;
        }

        .btn-secondary-landing {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }

        .btn-secondary-landing:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
            color: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 60px;
            max-width: 800px;
            animation: fadeInUp 2s ease-out;
        }

        .feature-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.1);
            border-color: rgba(220, 53, 69, 0.3);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #dc3545;
            margin-bottom: 15px;
        }

        .feature-title {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .feature-description {
            color: #cccccc;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(220, 53, 69, 0.3);
            border-radius: 50%;
            animation: float 6s infinite linear;
        }

        @keyframes loading {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .system-title {
                font-size: 2rem;
            }
            
            .system-subtitle {
                font-size: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                margin-top: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Particles Background -->
    <div class="particles">
        <?php for($i = 0; $i < 20; $i++): ?>
            <div class="particle" style="
                left: <?php echo rand(0, 100); ?>%;
                animation-delay: <?php echo rand(0, 6); ?>s;
                animation-duration: <?php echo rand(4, 8); ?>s;
            "></div>
        <?php endfor; ?>
    </div>

    <div class="loading-container">
        <!-- Logo and Title Section -->
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-university"></i>
            </div>
            <h1 class="system-title">BSU Inventory System</h1>
            <p class="system-subtitle">Batangas State University</p>
        </div>

        <!-- Loading Animation -->
        <div class="loading-bar">
            <div class="loading-progress"></div>
        </div>
        <p class="loading-text">Initializing system...</p>

        <!-- Action Buttons -->
        <div class="action-buttons">
                                                                       <a href="index.php" class="btn-landing btn-primary-landing">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </a>
                                                                       <a href="register.php" class="btn-landing btn-secondary-landing">
                <i class="fas fa-user-plus"></i>
                Register
            </a>
        </div>

        <!-- Features Grid -->
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-laptop"></i>
                </div>
                <h3 class="feature-title">Equipment Management</h3>
                <p class="feature-description">Track and manage all equipment with detailed information and QR codes</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3 class="feature-title">Maintenance Tracking</h3>
                <p class="feature-description">Schedule and monitor maintenance activities with cost tracking</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3 class="feature-title">Analytics & Reports</h3>
                <p class="feature-description">Generate comprehensive reports and view real-time analytics</p>
            </div>
        </div>
    </div>

    <script>
        // Simulate loading process
        setTimeout(() => {
            document.querySelector('.loading-text').textContent = 'System ready...';
        }, 1500);

        setTimeout(() => {
            document.querySelector('.loading-text').textContent = 'Welcome to BSU Inventory Management System';
        }, 3000);

        // Add click effects to buttons
        document.querySelectorAll('.btn-landing').forEach(button => {
            button.addEventListener('click', function(e) {
                // Add ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add hover effects to feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html> 