<?php
require_once __DIR__ . '/../../includes/fpdf/fpdf.php';


require_once '../../includes/session.php';
require_once '../../includes/db.php';

	include '../../logger.php';
	logAdminAction($_SESSION['user_id'], $_SESSION['user_name'], "Generated Report", "REQUEST FOR POSTING OF ANNOUNCEMENTS / GREETINGS");
class PDF extends FPDF {
    function Header() {
        $logoPath = __DIR__ . '/../../assets/logo/bsutneu.png';
        if(file_exists($logoPath)){
            $this->Cell(25, 20, '', 1, 0, 'C');
            $this->Image($logoPath, $this->GetX()-24, $this->GetY(), 23, 20);
        }
        $this->SetFont('Arial','',9);
        $this->Cell(95,20,'Reference No.: BatStateU-FO-CSD-01',1,0,'C');
        $this->Cell(40,20,'Effectivity Date: January 23, 2023',1,0,'C');
        $this->Cell(30,20,'Revision No.: 00',1,1,'C');

        $this->SetFont('Arial','B',11);
        $this->Cell(190,10,'REQUEST FOR POSTING OF ANNOUNCEMENTS / GREETINGS',1,1,'C');
    }

    function DrawCheckbox($label,$checked=false) {
        $x=$this->GetX(); $y=$this->GetY();
        $this->Rect($x,$y,5,5);
        if($checked){ $this->Text($x+1.5,$y+4,'X'); }
        $this->SetXY($x+7,$y-1);
        $this->Cell(50,7,$label,0,1,'L');
    }
}

$pdf=new PDF('P','mm','Legal');
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

// --- College/Office ---
$pdf->Cell(30,7,"College / Office:",1,0);
$pdf->Cell(160,7,$_POST['college_office'] ?? '',1,1);

// --- Purpose ---
$pdf->Cell(30,20,"Purpose:",1,0);
$pdf->MultiCell(160,20,$_POST['purpose'] ?? '',1);

// --- Means of Posting ---
$means=$_POST['means'] ?? [];
$pdf->Cell(30,40,"Means of Posting:",1,0);
$x=$pdf->GetX(); $y=$pdf->GetY();
$pdf->Cell(160,40,'',1,1); // draw right container
$pdf->SetXY($x,$y);

$pdf->SetX(40);
$pdf->DrawCheckbox("Bulletin Board",in_array("Bulletin Board",$means));
$pdf->SetX(40);
$pdf->DrawCheckbox("View Board",in_array("View Board",$means));
$pdf->SetX(40);
$pdf->DrawCheckbox("LED Board",in_array("LED Board",$means));
$pdf->SetX(40);
$pdf->DrawCheckbox("Social Media",in_array("Social Media",$means));

$pdf->Ln(2);
$pdf->SetX(40);
$pdf->Cell(0,5,"Indicate Specific Location / Media Site: ".($_POST['location'] ?? ''),0,1);
$pdf->SetX(40);
$pdf->MultiCell(150,5,$_POST['media_notes'] ?? '',0);

// --- Brief Content ---
$pdf->Cell(30,40,"Brief Content and Layout:",1,0);
$pdf->MultiCell(160,40,$_POST['content'] ?? '',1);

// --- Posting Period ---
$pdf->Cell(30,7,"Posting Period: (Maximum 30 days)",1,0);
$pdf->Cell(160,7,$_POST['period'] ?? '',1,1);

// --- Requested + Recommended (side by side) ---
$h = 35;
$y=$pdf->GetY();

// Draw two boxes
$pdf->Cell(95,$h,'',1,0);
$pdf->Cell(95,$h,'',1,1);

// Requested By
$pdf->SetXY(10,$y+2);
$pdf->SetFont('Arial','',9);
$pdf->Cell(90,5,"Requested by:",0,2,'L');
$pdf->SetFont('Arial','B',9);
$pdf->MultiCell(90,5,($_POST['requested_by'] ?? 'NAME OF HEAD OF OFFICE/UNIT')."\n".($_POST['requested_designation'] ?? 'Position/Designation'),0,'C');
$pdf->SetFont('Arial','',9);
$pdf->Cell(90,5,"Date Signed: ____________________",0,2,'L');

// Recommended Approval
$pdf->SetXY(105,$y+2);
$pdf->SetFont('Arial','',9);
$pdf->Cell(90,5,"Recommending Approval:",0,2,'L');
$pdf->SetFont('Arial','B',9);
$pdf->MultiCell(90,5,($_POST['recommended_by'] ?? 'Engr. JONNAH R. MELO')."\n".($_POST['recommended_designation'] ?? 'Head, ICT Services'),0,'C');
$pdf->SetFont('Arial','',9);
$pdf->Cell(90,5,"Date Signed: ____________________",0,2,'L');

// --- Approved + Remarks (side by side) ---
$y=$pdf->GetY();
$pdf->Cell(95,$h,'',1,0);
$pdf->Cell(95,$h,'',1,1);

// Approved By
$pdf->SetXY(10,$y+2);
$pdf->SetFont('Arial','',9);
$pdf->Cell(90,27,"Approved by:",0,2,'L');
$pdf->SetFont('Arial','B',9);
$pdf->MultiCell(90,0,($_POST['approved_by'] ?? 'Atty. ALVIN R. DE SILVA')."\n".($_POST['approved_designation'] ?? 'Chancellor'),0,'C');
$pdf->SetFont('Arial','',9);
$pdf->Cell(90,7,"Date Signed: ____________________",0,2,'L');

// Remarks
$pdf->SetXY(105,$y+2);
$pdf->SetFont('Arial','',9);
$pdf->Cell(90,5,"Remarks:",0,2,'L');
$pdf->MultiCell(90,5,($_POST['remarks'] ?? ''),0,'L');

// --- Note + Tracking ---
$pdf->SetFont('Arial','I',7);
$pdf->MultiCell(210,50,"Note: It is understood that the posting shall be removed after the approved duration.",0,'L');
$pdf->SetFont('Arial','',9);
$pdf->Cell(190,7,"Tracking Number: ___________________",0,1,'R');

$pdf->Output('I',"PostingRequestForm.pdf");
