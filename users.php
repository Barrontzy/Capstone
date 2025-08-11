<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in and is admin
requireAdmin();

$message = '';
$error = '';

// Handle user operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                $phone_number = trim($_POST['phone_number']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (empty($full_name) || empty($email) || empty($phone_number) || empty($password)) {
                    $error = 'Please fill in all required fields';
                } elseif ($password !== $confirm_password) {
                    $error = 'Passwords do not match';
                } elseif (strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters long';
                } else {
                    // Check if email already exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $error = 'Email address already exists';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (full_name, email, role, phone_number, password) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssss", $full_name, $email, $role, $phone_number, $hashed_password);
                        
                        if ($stmt->execute()) {
                            $message = 'User added successfully!';
                        } else {
                            $error = 'Failed to add user.';
                        }
                    }
                    $stmt->close();
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                $phone_number = trim($_POST['phone_number']);
                
                // Check if email exists for another user
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Email address already exists';
                } else {
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, phone_number = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $full_name, $email, $role, $phone_number, $id);
                    
                    if ($stmt->execute()) {
                        $message = 'User updated successfully!';
                    } else {
                        $error = 'Failed to update user.';
                    }
                }
                $stmt->close();
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Prevent deleting own account
                if ($id == $_SESSION['user_id']) {
                    $error = 'You cannot delete your own account.';
                } else {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $message = 'User deleted successfully!';
                    } else {
                        $error = 'Failed to delete user.';
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// Get users list with login records
// $users = $conn->query("
//     SELECT u.*, 
//            COUNT(DISTINCT e.id) as equipment_count,
//            COUNT(DISTINCT t.id) as task_count,
//            COUNT(DISTINCT mr.id) as maintenance_count,
//            COUNT(DISTINCT ul.id) as login_count,
//            MAX(ul.login_time) as last_login
//     FROM users u
//     LEFT JOIN equipment e ON u.id = e.assigned_to
//     LEFT JOIN tasks t ON u.id = t.assigned_to
//     LEFT JOIN maintenance_records mr ON u.id = mr.technician_id
//     LEFT JOIN user_logins ul ON u.id = ul.user_id
//     GROUP BY u.id
//     ORDER BY u.created_at DESC
// ");
// ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - BSU Inventory Management System</title>
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
        
        .user-card {
            transition: transform 0.3s ease;
        }
        
        .user-card:hover {
            transform: translateY(-2px);
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
                            <a href="reports.php" class="nav-link">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="users.php" class="nav-link active">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="alert alert-info mb-4">
                    
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                    <div>
                        <a href="users/login.php" class="btn btn-outline-info me-2" target="_blank">
                            <i class="fas fa-sign-in-alt"></i> User Login System
                        </a>
                        <a href="users/register.php" class="btn btn-outline-success me-2" target="_blank">
                            <i class="fas fa-user-plus"></i> User Registration System
                        </a>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
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

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Phone</th>
                                        <th>Activity</th>
                                        <th>Login Info</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- <?php while ($user = $users->fetch_assoc()): ?> -->
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                            <span class="badge bg-info ms-2">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php
                                                $role_class = $user['role'] == 'admin' ? 'danger' : 'info';
                                                ?>
                                                <span class="badge bg-<?php echo $role_class; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    Equipment: <?php echo $user['equipment_count']; ?> |
                                                    Tasks: <?php echo $user['task_count']; ?> |
                                                    Maintenance: <?php echo $user['maintenance_count']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    Logins: <?php echo $user['login_count']; ?><br>
                                                    <?php if ($user['last_login']): ?>
                                                        Last: <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                                                    <?php else: ?>
                                                        Never logged in
                                                    <?php endif; ?>
                                                    <?php 
                                                    // Check if user registered through users folder
                                                    $reg_check = $conn->prepare("SELECT COUNT(*) as reg_count FROM user_logins WHERE user_id = ? AND source = 'users_folder_registration'");
                                                    $reg_check->bind_param("i", $user['id']);
                                                    $reg_check->execute();
                                                    $reg_result = $reg_check->get_result();
                                                    $reg_data = $reg_result->fetch_assoc();
                                                    if ($reg_data['reg_count'] > 0): ?>
                                                        <br><span class="badge bg-success">Registered via Users System</span>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Active</span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['full_name']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-control" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="technician">Technician</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone_number" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(id, name) {
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

        function viewUser(id) {
            // Implement view functionality
            alert('View user details functionality will be implemented');
        }
    </script>
</body>
</html> 