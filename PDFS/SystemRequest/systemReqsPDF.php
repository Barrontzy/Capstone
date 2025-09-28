<?php
require_once __DIR__ . '/../../includes/fpdf/fpdf.php';
require_once '../../includes/session.php';
require_once '../../includes/db.php';

	include '../../logger.php';
	logAdminAction($_SESSION['user_id'], $_SESSION['user_name'], "Generated Report", "SYSTEM REQUEST FORM");
class PDF extends FPDF {
    function Header() {
        // Logo
        $logoPath = __DIR__ . '/../../assets/logo/bsutneu.png';
        if (file_exists($logoPath)) {
            $this->Cell(25, 20, '', 1, 0, 'C');
            $this->Image($logoPath, $this->GetX()-24, $this->GetY(), 23, 20);
        }
        $this->SetFont('Arial','',9);
        $this->Cell(95, 20, 'Reference No.: BatStateU-FO-ICT-03', 1, 0, 'L');
        $this->Cell(45, 20, 'Effectivity Date: Jan 23, 2023', 1, 0, 'L');
        $this->Cell(25, 20, 'Rev. No.: 02', 1, 1, 'L');

        // Title
        $this->SetFont('Arial','B',14);
        $this->Cell(0, 12, 'SYSTEM REQUEST FORM', 1, 1, 'C');
    }
}

// Render checkbox
function checkbox($label, $array, $value) {
    $mark = (is_array($array) && in_array($value, $array)) ? "[x]" : "[ ]";
    return $mark . " " . $label;
}

// Collect POST
$office      = $_POST['office'] ?? '';
$sysType     = $_POST['sysType'] ?? [];
$urgency     = $_POST['urgency'] ?? [];
$nameSystem  = $_POST['nameSystem'] ?? '';
$descRequest = $_POST['descRequest'] ?? '';
$remarks     = $_POST['remarks'] ?? '';

// Create PDF
$pdf = new PDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial','',11);
$fullWidth = 190;
$rowHeight = 10;

// --- Office ---
$pdf->Cell(50, $rowHeight, 'Requesting Office/Unit:', 1, 0);
$pdf->Cell($fullWidth-50, $rowHeight, $office, 1, 1);

// --- Type of Request ---
$lineHeight = 8;
$options = ['Correction of system issue','System enhancement','New System'];
$cellHeight = count($options) * $lineHeight;

// Left column
$pdf->Cell(50, $cellHeight, 'Type of Request:', 1, 0, 'L');

// Right column
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->Rect($x, $y, $fullWidth-50, $cellHeight);

// Checkboxes inside right column
foreach ($options as $opt) {
    $mark = checkbox($opt, $sysType, $opt);
    $pdf->SetXY($x, $y);
    $pdf->Cell($fullWidth-50, $lineHeight, $mark, 0, 1, 'L');
    $y += $lineHeight;
}

// --- Urgency ---
$lineHeight = 8;
$options = [
    'Immediate attention required',
    'Handle in normal priority',
    'Defer until new system is developed'
];
$cellHeight = count($options) * $lineHeight;

// Left column
$pdf->Cell(50, $cellHeight, 'Urgency:', 1, 0, 'L');

// Right column
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->Rect($x, $y, $fullWidth-50, $cellHeight);

// Checkboxes inside cell
foreach ($options as $opt) {
    $mark = checkbox($opt, $urgency, $opt);
    $pdf->SetXY($x, $y);
    $pdf->Cell($fullWidth-50, $lineHeight, $mark, 0, 1, 'L');
    $y += $lineHeight;
}

// --- Name of System ---
$label = 'Name of Existing / Proposed System:';
$labelWidth = 50;
$contentWidth = $fullWidth - $labelWidth;

$startX = $pdf->GetX();
$startY = $pdf->GetY();

// Print label with MultiCell (wrapping)
$pdf->MultiCell($labelWidth, $rowHeight, $label, 1, 'L');

// Restore X and Y for right-hand cell
$pdf->SetXY($startX + $labelWidth, $startY);

$pdf->MultiCell($contentWidth, $rowHeight + 10, $nameSystem, 1, 'L');

// --- Description ---
$pdf->Cell(50, $rowHeight, 'Description of Request:', 1, 0);
$pdf->MultiCell($fullWidth-50, $rowHeight, $descRequest, 1);

// --- Remarks ---
$pdf->Cell(50, $rowHeight, 'Remarks:', 1, 0);
$pdf->Cell($fullWidth-50, $rowHeight, $remarks, 1, 1);

// --- Signature Blocks ---
$colWidth = ($pdf->GetPageWidth()-20)/3;
$yStart = $pdf->GetY();
$blockHeight = 55;

// Requested by
$pdf->Rect(10, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10, $yStart+5);
$pdf->MultiCell($colWidth, 7, "Requested by:", 0, 'L');
$pdf->SetXY(10, $pdf->GetY());
$pdf->MultiCell($colWidth, 8, "\nNAME OF REQUESTING OFFICIAL / PERSONNEL\nDesignation\nDate: ___________", 0, 'C');

// Recommending Approval
$pdf->Rect(10+$colWidth, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10+$colWidth, $yStart+5);
$pdf->MultiCell($colWidth, 7, "Recommending Approval:", 0, 'L');
$pdf->SetXY(10+$colWidth, $pdf->GetY());
$pdf->MultiCell($colWidth, 8, "\nNAME\nDesignation\n\nDate: ___________", 0, 'C');

// Approved by
$pdf->Rect(10+2*$colWidth, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10+2*$colWidth, $yStart+5);
$pdf->MultiCell($colWidth, 7, "Approved by:", 0, 'L');
$pdf->SetXY(10+2*$colWidth, $pdf->GetY());
$pdf->MultiCell($colWidth, 8, "\nNAME\nDesignation\n\nDate: ___________", 0, 'C');

$pdf->SetY($yStart+$blockHeight);

// --- ICT Services Section ---
$pdf->SetFont('Arial','I',12);
$pdf->Cell(0, 8, '--- To be completed by ICT Services ---', 1, 1, 'C');
$pdf->SetFont('Arial','',11);

$pdf->Cell(30, $rowHeight, 'Date:', 1, 0);
$pdf->Cell(160, $rowHeight, '', 1, 1);

$pdf->Cell(30, $rowHeight, 'Assigned to:', 1, 0);
$pdf->Cell(160, $rowHeight, '', 1, 1);

$pdf->Cell(30, $rowHeight, 'Tasks:', 1, 0);
$pdf->MultiCell(160, $rowHeight, '', 1);

$pdf->Cell(30, $rowHeight, 'Work Done by:', 1, 0);
$pdf->Cell(160, $rowHeight, '', 1, 1);

$pdf->Cell(30, $rowHeight, 'Conforme:', 1, 0);
$pdf->Cell(160, $rowHeight, '', 1, 1);

// --- Footer ---
$pdf->Ln(5);
$pdf->SetFont('Arial','I',9);
$pdf->MultiCell(0, 5, "Note: Attach SRS and flowchart if new system is requested.");
$pdf->Ln(3);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0, 8, 'Tracking Number: _____________', 0, 1, 'L');

// Output
$pdf->Output('I','System_Request_Form.pdf');

?>
