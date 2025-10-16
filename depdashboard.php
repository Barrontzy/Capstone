<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/logs.php';

// ✅ Ensure user is logged in
requireLogin();

// ✅ Define stats safely
$totalRequests = $conn->query("SELECT COUNT(*) AS count FROM report_requests")->fetch_assoc()['count'] ?? 0;
$pendingRequests = $conn->query("SELECT COUNT(*) AS count FROM report_requests WHERE status='Pending'")->fetch_assoc()['count'] ?? 0;
$completedRequests = $conn->query("SELECT COUNT(*) AS count FROM report_requests WHERE status='Completed'")->fetch_assoc()['count'] ?? 0;
$activityLogs = $conn->query("SELECT COUNT(*) AS count FROM logs")->fetch_assoc()['count'] ?? 0;

// ✅ Include your modal forms (without PDF generation)
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
    <title>Department Dashboard - BSU ICT Management System</title>
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
        .stats-card {
            text-align: center;
            padding: 25px 10px;
        }
        .stats-card h3 {
            font-weight: bold;
            font-size: 2.2rem;
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="depdashboard.php">
            <img src="Ict logs.png" alt="Logo" style="height:40px;"> BSU ICT System
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
                <a href="depdashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#preventiveModal"><i class="fas fa-calendar-check"></i> Preventive Maintenance Plan</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#PreventiveMaintendancePlanIndexCard"><i class="fas fa-clipboard-list"></i> Index Card</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#ictServiceRequestModal"><i class="fas fa-laptop-code"></i> ICT Request Form</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#ispEvaluationModal"><i class="fas fa-wifi"></i> ISP Evaluation</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#announcementModal"><i class="fas fa-bullhorn"></i> Announcement Request</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#webPostingModal"><i class="fas fa-globe"></i> Website Posting</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#userAccountRequestModal"><i class="fas fa-user-shield"></i> User Account Request</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#postingRequestModal"><i class="fas fa-envelope"></i> Posting Request</a>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#systemReqsModal"><i class="fas fa-cog"></i> System Request</a>
                <a href="dep_activity_logs.php" class="nav-link"><i class="fas fa-history"></i> Activity Logs</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> Department Dashboard</h2>

            <!-- Stat Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stats-card text-primary">
                        <i class="fas fa-clipboard-list"></i>
                        <h5>Total Requests</h5>
                        <h3><?= $totalRequests ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card text-warning">
                        <i class="fas fa-hourglass-half"></i>
                        <h5>Pending Requests</h5>
                        <h3><?= $pendingRequests ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card text-success">
                        <i class="fas fa-check-circle"></i>
                        <h5>Completed Requests</h5>
                        <h3><?= $completedRequests ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card text-danger">
                        <i class="fas fa-file-alt"></i>
                        <h5>Activity Logs</h5>
                        <h3><?= $activityLogs ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap and JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- ✅ JS for Sending Requests -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".sendRequestBtn");

    buttons.forEach(btn => {
        btn.addEventListener("click", function () {
            const formType = this.getAttribute("data-form");

            fetch("request.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `action=send_request&form_type=${encodeURIComponent(formType)}`
            })
            .then(response => response.text())
            .then(text => {
                alert(text);

                // ✅ Close modal after request
                const modal = this.closest(".modal");
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) modalInstance.hide();
                }
            })
            .catch(err => {
                console.error("❌ Error:", err);
                alert("An error occurred while sending the request.");
            });
        });
    });
});
</script>

</body>
</html>
