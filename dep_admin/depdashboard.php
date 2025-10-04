<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// ✅ Corrected includes (since depdashboard is inside dep_admin)
include '../PDFS/PreventiveMaintenancePlan/preventiveForm.php';
include '../PDFS/PreventiveMaintenancePlanIndexCard/PreventiveMaintenancePlanIndexCard.php';
include '../PDFS/PreventiveMaintenancePlanIndexCard/preventivePDFIndexcard.php';

include '../PDFS/AnnouncementGreetings/announcementForm.php';
include '../PDFS/WebsitePosting/webpostingForm.php';
include '../PDFS/SystemRequest/systemReqsForm.php';
include '../PDFS/ICTRequestForm/ICTRequestForm.php';
include '../PDFS/ISPEvaluation/ISPEvaluation.php';
include '../PDFS/UserAccountForm/UserAccountForm.php';
include '../PDFS/PostingRequestForm/PostingRequestForm.php';

$departments = $conn->query("SELECT * FROM departments ORDER BY name");
$categories = $conn->query("SELECT * FROM equipment_categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Admin Dashboard - BSU Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
         :root { --primary-color: #198754; --secondary-color: #343a40; }
        .navbar { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); }
        .sidebar { background: #198754; min-height: calc(100vh - 56px); color: #fff; }
        .sidebar .nav-link { color: #fff; margin: 4px 10px; border-radius: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #145c32; color: #fff; }
        .main-content { padding: 20px; }
        .card { border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="depdashboard.php">
                <img src="../Ict logs.png" alt="Logo" style="height:40px;"> BSU Inventory System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="text-white me-3">Welcome, <strong>John Doe</strong></span>
                <a href="../logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (FORMS LIKE IN IMAGE) -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h6 class="text-uppercase text-white mb-3">Reports</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-file-alt"></i> Preventive Maintenance Plan</a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-id-card"></i> PM Plan Index Card</a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-laptop-code"></i> ICT Request Form</a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-network-wired"></i> ISP Evaluation</a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-bullhorn"></i> Announcement Request</a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-globe"></i> Website Posting</a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-user-plus"></i> User Account Request</a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-sticky-note"></i> Post Announcement</a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-cogs"></i> System Request</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-bar"></i> Department Admin Dashboard</h2>
                </div>

                <!-- Example cards (same as your design) -->
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <h5 class="card-title">Total Requests</h5>
                                <p class="fs-4 text-primary">248</p>
                                <small class="text-success">↑ 12% from last month</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <h5 class="card-title">Pending Approvals</h5>
                                <p class="fs-4 text-warning">32</p>
                                <small class="text-danger">Needs attention</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h5 class="card-title">Completed</h5>
                                <p class="fs-4 text-success">184</p>
                                <small class="text-success">74% completion rate</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center border-danger">
                            <div class="card-body">
                                <h5 class="card-title">Urgent Tasks</h5>
                                <p class="fs-4 text-danger">8</p>
                                <small class="text-danger">Requires action</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request Trends + Recent Activity -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card p-3">
                            <h6>Request Trends</h6>
                            <canvas id="requestTrends"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-3">
                            <h6>Recent Activity</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">ICT Request Form <small class="text-muted">2 mins ago</small></li>
                                <li class="list-group-item">Preventive Maintenance <small class="text-muted">15 mins ago</small></li>
                                <li class="list-group-item">User Account Request <small class="text-muted">1 hour ago</small></li>
                                <li class="list-group-item">Announcement Request <small class="text-muted">2 hours ago</small></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-4 p-3">
                    <h6>Quick Actions</h6>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-primary">+ New Request</a>
                        <a href="#" class="btn btn-success">View Reports</a>
                        <a href="#" class="btn btn-warning">Manage Users</a>
                        <a href="#" class="btn btn-info">Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Example chart for request trends
const ctx = document.getElementById('requestTrends').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct'],
        datasets: [
            { label: 'Total Requests', data: [40,45,42,55,60,58,70,75,76,85], borderColor: 'blue', fill: true },
            { label: 'Completed', data: [38,40,39,50,55,52,65,70,72,80], borderColor: 'green', fill: true }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>
</body>
</html>
