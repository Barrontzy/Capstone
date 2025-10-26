<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
requireLogin();

// First, ensure the requests table exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS `requests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `form_type` varchar(255) NOT NULL,
    `form_data` longtext DEFAULT NULL,
    `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`),
    KEY `form_type` (`form_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

$conn->query($createTableQuery);

// Check if form_data column exists, if not add it
$checkColumnQuery = "SHOW COLUMNS FROM `requests` LIKE 'form_data'";
$columnResult = $conn->query($checkColumnQuery);

if ($columnResult->num_rows == 0) {
    $addColumnQuery = "ALTER TABLE `requests` ADD COLUMN `form_data` longtext DEFAULT NULL AFTER `form_type`";
    $conn->query($addColumnQuery);
}

// Fetch all requests with user information
$query = "SELECT r.*, u.full_name FROM requests r 
          LEFT JOIN users u ON r.user_id = u.id 
          ORDER BY r.created_at DESC";
$result = $conn->query($query);

if (!$result) {
    die("❌ Query Error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests - BSU Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
         :root { --primary-color: #dc3545; --secondary-color: #343a40; }
        .navbar { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); }
        .sidebar { background: white; min-height: calc(100vh - 56px); box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar .nav-link { color: var(--secondary-color); margin: 4px 10px; border-radius: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: var(--primary-color); color: #fff; }
        .main-content { padding: 20px; }
        .card { border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .btn-view { background-color: #ffc107; color: black; }
        .btn-approve { background-color: #6c757d; color: white; }
        .btn-reject { background-color: #dc3545; color: white; }
        .btn-view:hover, .btn-approve:hover, .btn-reject:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="Ict logs.png" alt="Logo" style="height:40px;"> BSU Inventory System
            </a>
            <div class="navbar-nav ms-auto">
                <a href="profile.php" class="btn btn-light me-2"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="equipment.php" class="nav-link"><i class="fas fa-laptop"></i> Equipment</a></li>
                    <li class="nav-item"><a href="departments.php" class="nav-link"><i class="fas fa-building"></i> Departments</a></li>
                    <li class="nav-item"><a href="maintenance.php" class="nav-link"><i class="fas fa-tools"></i> Maintenance</a></li>
                    <li class="nav-item"><a href="tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Tasks</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li class="nav-item"><a href="request.php" class="nav-link active"><i class="fas fa-envelope"></i> Requests</a></li>
                    <li class="nav-item"><a href="system_logs.php" class="nav-link"><i class="fas fa-clipboard-list"></i> System Logs</a></li>
                    <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                    <li class="nav-item"><a href="admin_accounts.php" class="nav-link"><i class="fas fa-user-shield"></i> Admin Accounts</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <h2><i class="fas fa-envelope"></i> Requests for Approval</h2>
                <div class="card p-4 mt-3">
                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table id="requestTable" class="table table-bordered table-striped text-center align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>User Name</th>
                                        <th>Form Type</th>
                                        <th>Status</th>
                                        <th>Date Requested</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['id']) ?></td>
                                            <td><?= htmlspecialchars($row['full_name'] ?? 'Unknown User') ?></td>
                                            <td><?= htmlspecialchars($row['form_type']) ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match ($row['status']) {
                                                    'Approved' => 'bg-success text-white',
                                                    'Rejected' => 'bg-danger text-white',
                                                    default => 'bg-warning text-dark'
                                                };
                                                ?>
                                                <span class="badge <?= $statusClass ?>">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                                            <td>
                                                <!-- View Button -->
                                                <a href="view_request.php?id=<?= $row['id'] ?>" class="btn btn-view btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>

                                                <!-- Approve Button -->
                                                <a href="approve_request.php?id=<?= $row['id'] ?>" class="btn btn-approve btn-sm" onclick="return confirm('Approve this request?');">
                                                    <i class="fas fa-check"></i> Approve
                                                </a>

                                                <!-- Reject Button -->
                                                <a href="reject_request.php?id=<?= $row['id'] ?>" class="btn btn-reject btn-sm" onclick="return confirm('Reject this request?');">
                                                    <i class="fas fa-times"></i> Reject
                                                </a>

                                                <!-- Delete Button -->
                                                <a href="delete_request.php?id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this request? This action cannot be undone.');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No requests found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#requestTable').DataTable();
        });
    </script>
</body>
</html>