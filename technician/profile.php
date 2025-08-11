<?php
session_start();
require_once '../includes/session.php';
require_once '../includes/db.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'technician') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $phone_number = trim($_POST['phone_number']);
                
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $user_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Email address already exists.';
                } else {
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $full_name, $email, $phone_number, $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['user_name'] = $full_name;
                        $_SESSION['user_email'] = $email;
                        $success = 'Profile updated successfully!';
                    } else {
                        $error = 'Failed to update profile.';
                    }
                }
                $stmt->close();
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if (!password_verify($current_password, $user['password'])) {
                    $error = 'Current password is incorrect.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password must be at least 6 characters long.';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt->execute()) {
                        $success = 'Password changed successfully!';
                    } else {
                        $error = 'Failed to change password.';
                    }
                }
                $stmt->close();
                break;
        }
    }
}

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user statistics
$stats = $conn->query("
    SELECT 
        COUNT(DISTINCT e.id) as equipment_count,
        COUNT(DISTINCT t.id) as task_count,
        COUNT(DISTINCT mr.id) as maintenance_count
    FROM users u
    LEFT JOIN equipment e ON u.id = e.assigned_to
    LEFT JOIN tasks t ON u.id = t.assigned_to
    LEFT JOIN maintenance_records mr ON u.id = mr.technician_id
    WHERE u.id = $user_id
")->fetch_assoc();

$page_title = 'Profile';
require_once 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-user-circle"></i> My Profile</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-user"></i> Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone_number" 
                                               value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Member Since</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo date('M d, Y', strtotime($user['created_at'])); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Updated</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo date('M d, Y H:i', strtotime($user['updated_at'])); ?>" readonly>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-lock"></i> Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" required minlength="6">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar"></i> My Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="stat-item">
                                        <i class="fas fa-desktop fa-2x text-primary mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['equipment_count']; ?></h4>
                                        <small class="text-muted">Equipment Assigned</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-item">
                                        <i class="fas fa-tasks fa-2x text-info mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['task_count']; ?></h4>
                                        <small class="text-muted">Tasks Assigned</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-item">
                                        <i class="fas fa-tools fa-2x text-warning mb-2"></i>
                                        <h4 class="mb-1"><?php echo $stats['maintenance_count']; ?></h4>
                                        <small class="text-muted">Maintenance Records</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-item">
                                        <i class="fas fa-calendar fa-2x text-success mb-2"></i>
                                        <h4 class="mb-1"><?php echo date('M Y'); ?></h4>
                                        <small class="text-muted">Current Month</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="fas fa-tasks"></i> View All Tasks
                                </a>
                                <a href="mytasks.php" class="btn btn-outline-info">
                                    <i class="fas fa-list"></i> My Assigned Tasks
                                </a>
                                <a href="qr.php" class="btn btn-outline-success">
                                    <i class="fas fa-qrcode"></i> Scan QR Code
                                </a>
                                <a href="history.php" class="btn btn-outline-warning">
                                    <i class="fas fa-history"></i> Equipment History
                                </a>
                                <a href="logout.php" class="btn btn-outline-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-item {
    padding: 15px;
    border-radius: 8px;
    background-color: #f8f9fa;
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-control:read-only {
    background-color: #f8f9fa;
}
</style>

<?php require_once 'footer.php'; ?> 