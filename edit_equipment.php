<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in
requireLogin();

$message = '';
$error = '';

// Get equipment ID from URL
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$equipment_id) {
    header('Location: equipment.php');
    exit();
}

// Get equipment details
$stmt = $conn->prepare("
    SELECT e.*, 
           ec.name as category_name, 
           d.name as department_name, 
           u.full_name as assigned_user_name
    FROM equipment e
    LEFT JOIN equipment_categories ec ON e.category_id = ec.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN users u ON e.assigned_to = u.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$equipment = $stmt->get_result()->fetch_assoc();

if (!$equipment) {
    header('Location: equipment.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $department_id = $_POST['department_id'];
    $serial_number = trim($_POST['serial_number']);
    $model = trim($_POST['model']);
    $brand = trim($_POST['brand']);
    $acquisition_date = $_POST['acquisition_date'];
    $acquisition_cost = $_POST['acquisition_cost'];
    $status = $_POST['status'];
    $location = trim($_POST['location']);
    $assigned_to = $_POST['assigned_to'] ?: null;
    $notes = trim($_POST['notes']);
    
    // Validation
    if (empty($name) || empty($category_id) || empty($department_id)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if serial number already exists for another equipment
        $stmt = $conn->prepare("SELECT id FROM equipment WHERE serial_number = ? AND id != ?");
        $stmt->bind_param("si", $serial_number, $equipment_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Serial number already exists';
        } else {
            // Update equipment
            $stmt = $conn->prepare("
                UPDATE equipment 
                SET name = ?, category_id = ?, department_id = ?, serial_number = ?, 
                    model = ?, brand = ?, acquisition_date = ?, acquisition_cost = ?, 
                    status = ?, location = ?, assigned_to = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->bind_param("siissssdsssi", $name, $category_id, $department_id, $serial_number, 
                             $model, $brand, $acquisition_date, $acquisition_cost, $status, 
                             $location, $assigned_to, $notes, $equipment_id);
            
            if ($stmt->execute()) {
                $message = 'Equipment updated successfully!';
                
                // Refresh equipment data
                $stmt = $conn->prepare("
                    SELECT e.*, 
                           ec.name as category_name, 
                           d.name as department_name, 
                           u.full_name as assigned_user_name
                    FROM equipment e
                    LEFT JOIN equipment_categories ec ON e.category_id = ec.id
                    LEFT JOIN departments d ON e.department_id = d.id
                    LEFT JOIN users u ON e.assigned_to = u.id
                    WHERE e.id = ?
                ");
                $stmt->bind_param("i", $equipment_id);
                $stmt->execute();
                $equipment = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Failed to update equipment';
            }
        }
        $stmt->close();
    }
}

// Get categories and departments for dropdowns
$categories = $conn->query("SELECT * FROM equipment_categories ORDER BY name");
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
$users = $conn->query("SELECT * FROM users ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment - BSU Inventory Management System</title>
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
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
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
                            <a href="equipment.php" class="nav-link active">
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
                    <h2><i class="fas fa-edit"></i> Edit Equipment</h2>
                    <div>
                        <a href="view_equipment.php?id=<?php echo $equipment_id; ?>" class="btn btn-outline-primary me-2">
                            <i class="fas fa-eye"></i> View Equipment
                        </a>
                        <a href="equipment.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Equipment
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Equipment Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($equipment['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category *</label>
                                    <select class="form-control" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories->data_seek(0);
                                        while ($cat = $categories->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo ($equipment['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                <?php echo $cat['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department *</label>
                                    <select class="form-control" name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php 
                                        $departments->data_seek(0);
                                        while ($dept = $departments->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $dept['id']; ?>" <?php echo ($equipment['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                                <?php echo $dept['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" name="serial_number" value="<?php echo htmlspecialchars($equipment['serial_number']); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Model</label>
                                    <input type="text" class="form-control" name="model" value="<?php echo htmlspecialchars($equipment['model']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" name="brand" value="<?php echo htmlspecialchars($equipment['brand']); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Acquisition Date</label>
                                    <input type="date" class="form-control" name="acquisition_date" value="<?php echo $equipment['acquisition_date']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Acquisition Cost</label>
                                    <input type="number" step="0.01" class="form-control" name="acquisition_cost" value="<?php echo $equipment['acquisition_cost']; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-control" name="status">
                                        <option value="active" <?php echo ($equipment['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="maintenance" <?php echo ($equipment['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="disposed" <?php echo ($equipment['status'] == 'disposed') ? 'selected' : ''; ?>>Disposed</option>
                                        <option value="lost" <?php echo ($equipment['status'] == 'lost') ? 'selected' : ''; ?>>Lost</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($equipment['location']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Assigned To</label>
                                    <select class="form-control" name="assigned_to">
                                        <option value="">Not Assigned</option>
                                        <?php 
                                        $users->data_seek(0);
                                        while ($user = $users->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo ($equipment['assigned_to'] == $user['id']) ? 'selected' : ''; ?>>
                                                <?php echo $user['full_name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="4"><?php echo htmlspecialchars($equipment['notes']); ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="view_equipment.php?id=<?php echo $equipment_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Equipment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 