<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Technician Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #343a40;
            --gray-color: #6c757d;
            --blue-color: #007bff;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Header Navigation */
        .header-nav {
            background: linear-gradient(90deg, #dc3545 0%, #343a40 100%);
            padding: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 100%;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-brand {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
        }
        
        .header-brand i {
            margin-right: 8px;
        }
        
        .header-user {
            color: white;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .header-user i {
            margin-right: 5px;
        }
        
        .main-content {
            padding: 20px;
            margin-bottom: 80px; /* Space for footer nav */
        }
        
        @media (min-width: 768px) {
            .main-content {
                margin-bottom: 20px;
            }
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
        
        /* Footer Navigation */
        .footer-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
            border-top: 2px solid #e9ecef;
            z-index: 1000;
            padding: 12px 0 8px 0;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .nav-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            max-width: 100%;
            margin: 0 auto;
            padding: 0 10px;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #6c757d;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 64px;
            position: relative;
            overflow: hidden;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(220, 53, 69, 0.05) 100%);
            border-radius: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .nav-item i {
            font-size: 1.3rem;
            margin-bottom: 4px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 2;
        }
        
        .nav-item span {
            font-size: 0.7rem;
            line-height: 1.2;
            font-weight: 600;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .nav-item:hover {
            color: #dc3545;
            transform: translateY(-2px);
        }
        
        .nav-item:hover::before {
            opacity: 1;
        }
        
        .nav-item:hover i {
            transform: scale(1.1);
            color: #dc3545;
        }
        
        .nav-item.active {
            color: #dc3545;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.15) 0%, rgba(220, 53, 69, 0.08) 100%);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
        }
        
        .nav-item.active::before {
            opacity: 1;
        }
        
        .nav-item.active i {
            transform: scale(1.15);
            color: #dc3545;
        }
        
        .nav-item.active span {
            color: #dc3545;
            font-weight: 700;
        }
        
        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: #dc3545;
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(220, 53, 69, 0.5);
        }
        
        .nav-item:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <!-- Header Navigation -->
    <nav class="header-nav">
        <div class="header-container">
            <a href="index.php" class="header-brand">
                <i class="fas fa-tools"></i>
                Technician Portal
            </a>
            <div class="header-user">
                <i class="fas fa-user"></i>
                <?php echo $_SESSION['user_name']; ?>
            </div>
        </div>
    </nav>

    <div class="main-content"> 