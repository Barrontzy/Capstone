<?php
require_once 'includes/db.php';

if (!isset($_GET['id'])) die("Missing ID");

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT pdf_path, report_type FROM system_requests WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) die("Request not found");

$report_type = $request['report_type'];
$pdf_path = $request['pdf_path'];

// Map of report types to their corresponding PHP form file
$form_map = [
    'Preventive Maintenance Plan' => 'PDFS/PreventiveMaintenancePlan/preventiveForm.php',
    'Preventive Maintenance Plan Index Card' => 'PDFS/PreventiveMaintendancePlanIndexCard/PreventiveMaintendancePlanIndexCard.php',
    'Announcement Greetings' => 'PDFS/AnnouncementGreetings/announcementForm.php',
    'Website Posting Request' => 'PDFS/WebsitePosting/webpostingForm.php',
    'System Request' => 'PDFS/SystemRequest/systemReqsForm.php',
    'ICT Request Form' => 'PDFS/ICTRequestForm/ICTRequestForm.php',
    'ISP Evaluation' => 'PDFS/ISPEvaluation/ISPEvaluation.php',
    'User Account Form' => 'PDFS/UserAccountForm/UserAccountForm.php',
    'Posting Request Form' => 'PDFS/PostingRequestForm/PostingRequestForm.php'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <h3><?= htmlspecialchars($report_type) ?> - Request Preview</h3>
    <hr>

    <?php if (!empty($pdf_path) && file_exists($pdf_path)): ?>
        <div class="mb-4">
            <iframe src="<?= htmlspecialchars($pdf_path) ?>" width="100%" height="800px" style="border: 1px solid #ccc;"></iframe>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No PDF attached to this request.</div>
    <?php endif; ?>

    <?php
    // If the form file exists, include it as a visual reference
    if (isset($form_map[$report_type]) && file_exists($form_map[$report_type])) {
        echo "<hr><h5>Form Preview:</h5>";
        include $form_map[$report_type];
    } else {
        echo "<div class='alert alert-secondary mt-3'>No specific form layout found for this request type.</div>";
    }
    ?>

    <div class="mt-4">
        <a href="request.php" class="btn btn-secondary">Back to Requests</a>
    </div>
</div>

</body>
</html>
