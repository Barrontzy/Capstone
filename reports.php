<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in
requireLogin();

$message = '';
$error = '';

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $equipment_category = $_POST['equipment_category'] ?? '';
    
    // Generate report based on type
    switch ($report_type) {
        case 'inventory':
            generateInventoryReport($conn, $date_from, $date_to, $department_id, $equipment_category);
            break;
        case 'financial':
            generateFinancialReport($conn, $date_from, $date_to, $department_id);
            break;
        case 'department':
            generateDepartmentReport($conn, $date_from, $date_to);
            break;
        case 'maintenance':
            generateMaintenanceReport($conn, $date_from, $date_to, $department_id);
            break;
        case 'incomplete':
            generateIncompleteReport($conn, $department_id);
            break;
        case 'acquisition':
            generateAcquisitionReport($conn, $date_from, $date_to, $department_id);
            break;
    }
}

function generateInventoryReport($conn, $date_from, $date_to, $department_id, $equipment_category) {
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($date_from) {
        $where_conditions[] = "e.created_at >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    if ($date_to) {
        $where_conditions[] = "e.created_at <= ?";
        $params[] = $date_to . ' 23:59:59';
        $types .= 's';
    }
    if ($department_id) {
        $where_conditions[] = "e.department_id = ?";
        $params[] = $department_id;
        $types .= 'i';
    }
    if ($equipment_category) {
        $where_conditions[] = "e.category_id = ?";
        $params[] = $equipment_category;
        $types .= 'i';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $query = "
        SELECT e.*, ec.name as category_name, d.name as department_name, u.full_name as assigned_user
        FROM equipment e
        LEFT JOIN equipment_categories ec ON e.category_id = ec.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN users u ON e.assigned_to = u.id
        $where_clause
        ORDER BY e.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Generate CSV
    $filename = "inventory_report_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Category', 'Department', 'Serial Number', 'Model', 'Brand', 'Status', 'Location', 'Acquisition Date', 'Cost', 'Assigned To', 'Created Date']);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['category_name'],
            $row['department_name'],
            $row['serial_number'],
            $row['model'],
            $row['brand'],
            $row['status'],
            $row['location'],
            $row['acquisition_date'],
            $row['acquisition_cost'],
            $row['assigned_user'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit();
}

function generateFinancialReport($conn, $date_from, $date_to, $department_id) {
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($date_from) {
        $where_conditions[] = "e.acquisition_date >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    if ($date_to) {
        $where_conditions[] = "e.acquisition_date <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    if ($department_id) {
        $where_conditions[] = "e.department_id = ?";
        $params[] = $department_id;
        $types .= 'i';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $query = "
        SELECT d.name as department_name, ec.name as category_name,
               COUNT(e.id) as equipment_count,
               SUM(e.acquisition_cost) as total_cost,
               AVG(e.acquisition_cost) as avg_cost
        FROM equipment e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN equipment_categories ec ON e.category_id = ec.id
        $where_clause
        GROUP BY d.id, ec.id
        ORDER BY d.name, ec.name
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $filename = "financial_report_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Department', 'Category', 'Equipment Count', 'Total Cost', 'Average Cost']);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['department_name'],
            $row['category_name'],
            $row['equipment_count'],
            $row['total_cost'],
            $row['avg_cost']
        ]);
    }
    fclose($output);
    exit();
}

function generateDepartmentReport($conn, $date_from, $date_to) {
    $query = "
        SELECT d.name as department_name,
               COUNT(e.id) as total_equipment,
               SUM(CASE WHEN e.status = 'active' THEN 1 ELSE 0 END) as active_equipment,
               SUM(CASE WHEN e.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_equipment,
               SUM(CASE WHEN e.status = 'disposed' THEN 1 ELSE 0 END) as disposed_equipment,
               SUM(e.acquisition_cost) as total_value
        FROM departments d
        LEFT JOIN equipment e ON d.id = e.department_id
        GROUP BY d.id
        ORDER BY d.name
    ";
    
    $result = $conn->query($query);
    
    $filename = "department_analysis_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Department', 'Total Equipment', 'Active', 'Under Maintenance', 'Disposed', 'Total Value']);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['department_name'],
            $row['total_equipment'],
            $row['active_equipment'],
            $row['maintenance_equipment'],
            $row['disposed_equipment'],
            $row['total_value']
        ]);
    }
    fclose($output);
    exit();
}

function generateMaintenanceReport($conn, $date_from, $date_to, $department_id) {
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($date_from) {
        $where_conditions[] = "mr.start_date >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    if ($date_to) {
        $where_conditions[] = "mr.end_date <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    if ($department_id) {
        $where_conditions[] = "e.department_id = ?";
        $params[] = $department_id;
        $types .= 'i';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $query = "
        SELECT mr.*, e.name as equipment_name, e.serial_number,
               d.name as department_name, u.full_name as technician_name
        FROM maintenance_records mr
        LEFT JOIN equipment e ON mr.equipment_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN users u ON mr.technician_id = u.id
        $where_clause
        ORDER BY mr.start_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $filename = "maintenance_report_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Equipment', 'Department', 'Type', 'Technician', 'Status', 'Start Date', 'End Date', 'Cost', 'Description']);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['equipment_name'] . ' (' . $row['serial_number'] . ')',
            $row['department_name'],
            $row['maintenance_type'],
            $row['technician_name'],
            $row['status'],
            $row['start_date'],
            $row['end_date'],
            $row['cost'],
            $row['description']
        ]);
    }
    fclose($output);
    exit();
}

