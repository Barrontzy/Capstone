<?php
require_once __DIR__ . '/../../includes/fpdf/fpdf.php';

$items = $_POST['items'] ?? [];

require_once '../../includes/session.php';
require_once '../../includes/db.php';

	include '../../logger.php';
	logAdminAction($_SESSION['user_id'], $_SESSION['user_name'], "Generated Report", "Preventive Maintenance of ICT-Related Equipment Index Card");

class PDF extends FPDF {
    function Header() {
        // --- Top Header ---
        $logoPath = __DIR__ . '/../../assets/logo/bsutneu.png';
        if (file_exists($logoPath)) {
            $this->Cell(25, 20, '', 1, 0, 'C');
            $this->Image($logoPath, $this->GetX() - 24, $this->GetY(), 23, 20);
        }

        $this->SetFont('Arial','',10);
        $this->Cell(80, 20, 'Reference No.: BatStateU-FO-ICT-06', 1, 0, 'C');
        $this->Cell(55, 20, 'Eff. Date: January 3, 2023', 1, 0, 'C');
        $this->Cell(30, 20, 'Rev. No.: 00', 1, 1, 'C');

        // --- Title ---
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(190, 8, 'Preventive Maintenance of ICT-Related Equipment Index Card', 1, 1, 'C');

        // --- Campus ---
        $this->SetFont('Arial', '', 10);
        $this->Cell(190, 7, 'INFORMATION AND COMMUNICATIONS TECHNOLOGY SERVICES', 1, 1, 'C');
        $this->Cell(190, 7, '{CAMPUS}', 1, 1, 'C');

        // --- Equipment No. ---
        $this->SetFont('Arial','B',11);
        $this->Cell(190, 8, 'Equipment No.:', 1, 1, 'L');

        // --- Table Header ---
        $this->SetFont('Arial','B',10);
        $this->Cell(40, 8, 'Date', 1, 0, 'C');
        $this->Cell(90, 8, 'Repair/Maintenance Task', 1, 0, 'C');
        $this->Cell(60, 8, 'Performed by:', 1, 1, 'C');
    }
}

$pdf = new PDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

$rowHeight = 20; 
$maxRows   = 10; 
$rowsPrinted = 0;

foreach ($items as $item) {

    $pdf->Cell(40, $rowHeight, $item['date'], 1, 0, 'C');
    $pdf->Cell(90, $rowHeight, $item['report'], 1, 0, 'C');
    $pdf->Cell(60, $rowHeight, $item['perform'], 1, 1, 'C');
    $rowsPrinted++;
}

while ($rowsPrinted < $maxRows) {
    $pdf->Cell(40, $rowHeight, '', 1, 0);
    $pdf->Cell(90, $rowHeight, '', 1, 0);
    $pdf->Cell(60, $rowHeight, '', 1, 1);
    $rowsPrinted++;
}

$pdf->Output('I','Preventive_Maintenance_IndexCard.pdf'); // I = inline view
