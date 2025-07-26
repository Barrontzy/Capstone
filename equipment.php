<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in
requireLogin();

$message = '';
$error = '';

// Handle equipment operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $category_id = $_POST['category_id'];
                $department_id = $_POST['department_id'];
                $serial_number = trim($_POST['serial_number']);
                $model = trim($_POST['model']);
                $brand = trim($_POST['brand']);
                $acquisition_date = $_POST['acquisition_date'];
                $acquisition_cost = $_POST['acquisition_cost'];
                $location = trim($_POST['location']);
                $notes = trim($_POST['notes']);
                
                // Generate QR code
                $qr_data = "Equipment: $name\nSerial: $serial_number\nLocation: $location";
                $qr_code = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);
                
                $stmt = $conn->prepare("INSERT INTO equipment (name, category_id, department_id, serial_number, model, brand, acquisition_date, acquisition_cost, location, notes, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siissssdsss", $name, $category_id, $department_id, $serial_number, $model, $brand, $acquisition_date, $acquisition_cost, $location, $notes, $qr_code);
                
                if ($stmt->execute()) {
                    $equipment_id = $conn->insert_id;
                    $equipment_data = [
                        'name' => $name,
                        'category_id' => $category_id,
                        'department_id' => $department_id,
                        'serial_number' => $serial_number,
                        'model' => $model,
                        'brand' => $brand,
                        'acquisition_date' => $acquisition_date,
                        'acquisition_cost' => $acquisition_cost,
                        'location' => $location,
                        'notes' => $notes
                    ];
                    // $logger->logEquipmentCreated($equipment_id, $equipment_data); // Removed activity logging
                    $message = 'Equipment added successfully!';
                } else {
                    $error = 'Failed to add equipment.';
                }
                $stmt->close();
                break;
                
            case 'update':
                $id = $_POST['id'];
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
                $notes = trim($_POST['notes']);
                
                $stmt = $conn->prepare("UPDATE equipment SET name=?, category_id=?, department_id=?, serial_number=?, model=?, brand=?, acquisition_date=?, acquisition_cost=?, status=?, location=?, notes=? WHERE id=?");
                $stmt->bind_param("siissssdsssi", $name, $category_id, $department_id, $serial_number, $model, $brand, $acquisition_date, $acquisition_cost, $status, $location, $notes, $id);
                
                if ($stmt->execute()) {
                    $message = 'Equipment updated successfully!';
                } else {
                    $error = 'Failed to update equipment.';
                }
                $stmt->close();
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    // $equipment_data = [ // Removed activity logging
                    //     'name' => $equipment['name'],
                    //     'serial_number' => $equipment['serial_number'],
                    //     'model' => $equipment['model'],
                    //     'brand' => $equipment['brand']
                    // ];
                    // $logger->logEquipmentDeleted($id, $equipment_data); // Removed activity logging
                    $message = 'Equipment deleted successfully!';
                } else {
                    $error = 'Failed to delete equipment.';
                }
                $stmt->close();
                break;
        }
    }
}

// Get equipment list with filters
$where_conditions = [];
$params = [];
$types = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_conditions[] = "(e.name LIKE ? OR e.serial_number LIKE ? OR e.model LIKE ?)";
    $search = '%' . $_GET['search'] . '%';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sss';
}

if (isset($_GET['department']) && !empty($_GET['department'])) {
    $where_conditions[] = "e.department_id = ?";
    $params[] = $_GET['department'];
    $types .= 'i';
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_conditions[] = "e.category_id = ?";
    $params[] = $_GET['category'];
    $types .= 'i';
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = "e.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
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
$equipment_result = $stmt->get_result();

// Get categories and departments for filters
$categories = $conn->query("SELECT * FROM equipment_categories ORDER BY name");
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Management - BSU Inventory System</title>
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
                    <h2><i class="fas fa-laptop"></i> Equipment Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                        <i class="fas fa-plus"></i> Add Equipment
                    </button>
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

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" value="<?php echo $_GET['search'] ?? ''; ?>" placeholder="Name, Serial, Model...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Department</label>
                                <select class="form-control" name="department">
                                    <option value="">All Departments</option>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['department']) && $_GET['department'] == $dept['id']) ? 'selected' : ''; ?>>
                                            <?php echo $dept['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
                                <select class="form-control" name="category">
                                    <option value="">All Categories</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo $cat['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="maintenance" <?php echo (isset($_GET['status']) && $_GET['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="disposed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'disposed') ? 'selected' : ''; ?>>Disposed</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="equipment.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Equipment Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Department</th>
                                        <th>Serial Number</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($equipment = $equipment_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($equipment['name']); ?></strong>
                                                <?php if ($equipment['model']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($equipment['model']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($equipment['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($equipment['department_name']); ?></td>
                                            <td><?php echo htmlspecialchars($equipment['serial_number']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'active' => 'success',
                                                    'maintenance' => 'warning',
                                                    'disposed' => 'danger',
                                                    'lost' => 'secondary'
                                                ];
                                                $status_icon = [
                                                    'active' => 'check-circle',
                                                    'maintenance' => 'tools',
                                                    'disposed' => 'trash',
                                                    'lost' => 'question-circle'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $status_class[$equipment['status']]; ?>">
                                                    <i class="fas fa-<?php echo $status_icon[$equipment['status']]; ?>"></i>
                                                    <?php echo ucfirst($equipment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($equipment['location']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="view_equipment.php?id=<?php echo $equipment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_equipment.php?id=<?php echo $equipment['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-info" onclick="showQRCode('<?php echo $equipment['qr_code']; ?>', '<?php echo $equipment['name']; ?>')">
                                                        <i class="fas fa-qrcode"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteEquipment(<?php echo $equipment['id']; ?>, '<?php echo $equipment['name']; ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
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

    <!-- Add Equipment Modal -->
    <div class="modal fade" id="addEquipmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Equipment Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-control" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories->data_seek(0);
                                    while ($cat = $categories->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-control" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while ($dept = $departments->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Serial Number</label>
                                <input type="text" class="form-control" name="serial_number" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" name="model">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" class="form-control" name="brand">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Acquisition Date</label>
                                <input type="date" class="form-control" name="acquisition_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Acquisition Cost</label>
                                <input type="number" step="0.01" class="form-control" name="acquisition_cost">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Equipment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div class="modal fade" id="qrCodeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-qrcode"></i> QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 id="qrEquipmentName"></h6>
                    <img id="qrCodeImage" src="" alt="QR Code" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="downloadQRCode()">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showQRCode(qrCode, equipmentName) {
            document.getElementById('qrEquipmentName').textContent = equipmentName;
            document.getElementById('qrCodeImage').src = qrCode;
            new bootstrap.Modal(document.getElementById('qrCodeModal')).show();
        }

        function downloadQRCode() {
            const img = document.getElementById('qrCodeImage');
            const link = document.createElement('a');
            link.download = 'qr-code.png';
            link.href = img.src;
            link.click();
        }

        function deleteEquipment(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editEquipment(id) {
            // Implement edit functionality
            alert('Edit functionality will be implemented');
        }

        function viewEquipment(id) {
            // Implement view functionality
            alert('View functionality will be implemented');
        }
    </script>
</body>
</html> 