<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// Memuat autoloader Composer dan functions.php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// --- Definisi Class PDF ---
class PDF extends FPDF
{
    private $reportTitle;
    private $period;

    public function setReportDetails($title, $period) {
        $this->reportTitle = $title;
        $this->period = $period;
    }

    // Header Halaman
    public function Header()
    {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10, 'Laporan ' . APP_NAME . 'Minimarket Panuntun', 0, 1, 'C');
        $this->SetFont('Arial','B',12);
        $this->Cell(0,8, $this->reportTitle, 0, 1, 'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,6, $this->period, 0, 1, 'C');
        $this->Ln(5);
    }

    // Footer Halaman
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Halaman '.$this->PageNo().'/{nb}',0,0,'C');
        $this->Cell(0,10,'Dibuat pada: ' . date('d/m/Y H:i:s'),0,0,'R');
    }

    // --- Fungsi Tabel Generik ---
    public function BasicTable($header, $data, $widths, $aligns)
    {
        // Header
        $this->SetFont('Arial','B',9);
        $this->SetFillColor(230, 230, 230);
        foreach($header as $i => $col) {
            $this->Cell($widths[$i], 7, $col, 1, 0, 'C', true);
        }
        $this->Ln();

        // Data
        $this->SetFont('Arial','',9);
        foreach($data as $row) {
            foreach($row as $i => $cell) {
                $this->Cell($widths[$i], 6, $cell, 'LR', 0, $aligns[$i]);
            }
            $this->Ln();
        }
        // Garis penutup
        $this->Cell(array_sum($widths), 0, '', 'T');
    }
    
    // --- Tabel Khusus untuk Laporan Jurnal Umum ---
    public function JournalTable($header, $data, $totals)
    {
        $widths = [30, 85, 35, 35];
        $this->BasicTable($header, $data, $widths, ['L', 'L', 'R', 'R']);
        $this->Ln();

        // Baris Total
        $this->SetFont('Arial','B',9);
        $this->SetFillColor(230, 230, 230);
        $this->Cell($widths[0] + $widths[1], 7, 'TOTAL', 1, 0, 'R', true);
        $this->Cell($widths[2], 7, formatCurrency($totals['debit']), 1, 0, 'R', true);
        $this->Cell($widths[3], 7, formatCurrency($totals['kredit']), 1, 1, 'R', true);
    }
}

// --- Logika Utama ---
$database = new Database();
$db = $database->getConnection();

// Mengambil parameter dari URL
$report_type = $_GET['report_type'] ?? 'cash_inflow';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Validasi rentang tanggal
if (strtotime($start_date) > strtotime($end_date)) {
    list($start_date, $end_date) = [$end_date, $start_date];
}

// Daftar laporan yang tersedia
$report_types = [
    'cash_inflow'       => 'Laporan Kas Masuk (Penjualan)',
    'cash_outflow'      => 'Laporan Kas Keluar (Pembelian)',
    'journal'           => 'Jurnal Umum Transaksi',
    'cash_flow_summary' => 'Laporan Kas',
    'users'             => 'Laporan Daftar Pengguna',
    'inventory'         => 'Laporan Daftar Barang',
    "suppliers"         => 'Laporan Daftar Pemasok',
];

$reportTitle = $report_types[$report_type] ?? 'Laporan';
$period = 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));

// Inisialisasi PDF
$pdf = new PDF();
$pdf->setReportDetails($reportTitle, $period);
$pdf->AliasNbPages();
$pdf->AddPage('P', 'A4');

