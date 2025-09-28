<?php
require_once __DIR__ . '/../../includes/fpdf/fpdf.php';

require_once '../../includes/session.php';
require_once '../../includes/db.php';

	include '../../logger.php';
	logAdminAction($_SESSION['user_id'], $_SESSION['user_name'], "Generated Report", "REQUEST FOR SYSTEM USER ACCOUNT FORM");
class PDF extends FPDF {
    function Header() {
        $logoPath = __DIR__ . '/../../assets/logo/bsutneu.png';
        if(file_exists($logoPath)){
            $this->Cell(25, 20, '', 1, 0, 'C');
            $this->Image($logoPath, $this->GetX()-24, $this->GetY(), 23, 20);
        }
        $this->SetFont('Arial','',9);
        $this->Cell(80,20,'Reference No.: BatStateU-FO-ICT-05',1,0,'C');
        $this->Cell(50,20,'Effectivity Date: May 18, 2022',1,0,'C');
        $this->Cell(35,20,'Revision No.: 02',1,1,'C');

        $this->SetFont('Arial','B',11);
        $this->Cell(190,10,'REQUEST FOR SYSTEM USER ACCOUNT FORM',1,1,'C');
    }

    function Checkbox($label,$checked=false,$w=63,$h=7) {
        $x=$this->GetX(); $y=$this->GetY();
        $this->Cell($w,$h,'',1,0);
        $this->Rect($x+2,$y+2,3,3);
        if($checked){ $this->Text($x+2.7,$y+4.7,'X'); }
        $this->SetXY($x+7,$y);
        $this->Cell($w-7,$h,$label,0,0,'L');
    }

    function FancyRow($data,$widths,$height=14,$align='C'){
        $nb=0;
        foreach($data as $i=>$txt){
            $nb=max($nb,$this->NbLines($widths[$i],$txt));
        }
        $h=$height*$nb/2;
        $x=$this->GetX(); $y=$this->GetY();
        foreach($data as $i=>$txt){
            $w=$widths[$i];
            $this->Rect($x,$y,$w,$h);
            $this->MultiCell($w,7,$txt,0,$align);
            $x+=$w;
            $this->SetXY($x,$y);
        }
        $this->Ln($h);
    }

    function NbLines($w,$txt){
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n") $nb--;
        $sep=-1; $i=0; $j=0; $l=0; $nl=1;
        while($i<$nb){
            $c=$s[$i];
            if($c=="\n"){ $i++; $sep=-1; $j=$i; $l=0; $nl++; continue; }
            if($c==' ') $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax){
                if($sep==-1){
                    if($i==$j) $i++;
                }else $i=$sep+1;
                $sep=-1; $j=$i; $l=0; $nl++;
            }else $i++;
        }
        return $nl;
    }
}

$pdf=new PDF('P','mm','Legal');
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

// --- Services ---
$pdf->Cell(190,7,"Please check the requested service:",1,1,'L');
$pdf->Checkbox("Account Creation",in_array("Account Creation",$_POST['services']??[]),63,7);
$pdf->Checkbox("Account Modification",in_array("Account Modification",$_POST['services']??[]),63,7);
$pdf->Checkbox("Account Deletion",in_array("Account Deletion",$_POST['services']??[]),64,7);
$pdf->Ln(9);

// --- Reason ---
$pdf->Cell(190,7,"Reason for Request:",1,1);
$pdf->MultiCell(190,7,$_POST['reason'] ?? '',1);

// --- Application ---
$pdf->Cell(190,7,"Name of the Application or System: ".($_POST['application'] ?? ''),1,1);

// --- Requested User Info ---
$pdf->Cell(190,7,"Requested User's Information",1,1);

