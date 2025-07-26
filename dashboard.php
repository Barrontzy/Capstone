<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in
requireLogin();

// Get dashboard statistics
$stats = [];

// Total equipment
$result = $conn->query("SELECT COUNT(*) as total FROM equipment");
$stats['total_equipment'] = $result->fetch_assoc()['total'];

// Active equipment
$result = $conn->query("SELECT COUNT(*) as total FROM equipment WHERE status = 'active'");
$stats['active_equipment'] = $result->fetch_assoc()['total'];

// Equipment under maintenance
$result = $conn->query("SELECT COUNT(*) as total FROM equipment WHERE status = 'maintenance'");
$stats['maintenance_equipment'] = $result->fetch_assoc()['total'];

// Total departments
$result = $conn->query("SELECT COUNT(*) as total FROM departments");
$stats['total_departments'] = $result->fetch_assoc()['total'];

// Get monthly acquisition data for chart
$acquisition_data = [];
$result = $conn->query("
    SELECT DATE_FORMAT(acquisition_date, '%Y-%m') as month, 
           COUNT(*) as count, 
           SUM(acquisition_cost) as total_cost
    FROM equipment 
    WHERE acquisition_date IS NOT NULL 
    GROUP BY DATE_FORMAT(acquisition_date, '%Y-%m')
    ORDER BY month DESC 
    LIMIT 12
");
while ($row = $result->fetch_assoc()) {
    $acquisition_data[] = $row;
}

// Get equipment by category
$category_data = [];
$result = $conn->query("
    SELECT ec.name, COUNT(e.id) as count
    FROM equipment_categories ec
    LEFT JOIN equipment e ON ec.id = e.category_id
    GROUP BY ec.id, ec.name
");
while ($row = $result->fetch_assoc()) {
    $category_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BSU Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #343a40;
            --accent-color: #6c757d;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            min-height: calc(100vh - 76px);
        }
        
        .sidebar .nav-link {
            color: var(--secondary-color);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .user-info {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .logout-btn {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            color: white;
            padding: 8px 15px;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #c82333;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-university"></i> BSU Inventory System
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="d-flex flex-column flex-shrink-0 p-3">
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link active">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="equipment.php" class="nav-link">
                                <i class="fas fa-laptop"></i> Equipment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="departments.php" class="nav-link">
                                <i class="fas fa-building"></i> Departments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="maintenance.php" class="nav-link">
                                <i class="fas fa-tools"></i> Maintenance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="tasks.php" class="nav-link">
                                <i class="fas fa-tasks"></i> Tasks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports.php" class="nav-link">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="users.php" class="nav-link">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                       
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                    <span class="text-muted">Welcome back, <?php echo $_SESSION['user_name']; ?>!</span>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="text-primary"><?php echo $stats['total_equipment']; ?></h3>
                                    <p class="text-muted mb-0">Total Equipment</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-laptop fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="text-success"><?php echo $stats['active_equipment']; ?></h3>
                                    <p class="text-muted mb-0">Active Equipment</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="text-warning"><?php echo $stats['maintenance_equipment']; ?></h3>
                                    <p class="text-muted mb-0">Under Maintenance</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-tools fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="text-info"><?php echo $stats['total_departments']; ?></h3>
                                    <p class="text-muted mb-0">Departments</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-building fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <h5><i class="fas fa-chart-line"></i> Monthly Acquisition Trends</h5>
                            <canvas id="acquisitionChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <h5><i class="fas fa-chart-pie"></i> Equipment by Category</h5>
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5><i class="fas fa-clock"></i> Recent Equipment Added</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Equipment</th>
                                            <th>Department</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result = $conn->query("
                                            SELECT e.name, d.name as dept_name, e.created_at
                                            FROM equipment e
                                            LEFT JOIN departments d ON e.department_id = d.id
                                            ORDER BY e.created_at DESC
                                            LIMIT 5
                                        ");
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>{$row['name']}</td>";
                                            echo "<td>{$row['dept_name']}</td>";
                                            echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5><i class="fas fa-exclamation-triangle"></i> Maintenance Alerts</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Equipment</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result = $conn->query("
                                            SELECT e.name, e.status
                                            FROM equipment e
                                            WHERE e.status = 'maintenance'
                                            ORDER BY e.updated_at DESC
                                            LIMIT 5
                                        ");
                                        while ($row = $result->fetch_assoc()) {
                                            $status_class = $row['status'] == 'maintenance' ? 'warning' : 'danger';
                                            echo "<tr>";
                                            echo "<td>{$row['name']}</td>";
                                            echo "<td><span class='badge bg-{$status_class}'>{$row['status']}</span></td>";
                                            echo "<td><a href='maintenance.php' class='btn btn-sm btn-outline-primary'>View</a></td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Acquisition Chart
        const acquisitionCtx = document.getElementById('acquisitionChart').getContext('2d');
        new Chart(acquisitionCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($acquisition_data), 'month')); ?>,
                datasets: [{
                    label: 'Equipment Acquired',
                    data: <?php echo json_encode(array_column(array_reverse($acquisition_data), 'count')); ?>,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($category_data, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($category_data, 'count')); ?>,
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#ffc107',
                        '#198754',
                        '#0dcaf0'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html> 