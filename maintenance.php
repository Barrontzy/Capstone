<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in
requireLogin();

$message = '';
$error = '';

// Handle maintenance operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $equipment_id = $_POST['equipment_id'];
                $equipment_type = $_POST['equipment_type'];
                $technician_id = $_POST['technician_id'];
                $maintenance_type = $_POST['maintenance_type'];
                $description = trim($_POST['description']);
                $cost = $_POST['cost'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                $stmt = $conn->prepare("INSERT INTO maintenance_records 
                    (equipment_id, equipment_type, technician_id, maintenance_type, description, cost, start_date, end_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
                $stmt->bind_param("isissdss", $equipment_id, $equipment_type, $technician_id, $maintenance_type, $description, $cost, $start_date, $end_date);
                
                if ($stmt->execute()) {
                    $message = 'Maintenance record added successfully!';
                } else {
                    $error = 'Failed to add maintenance record.';
                }
                $stmt->close();
                break;
                
            case 'update':
                $id = $_POST['id'];
                $equipment_id = $_POST['equipment_id'];
                $equipment_type = $_POST['equipment_type'];
                $technician_id = $_POST['technician_id'];
                $maintenance_type = $_POST['maintenance_type'];
                $description = trim($_POST['description']);
                $cost = $_POST['cost'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE maintenance_records 
                    SET equipment_id = ?, equipment_type = ?, technician_id = ?, maintenance_type = ?, description = ?, cost = ?, start_date = ?, end_date = ?, status = ? 
                    WHERE id = ?");
                $stmt->bind_param("isissdsssi", $equipment_id, $equipment_type, $technician_id, $maintenance_type, $description, $cost, $start_date, $end_date, $status, $id);
                
                if ($stmt->execute()) {
                    $message = 'Maintenance record updated successfully!';
                } else {
                    $error = 'Failed to update maintenance record.';
                }
                $stmt->close();
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                $stmt = $conn->prepare("DELETE FROM maintenance_records WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = 'Maintenance record deleted successfully!';
                } else {
                    $error = 'Failed to delete maintenance record.';
                }
                $stmt->close();
                break;
        }
    }
}

// Get maintenance records
$maintenance_records = $conn->query("
    SELECT mr.*, u.full_name AS technician_name,
        CASE mr.equipment_type
            WHEN 'desktop' THEN (SELECT model FROM desktop WHERE id = mr.equipment_id)
            WHEN 'laptops' THEN (SELECT hardware_specifications FROM laptops WHERE id = mr.equipment_id)
            WHEN 'printers' THEN (SELECT hardware_specifications FROM printers WHERE id = mr.equipment_id)
            WHEN 'accesspoint' THEN (SELECT hardware_specifications FROM accesspoint WHERE id = mr.equipment_id)
            WHEN 'switch' THEN (SELECT hardware_specifications FROM switch WHERE id = mr.equipment_id)
            WHEN 'telephone' THEN (SELECT hardware_specifications FROM telephone WHERE id = mr.equipment_id)
        END AS equipment_name,
        CASE mr.equipment_type
            WHEN 'desktop' THEN (SELECT department_office FROM desktop WHERE id = mr.equipment_id)
            WHEN 'laptops' THEN (SELECT department FROM laptops WHERE id = mr.equipment_id)
            WHEN 'printers' THEN (SELECT department FROM printers WHERE id = mr.equipment_id)
            WHEN 'accesspoint' THEN (SELECT department FROM accesspoint WHERE id = mr.equipment_id)
            WHEN 'switch' THEN (SELECT department FROM switch WHERE id = mr.equipment_id)
            WHEN 'telephone' THEN (SELECT department FROM telephone WHERE id = mr.equipment_id)
        END AS department_name
    FROM maintenance_records mr
    LEFT JOIN users u ON mr.technician_id = u.id
    ORDER BY mr.created_at DESC
");

// Get equipment list for dropdown
$equipment_list = [];
$tables = [
    'desktop'     => 'model',
    'laptops'     => 'hardware_specifications',
    'printers'    => 'hardware_specifications',
    'accesspoint' => 'hardware_specifications',
    'switch'      => 'hardware_specifications',
    'telephone'   => 'hardware_specifications'
];

foreach ($tables as $table => $col) {
    $result = $conn->query("SELECT id, $col AS label FROM $table");
    while ($row = $result->fetch_assoc()) {
        $row['type'] = $table;
        $equipment_list[] = $row;
    }
}

// Get technicians
$technicians = $conn->query("SELECT id, full_name FROM users WHERE role = 'technician' ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - BSU Inventory Management System</title>
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
        .status-badge {
            font-size: 0.8em;
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
                        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="equipment.php" class="nav-link"><i class="fas fa-laptop"></i> Equipment</a></li>
                        <li class="nav-item"><a href="departments.php" class="nav-link"><i class="fas fa-building"></i> Departments</a></li>
                        <li class="nav-item"><a href="maintenance.php" class="nav-link active"><i class="fas fa-tools"></i> Maintenance</a></li>
                        <li class="nav-item"><a href="tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Tasks</a></li>
                        <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
                        <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tools"></i> Maintenance Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                        <i class="fas fa-plus"></i> Schedule Maintenance
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Maintenance Records Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Equipment</th>
                                        <th>Department</th>
                                        <th>Type</th>
                                        <th>Technician</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = $maintenance_records->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['equipment_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['department_name']); ?></td>
                                            <td><span class="badge bg-info"><?php echo ucfirst($record['maintenance_type']); ?></span></td>
                                            <td><?php echo htmlspecialchars($record['technician_name']); ?></td>
                                            <td><span class="badge bg-warning"><?php echo ucfirst($record['status']); ?></span></td>
                                            <td><?php echo $record['start_date']; ?></td>
                                            <td><?php echo $record['end_date']; ?></td>
                                            <td>₱<?php echo number_format($record['cost'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Maintenance Modal -->
    <div class="modal fade" id="addMaintenanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Equipment</label>
                                <select class="form-control" name="equipment_id" required>
                                    <option value="">Select Equipment</option>
                                    <?php foreach ($equipment_list as $equipment): ?>
                                        <option value="<?php echo $equipment['id']; ?>" data-type="<?php echo $equipment['type']; ?>">
                                            [<?php echo ucfirst($equipment['type']); ?>] <?php echo htmlspecialchars($equipment['label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="equipment_type" id="equipment_type">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Technician</label>
                                <select class="form-control" name="technician_id" required>
                                    <option value="">Select Technician</option>
                                    <?php while ($tech = $technicians->fetch_assoc()): ?>
                                        <option value="<?php echo $tech['id']; ?>"><?php echo $tech['full_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maintenance Type</label>
                            <select class="form-control" name="maintenance_type" required>
                                <option value="">Select Type</option>
                                <option value="preventive">Preventive</option>
                                <option value="corrective">Corrective</option>
                                <option value="upgrade">Upgrade</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estimated Cost</label>
                            <input type="number" step="0.01" class="form-control" name="cost">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.querySelector('select[name="equipment_id"]').addEventListener('change', function() {
        let type = this.options[this.selectedIndex].getAttribute('data-type');
        document.getElementById('equipment_type').value = type;
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
