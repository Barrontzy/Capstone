<?php
require_once __DIR__ . '/../../includes/fpdf/fpdf.php';

class PDF extends FPDF {
    function Header() {
        // Reference box
        $logoPath = __DIR__ . '/../../assets/logo/bsutneu.png';
        if (file_exists($logoPath)) {
            $this->Cell(25, 20, '', 1, 0, 'C');
            $this->Image($logoPath, $this->GetX() - 24, $this->GetY(), 23, 20);
        }
        $this->SetFont('Arial','',10);
        $this->Cell(80, 20, 'Reference No.: BatStateU-FO-ICT-06', 1, 0, 'C');
        $this->Cell(55, 20, 'Eff. Date: January 3, 2023', 1, 0, 'C');
        $this->Cell(30, 20, 'Rev. No.: 00', 1, 1, 'C');

        // Title
        $this->SetFont('Arial','B',14);
        $this->Cell(0, 10, 'WEBSITE POSTING REQUEST FORM', 1, 1, 'C');
    }
}

// Collect POST data
$office          = $_POST['office'] ?? '';
$datePosting     = $_POST['datePosting'] ?? '';
$durationPosting = $_POST['durationPosting'] ?? '';
$purpose         = $_POST['purpose'] ?? '';
$content         = $_POST['content'] ?? '';

// Create PDF
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial','',11);
$fullWidth = 190;

// --- Requesting Office/Unit ---
$pdf->Cell(50, 10, 'Requesting Office/Unit:', 1, 0, 'L');
$pdf->Cell($fullWidth-50, 10, $office, 1, 1, 'C');

// --- Proposed Date of Posting ---
$pdf->Cell(50, 10, 'Proposed Date of Posting:', 1, 0, 'L');
$pdf->Cell(60, 10, $datePosting, 1, 0, 'C');

// --- Duration of Posting ---
$pdf->Cell(40, 10, 'Duration of Posting:', 1, 0, 'L');
$pdf->Cell(40, 10, $durationPosting, 1, 1, 'C');

// --- Purpose ---
$pdf->Cell(25, 40, 'Purpose:', 1, 0, 'L');
$pdf->Cell($fullWidth-25, 40, $purpose, 1, 1, 'C');

// --- Content ---
$pdf->Cell(25, 40, 'Content:', 1, 0, 'L');
$pdf->Cell($fullWidth-25, 40, $content, 1, 1, 'C');

// --- Signature Blocks ---
$yStart      = $pdf->GetY();
$colWidth    = ($pdf->GetPageWidth() - 20) / 2; // 2 columns
$blockHeight = 50;
$rowHeight   = 8;

// Prepared / Requested by
$pdf->Rect(10, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10, $yStart+5);
$pdf->MultiCell($colWidth, 7, "Prepared by:", 0, 'L');
$pdf->SetX(10);
$pdf->MultiCell($colWidth, 7, "\nNAME OF REQUESTING OFFICIAL / PERSONNEL\nPosition/Designation\n", 0, 'C');
$pdf->SetX(10);
$pdf->MultiCell($colWidth, 7, "Date Signed: ___________", 0, 'L');

// Reviewed / Approved by
$pdf->Rect(10+$colWidth, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10+$colWidth, $yStart+5);
$pdf->MultiCell($colWidth, 7, "Reviewed and Approved by:", 0, 'L');
$pdf->SetX(10+$colWidth);
$pdf->MultiCell($colWidth, 7, "\nNAME\nDirector for ICT Services/\nVice Chancellor for Development and External Affairs\n", 0, 'C');
$pdf->SetX(10+$colWidth);
$pdf->MultiCell($colWidth, 7, "Date Signed: ___________", 0, 'L');

// Move cursor below signature blocks
$pdf->SetY($yStart+$blockHeight);

// --- Remarks ---
$pdf->Cell(30, $rowHeight*2, 'Remarks:', 1, 0);
$pdf->Cell($fullWidth-30, $rowHeight*2, '', 1, 1);

$pdf->Ln(5);

// Required Attachments
$pdf->SetFont('Arial','I',10);
$pdf->MultiCell(0, 5, 'Required Attachments: PDF format of the requested file/s to be posted shall be sent thru the email address of ICT Services-Central/ ICT Services-Constituent Campus.');

// Tracking Number
$pdf->Ln(15);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0, 8, 'Tracking No.: ____________________', 0, 1, 'L');

$pdf->Output('I', 'Website_Posting_Request.pdf');

?>