<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
include 'PDFS/PreventiveMaintenancePlan/preventiveForm.php';
include 'PDFS/PreventiveMaintendancePlanIndexCard/PreventiveMaintendancePlanIndexCard.php';
include 'PDFS/AnnouncementGreetings/announcementForm.php';
include 'PDFS/WebsitePosting/webpostingForm.php';
include 'PDFS/SystemRequest/systemReqsForm.php';
include 'PDFS/ICTRequestForm/ICTRequestForm.php';
include 'PDFS/ISPEvaluation/ISPEvaluation.php';
include 'PDFS/UserAccountForm/UserAccountForm.php';
include 'PDFS/PostingRequestForm/PostingRequestForm.php';

$departments = $conn->query("SELECT * FROM departments ORDER BY name");
$categories = $conn->query("SELECT * FROM equipment_categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - BSU Inventory Management System</title>
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
    </style>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="Ict logs.png" alt="Logo" style="height:40px;"> BSU Inventory System
            </a>
            <div class="navbar-nav ms-auto">
                <a href="dep_profile.php" class="btn btn-light me-2"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>


            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
                </div>

                <!-- Report Types -->
                <div class="row">
    <!-- System Data Reports -->
   <div class="row">
    <!-- System Data Reports -->
    <!-- System Generated Reports -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white text-center">
                <i class="fas fa-cogs"></i> System Generated Reports
            </div>
            <div class="card-body">
                <div class="list-group">
                    <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#preventiveModal">
                        <i class="fas fa-calendar-check text-success me-2"></i> Preventive Maintenance Plan
                    </button>
                    <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#PreventiveMaintendancePlanIndexCard">
                        <i class="fas fa-clipboard-list text-primary me-2"></i> Preventive Maintenance Plan Index Card
                    </button>
                    <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#ictServiceRequestModal">
                        <i class="fas fa-laptop-code text-dark me-2"></i> ICT Request Form
                    </button>
                    <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#ispEvaluationModal">
                        <i class="fas fa-wifi text-info me-2"></i> ISP Evaluation Form
                    </button>
                    <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#announcementModal">
                        <i class="fas fa-bullhorn text-danger me-2"></i> Announcement / Greetings Request
                    </button>
                    <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#webPostingModal">
                        <i class="fas fa-globe text-secondary me-2"></i> Website Posting Request
                    </button>
                    <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#userAccountRequestModal">
                        <i class="fas fa-user-shield text-warning me-2"></i> System User Account Request Form
                    </button>
                    <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#postingRequestModal">
                        <i class="fas fa-envelope-open-text text-success me-2"></i> Posting of Announcement/Greetings
                    </button>
                    <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#systemReqsModal">
                        <i class="fas fa-cog text-dark me-2"></i> System Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function showReportModal(reportType) {
    const reportMap = {
        'inventory': 'complete_inventory.php',
        'financial': 'financial_report.php',
        'department': 'department_report.php',
        'maintenance': 'maintenance_report.php',
        'incomplete': 'incomplete_report.php',
        'acquisition': 'acquisition_report.php',
    };

    if (!reportMap[reportType]) return;

    // ✅ Create a temporary form for immediate download
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'PDFS/' + reportMap[reportType];
    form.target = '_blank';

    // Optional hidden input for filters (date/department/etc.)
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'report_type';
    input.value = reportType;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>

</body>
</html> 