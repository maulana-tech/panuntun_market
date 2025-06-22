<?php
// Cash Flow Summary Report

// Get cash flow summary data
$query = "SELECT 
    DATE(tanggal) as date,
    SUM(kas_masuk) as total_inflow,
    SUM(kas_keluar) as total_outflow,
    SUM(kas_masuk - kas_keluar) as net_flow
FROM kas 
WHERE DATE(tanggal) BETWEEN :start_date AND :end_date 
GROUP BY DATE(tanggal)
ORDER BY DATE(tanggal) ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$daily_cash_flow = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get period totals
$query = "SELECT 
    SUM(kas_masuk) as period_inflow,
    SUM(kas_keluar) as period_outflow,
    SUM(kas_masuk - kas_keluar) as period_net
FROM kas 
WHERE DATE(tanggal) BETWEEN :start_date AND :end_date";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$period_totals = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current cash balance
$query = "SELECT SUM(kas_masuk - kas_keluar) as current_balance FROM kas";
$stmt = $db->prepare($query);
$stmt->execute();
$current_balance = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Cash Flow Summary Report</h3>
    <p class="text-sm text-gray-600">Period: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
</div>

<div class="p-6">
    <!-- Period Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm font-medium text-green-800">Total Cash Inflow</div>
            <div class="text-2xl font-bold text-green-600"><?php echo formatCurrency($period_totals['period_inflow'] ?? 0); ?></div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm font-medium text-red-800">Total Cash Outflow</div>
            <div class="text-2xl font-bold text-red-600"><?php echo formatCurrency($period_totals['period_outflow'] ?? 0); ?></div>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm font-medium text-blue-800">Net Cash Flow</div>
            <div class="text-2xl font-bold <?php echo ($period_totals['period_net'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo formatCurrency($period_totals['period_net'] ?? 0); ?>
            </div>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="text-sm font-medium text-purple-800">Current Balance</div>
            <div class="text-2xl font-bold text-purple-600"><?php echo formatCurrency($current_balance['current_balance'] ?? 0); ?></div>
        </div>
    </div>

    <!-- Daily Cash Flow Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cash Inflow</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cash Outflow</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net Flow</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($daily_cash_flow)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            No cash flow data found for the selected period.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daily_cash_flow as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('d/m/Y', strtotime($row['date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                <?php echo formatCurrency($row['total_inflow']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                <?php echo formatCurrency($row['total_outflow']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium <?php echo $row['net_flow'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo formatCurrency($row['net_flow']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Totals Row -->
                    <tr class="bg-gray-50 font-bold">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TOTAL</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">
                            <?php echo formatCurrency($period_totals['period_inflow'] ?? 0); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                            <?php echo formatCurrency($period_totals['period_outflow'] ?? 0); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right <?php echo ($period_totals['period_net'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo formatCurrency($period_totals['period_net'] ?? 0); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

