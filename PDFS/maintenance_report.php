<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/fpdf/fpdf.php';

class PDF extends FPDF {
    public $headers = ['Equipment','Type','Department','Status','Start Date','End Date','Task/Description'];
    public $widths;

    function __construct($orientation='L',$unit='mm',$size='A4') {
        parent::__construct($orientation,$unit,$size);
        $usableWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        $ratios = [0.20,0.08,0.15,0.12,0.12,0.12,0.21];
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
        $this->Cell(0,12,'MAINTENANCE & STATUS REPORT',1,1,'C');

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

// ✅ Use LEFT JOIN so equipment always shows
$queries = [
    "SELECT e.asset_tag AS equip, 'Desktop' AS type, e.department_office AS department,
            mr.status, mr.start_date, mr.end_date, mr.description
     FROM desktop e
     LEFT JOIN maintenance_records mr ON mr.equipment_id=e.id AND mr.equipment_type='desktop'",

    "SELECT e.asset_tag AS equip, 'Laptop' AS type, e.department AS department,
            mr.status, mr.start_date, mr.end_date, mr.description
     FROM laptops e
     LEFT JOIN maintenance_records mr ON mr.equipment_id=e.id AND mr.equipment_type='laptop'",

    "SELECT e.asset_tag AS equip, 'Printer' AS type, e.department AS department,
            mr.status, mr.start_date, mr.end_date, mr.description
     FROM printers e
     LEFT JOIN maintenance_records mr ON mr.equipment_id=e.id AND mr.equipment_type='printer'",

    "SELECT e.asset_tag AS equip, 'Switch' AS type, e.department AS department,
            mr.status, mr.start_date, mr.end_date, mr.description
     FROM switch e
     LEFT JOIN maintenance_records mr ON mr.equipment_id=e.id AND mr.equipment_type='switch'",

    "SELECT e.asset_tag AS equip, 'Telephone' AS type, e.department AS department,
            mr.status, mr.start_date, mr.end_date, mr.description
     FROM telephone e
     LEFT JOIN maintenance_records mr ON mr.equipment_id=e.id AND mr.equipment_type='telephone'"
];

foreach($queries as $sql){
    $result = $conn->query($sql);
    while($row=$result->fetch_assoc()){
        $pdf->Cell($widths[0],8,$row['equip'] ?: '-',1,0,'L');
        $pdf->Cell($widths[1],8,$row['type'],1,0,'C');
        $pdf->Cell($widths[2],8,$row['department'] ?: '-',1,0,'C');
        $pdf->Cell($widths[3],8,$row['status'] ?: 'No Record',1,0,'C');
        $pdf->Cell($widths[4],8,$row['start_date'] ?: '-',1,0,'C');
        $pdf->Cell($widths[5],8,$row['end_date'] ?: '-',1,0,'C');
        $pdf->Cell($widths[6],8,$row['description'] ?: '-',1,1,'L');
    }
}

$pdf->Output('I','Maintenance_Status_Report.pdf');