// Individual
$pdf->Cell(190,7,"For Individual Employee Requests",1,1);
$pdf->SetFont('Arial','B',7);
$pdf->FancyRow(
    ["Full Name\n(Last Name, First Name M.I.)","ID No.","Username\n(This field is for account modification and deletion request only)","Position/\nDesignation","Employment\nStatus","Access Details\n(Include information such as: same access as role, specific type of access, etc.)"],
    [40,20,30,30,30,40]
);
$pdf->SetFont('Arial','',8);
foreach($_POST['individual'] ?? [] as $row){
    $pdf->Cell(40,7,$row['name'],1,0);
    $pdf->Cell(20,7,$row['id'],1,0);
    $pdf->Cell(30,7,$row['username'],1,0);
    $pdf->Cell(30,7,$row['position'],1,0);
    $pdf->Cell(30,7,$row['status'],1,0);
    $pdf->Cell(40,7,$row['access'],1,1);
}
$pdf->Cell(190,7,"",1,1);

// Department
$pdf->Cell(190,7,"For Office/Department Requests",1,1);
$pdf->SetFont('Arial','B',7);
$pdf->FancyRow(
    ["Name of Office/Department","Username\n(This field is for account modification and deletion request only)","Access Details\n(Include information such as: same access as role, specific type of access, etc.)"],
    [80,55,55]
);
$pdf->SetFont('Arial','',8);
foreach($_POST['department'] ?? [] as $row){
    $pdf->Cell(80,7,$row['office'],1,0);
    $pdf->Cell(55,7,$row['username'],1,0);
    $pdf->Cell(55,7,$row['access'],1,1);
}
$pdf->Cell(190,7,"",1,1);

// Permissions
$pdf->SetFont('Arial','I',8);
$pdf->Cell(190,5,"*Kindly use additional sheet if necessary",0,1);
$pdf->SetFont('Arial','',9);
$pdf->Cell(190,7,"Additional permissions or needs (if any):",1,1);
$pdf->MultiCell(190,7,$_POST['permissions'] ?? '',1);

// Requested/Reviewed
$pdf->SetFont('Arial','',9);
$pdf->Cell(95,20,"Requested by:\n".($_POST['requested_by'] ?? '')."\n".($_POST['requested_designation'] ?? '')."\nDate: ___________",1,0,'L');
$pdf->Cell(95,20,"Reviewed and Approved by:\n".($_POST['reviewed_by'] ?? 'Engr. JONNAH R. MELO')."\n".($_POST['reviewed_designation'] ?? 'Head, ICT Services')."\nDate: ___________",1,1,'L');

// Remarks
$pdf->Cell(190,7,"Remarks:",1,1);
$pdf->MultiCell(190,7,$_POST['remarks'] ?? '',1);

// ICT Services
$pdf->Cell(190,7,"------------------- To be completed by the ICT Services -------------------",1,1,'C');
$pdf->SetFont('Arial','B',8);
$pdf->Cell(47.5,7,"Name",1,0,'C');
$pdf->Cell(47.5,7,"Username",1,0,'C');
$pdf->Cell(47.5,7,"Default Password",1,0,'C');
$pdf->Cell(47.5,7,"Access Details",1,1,'C');
$pdf->SetFont('Arial','',8);
for($i=0;$i<3;$i++){
    $pdf->Cell(47.5,7,"",1,0);
    $pdf->Cell(47.5,7,"",1,0);
    $pdf->Cell(47.5,7,"",1,0);
    $pdf->Cell(47.5,7,"",1,1);
}

// Conforme
// --- Assigned to & Conforme (Clean Layout) ---
$pdf->SetFont('Arial','',9);

// Assigned To
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->MultiCell(95,6,"Assigned to:\n\n__________________________\nSignature over Printed Name\nDesignation: __________________\nDate: ____________",1,'L');

// Conforme (same height box as Assigned to)
$pdf->SetXY($x+95,$y);
$pdf->MultiCell(95,6,"Conforme:\n\n__________________________\nSignature over Printed Name\nDesignation: __________________\nDate: ____________",1,'L');

// Note + Tracking
$pdf->SetFont('Arial','I',7);
$pdf->MultiCell(190,5,"Note: The user is required to create a new password or change the default password given for security reasons.",0,'L');
$pdf->SetFont('Arial','',9);
$pdf->Cell(190,7,"Tracking No.: ___________________",0,1,'R');

$pdf->Output('I',"SystemUserAccountForm.pdf");
