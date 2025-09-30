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
    <title>Reports - BSU Inventory Management System</title>
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
        
        .report-card {
            transition: transform 0.3s ease;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
        }
        #categoryChart { max-height: 250px; }

        .navbar-brand { display: flex; align-items: center; gap: 8px; }

        .logo-icon {
            height: 24px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
        }


        .navbar { height: 56px; padding-top: 0; padding-bottom: 0; }
        .navbar .container-fluid { height: 56px; align-items: center; }

        .navbar-brand { display: flex; align-items: center; gap: 8px; padding: 0; }


        .logo-icon {
            height: 40px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
   <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="Ict logs.png" alt="BSU Logo" class="logo-icon"> BSU Inventory System
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
                            <a href="reports.php" class="nav-link active">
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
                    <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
                </div>

                <!-- Report Types -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-list fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Complete Inventory Report</h5>
                                <p class="card-text">Generate a comprehensive list of all equipment with detailed information.</p>
                                <button class="btn btn-primary" onclick="showReportModal('inventory')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                                <h5 class="card-title">Financial Summary Report</h5>
                                <p class="card-text">Financial analysis including costs, budgets, and expenditure summaries.</p>
                                <button class="btn btn-primary" onclick="showReportModal('financial')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-3x text-info mb-3"></i>
                                <h5 class="card-title">Department Analysis Report</h5>
                                <p class="card-text">Equipment distribution and analysis by department.</p>
                                <button class="btn btn-primary" onclick="showReportModal('department')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-tools fa-3x text-warning mb-3"></i>
                                <h5 class="card-title">Maintenance & Status Report</h5>
                                <p class="card-text">Maintenance records, schedules, and equipment status reports.</p>
                                <button class="btn btn-primary" onclick="showReportModal('maintenance')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                                <h5 class="card-title">Incomplete Items Report</h5>
                                <p class="card-text">Equipment with missing or incomplete information.</p>
                                <button class="btn btn-primary" onclick="showReportModal('incomplete')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Acquisition Timeline Report</h5>
                                <p class="card-text">Equipment acquisition timeline and purchase history.</p>
                                <button class="btn btn-primary" onclick="showReportModal('acquisition')">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Preventive Maintenance Plan</h5>
                                <p class="card-text">--------</p>

                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#preventiveModal">
                                Generate Preventive Maintenance Plan
                                </button>
                            </div>
                        </div>
                    </div>
					<!-- preventive maintendance index card -->
					
					
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Preventive Maintenance Plan Index Card</h5>
                                <p class="card-text">--------</p>

                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#PreventiveMaintendancePlanIndexCard">
                                Generate Preventive Maintenance Plan Index Card Form
                                </button>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">ICT Request Form</h5>
                                <p class="card-text">--------</p>

                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ictServiceRequestModal">
                                Generate ICT Request Form
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Existing Internet Service Provider's Evaluation</h5>
                                <p class="card-text">--------</p>

                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ispEvaluationModal">
                                Generate Existing Internet Service Provider's Evaluation Form
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Announcement / Greetings Request</h5>
                                <p class="card-text">--------</p>

                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#announcementModal">
                                Generate Announcement / Greetings Request
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Website Posting Request</h5>
                                <p class="card-text">--------</p>

                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#webPostingModal">
                                Generate Announcement / Greetings Request
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Request For System User Account Form</h5>
                                <p class="card-text">--------</p>

                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userAccountRequestModal">
                                Generate Request For System User Account Form
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Request For Posting of Announcement/Greetings</h5>
                                <p class="card-text">--------</p>

                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#postingRequestModal">
                                Request For Posting of Announcement/Greetings Form
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">System Request</h5>
                                <p class="card-text">--------</p>

                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#systemReqsModal">
                                Generate System Request
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Generation Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalTitle"><i class="fas fa-chart-bar"></i> Generate Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <!-- ✅ Added id and target -->
                <form method="POST" id="reportForm" target="_blank">
                    <input type="hidden" name="generate_report" value="1">
                    <input type="hidden" name="report_type" id="reportType">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-control" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php $departments->data_seek(0); while ($d = $departments->fetch_assoc()): ?>
                                        <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Equipment Category</label>
                                <select class="form-control" name="equipment_category">
                                    <option value="">All Categories</option>
                                    <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                                        <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download"></i> Generate & Download
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function showReportModal(reportType) {
        document.getElementById('reportType').value = reportType;

        const titles = {
            'inventory': 'Complete Inventory Report',
            'financial': 'Financial Summary Report',
            'department': 'Department Analysis Report',
            'maintenance': 'Maintenance & Status Report',
            'incomplete': 'Incomplete Items Report',
            'acquisition': 'Acquisition Timeline Report',
        };

        document.getElementById('reportModalTitle').innerHTML =
            '<i class="fas fa-chart-bar"></i> ' + titles[reportType];

        const reportMap = {
            'inventory': 'complete_inventory.php',
            'financial': 'financial_report.php',
            'department': 'department_report.php',
            'maintenance': 'maintenance_report.php',
            'incomplete': 'incomplete_report.php',
            'acquisition': 'acquisition_report.php',
        };

        // ✅ update action dynamically
        document.getElementById('reportForm').action = 'PDFS/' + reportMap[reportType];

        new bootstrap.Modal(document.getElementById('reportModal')).show();
    }
    </script>
</body>
</html> 