// --- Switch untuk Menangani Setiap Jenis Laporan ---
switch ($report_type) {
    case 'cash_inflow':
        $stmt = $db->prepare("SELECT p.tgl_jual, p.nama_barang, p.qty, p.harga, p.total_penjualan FROM penjualan p WHERE DATE(p.tgl_jual) BETWEEN ? AND ? ORDER BY p.tgl_jual ASC");
        $stmt->execute([$start_date, $end_date]);
        $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $header = ['Tanggal', 'Keterangan', 'Jml', 'Harga', 'Total'];
        $data = array_map(fn($row) => [
            formatDate($row['tgl_jual']),
            'Penjualan ' . htmlspecialchars_decode($row['nama_barang']),
            $row['qty'],
            formatCurrency($row['harga']),
            formatCurrency($row['total_penjualan'])
        ], $rawData);
        
        $pdf->BasicTable($header, $data, [30, 70, 20, 35, 35], ['L', 'L', 'C', 'R', 'R']);
        break;

    case 'cash_outflow':
        $stmt = $db->prepare("SELECT p.tgl_beli, p.id_pembelian, p.nama_barang, CONCAT('Pembelian dari ', s.nama_supplier) as keterangan, p.total_pembelian FROM pembelian p JOIN supplier s ON p.id_supplier = s.id_supplier WHERE DATE(p.tgl_beli) BETWEEN ? AND ? ORDER BY p.tgl_beli ASC");
        $stmt->execute([$start_date, $end_date]);
        $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['Tanggal', 'ID Pembelian', 'Nama Barang', 'Keterangan', 'Total'];
        $data = array_map(fn($row) => [
            formatDate($row['tgl_beli']),
            'PEM-' . str_pad($row['id_pembelian'], 4, '0', STR_PAD_LEFT),
            htmlspecialchars_decode($row['nama_barang']),
            htmlspecialchars_decode($row['keterangan']),
            formatCurrency($row['total_pembelian'])
        ], $rawData);
        
        $pdf->BasicTable($header, $data, [25, 25, 45, 60, 35], ['L', 'L', 'L', 'L', 'R']);
        break;

    case 'journal':
        $stmt = $db->prepare("(SELECT 'Sale' as type, tgl_jual as date, total_penjualan as amount FROM penjualan WHERE DATE(tgl_jual) BETWEEN ? AND ?) UNION ALL (SELECT 'Purchase' as type, tgl_beli as date, total_pembelian as amount FROM pembelian WHERE DATE(tgl_beli) BETWEEN ? AND ?) ORDER BY date ASC");
        $stmt->execute([$start_date, $end_date, $start_date, $end_date]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $journal_entries = [];
        $totals = ['debit' => 0, 'kredit' => 0];
        foreach ($entries as $entry) {
            $amount = floatval($entry['amount']);
            if ($entry['type'] === 'Sale') {
                $journal_entries[] = ['date' => $entry['date'], 'keterangan' => 'Kas', 'debit' => $amount, 'kredit' => 0];
                $journal_entries[] = ['date' => null, 'keterangan' => '   Pendapatan Penjualan', 'debit' => 0, 'kredit' => $amount];
            } else {
                $journal_entries[] = ['date' => $entry['date'], 'keterangan' => 'Beban/Persediaan', 'debit' => $amount, 'kredit' => 0];
                $journal_entries[] = ['date' => null, 'keterangan' => '   Kas', 'debit' => 0, 'kredit' => $amount];
            }
            $totals['debit'] += $amount;
            $totals['kredit'] += $amount;
        }

        $header = ['Tanggal', 'Keterangan', 'Debit', 'Kredit'];
        $data = array_map(fn($row) => [
            $row['date'] ? date('d/m/Y', strtotime($row['date'])) : '',
            $row['keterangan'],
            $row['debit'] > 0 ? formatCurrency($row['debit']) : '',
            $row['kredit'] > 0 ? formatCurrency($row['kredit']) : ''
        ], $journal_entries);
        
        $pdf->JournalTable($header, $data, $totals);
        break;
        
    case 'cash_flow_summary':
        $stmt = $db->prepare("SELECT id_kas, tgl_transaksi, ket_transaksi, kas_masuk, kas_keluar FROM v_cash_flow_summary WHERE DATE(tgl_transaksi) BETWEEN ? AND ? ORDER BY tgl_transaksi ASC, id_kas ASC");
        $stmt->execute([$start_date, $end_date]);
        $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['Tanggal', 'ID Kas', 'Keterangan', 'Kas Masuk', 'Kas Keluar'];
        $data = array_map(fn($row) => [
            formatDate($row['tgl_transaksi']),
            'KAS-' . str_pad($row['id_kas'], 4, '0', STR_PAD_LEFT),
            htmlspecialchars_decode($row['ket_transaksi']),
            $row['kas_masuk'] > 0 ? formatCurrency($row['kas_masuk']) : '-',
            $row['kas_keluar'] > 0 ? formatCurrency($row['kas_keluar']) : '-'
        ], $rawData);

        $pdf->BasicTable($header, $data, [25, 20, 75, 35, 35], ['L', 'L', 'L', 'R', 'R']);
        break;

    case 'users':
        $stmt = $db->query("SELECT id_pengguna, nama, jabatan, email FROM pengguna ORDER BY id_pengguna ASC");
        $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['ID Pengguna', 'Nama', 'Jabatan', 'Email'];
        $data = array_map(fn($row) => [
            'P-' . str_pad($row['id_pengguna'], 4, '0', STR_PAD_LEFT),
            htmlspecialchars_decode($row['nama']),
            $row['jabatan'],
            $row['email']
        ], $rawData);
        
        $pdf->BasicTable($header, $data, [30, 50, 30, 70], ['L', 'L', 'L', 'L']);
        break;

    case 'inventory':
        $stmt = $db->query("SELECT updated_at, kode_barang, nama_barang, stok FROM barang ORDER BY nama_barang ASC");
        $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $header = ['Tanggal Update', 'Kode Barang', 'Nama Barang', 'Stok'];
        $data = array_map(fn($row) => [
            formatDate($row['updated_at']),
            'BRG-' . str_pad($row['kode_barang'], 4, '0', STR_PAD_LEFT),
            htmlspecialchars_decode($row['nama_barang']),
            $row['stok']
        ], $rawData);

        $pdf->BasicTable($header, $data, [40, 40, 80, 20], ['L', 'L', 'L', 'C']);
        break;

    case 'suppliers':
        $stmt = $db->query("SELECT id_supplier, nama_supplier, no_tlp, alamat FROM supplier ORDER BY id_supplier ASC");
        $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['ID Supplier', 'Nama', 'No Tlp', 'Alamat'];
        $data = array_map(fn($row) => [
            'SUP-' . str_pad($row['id_supplier'], 4, '0', STR_PAD_LEFT),
            htmlspecialchars_decode($row['nama_supplier']),
            $row['no_tlp'],
            htmlspecialchars_decode($row['alamat'])
        ], $rawData);

        $pdf->BasicTable($header, $data, [30, 50, 30, 80], ['L', 'L', 'L', 'L']);
        break;

    default:
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Jenis laporan ini tidak valid.',0,1, 'C');
        break;
}

// Mengirimkan file PDF ke browser
$pdf->Output('D', str_replace(' ', '_', strtolower($reportTitle)) . "_report.pdf");