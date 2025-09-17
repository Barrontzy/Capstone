<?php
require_once __DIR__ . '/../../includes/fpdf/fpdf.php';

$items = $_POST['items'] ?? [];

class PDF extends FPDF {
    function Header() {
        // Header
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
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(190, 15, 'PREVENTIVE MAINTENANCE PLAN', 1, 1, 'C');

        // Table Header
        $this->SetFont('Arial','',11);
        $this->Cell(30, 7, 'Office/College', 1, 0, 'C');
        $this->Cell(110, 7, ' ', 1, 0, 'C');
        $this->Cell(10, 7, 'FY:', 1, 0, 'C');
        $this->Cell(40, 7, ' ', 1, 1, 'C');
        
        $this->SetFont('Arial','B',10);
        $this->Cell(40,8,'Item',1, 0,'C');
        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        foreach ($months as $m) {
            $this->Cell(12.5, 8, $m, 1, 0, 'C');
        }
        $this->Ln();
    }
}

$pdf = new PDF('P','mm','A4'); // Portrait
$pdf->AliasNbPages();
$pdf->AddPage();

// Table Rows
$pdf->SetFont('Arial','',12);
$rowHeight = 8;
$maxRows   = 13; // adjust to fill page
$rowsPrinted = 0;

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

foreach ($items as $item) {
    $pdf->Cell(40,$rowHeight,$item['description'],1,0);

    foreach ($months as $m) {
        $mark = ($item['schedule'] == 'M') ? 'M'
              : ($item['schedule'] == 'Q' && in_array($m,['Mar','Jun','Sep','Dec']) ? 'Q'
              : ($item['schedule'] == 'SA' && in_array($m,['Jun','Dec']) ? 'SA' : ''));
        $pdf->Cell(12.5,$rowHeight,$mark,1,0,'C');
    }
    $pdf->Ln();
    $rowsPrinted++;
}

// Blank rows until table is full
while ($rowsPrinted < $maxRows) {
    $pdf->Cell(40,$rowHeight,'',1,0);
    foreach ($months as $m) {
        $pdf->Cell(12.5,$rowHeight,'',1,0,'C');
    }
    $pdf->Ln();
    $rowsPrinted++;
}

// Legend
$pdf->SetFont('Arial','I',12);
$pdf->Cell(0,7,'Legend: M = Monthly, Q = Quarterly, SA = Semi-Annually',1,1,'C');

// --- Signatories Section ---
$pdf->SetFont('Arial','',9);
$pageWidth   = $pdf->GetPageWidth() - 20;
$colWidth    = $pageWidth / 2;
$blockHeight = 50; 
$yStart      = $pdf->GetY();

// --- Prepared By ---
$pdf->Rect(10, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10, $yStart);
$pdf->MultiCell($colWidth, 6, "Prepared By:", 0, 'L');
$pdf->SetXY(10, $pdf->GetY());
$pdf->MultiCell($colWidth, 6, "____________________\nAssistant Director, General Services/Head, GSO\nICT Services Staff/\nHead, Project and Facility Management/\nHealth Services Staff/\nLaboratory Technician", 0, 'C');
$pdf->SetXY(10, $pdf->GetY());
$pdf->MultiCell($colWidth, 6, "Date Signed: ________________", 0, 'L');

// --- Reviewed By ---
$pdf->Rect(10+$colWidth, $yStart, $colWidth, $blockHeight);
$pdf->SetXY(10+$colWidth, $yStart);
$pdf->MultiCell($colWidth, 6, "Reviewed By:", 0, 'L');
$pdf->SetXY(10+$colWidth, $pdf->GetY());
$pdf->MultiCell($colWidth, 6, "____________________\nDirector, Administration Services\nDirector, ICT Services/Head, ICT Services\nVice Chancellor for Administration and Finance\nHead, Health Services/\nDean/Head, Academic Affairs", 0, 'C');
$pdf->SetXY(10+$colWidth, $pdf->GetY());
$pdf->MultiCell($colWidth, 6, "Date Signed: ________________", 0, 'L');

// --- Row 2 ---
$yStart2 = $yStart + $blockHeight;

// --- Approved By ---
$pdf->Rect(10, $yStart2, $colWidth, $blockHeight);
$pdf->SetXY(10, $yStart2);
$pdf->MultiCell($colWidth, 6, "Approved By:", 0, 'L');
$pdf->SetXY(10, $pdf->GetY());
$pdf->MultiCell($colWidth, 6, "____________________\nVice President for Administration and Finance/\nVice President for Development and External Affairs/\nVice Chancellor for Development and External Affairs/\nChancellor/\nVice Chancellor for Academic Affairs", 0, 'C');
$pdf->SetXY(10, $pdf->GetY());
$pdf->MultiCell($colWidth, 6, "Date Signed: ________________", 0, 'L');

// --- Remarks ---
$pdf->Rect(10+$colWidth, $yStart2, $colWidth, $blockHeight);
$pdf->SetXY(10+$colWidth, $yStart2+5);
$pdf->MultiCell($colWidth, 6, "Remarks:", 0, 'L');

$pdf->Output('I','Preventive_Maintenance_Plan.pdf');
?>