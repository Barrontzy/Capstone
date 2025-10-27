<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/logs.php';

// ✅ Ensure user is logged in
requireLogin();

// ✅ Ensure the requests table exists
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

// ✅ Define stats safely - updated to use correct table and statuses
$totalRequests = $conn->query("SELECT COUNT(*) AS count FROM requests")->fetch_assoc()['count'] ?? 0;
$pendingRequests = $conn->query("SELECT COUNT(*) AS count FROM requests WHERE status='Pending'")->fetch_assoc()['count'] ?? 0;
$completedRequests = $conn->query("SELECT COUNT(*) AS count FROM requests WHERE status='Approved'")->fetch_assoc()['count'] ?? 0;
$activityLogs = $conn->query("SELECT COUNT(*) AS count FROM logs")->fetch_assoc()['count'] ?? 0;

// ✅ Get analytics for most requested forms
$analyticsQuery = "SELECT form_type, COUNT(*) as count, 
                   SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
                   SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                   SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count
                   FROM requests 
                   GROUP BY form_type 
                   ORDER BY count DESC";
$analyticsResult = $conn->query($analyticsQuery);
$formAnalytics = [];
while ($row = $analyticsResult->fetch_assoc()) {
    $formAnalytics[] = $row;
}

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
        #requestChart {
            max-width: 100%;
            height: auto;
        }
        @media (max-width: 768px) {
            .stats-card h3 {
                font-size: 1.8rem;
            }
            .stats-card i {
                font-size: 1.5rem;
            }
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
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#ictServiceRequestModal"><i class="fas fa-desktop"></i> ICT Request Form</a>
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

            <!-- Analytics Section -->
            <?php if (!empty($formAnalytics)): ?>
            <div class="row g-4 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Most Requested Forms - Analytics</h5>
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#analyticsTable" aria-expanded="true">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="collapse show" id="analyticsTable">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Form Type</th>
                                            <th>Total Requests</th>
                                            <th>Approved</th>
                                            <th>Pending</th>
                                            <th>Rejected</th>
                                            <th>Visual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($formAnalytics as $analytics): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($analytics['form_type']) ?></strong></td>
                                            <td><?= $analytics['count'] ?></td>
                                            <td><span class="badge bg-success"><?= $analytics['approved_count'] ?></span></td>
                                            <td><span class="badge bg-warning text-dark"><?= $analytics['pending_count'] ?></span></td>
                                            <td><span class="badge bg-danger"><?= $analytics['rejected_count'] ?></span></td>
                                            <td>
                                                <?php 
                                                $maxCount = max(array_column($formAnalytics, 'count'));
                                                $percentage = $maxCount > 0 ? ($analytics['count'] / $maxCount) * 100 : 0;
                                                ?>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar progress-bar-striped" role="progressbar" 
                                                         style="width: <?= $percentage ?>%" 
                                                         aria-valuenow="<?= $analytics['count'] ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="<?= $maxCount ?>">
                                                        <?= $analytics['count'] ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Section -->
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Request Distribution</h5>
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#requestChartSection" aria-expanded="true">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="collapse show" id="requestChartSection">
                        <div class="card-body p-3" style="background: #ffffff;">
                            <div style="position: relative; height: 400px; width: 100%;">
                                <canvas id="requestChart"></canvas>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- Status Summary -->
                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-tasks"></i> Status Summary</h5>
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#statusSummarySection" aria-expanded="true">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="collapse show" id="statusSummarySection">
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fas fa-check-circle text-success"></i> Approved</span>
                                    <strong><?= $completedRequests ?></strong>
                                </div>
                                <?php $approvedPercentage = $totalRequests > 0 ? ($completedRequests / $totalRequests) * 100 : 0; ?>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?= $approvedPercentage ?>%"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fas fa-hourglass-half text-warning"></i> Pending</span>
                                    <strong><?= $pendingRequests ?></strong>
                                </div>
                                <?php $pendingPercentage = $totalRequests > 0 ? ($pendingRequests / $totalRequests) * 100 : 0; ?>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: <?= $pendingPercentage ?>%"></div>
                                </div>
                            </div>

                            <?php 
                            $rejectedRequests = $conn->query("SELECT COUNT(*) AS count FROM requests WHERE status='Rejected'")->fetch_assoc()['count'] ?? 0;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fas fa-times-circle text-danger"></i> Rejected</span>
                                    <strong><?= $rejectedRequests ?></strong>
                                </div>
                                <?php $rejectedPercentage = $totalRequests > 0 ? ($rejectedRequests / $totalRequests) * 100 : 0; ?>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" style="width: <?= $rejectedPercentage ?>%"></div>
                                </div>
                            </div>

                            <hr>
                            <div class="text-center">
                                <h3 class="text-primary"><?= $totalRequests ?></h3>
                                <p class="text-muted mb-0">Total Requests</p>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No analytics data available yet</h5>
                            <p class="text-muted">Start making requests to see analytics and insights</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap and JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<!-- ✅ JS for Sending Requests -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".sendRequestBtn");

    buttons.forEach(btn => {
        btn.addEventListener("click", function () {
            const formType = this.getAttribute("data-form");
            const form = this.closest("form");
            
            // Collect all form data
            const formData = new FormData(form);
            formData.append('form_type', formType);
            
            // Convert FormData to URL-encoded string
            const params = new URLSearchParams();
            for (let [key, value] of formData.entries()) {
                params.append(key, value);
            }

            fetch("send_request.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: params.toString()
            })
            .then(response => response.text())
            .then(text => {
                alert(text);

                // Reload page to update stats
                setTimeout(() => {
                    window.location.reload();
                }, 500);

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

    // ✅ Initialize Chart
    <?php if (!empty($formAnalytics)): ?>
    const ctx = document.getElementById('requestChart');
    if (ctx) {
        const formTypes = <?= json_encode(array_map(function($item) { return $item['form_type']; }, $formAnalytics)) ?>;
        const counts = <?= json_encode(array_map(function($item) { return intval($item['count']); }, $formAnalytics)) ?>;
        const approved = <?= json_encode(array_map(function($item) { return intval($item['approved_count']); }, $formAnalytics)) ?>;
        const pending = <?= json_encode(array_map(function($item) { return intval($item['pending_count']); }, $formAnalytics)) ?>;
        const rejected = <?= json_encode(array_map(function($item) { return intval($item['rejected_count']); }, $formAnalytics)) ?>;

        // Create gradient fills for area chart
        const createGradient = (color1, color2) => {
            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, color2);
            gradient.addColorStop(1, color1);
            return gradient;
        };

        const approvedGradient = createGradient('rgba(40, 167, 69, 0.1)', 'rgba(40, 167, 69, 0.6)');
        const pendingGradient = createGradient('rgba(255, 193, 7, 0.1)', 'rgba(255, 193, 7, 0.6)');
        const rejectedGradient = createGradient('rgba(220, 53, 69, 0.1)', 'rgba(220, 53, 69, 0.6)');

        window.myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: formTypes,
                datasets: [{
                    label: 'Approved',
                    data: approved,
                    borderColor: '#28a745',
                    backgroundColor: approvedGradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#28a745',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }, {
                    label: 'Pending',
                    data: pending,
                    borderColor: '#ffc107',
                    backgroundColor: pendingGradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffc107',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }, {
                    label: 'Rejected',
                    data: rejected,
                    borderColor: '#dc3545',
                    backgroundColor: rejectedGradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#dc3545',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#333333',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#333333',
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)',
                            lineWidth: 1
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#333333',
                            font: {
                                size: 11
                            },
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)',
                            lineWidth: 1
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // ✅ Toggle Chevron Icons on Collapse
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            
            // Toggle icon after a short delay to match Bootstrap's collapse animation
            setTimeout(() => {
                if (target.classList.contains('show')) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                } else {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            }, 50);
        });
    });

    // ✅ Make chart responsive on window resize
    window.addEventListener('resize', function() {
        <?php if (!empty($formAnalytics)): ?>
        const chartCanvas = document.getElementById('requestChart');
        if (chartCanvas && window.myChart) {
            window.myChart.resize();
        }
        <?php endif; ?>
    });
});
</script>

</body>
</html>
