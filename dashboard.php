<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in
requireLogin();

// Correct mapping: table → display column + department column
$equipmentTables = [
    'desktop'     => ['name' => 'model', 'dept' => 'department_office'],
    'laptops'     => ['name' => 'hardware_specifications', 'dept' => 'department'],
    'printers'    => ['name' => 'hardware_specifications', 'dept' => 'department'],
    'accesspoint' => ['name' => 'hardware_specifications', 'dept' => 'department'],
    'switch'      => ['name' => 'hardware_specifications', 'dept' => 'department'],
    'telephone'   => ['name' => 'hardware_specifications', 'dept' => 'department']
];

// Initialize stats
$stats = [
    'total_equipment' => 0,
    'working_units' => 0,
    'not_working_units' => 0,
    'total_departments' => 0
];

$category_data = [];

// Loop through equipment tables
foreach ($equipmentTables as $table => $cols) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        // Total
        $res = $conn->query("SELECT COUNT(*) as total FROM $table");
        $count_total = $res->fetch_assoc()['total'];
        $stats['total_equipment'] += $count_total;

        // Working
        $res = $conn->query("SELECT COUNT(*) as total FROM $table WHERE remarks LIKE '%Working%'");
        $count_working = $res->fetch_assoc()['total'];
        $stats['working_units'] += $count_working;

        // Not Working
        $res = $conn->query("SELECT COUNT(*) as total FROM $table WHERE remarks NOT LIKE '%Working%'");
        $count_not_working = $res->fetch_assoc()['total'];
        $stats['not_working_units'] += $count_not_working;

        // Save category for pie chart
        $category_data[] = ['name' => ucfirst($table), 'count' => $count_total];
    }
}

// Departments
$res = $conn->query("SELECT COUNT(*) as total FROM departments");
$stats['total_departments'] = $res->fetch_assoc()['total'];

// Monthly acquisitions
$acquisition_data = [];
foreach ($equipmentTables as $table => $cols) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        $res = $conn->query("
            SELECT DATE_FORMAT(date_acquired, '%Y-%m') as month, COUNT(*) as count
            FROM $table 
            WHERE date_acquired IS NOT NULL
            GROUP BY DATE_FORMAT(date_acquired, '%Y-%m')
        ");
        while ($row = $res->fetch_assoc()) {
            $month = $row['month'];
            if (!isset($acquisition_data[$month])) {
                $acquisition_data[$month] = 0;
            }
            $acquisition_data[$month] += $row['count'];
        }
    }
}
ksort($acquisition_data);

// Recent equipment
$recent_equipment = [];
foreach ($equipmentTables as $table => $cols) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        $res = $conn->query("
            SELECT {$cols['name']} AS equipment_name, {$cols['dept']} AS department, date_acquired, remarks 
            FROM $table 
            ORDER BY date_acquired DESC 
            LIMIT 5
        ");
        while ($row = $res->fetch_assoc()) {
            $recent_equipment[] = array_merge($row, ['table' => ucfirst($table)]);
        }
    }
}
usort($recent_equipment, fn($a, $b) => strtotime($b['date_acquired']) - strtotime($a['date_acquired']));
$recent_equipment = array_slice($recent_equipment, 0, 5);

// Maintenance alerts
$maintenance_alerts = [];
foreach ($equipmentTables as $table => $cols) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        $res = $conn->query("
            SELECT {$cols['name']} AS equipment_name, {$cols['dept']} AS department, remarks, date_acquired 
            FROM $table 
            WHERE remarks NOT LIKE '%Working%' 
            ORDER BY date_acquired DESC 
            LIMIT 5
        ");
        while ($row = $res->fetch_assoc()) {
            $maintenance_alerts[] = array_merge($row, ['table' => ucfirst($table)]);
        }
    }
}
usort($maintenance_alerts, fn($a, $b) => strtotime($b['date_acquired']) - strtotime($a['date_acquired']));
$maintenance_alerts = array_slice($maintenance_alerts, 0, 5);
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
        }
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            text-align: center;
        }
        .chart-container { 
            background: white; 
            border-radius: 15px; 
            padding: 20px; 
            margin-bottom: 20px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        #categoryChart { max-height: 250px; }
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
                        <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="equipment.php" class="nav-link"><i class="fas fa-laptop"></i> Equipment</a></li>
                        <li class="nav-item"><a href="departments.php" class="nav-link"><i class="fas fa-building"></i> Departments</a></li>
                        <li class="nav-item"><a href="maintenance.php" class="nav-link"><i class="fas fa-tools"></i> Maintenance</a></li>
                        <li class="nav-item"><a href="tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Tasks</a></li>
                        <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
                        <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                <div class="row mb-4">
                    <div class="col-md-3"><div class="stats-card"><h3><?php echo $stats['total_equipment']; ?></h3><p>Total Equipment</p></div></div>
                    <div class="col-md-3"><div class="stats-card"><h3 class="text-success"><?php echo $stats['working_units']; ?></h3><p>Working</p></div></div>
                    <div class="col-md-3"><div class="stats-card"><h3 class="text-danger"><?php echo $stats['not_working_units']; ?></h3><p>Not Working</p></div></div>
                    <div class="col-md-3"><div class="stats-card"><h3 class="text-info"><?php echo $stats['total_departments']; ?></h3><p>Departments</p></div></div>
                </div>

                <div class="row">
                    <div class="col-md-6"><div class="chart-container"><h5>Equipment by Category</h5><canvas id="categoryChart"></canvas></div></div>
                    <div class="col-md-6"><div class="chart-container"><h5>Monthly Acquisitions</h5><canvas id="acquisitionChart"></canvas></div></div>
                </div>

                <div class="row">
                    <!-- Recent Equipment -->
                    <div class="col-md-6"><div class="chart-container">
                        <h5>Recent Equipment Added</h5>
                        <table class="table table-striped">
                            <thead><tr><th>Equipment</th><th>Department</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_equipment as $eq): ?>
                                <tr>
                                    <td><?php echo $eq['equipment_name']; ?></td>
                                    <td><?php echo $eq['department']; ?></td>
                                    <td><?php echo date("M d, Y", strtotime($eq['date_acquired'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div></div>

                    <!-- Maintenance Alerts -->
                    <div class="col-md-6"><div class="chart-container">
                        <h5>Maintenance Alerts</h5>
                        <table class="table table-striped">
                            <thead><tr><th>Equipment</th><th>Department</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($maintenance_alerts as $eq): ?>
                                <tr>
                                    <td><?php echo $eq['equipment_name']; ?></td>
                                    <td><?php echo $eq['department']; ?></td>
                                    <td><span class="badge bg-danger"><?php echo $eq['remarks']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Category Pie Chart
        new Chart(document.getElementById('categoryChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($category_data, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($category_data, 'count')); ?>,
                    backgroundColor: ['#dc3545','#0d6efd','#198754','#ffc107','#fd7e14','#6f42c1']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Acquisition Bar Chart
        new Chart(document.getElementById('acquisitionChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($acquisition_data)); ?>,
                datasets: [{
                    label: 'Units Acquired',
                    data: <?php echo json_encode(array_values($acquisition_data)); ?>,
                    backgroundColor: '#0d6efd'
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
