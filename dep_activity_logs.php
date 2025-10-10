<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// ✅ Check user login
requireLogin();

// ✅ Fetch all logs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$totalLogs = $conn->query("SELECT COUNT(*) AS count FROM logs")->fetch_assoc()['count'] ?? 0;
$totalPages = ceil($totalLogs / $limit);

// Fetch logs for current page
$logs = $conn->query("SELECT * FROM logs ORDER BY date DESC LIMIT $limit OFFSET $offset");

// ✅ Include your forms (modals)
include 'PDFS/PreventiveMaintenancePlan/preventiveForm.php';
include 'PDFS/PreventiveMaintendancePlanIndexCard/PreventiveMaintendancePlanIndexCard.php';
include 'PDFS/AnnouncementGreetings/announcementForm.php';
include 'PDFS/WebsitePosting/webpostingForm.php';
include 'PDFS/SystemRequest/systemReqsForm.php';
include 'PDFS/ICTRequestForm/ICTRequestForm.php';
include 'PDFS/ISPEvaluation/ISPEvaluation.php';
include 'PDFS/UserAccountForm/UserAccountForm.php';
include 'PDFS/PostingRequestForm/PostingRequestForm.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - BSU Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #343a40;
        }
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        .sidebar {
            background: #fff;
            min-height: calc(100vh - 56px);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: var(--secondary-color);
            margin: 5px 10px;
            border-radius: 8px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: var(--primary-color);
            color: #fff;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .pagination {
            justify-content: center;
        }
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .page-link {
            color: var(--primary-color);
        }
        .page-link:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="depdashboard.php">
            <img src="Ict logs.png" alt="Logo" style="height:40px;"> BSU Inventory System
        </a>
        <div class="navbar-nav ms-auto">
            <a href="dep_profile.php" class="btn btn-light me-2"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar py-4">
            <h5 class="text-center text-danger mb-3"><i class="fas fa-cogs"></i> System Reports</h5>
            <div class="nav flex-column">
                <a href="depdashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#preventiveModal"><i class="fas fa-calendar-check"></i> Preventive Maintenance Plan</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#PreventiveMaintendancePlanIndexCard"><i class="fas fa-clipboard-list"></i> Index Card</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#ictServiceRequestModal"><i class="fas fa-laptop-code"></i> ICT Request Form</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#ispEvaluationModal"><i class="fas fa-wifi"></i> ISP Evaluation</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#announcementModal"><i class="fas fa-bullhorn"></i> Announcement Request</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#webPostingModal"><i class="fas fa-globe"></i> Website Posting</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#userAccountRequestModal"><i class="fas fa-user-shield"></i> User Account Request</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#postingRequestModal"><i class="fas fa-envelope"></i> Posting Request</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#systemReqsModal"><i class="fas fa-cog"></i> System Request</a>
                <a href="dep_activity_logs.php" class="nav-link active"><i class="fas fa-history"></i> Activity Logs</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-history"></i> Activity Logs</h2>
                <div class="text-muted">
                    <i class="fas fa-info-circle"></i> Total: <?= $totalLogs ?> logs
                </div>
            </div>

            <!-- Activity Logs Table -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-list"></i> All Activity Logs
                </div>
                <div class="card-body p-0">
                    <?php if ($logs->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($log = $logs->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['id'] ?? '') ?></td>
                                            <td>
                                                <i class="fas fa-user-circle text-primary me-2"></i>
                                                <?= htmlspecialchars($log['user']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($log['action']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <i class="fas fa-clock text-muted me-2"></i>
                                                <?= htmlspecialchars($log['date']) ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No activity logs found</h5>
                            <p class="text-muted">Activity logs will appear here when users perform actions in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Activity logs pagination" class="mt-4">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// System Request Form Date Enhancement
document.addEventListener('DOMContentLoaded', function() {
    const systemReqsModal = document.getElementById('systemReqsModal');
    
    if (systemReqsModal) {
        systemReqsModal.addEventListener('shown.bs.modal', function() {
            // Set today's date as default for ICT Services date field
            const today = new Date();
            const todayString = today.toISOString().split('T')[0];
            
            // Set default date for ICT Services
            const ictDateInput = document.querySelector('input[name="ictDate"]');
            if (ictDateInput && !ictDateInput.value) {
                ictDateInput.value = todayString;
            }
            
            // Set default date for Work Done By date if empty
            const workDoneDateInput = document.querySelector('input[name="ictWorkByDate"]');
            if (workDoneDateInput && !workDoneDateInput.value) {
                workDoneDateInput.value = todayString;
            }
            
            // Set default date for Conforme date if empty
            const conformeDateInput = document.querySelector('input[name="ictConformeDate"]');
            if (conformeDateInput && !conformeDateInput.value) {
                conformeDateInput.value = todayString;
            }
        });
        
        // Clear form when modal is hidden
        systemReqsModal.addEventListener('hidden.bs.modal', function() {
            const form = systemReqsModal.querySelector('form');
            if (form) {
                form.reset();
            }
        });
    }
});
</script>
</body>
</html>
