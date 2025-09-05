<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in
requireLogin();

$message = '';
$error = '';

// Handle department operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $building = trim($_POST['building']);
                $location = trim($_POST['location']);
                
                $stmt = $conn->prepare("INSERT INTO departments (name, building, location) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $building, $location);
                
                if ($stmt->execute()) {
                    $message = 'Department added successfully!';
                } else {
                    $error = 'Failed to add department.';
                }
                $stmt->close();
                break;
                
            case 'update':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $building = trim($_POST['building']);
                $location = trim($_POST['location']);
                
                $stmt = $conn->prepare("UPDATE departments SET name = ?, building = ?, location = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $building, $location, $id);
                
                if ($stmt->execute()) {
                    $message = 'Department updated successfully!';
                } else {
                    $error = 'Failed to update department.';
                }
                $stmt->close();
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Check if department has equipment
                $stmt = $conn->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM desktop WHERE department_office = (SELECT name FROM departments WHERE id = ?)) +
                        (SELECT COUNT(*) FROM laptops WHERE department = (SELECT name FROM departments WHERE id = ?)) +
                        (SELECT COUNT(*) FROM printers WHERE department = (SELECT name FROM departments WHERE id = ?)) +
                        (SELECT COUNT(*) FROM accesspoint WHERE department = (SELECT name FROM departments WHERE id = ?)) +
                        (SELECT COUNT(*) FROM switch WHERE department = (SELECT name FROM departments WHERE id = ?)) +
                        (SELECT COUNT(*) FROM telephone WHERE department = (SELECT name FROM departments WHERE id = ?))
                        AS count
                ");
                $stmt->bind_param("iiiiii", $id, $id, $id, $id, $id, $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $error = 'Cannot delete department. It has associated equipment.';
                } else {
                    $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $message = 'Department deleted successfully!';
                    } else {
                        $error = 'Failed to delete department.';
                    }
                }
                $stmt->close();
                break;
        }
    }
}

// ✅ Get departments with equipment count
$departments = $conn->query("
    SELECT d.id, d.name, d.building, d.location, d.created_at,
           (
                (SELECT COUNT(*) FROM desktop WHERE department_office = d.name) +
                (SELECT COUNT(*) FROM laptops WHERE department = d.name) +
                (SELECT COUNT(*) FROM printers WHERE department = d.name) +
                (SELECT COUNT(*) FROM accesspoint WHERE department = d.name) +
                (SELECT COUNT(*) FROM switch WHERE department = d.name) +
                (SELECT COUNT(*) FROM telephone WHERE department = d.name)
           ) AS equipment_count
    FROM departments d
    ORDER BY d.name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - BSU Inventory Management System</title>
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
                        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="equipment.php" class="nav-link"><i class="fas fa-laptop"></i> Equipment</a></li>
                        <li class="nav-item"><a href="departments.php" class="nav-link active"><i class="fas fa-building"></i> Departments</a></li>
                        <li class="nav-item"><a href="maintenance.php" class="nav-link"><i class="fas fa-tools"></i> Maintenance</a></li>
                        <li class="nav-item"><a href="tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Tasks</a></li>
                        <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
                        <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-building"></i> Department Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                        <i class="fas fa-plus"></i> Add Department
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

                <!-- Departments Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Department Name</th>
                                        <th>Building</th>
                                        <th>Location</th>
                                        <th>Equipment Count</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($dept['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($dept['building']); ?></td>
                                            <td><?php echo htmlspecialchars($dept['location']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $dept['equipment_count']; ?> equipment</span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($dept['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="editDepartment(<?php echo $dept['id']; ?>, '<?php echo addslashes($dept['name']); ?>', '<?php echo addslashes($dept['building']); ?>', '<?php echo addslashes($dept['location']); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment(<?php echo $dept['id']; ?>, '<?php echo addslashes($dept['name']); ?>')">
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

    <!-- Add Department Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Department Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Building</label>
                            <input type="text" class="form-control" name="building" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editDeptId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Department Name</label>
                            <input type="text" class="form-control" name="name" id="editDeptName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Building</label>
                            <input type="text" class="form-control" name="building" id="editDeptBuilding" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" id="editDeptLocation" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDepartment(id, name, building, location) {
            document.getElementById('editDeptId').value = id;
            document.getElementById('editDeptName').value = name;
            document.getElementById('editDeptBuilding').value = building;
            document.getElementById('editDeptLocation').value = location;
            new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
        }

        function deleteDepartment(id, name) {
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
    </script>
</body>
</html>
