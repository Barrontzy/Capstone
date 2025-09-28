<?php
require_once __DIR__ . '/../includes/db.php'; 
require_once __DIR__ . '/../includes/fpdf/fpdf.php';

class PDF extends FPDF {
    public $headers = ['ID','Type','Asset Tag','Property/Equip','Department','Assigned To','Location','Cost'];
    public $widths;

    function __construct($orientation='L', $unit='mm', $size='A4') {
        parent::__construct($orientation, $unit, $size);

        // Get usable width (A4 Landscape = 297mm - margins)
        $usableWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // Define relative proportions (must add up to 1.0)
        $ratios = [0.05, 0.09, 0.15, 0.15, 0.12, 0.15, 0.19, 0.10];

        // Scale to page width
        $this->widths = array_map(function($r) use ($usableWidth) {
            return $r * $usableWidth;
        }, $ratios);
    }

    function Header() {
        // --- Logo ---
        $logoPath = __DIR__ . '/../assets/logo/bsutneu.png';
        if (file_exists($logoPath)) {
            $this->Cell(25, 20, '', 1, 0, 'C');
            $this->Image($logoPath, $this->GetX() - 24, $this->GetY(), 23, 20);
        } else {
            $this->Cell(25, 20, 'NO LOGO', 1, 0, 'C');
        }

        // --- Reference Info ---
        $this->SetFont('Arial','',9);
        $this->Cell(120, 20, 'Reference No.: BatStateU-FO-ICT-06', 1, 0, 'L');
        $this->Cell(107, 20, 'Eff. Date: Jan 23, 2023', 1, 0, 'L');
        $this->Cell(25, 20, 'Rev. No.: 00', 1, 1, 'L');

        // --- Title ---
        $this->SetFont('Arial','B',14);
        $this->Cell(0, 12, 'COMPLETE INVENTORY REPORT', 1, 1, 'C');

        $this->SetFont('Arial','',12);
        $this->Cell(0, 12, 'INFORMATION AND COMMUNICATIONS TECHNOLOGY SERVICES', 1, 1, 'C');

        // --- Table Header ---
        $this->SetFont('Arial','B',9);
        foreach ($this->headers as $i => $h) {
            $this->Cell($this->widths[$i], 10, $h, 1, 0, 'C');
        }
        $this->Ln();
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

// --- PDF Start ---
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',8);

$widths = $pdf->widths;
$grandTotal = 0;

// Queries
$queries = [
    "SELECT id, 'Desktop' as type, asset_tag, property_equipment, department_office as department, assigned_person, location, unit_price FROM desktop",
    "SELECT id, 'Laptop' as type, asset_tag, property_equipment, department, assigned_person, location, unit_price FROM laptops",
    "SELECT id, 'Printer' as type, asset_tag, property_equipment, department, assigned_person, location, unit_price FROM printers",
    "SELECT id, 'Switch' as type, asset_tag, property_equipment, department, assigned_person, location, unit_price FROM switch",
    "SELECT id, 'Telephone' as type, asset_tag, property_equipment, department, assigned_person, location, unit_price FROM telephone"
];

foreach ($queries as $sql) {
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell($widths[0], 8, $row['id'], 1, 0, 'C');
        $pdf->Cell($widths[1], 8, $row['type'], 1, 0, 'C');
        $pdf->Cell($widths[2], 8, $row['asset_tag'], 1, 0, 'C');
        $pdf->Cell($widths[3], 8, $row['property_equipment'], 1, 0, 'C');
        $pdf->Cell($widths[4], 8, $row['department'], 1, 0, 'C');
        $pdf->Cell($widths[5], 8, $row['assigned_person'], 1, 0, 'C');
        $pdf->Cell($widths[6], 8, $row['location'], 1, 0, 'C');

        $cost = $row['unit_price'] ? (float)$row['unit_price'] : 0;
        $grandTotal += $cost;
        $pdf->Cell($widths[7], 8, $cost ? number_format($cost, 2) : '-', 1, 1, 'R');
    }
}

// Grand Total
$pdf->SetFont('Arial','B',9);
$pdf->Cell(array_sum($widths) - $widths[7], 10, 'GRAND TOTAL', 1, 0, 'R');
$pdf->Cell($widths[7], 10, chr(8369) . number_format($grandTotal, 2), 1, 1, 'R');

$pdf->Output('I', 'Complete_Inventory_Report.pdf');