function generateIncompleteReport($conn, $department_id) {
    $where_clause = '';
    $params = [];
    $types = '';
    
    if ($department_id) {
        $where_clause = 'WHERE e.department_id = ?';
        $params[] = $department_id;
        $types .= 'i';
    }
    
    $query = "
        SELECT e.*, ec.name as category_name, d.name as department_name
        FROM equipment e
        LEFT JOIN equipment_categories ec ON e.category_id = ec.id
        LEFT JOIN departments d ON e.department_id = d.id
        $where_clause
        WHERE e.serial_number IS NULL 
           OR e.model IS NULL 
           OR e.brand IS NULL 
           OR e.acquisition_date IS NULL 
           OR e.acquisition_cost IS NULL
        ORDER BY e.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $filename = "incomplete_items_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Category', 'Department', 'Serial Number', 'Model', 'Brand', 'Acquisition Date', 'Cost', 'Missing Fields']);
    
    while ($row = $result->fetch_assoc()) {
        $missing_fields = [];
        if (!$row['serial_number']) $missing_fields[] = 'Serial Number';
        if (!$row['model']) $missing_fields[] = 'Model';
        if (!$row['brand']) $missing_fields[] = 'Brand';
        if (!$row['acquisition_date']) $missing_fields[] = 'Acquisition Date';
        if (!$row['acquisition_cost']) $missing_fields[] = 'Cost';
        
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['category_name'],
            $row['department_name'],
            $row['serial_number'] ?: 'N/A',
            $row['model'] ?: 'N/A',
            $row['brand'] ?: 'N/A',
            $row['acquisition_date'] ?: 'N/A',
            $row['acquisition_cost'] ?: 'N/A',
            implode(', ', $missing_fields)
        ]);
    }
    fclose($output);
    exit();
}

function generateAcquisitionReport($conn, $date_from, $date_to, $department_id) {
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($date_from) {
        $where_conditions[] = "e.acquisition_date >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    if ($date_to) {
        $where_conditions[] = "e.acquisition_date <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    if ($department_id) {
        $where_conditions[] = "e.department_id = ?";
        $params[] = $department_id;
        $types .= 'i';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $query = "
        SELECT e.*, ec.name as category_name, d.name as department_name
        FROM equipment e
        LEFT JOIN equipment_categories ec ON e.category_id = ec.id
        LEFT JOIN departments d ON e.department_id = d.id
        $where_clause
        ORDER BY e.acquisition_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $filename = "acquisition_timeline_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Equipment', 'Category', 'Department', 'Serial Number', 'Cost']);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['acquisition_date'],
            $row['name'],
            $row['category_name'],
            $row['department_name'],
            $row['serial_number'],
            $row['acquisition_cost']
        ]);
    }
    fclose($output);
    exit();
}

// Get departments and categories for filters
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
$categories = $conn->query("SELECT * FROM equipment_categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - BSU Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
        
        .report-card {
            transition: transform 0.3s ease;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
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
                            <a href="dashboard.php" class="nav-link">
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
                            <a href="reports.php" class="nav-link active">
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
                    <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
                </div>

                <!-- Report Types -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-list fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Complete Inventory Report</h5>
                                <p class="card-text">Generate a comprehensive list of all equipment with detailed information.</p>
                                <button class="btn btn-primary" onclick="showReportModal('inventory')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                                <h5 class="card-title">Financial Summary Report</h5>
                                <p class="card-text">Financial analysis including costs, budgets, and expenditure summaries.</p>
                                <button class="btn btn-primary" onclick="showReportModal('financial')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-3x text-info mb-3"></i>
                                <h5 class="card-title">Department Analysis Report</h5>
                                <p class="card-text">Equipment distribution and analysis by department.</p>
                                <button class="btn btn-primary" onclick="showReportModal('department')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-tools fa-3x text-warning mb-3"></i>
                                <h5 class="card-title">Maintenance & Status Report</h5>
                                <p class="card-text">Maintenance records, schedules, and equipment status reports.</p>
                                <button class="btn btn-primary" onclick="showReportModal('maintenance')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                                <h5 class="card-title">Incomplete Items Report</h5>
                                <p class="card-text">Equipment with missing or incomplete information.</p>
                                <button class="btn btn-primary" onclick="showReportModal('incomplete')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Acquisition Timeline Report</h5>
                                <p class="card-text">Equipment acquisition timeline and purchase history.</p>
                                <button class="btn btn-primary" onclick="showReportModal('acquisition')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Generation Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalTitle"><i class="fas fa-chart-bar"></i> Generate Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="generate_report" value="1">
                    <input type="hidden" name="report_type" id="reportType">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-control" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while ($dept = $departments->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Equipment Category</label>
                                <select class="form-control" name="equipment_category">
                                    <option value="">All Categories</option>
                                    <?php 
                                    $categories->data_seek(0);
                                    while ($cat = $categories->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download"></i> Generate & Download
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showReportModal(reportType) {
            document.getElementById('reportType').value = reportType;
            
            const titles = {
                'inventory': 'Complete Inventory Report',
                'financial': 'Financial Summary Report',
                'department': 'Department Analysis Report',
                'maintenance': 'Maintenance & Status Report',
                'incomplete': 'Incomplete Items Report',
                'acquisition': 'Acquisition Timeline Report'
            };
            
            document.getElementById('reportModalTitle').innerHTML = '<i class="fas fa-chart-bar"></i> ' + titles[reportType];
            new bootstrap.Modal(document.getElementById('reportModal')).show();
        }
    </script>
</body>
</html> 