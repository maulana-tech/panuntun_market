<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Mengambil parameter filter dari URL
$report_type = $_GET['report_type'] ?? 'cash_flow_summary'; // Laporan default
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Hari pertama bulan ini
$end_date = $_GET['end_date'] ?? date('Y-m-t');   // Hari terakhir bulan ini
$print_mode = isset($_GET['print']) && $_GET['print'] === '1';

// Validasi rentang tanggal
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Daftar jenis laporan yang tersedia (label disesuaikan agar lebih jelas)
$report_types = [
    'cash_flow_summary' => 'Ringkasan Arus Kas',
    'cash_inflow'       => 'Laporan Kas Masuk (Penjualan)',
    'cash_outflow'      => 'Laporan Kas Keluar (Pembelian)',
    'journal'           => 'Jurnal Umum Transaksi',
    'inventory'         => 'Laporan Inventaris',
    'suppliers'         => 'Laporan Pemasok (Supplier)',
    'users'             => 'Laporan Pengguna'
];

// Hanya muat header jika bukan mode cetak
if (!$print_mode) {
    $pageTitle = 'Laporan';
    include dirname(__DIR__) . '/components/header.php';
}
?>

<?php if (!$print_mode): ?>
    <div class="fade-in">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="md:flex md:items-center md:justify-between mb-6">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Laporan
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Hasilkan dan lihat laporan bisnis yang komprehensif.
                    </p>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Filter Laporan</h3>
                </div>
                <div class="p-6">
                    <form method="GET" action="reports.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="report_type" class="block text-sm font-medium text-gray-700 mb-2">Jenis Laporan</label>
                            <select name="report_type" id="report_type" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <?php foreach ($report_types as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $report_type === $key ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Selesai</label>
                            <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Tampilkan
                            </button>
                            <a href="reports/generate_pdf.php?report_type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2" title="Cetak ke PDF">
                                🖨️ Cetak
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg" id="report-content">
<?php endif; // Akhir dari blok !print_mode ?>

<?php
// Memuat file laporan yang sesuai berdasarkan pilihan user
$report_file = __DIR__ . "/reports/{$report_type}.php";

if (file_exists($report_file)) {
    // Memasukkan konten dari file laporan yang dipilih (misal: pages/reports/inventory.php)
    include $report_file;
} else {
    // Tampilkan pesan jika file laporan tidak ada
    echo "<div class=\"p-6 text-center text-gray-500\">Jenis laporan tidak ditemukan.</div>";
}
?>

<?php if (!$print_mode): ?>
            </div> </div>
    </div>
<?php endif; // Akhir dari blok !print_mode ?>

<?php 
// Hanya muat footer jika bukan mode cetak
if (!$print_mode) {
    // Menambahkan JavaScript untuk auto-submit form saat jenis laporan diganti
    $additionalJS = "
    <script>
    document.getElementById('report_type').addEventListener('change', function() {
        this.form.submit();
    });
    </script>
    ";
    include dirname(__DIR__) . '/components/footer.php';
}
?>