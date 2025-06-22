<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$report_type = $_GET['report_type'] ?? 'summary';
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$print_mode = isset($_GET['print']) && $_GET['print'] === '1';

// Validate date range
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Available report types
$report_types = [
    'summary' => 'Cash Flow Summary',
    'cash_inflow' => 'Cash Inflow Report',
    'cash_outflow' => 'Cash Flow Report',
    'journal' => 'Journal Report',
    'inventory' => 'Inventory Report',
    'suppliers' => 'Supplier Report',
    'users' => 'User Report'
];

// Include header only if not in print mode
if (!$print_mode) {
    $pageTitle = 'Reports';
    include dirname(__DIR__) . '/components/header.php';

}
?>

<?php if (!$print_mode): ?>
<div class="min-h-screen bg-gray-50">
    <?php include 'components/navigation.php'; ?>
    
    <div class="flex-1 flex flex-col lg:ml-64">
        <main class="flex-1">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="md:flex md:items-center md:justify-between mb-6">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Reports
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Generate and view comprehensive business reports
                            </p>
                        </div>
                    </div>

                    <!-- Report Filters -->
                    <div class="bg-white shadow rounded-lg mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Report Filters</h3>
                        </div>
                        <div class="p-6">
                            <form method="GET" action="reports.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="report_type" class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                                    <select name="report_type" id="report_type" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <?php foreach ($report_types as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $report_type === $key ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                                    <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div class="flex items-end space-x-2">
                                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        Generate Report
                                    </button>
                                    <button type="button" onclick="window.location.href=\'reports/generate_pdf.php?report_type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>\';" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                        🖨️ Print
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Report Content -->
                    <div class="bg-white shadow rounded-lg" id="report-content">
<?php endif; ?>

<?php
ob_start(); // Start output buffering
// Include the appropriate report module
$report_file = "reports/{$report_type}.php";
if (file_exists($report_file)) {
    include $report_file;
} else {
    echo "<div class=\"p-6 text-center text-gray-500\">Report not found.</div>";
}
$reportContent = ob_get_clean(); // Get content and clean buffer
echo $reportContent; // Output the content
?>

<?php if (!$print_mode): ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function printReport() {
    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('print', '1');
    
    // Open print version in new window
    const printUrl = window.location.pathname + '?' + urlParams.toString();
    const printWindow = window.open(printUrl, '_blank', 'width=1200,height=800');
    
    // Wait for content to load then print
    printWindow.onload = function() {
        setTimeout(function() {
            printWindow.print();
        }, 500);
    };
}

// Auto-submit form when report type changes
document.getElementById('report_type').addEventListener('change', function() {
    this.form.submit();
});
</script>

<?php else: ?>
<!-- Print Mode Styles -->
<style>
    @media print {
        body { font-size: 12px; }
        .no-print { display: none !important; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
    }
    
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: white;
    }
    
    .print-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #333;
        padding-bottom: 20px;
    }
    
    .print-footer {
        margin-top: 30px;
        text-align: center;
        font-size: 10px;
        color: #666;
        border-top: 1px solid #ccc;
        padding-top: 10px;
    }
</style>

<div class="print-header">
    <h1>Cash Flow Management System - Minimarket Panuntun</h1>
    <h2><?php echo $report_types[$report_type]; ?></h2>
    <p>Generated on: <?php echo date('d/m/Y H:i:s'); ?></p>
    <?php if (in_array($report_type, ['summary', 'cash_inflow', 'cash_outflow', 'journal'])): ?>
        <p>Period: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
    <?php endif; ?>
</div>

<script>
// Auto-print when page loads in print mode
window.onload = function() {
    setTimeout(function() {
        window.print();
    }, 1000);
};
</script>

<div class="print-footer">
    <p>This report was generated automatically by the Cash Flow Management System</p>
    <p>© <?php echo date('Y'); ?> Minimarket Panuntun - All rights reserved</p>
</div>

<?php endif; ?>

<?php if (!$print_mode) include dirname(__DIR__) . '/components/footer.php';
 ?>


