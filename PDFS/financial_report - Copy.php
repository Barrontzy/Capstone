<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/fpdf/fpdf.php';

class PDF extends FPDF {
    public $headers = ['Department','Type','Count','Total Cost','Average Cost'];
    public $widths;

    function __construct($orientation='L',$unit='mm',$size='A4') {
        parent::__construct($orientation,$unit,$size);
        $usableWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // relative ratios (must ~1)
        $ratios = [0.30,0.15,0.10,0.22,0.23];
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
        $this->Cell(0,12,'FINANCIAL SUMMARY REPORT',1,1,'C');

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

// 🟢 Query each equipment table and UNION results
$sql = "
    SELECT department_office AS department, 'Desktop' AS type, COUNT(*) AS cnt, SUM(unit_price) AS total, AVG(unit_price) AS avgcost
    FROM desktop GROUP BY department_office
    UNION ALL
    SELECT department, 'Laptop' AS type, COUNT(*) AS cnt, SUM(unit_price), AVG(unit_price)
    FROM laptops GROUP BY department
    UNION ALL
    SELECT department, 'Printer' AS type, COUNT(*) AS cnt, SUM(unit_price), AVG(unit_price)
    FROM printers GROUP BY department
    UNION ALL
    SELECT department, 'Switch' AS type, COUNT(*) AS cnt, SUM(unit_price), AVG(unit_price)
    FROM switch GROUP BY department
    UNION ALL
    SELECT department, 'Telephone' AS type, COUNT(*) AS cnt, SUM(unit_price), AVG(unit_price)
    FROM telephone GROUP BY department
";

$result = $conn->query($sql);

while($row = $result->fetch_assoc()){
    $count = (int)$row['cnt'];
    $total = (float)$row['total'];
    $avg   = (float)$row['avgcost'];

    $grandTotal += $total;

    $pdf->Cell($widths[0],8,$row['department'] ?: '-',1,0,'L');
    $pdf->Cell($widths[1],8,$row['type'],1,0,'C');
    $pdf->Cell($widths[2],8,$count,1,0,'C');
    $pdf->Cell($widths[3],8, $total ? number_format($total,2) : '-',1,0,'R');
    $pdf->Cell($widths[4],8, $avg ? number_format($avg,2) : '-',1,1,'R');
}

// 🟢 Grand total
$pdf->SetFont('Arial','B',9);
$pdf->Cell($widths[0]+$widths[1]+$widths[2],10,'GRAND TOTAL',1,0,'R');
$pdf->Cell($widths[3],10,number_format($grandTotal,2),1,0,'R');
$pdf->Cell($widths[4],10,'-',1,1,'C');

$pdf->Output('I','Financial_Summary_Report.pdf');
