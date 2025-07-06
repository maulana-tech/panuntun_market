<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Mengambil parameter filter dari URL
$report_type = $_GET['report_type'] ?? 'cash_flow_summary';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$print_mode = isset($_GET['print']) && $_GET['print'] === '1';

// Validasi rentang tanggal
if (strtotime($start_date) > strtotime($end_date)) {
    list($start_date, $end_date) = [$end_date, $start_date];
}

// Daftar jenis laporan yang tersedia
$report_types = [
    'cash_flow_summary' => 'Ringkasan Arus Kas',
    'cash_inflow'       => 'Laporan Kas Masuk (Penjualan)',
    'cash_outflow'      => 'Laporan Kas Keluar (Pembelian)',
    'journal'           => 'Jurnal Umum Transaksi',
    'users'             => 'Laporan Daftar Pengguna',
    'inventory'         => 'Laporan Daftar Barang',
    "suppliers"         => 'Laporan Daftar Pemasok',
];

if (!$print_mode) {
    $pageTitle = 'Laporan';
    include dirname(__DIR__) . '/components/header.php';
}
?>

<style>
    .filter-card {
        background-color: white;
        border-radius: 1.25rem;
        padding: 2.5rem; /* Padding lebih lega */
        box-shadow: 0 8px 40px rgba(0,0,0,0.05);
    }
    .filter-group-title {
        font-size: 1.125rem; /* 18px */
        font-weight: 600;
        color: #1e293b; /* slate-800 */
        margin-bottom: 1.5rem;
    }
    .form-label {
        display: block;
        font-size: 0.875rem; /* 14px */
        font-weight: 500;
        color: #475569; /* slate-600 */
        margin-bottom: 0.5rem;
    }
    .input-wrapper {
        position: relative;
    }
    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        padding-left: 2.75rem; /* Ruang untuk ikon */
        border: 1px solid #cbd5e1; /* slate-300 */
        border-radius: 0.5rem;
        background-color: white;
        color: #1e293b;
        transition: all 0.2s ease;
    }
    .form-input:focus {
        outline: 2px solid transparent;
        outline-offset: 2px;
        border-color: #4f46e5; /* indigo-600 */
        box-shadow: 0 0 0 3px rgba(199, 210, 254, 0.5); /* indigo-200 with opacity */
    }
    .input-icon {
        position: absolute;
        left: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8; /* slate-400 */
        pointer-events: none;
    }
    /* Menyembunyikan ikon kalender default pada Chrome/Safari */
    input[type="date"]::-webkit-calendar-picker-indicator {
        background: transparent;
        bottom: 0;
        color: transparent;
        cursor: pointer;
        height: auto;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        width: auto;
    }
    .btn-primary {
        background-color: #4f46e5; /* indigo-600 */
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.2s ease;
        box-shadow: 0 4px 15px -5px rgba(79, 70, 229, 0.6);
    }
    .btn-primary:hover { background-color: #4338ca; /* indigo-700 */ transform: translateY(-2px); }
    .btn-secondary {
        background-color: transparent;
        color: #475569; /* slate-600 */
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .btn-secondary:hover { background-color: #f1f5f9; /* slate-100 */ color: #1e293b; }
</style>

<?php if (!$print_mode): ?>
<div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <header class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Pusat Laporan</h1>
        <p class="mt-2 text-lg text-gray-500">Analisis dan ekspor data bisnis Anda.</p>
    </header>

    <div class="filter-card mb-8">
        <form method="GET" action="reports.php">
            <h3 class="filter-group-title">Filter Laporan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-6">
                <div class="lg:col-span-2">
                    <label for="report_type" class="form-label">Jenis Laporan</label>
                    <div class="input-wrapper">
                        <svg class="input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"></path></svg>
                        <select name="report_type" id="report_type" class="form-input">
                            <?php foreach ($report_types as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $report_type === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <div class="input-wrapper">
                        <svg class="input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18"></path></svg>
                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>" class="form-input">
                    </div>
                </div>
                <div>
                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                    <div class="input-wrapper">
                        <svg class="input-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18"></path></svg>
                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>" class="form-input">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-100">
                <a href="reports.php" class="btn-secondary">Reset Filter</a>
                <a href="reports/generate_pdf.php?report_type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" target="_blank" class="btn-secondary">Cetak PDF</a>
                <button type="submit" class="btn-primary">Tampilkan Laporan</button>
            </div>
        </form>
    </div>

    <div class="bg-white shadow-lg rounded-2xl" id="report-content">
<?php endif; ?>

<?php
$report_file = __DIR__ . "/reports/{$report_type}.php";
if (file_exists($report_file)) { include $report_file; } 
else { echo "<div class=\"p-10 text-center text-gray-500\">Jenis laporan tidak valid.</div>"; }
?>

<?php if (!$print_mode): ?>
    </div> </div> <?php endif; ?>

<?php 
if (!$print_mode) {
    $additionalJS = "<script>document.getElementById('report_type').addEventListener('change', function() { this.form.submit(); });</script>";
    include dirname(__DIR__) . '/components/footer.php';
}
?>