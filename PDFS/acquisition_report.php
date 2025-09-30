<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/fpdf/fpdf.php';

class PDF extends FPDF {
    public $headers = ['Date Acquired','Type','Asset Tag','Department','Assigned To','Location','Cost'];
    public $widths;

    function __construct($orientation='L',$unit='mm',$size='A4') {
        parent::__construct($orientation,$unit,$size);
        $usableWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        $ratios = [0.15,0.10,0.20,0.15,0.15,0.15,0.10];
        $this->widths = array_map(fn($r)=>$r*$usableWidth,$ratios);
    }

    function Header() {
        $logoPath = __DIR__ . '/../assets/logo/bsutneu.png';
        if(file_exists($logoPath)){
            $this->Cell(25,20,'',1,0,'C');
            $this->Image($logoPath,$this->GetX()-24,$this->GetY(),23,20);
        } else {
            $this->Cell(25,20,'NO LOGO',1,0,'C');
        }

        $this->SetFont('Arial','',9);
        $this->Cell(120,20,'Reference No.: BatStateU-FO-ICT-06',1,0,'L');
        $this->Cell(107,20,'Eff. Date: Jan 23, 2023',1,0,'L');
        $this->Cell(25,20,'Rev. No.: 00',1,1,'L');

        $this->SetFont('Arial','B',14);
        $this->Cell(0,12,'ACQUISITION TIMELINE REPORT',1,1,'C');

        $this->SetFont('Arial','',12);
        $this->Cell(0,12,'INFORMATION AND COMMUNICATIONS TECHNOLOGY SERVICES',1,1,'C');

        $this->SetFont('Arial','B',9);
        foreach($this->headers as $i=>$h){
            $this->Cell($this->widths[$i],10,$h,1,0,'C');
        }
        $this->Ln();
    }

    function Footer(){
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',8);

$widths = $pdf->widths;
$grandTotal = 0;

// 🟢 Corrected: use date_acquired instead of acquisition_date
$sql = "
    SELECT date_acquired, 'Desktop' AS type, asset_tag, department_office AS department, assigned_person, location, unit_price
    FROM desktop
    UNION ALL
    SELECT date_acquired, 'Laptop' AS type, asset_tag, department, assigned_person, location, unit_price
    FROM laptops
    UNION ALL
    SELECT date_acquired, 'Printer' AS type, asset_tag, department, assigned_person, location, unit_price
    FROM printers
    UNION ALL
    SELECT date_acquired, 'Switch' AS type, asset_tag, department, assigned_person, location, unit_price
    FROM switch
    UNION ALL
    SELECT date_acquired, 'Telephone' AS type, asset_tag, department, assigned_person, location, unit_price
    FROM telephone
    ORDER BY date_acquired ASC
";

$result = $conn->query($sql);

while($row = $result->fetch_assoc()){
    $cost = (float)($row['unit_price'] ?? 0);
    $grandTotal += $cost;

    $pdf->Cell($widths[0],8,$row['date_acquired'] ?: '-',1,0,'C');
    $pdf->Cell($widths[1],8,$row['type'],1,0,'C');
    $pdf->Cell($widths[2],8,$row['asset_tag'],1,0,'L');
    $pdf->Cell($widths[3],8,$row['department'] ?: '-',1,0,'C');
    $pdf->Cell($widths[4],8,$row['assigned_person'] ?: '-',1,0,'C');
    $pdf->Cell($widths[5],8,$row['location'] ?: '-',1,0,'C');
    $pdf->Cell($widths[6],8,$cost ? number_format($cost,2) : '-',1,1,'R');
}

// 🟢 Grand Total
$pdf->SetFont('Arial','B',9);
$pdf->Cell(array_sum($widths)-$widths[6],10,'GRAND TOTAL',1,0,'R');
$pdf->Cell($widths[6],10,number_format($grandTotal,2),1,1,'R');

$pdf->Output('I','Acquisition_Timeline_Report.pdf');
