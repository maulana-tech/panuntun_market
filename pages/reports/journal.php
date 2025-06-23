<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Cek jika variabel $db dan tanggal belum ada
if (!isset($db) || !isset($start_date) || !isset($end_date)) {
    require_once dirname(__DIR__, 2) . '/includes/functions.php';
    
    // Buat koneksi database jika belum ada
    if (!isset($db)) {
        $database = new Database();
        $db = $database->getConnection();
    }
    
    // Ambil tanggal dari URL jika belum ada
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
}
// Journal Report - Complete transaction history

// Get all transactions (both sales and purchases) with details
$query = "
    SELECT 
        'Sale' as transaction_type,
        p.tgl_jual as transaction_date,
        b.nama_barang as item_name,
        p.qty as quantity,
        'pcs' as unit,
        p.harga as unit_price,
        p.total_penjualan as total_amount,
        'Cash Inflow' as cash_flow_type,
        'System User' as user_name,
        NULL as supplier_name,
        CONCAT('Sale of ', b.nama_barang) as notes
    FROM penjualan p
    JOIN barang b ON p.kode_barang = b.kode_barang
    WHERE DATE(p.tgl_jual) BETWEEN :start_date AND :end_date
    
    UNION ALL
    
    SELECT 
        'Purchase' as transaction_type,
        pm.tgl_beli as transaction_date,
        b.nama_barang as item_name,
        pm.qty as quantity,
        'pcs' as unit,
        pm.harga as unit_price,
        pm.total_pembelian as total_amount,
        'Cash Outflow' as cash_flow_type,
        'System User' as user_name,
        s.nama_supplier as supplier_name,
        CONCAT('Purchase of ', b.nama_barang) as notes
    FROM pembelian pm
    JOIN barang b ON pm.kode_barang = b.kode_barang
    JOIN supplier s ON pm.id_supplier = s.id_supplier
    WHERE DATE(pm.tgl_beli) BETWEEN :start_date AND :end_date
    
    ORDER BY transaction_date DESC
";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$journal_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$query = "
    SELECT 
        COUNT(CASE WHEN transaction_type = 'Sale' THEN 1 END) as total_sales,
        COUNT(CASE WHEN transaction_type = 'Purchase' THEN 1 END) as total_purchases,
        SUM(CASE WHEN transaction_type = 'Sale' THEN total_amount ELSE 0 END) as total_sales_amount,
        SUM(CASE WHEN transaction_type = 'Purchase' THEN total_amount ELSE 0 END) as total_purchases_amount
    FROM (
        SELECT 'Sale' as transaction_type, total_penjualan as total_amount
        FROM penjualan 
        WHERE DATE(tgl_jual) BETWEEN :start_date AND :end_date
        
        UNION ALL
        
        SELECT 'Purchase' as transaction_type, total_pembelian as total_amount
        FROM pembelian 
        WHERE DATE(tgl_beli) BETWEEN :start_date AND :end_date
    ) as combined_transactions
";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$journal_summary = $stmt->fetch(PDO::FETCH_ASSOC);

require_once dirname(__DIR__, 2) . '/components/header.php';

?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Journal Report</h3>
    <p class="text-sm text-gray-600">Complete transaction history for period: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
</div>

<div class="p-6">
    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-green-800">Total Sales</div>
            <div class="text-xl font-bold text-green-600"><?php echo $journal_summary['total_sales'] ?? 0; ?></div>
            <div class="text-sm text-green-600"><?php echo formatCurrency($journal_summary['total_sales_amount'] ?? 0); ?></div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-red-800">Total Purchases</div>
            <div class="text-xl font-bold text-red-600"><?php echo $journal_summary['total_purchases'] ?? 0; ?></div>
            <div class="text-sm text-red-600"><?php echo formatCurrency($journal_summary['total_purchases_amount'] ?? 0); ?></div>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-blue-800">Total Transactions</div>
            <div class="text-xl font-bold text-blue-600"><?php echo ($journal_summary['total_sales'] ?? 0) + ($journal_summary['total_purchases'] ?? 0); ?></div>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-purple-800">Net Cash Flow</div>
            <?php 
            $net_flow = ($journal_summary['total_sales_amount'] ?? 0) - ($journal_summary['total_purchases_amount'] ?? 0);
            $flow_color = $net_flow >= 0 ? 'text-green-600' : 'text-red-600';
            ?>
            <div class="text-xl font-bold <?php echo $flow_color; ?>"><?php echo formatCurrency($net_flow); ?></div>
        </div>
    </div>

    <!-- Journal Entries Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Party</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cash Flow</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($journal_entries)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            No transactions found for the selected period.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($journal_entries as $entry): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('d/m/Y', strtotime($entry['transaction_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $entry['transaction_type'] === 'Sale' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo $entry['transaction_type']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($entry['item_name']); ?></div>
                                <?php if (!empty($entry['notes'])): ?>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($entry['notes']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $entry['transaction_type'] === 'Sale' ? 'Customer' : htmlspecialchars($entry['supplier_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                <?php echo $entry['quantity']; ?> <?php echo htmlspecialchars($entry['unit']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                <?php echo formatCurrency($entry['unit_price']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium <?php echo $entry['transaction_type'] === 'Sale' ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo formatCurrency($entry['total_amount']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $entry['cash_flow_type'] === 'Cash Inflow' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $entry['cash_flow_type']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($entry['user_name']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Summary Row -->
                    <tr class="bg-gray-50 font-bold">
                        <td colspan="6" class="px-6 py-4 text-sm text-gray-900">PERIOD TOTALS</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            <div class="text-green-600">+<?php echo formatCurrency($journal_summary['total_sales_amount'] ?? 0); ?></div>
                            <div class="text-red-600">-<?php echo formatCurrency($journal_summary['total_purchases_amount'] ?? 0); ?></div>
                            <div class="border-t pt-1 <?php echo $net_flow >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo formatCurrency($net_flow); ?>
                            </div>
                        </td>
                        <td colspan="2" class="px-6 py-4 text-sm text-gray-500">
                            Sales: <?php echo $journal_summary['total_sales'] ?? 0; ?> | 
                            Purchases: <?php echo $journal_summary['total_purchases'] ?? 0; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>