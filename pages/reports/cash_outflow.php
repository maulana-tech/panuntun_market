<?php
// Cash Outflow Report

// Get cash outflow data with purchase details
$query = "SELECT 
    kk.tanggal,
    kk.jumlah,
    kk.keterangan,
    p.jumlah as qty_purchased,
    p.harga_satuan,
    b.nama_barang,
    s.nama_supplier,
    u.nama as user_name
FROM kas_keluar kk
JOIN pembelian p ON kk.id_pembelian = p.id_pembelian
JOIN barang b ON p.id_barang = b.id_barang
JOIN supplier s ON p.id_supplier = s.id_supplier
JOIN pengguna u ON p.id_pengguna = u.id_pengguna
WHERE DATE(kk.tanggal) BETWEEN :start_date AND :end_date
ORDER BY kk.tanggal DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$cash_outflow_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$query = "SELECT 
    COUNT(*) as total_transactions,
    SUM(jumlah) as total_amount,
    AVG(jumlah) as average_amount,
    MAX(jumlah) as max_amount,
    MIN(jumlah) as min_amount
FROM kas_keluar 
WHERE DATE(tanggal) BETWEEN :start_date AND :end_date";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$summary_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get supplier breakdown
$query = "SELECT 
    s.nama_supplier,
    COUNT(kk.id_kas_keluar) as transaction_count,
    SUM(kk.jumlah) as total_amount
FROM kas_keluar kk
JOIN pembelian p ON kk.id_pembelian = p.id_pembelian
JOIN supplier s ON p.id_supplier = s.id_supplier
WHERE DATE(kk.tanggal) BETWEEN :start_date AND :end_date
GROUP BY s.id_supplier, s.nama_supplier
ORDER BY total_amount DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$supplier_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily totals
$query = "SELECT 
    DATE(tanggal) as date,
    COUNT(*) as transaction_count,
    SUM(jumlah) as daily_total
FROM kas_keluar 
WHERE DATE(tanggal) BETWEEN :start_date AND :end_date
GROUP BY DATE(tanggal)
ORDER BY date ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$daily_totals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Cash Outflow Report</h3>
    <p class="text-sm text-gray-600">Period: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
</div>

<div class="p-6">
    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-red-800">Total Transactions</div>
            <div class="text-xl font-bold text-red-600"><?php echo $summary_stats['total_transactions'] ?? 0; ?></div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-red-800">Total Amount</div>
            <div class="text-xl font-bold text-red-600"><?php echo formatCurrency($summary_stats['total_amount'] ?? 0); ?></div>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-blue-800">Average Amount</div>
            <div class="text-xl font-bold text-blue-600"><?php echo formatCurrency($summary_stats['average_amount'] ?? 0); ?></div>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-purple-800">Largest Purchase</div>
            <div class="text-xl font-bold text-purple-600"><?php echo formatCurrency($summary_stats['max_amount'] ?? 0); ?></div>
        </div>
        
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-gray-800">Smallest Purchase</div>
            <div class="text-xl font-bold text-gray-600"><?php echo formatCurrency($summary_stats['min_amount'] ?? 0); ?></div>
        </div>
    </div>

    <!-- Supplier Breakdown -->
    <?php if (!empty($supplier_breakdown)): ?>
        <div class="mb-8">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Supplier Breakdown</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $total_all_suppliers = array_sum(array_column($supplier_breakdown, 'total_amount'));
                        foreach ($supplier_breakdown as $supplier): 
                            $percentage = $total_all_suppliers > 0 ? ($supplier['total_amount'] / $total_all_suppliers) * 100 : 0;
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($supplier['nama_supplier']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $supplier['transaction_count']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                    <?php echo formatCurrency($supplier['total_amount']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                    <?php echo number_format($percentage, 1); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Daily Totals -->
    <?php if (!empty($daily_totals)): ?>
        <div class="mb-8">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Daily Summary</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($daily_totals as $day): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($day['date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $day['transaction_count']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                    <?php echo formatCurrency($day['daily_total']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Detailed Transactions -->
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Detailed Transactions</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($cash_outflow_data)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No cash outflow transactions found for the selected period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cash_outflow_data as $transaction): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatDateTime($transaction['tanggal']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($transaction['nama_barang']); ?></div>
                                    <?php if (!empty($transaction['keterangan'])): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($transaction['keterangan']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($transaction['nama_supplier']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $transaction['qty_purchased']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                    <?php echo formatCurrency($transaction['harga_satuan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                    <?php echo formatCurrency($transaction['jumlah']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($transaction['user_name']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

