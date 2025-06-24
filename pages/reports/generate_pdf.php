<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
    require_once dirname(__DIR__, 2) . '/includes/functions.php';
    require_once dirname(__DIR__,2) . '/vendor/setasign/fpdf/fpdf.php';

$database = new Database();
$db = $database->getConnection();

$report_type = $_GET['report_type'] ?? 'summary';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Validate date range
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

$report_types = [
    'summary' => 'Cash Flow Summary',
    'cash_inflow' => 'Cash Inflow Report',
    'cash_outflow' => 'Cash Outflow Report',
    'journal' => 'Journal Report',
    'inventory' => 'Inventory Report',
    'suppliers' => 'Supplier Report',
    'users' => 'User Report'
];

$reportTitle = $report_types[$report_type] ?? 'Report';

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        global $reportTitle, $start_date, $end_date;
        $this->SetFont('Arial','B',15);
        $this->Cell(0,10,'Cash Flow Management System - Minimarket Panuntun',0,1,'C');
        $this->SetFont('Arial','',12);
        $this->Cell(0,10,$reportTitle,0,1,'C');
        $this->Cell(0,10,'Period: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)),0,1,'C');
        $this->Ln(10);
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
        $this->Cell(0,10,'Generated on: ' . date('d/m/Y H:i:s'),0,0,'R');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

// Include the appropriate report content
$report_file = __DIR__ . "/{$report_type}.php";
if (file_exists($report_file)) {
    // Capture output of the report file
    ob_start();
    include $report_file;
    $reportContent = ob_get_clean();

    // Convert HTML content to PDF (basic conversion, might need more advanced library for complex HTML)
    $pdf->MultiCell(0, 5, strip_tags($reportContent));

} else {
    $pdf->Cell(0,10,'Report not found.',0,1);
}

$pdf->Output('D', "{$report_type}_report.pdf");


