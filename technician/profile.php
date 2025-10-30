<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// Check if user is logged in and is a technician
if (!isLoggedIn() || !isTechnician()) {
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
                
                // Validation
                if (empty($full_name) || empty($email) || empty($phone_number)) {
                    $error = 'Please fill in all required fields.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } elseif (!preg_match('/@g\.batstate-u\.edu\.ph$/', $email)) {
                    $error = 'Email must be from @g.batstate-u.edu.ph';
                } elseif (!preg_match('/^09\d{9}$/', $phone_number)) {
                    $error = 'Phone number must be exactly 11 digits starting with 09';
                } else {
                    // Check if email already exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->bind_param("si", $email, $user_id);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $error = 'Email address already exists.';
                    } else {
                        // Update without touching profile_image
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
                } elseif (strlen($new_password) < 8) {
                    $error = 'Password must be at least 8 characters long.';
                } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=:;\'"\\|,.<>\/?]).{8,}$/', $new_password)) {
                    $error = 'Password must be at least 8 characters with one uppercase, one lowercase, one digit, and one special character.';
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

// Stats
$stats_query = "
    SELECT 
        ( 
            SELECT COUNT(*) FROM (
                SELECT assigned_person FROM desktop WHERE assigned_person = ?
                UNION ALL
                SELECT assigned_person FROM laptops WHERE assigned_person = ?
                UNION ALL
                SELECT assigned_person FROM printers WHERE assigned_person = ?
                UNION ALL
                SELECT assigned_person FROM accesspoint WHERE assigned_person = ?
                UNION ALL
                SELECT assigned_person FROM `switch` WHERE assigned_person = ?
                UNION ALL
                SELECT assigned_person FROM telephone WHERE assigned_person = ?
            ) AS eq
        ) AS equipment_count,
        (SELECT COUNT(*) FROM tasks WHERE assigned_to = ?) AS task_count,
        (SELECT COUNT(*) FROM history WHERE user_id = ?) AS maintenance_count
";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("ssssssii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

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
                <div class="col-md-6">
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

                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="indet.php" class="btn btn-outline-primary">
                                    <i class="fas fa-tasks"></i> View All Tasks
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

<!-- Edit Profile Modal (without image upload) -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user"></i> Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editProfileForm">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="personal-info-section mb-4">
                        <h6 class="section-title">Personal Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="username@g.batstate-u.edu.ph" required>
                                <div class="form-text">Must be from @g.batstate-u.edu.ph</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" placeholder="09123456789" maxlength="11" pattern="^09\d{9}$" required>
                                <div class="form-text">Must be exactly 11 digits starting with 09</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal remains unchanged -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-lock"></i> Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="changePasswordForm">
                    <input type="hidden" name="action" value="change_password">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="8">
                            <div class="form-text">Must be at least 8 characters with uppercase, lowercase, number, and special character</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="8">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.stat-item { padding:15px; border-radius:8px; background-color:#f8f9fa; transition:transform .3s ease; }
.stat-item:hover { transform:translateY(-2px); }
.card { border:none; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
.form-control:read-only { background-color:#f8f9fa; }
.form-text { font-size:0.8rem; color:#6c757d; }
.section-title { font-weight:600; color:#333; margin-bottom:15px; font-size:1rem; }
.section-divider { margin:25px 0; border-color:#e9ecef; }
.btn-danger { background-color:#dc3545; border-color:#dc3545; }
.btn-danger:hover { background-color:#c82333; border-color:#bd2130; }
.btn-warning { background-color:#ffc107; border-color:#ffc107; color:#000; }
.btn-warning:hover { background-color:#e0a800; border-color:#d39e00; color:#000; }
#changePasswordModal .modal-body { padding:30px; }
#changePasswordModal .form-label { font-weight:600; color:#333; margin-bottom:8px; }
#changePasswordModal .form-control { border:1px solid #ced4da; border-radius:6px; padding:12px 15px; font-size:1rem; }
#changePasswordModal .form-control:focus { border-color:#80bdff; box-shadow:0 0 0 0.2rem rgba(0,123,255,0.25); }
#changePasswordModal .btn-warning { padding:12px 24px; font-weight:600; border-radius:6px; }
#changePasswordModal .modal-footer { border-top:1px solid #e9ecef; padding:20px 30px; background-color:#f8f9fa; }
</style>

<script>
// Client-side validation
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.querySelector('input[name="email"]');
    const phoneInput = document.querySelector('input[name="phone_number"]');

    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const email = this.value;
            const isValid = email.endsWith('@g.batstate-u.edu.ph');
            this.setCustomValidity(isValid ? '' : 'Email must be from @g.batstate-u.edu.ph');
        });
    }
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            const phone = this.value;
            const isValid = /^09\d{9}$/.test(phone);
            this.setCustomValidity(isValid ? '' : 'Phone number must be exactly 11 digits starting with 09');
        });
    }

    // Change Password Modal validation
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        const newPasswordInputModal = changePasswordForm.querySelector('input[name="new_password"]');
        const confirmPasswordInput = changePasswordForm.querySelector('input[name="confirm_password"]');
        if (newPasswordInputModal) {
            newPasswordInputModal.addEventListener('input', function() {
                const password = this.value;
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasSpecial = /[!@#$%^&*()_+\-_=\[\]{};':"\\|,.<>\/?]/.test(password);
                const isValid = password.length >= 8 && hasUpper && hasLower && hasNumber && hasSpecial;
                this.setCustomValidity(isValid ? '' : 'Password must be at least 8 characters with one uppercase, one lowercase, one digit, and one special character');
            });
        }
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const password = newPasswordInputModal.value;
                const confirmPassword = this.value;
                this.setCustomValidity(password === confirmPassword ? '' : 'Passwords do not match');
            });
        }
    }
});
</script>

<?php require_once 'footer.php'; ?> 