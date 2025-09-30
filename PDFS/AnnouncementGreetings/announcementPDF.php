<?php
require_once __DIR__ . '/../../includes/fpdf/fpdf.php';

require_once '../../includes/session.php';
require_once '../../includes/db.php';

	include '../../logger.php';
	logAdminAction($_SESSION['user_id'], $_SESSION['user_name'], "Generated Report", "REQUEST FOR POSTING OF ANNOUNCEMENTS / GREETINGS");


class PDF extends FPDF {
    function Header() {
        // --- Logo ---
        $logoPath = __DIR__ . '/../../assets/logo/bsutneu.png';
        if (file_exists($logoPath)) {
            $this->Cell(25, 20, '', 1, 0, 'C');
            $this->Image($logoPath, $this->GetX() - 24, $this->GetY(), 23, 20);
        }

        // --- Reference Info ---
        $this->SetFont('Arial','',9);
        $this->Cell(95, 20, 'Reference No.: BatStateU-FO-CSD-01', 1, 0, 'L');
        $this->Cell(45, 20, 'Effectivity Date: Jan 23, 2023', 1, 0, 'L');
        $this->Cell(25, 20, 'Rev. No.: 02', 1, 1, 'L');

        // --- Title ---
        $this->SetFont('Arial','B',14);
        $this->Cell(0, 12, 'REQUEST FOR POSTING OF ANNOUNCEMENTS / GREETINGS', 1, 1, 'C');
    }
}

// --- Helper: render checkbox ---
function checkbox($label, $array, $value) {
    $mark = (is_array($array) && in_array($value, $array)) ? "[x]" : "[ ]";
    return $mark . " " . $label;
}

// --- Collect POST Data ---
$college       = $_POST['college']       ?? '';
$purpose       = $_POST['purpose']       ?? '';
$posting       = $_POST['posting']       ?? [];
$location      = $_POST['location']      ?? '';
$content       = $_POST['content']       ?? '';
$postingPeriod = $_POST['postingPeriod'] ?? '';

// --- Create PDF ---
$pdf = new PDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial','',11);
$fullWidth = 190;
$rowHeight = 10;

// --- College / Office ---
$pdf->Cell(50, $rowHeight, 'College / Office:', 1, 0);
$pdf->Cell($fullWidth-50, $rowHeight, $college, 1, 1);

// --- Purpose ---
$pdf->Cell(50, $rowHeight, 'Purpose:', 1, 0);
$pdf->MultiCell($fullWidth-50, $rowHeight, $purpose, 1);

// --- Means of Posting ---
$options = ['Bulletin Board','View Board','LED Board','Social Media'];
$lineHeight = 8;
$locationHeight = 16; // allocate space for location field
$cellHeight = count($options) * $lineHeight + $locationHeight;

$pdf->Cell(50, $cellHeight, 'Means of Posting:', 1, 0, 'L');

// Right cell border
$x = $pdf->GetX();
$y = $pdf->GetY();
$w = $fullWidth - 50;
$pdf->Rect($x, $y, $w, $cellHeight);

// Checkboxes
$yy = $y + 2;
foreach ($options as $opt) {
    $mark = checkbox($opt, $posting, $opt);
    $pdf->SetXY($x+2, $yy);
    $pdf->Cell($w-4, $lineHeight, $mark, 0, 1, 'L');
    $yy += $lineHeight;
}

$pdf->SetXY($x+2, $yy+2);
$pdf->MultiCell($w-4, 6, "Specific Location / Media Site:\n".$location, 0, 'L');

// Move Y to bottom of the whole cell
$pdf->SetY($y + $cellHeight);

// --- Brief Content and Layout ---
$pdf->Cell(50, $rowHeight, 'Brief Content and Layout:', 1, 0);
$pdf->MultiCell($fullWidth-50, $rowHeight, $content, 1);

// --- Posting Period ---
$pdf->Cell(50, $rowHeight, 'Posting Period:', 1, 0);
$pdf->Cell($fullWidth-50, $rowHeight, $postingPeriod, 1, 1);

// --- Signature Blocks (3 columns) ---
$colWidth = ($pdf->GetPageWidth()-20)/3;
$yStart = $pdf->GetY();
$blockHeight = 50;

// Requested by
$pdf->Rect(10, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10, $yStart+5);
$pdf->MultiCell($colWidth, 7, "Requested by:", 0, 'L');
$pdf->SetX(10);
$pdf->MultiCell($colWidth, 7, "\nNAME OF HEAD OF OFFICE/UNIT\nPosition/Designation\n", 0, 'C');
$pdf->SetX(10);
$pdf->MultiCell($colWidth, 7, "Date Signed: ___________", 0, 'L');

// Recommending Approval
$pdf->Rect(10+$colWidth, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10+$colWidth, $yStart+5);
$pdf->MultiCell($colWidth, 7, "Recommending Approval:", 0, 'L');
$pdf->SetX(10+$colWidth);
$pdf->MultiCell($colWidth, 7, "\nEngr. JONNAH R. MELO\nHead, ICT Services\n", 0, 'C');
$pdf->SetX(10+$colWidth);
$pdf->MultiCell($colWidth, 7, "\nDate Signed: ___________", 0, 'L');

// Approved by
$pdf->Rect(10+2*$colWidth, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10+2*$colWidth, $yStart+5);
$pdf->MultiCell($colWidth, 7, "Approved by:", 0, 'L');
$pdf->SetX(10+2*$colWidth);
$pdf->MultiCell($colWidth, 7, "\nAtty. ALVIN R. DE SILVA\nChancellor\n", 0, 'C');
$pdf->SetX(10+2*$colWidth);
$pdf->MultiCell($colWidth, 7, "\nDate Signed: ___________", 0, 'L');


$pdf->SetY($yStart+$blockHeight);

// --- Remarks ---
$pdf->Cell(30, $rowHeight*2, 'Remarks:', 1, 0);
$pdf->Cell($fullWidth-30, $rowHeight*2, '', 1, 1);

$pdf->Ln(5);

// --- Note ---
$pdf->SetFont('Arial','I',9);
$pdf->MultiCell(0, 6, 'Note: It is understood that the posting shall be removed after the approved duration.');

// --- Tracking Number ---
$pdf->Ln(5);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0, 8, 'Tracking Number: _____________', 0, 1, 'L');

// --- Output ---
$pdf->Output('I','Announcement_Request.pdf');
?>
