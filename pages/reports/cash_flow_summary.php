<?php
// Cash Flow Summary Report
// Mengganti semua 'tanggal' menjadi 'tgl_transaksi' agar sesuai dengan tabel 'kas'

// Get daily cash flow data
$query_daily = "
    SELECT 
        DATE(tgl_transaksi) as date,
        SUM(CASE WHEN id_kas_masuk IS NOT NULL THEN (SELECT jumlah FROM kas_masuk WHERE id_kas_masuk = kas.id_kas_masuk) ELSE 0 END) as total_inflow,
        SUM(CASE WHEN id_kas_keluar IS NOT NULL THEN (SELECT jumlah FROM kas_keluar WHERE id_kas_keluar = kas.id_kas_keluar) ELSE 0 END) as total_outflow
    FROM kas
    WHERE DATE(tgl_transaksi) BETWEEN :start_date AND :end_date 
    GROUP BY DATE(tgl_transaksi)
    ORDER BY date ASC";

$stmt_daily = $db->prepare($query_daily);
$stmt_daily->bindParam(':start_date', $start_date);
$stmt_daily->bindParam(':end_date', $end_date);
$stmt_daily->execute();
$daily_cash_flow = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

// Get period totals
$query_inflow = "SELECT COALESCE(SUM(jumlah), 0) FROM kas_masuk WHERE DATE(tgl_transaksi) BETWEEN :start_date AND :end_date";
$stmt_inflow = $db->prepare($query_inflow);
$stmt_inflow->bindParam(':start_date', $start_date);
$stmt_inflow->bindParam(':end_date', $end_date);
$stmt_inflow->execute();
$period_inflow = $stmt_inflow->fetchColumn();

$query_outflow = "SELECT COALESCE(SUM(jumlah), 0) FROM kas_keluar WHERE DATE(tgl_transaksi) BETWEEN :start_date AND :end_date";
$stmt_outflow = $db->prepare($query_outflow);
$stmt_outflow->bindParam(':start_date', $start_date);
$stmt_outflow->bindParam(':end_date', $end_date);
$stmt_outflow->execute();
$period_outflow = $stmt_outflow->fetchColumn();

$period_net = $period_inflow - $period_outflow;

// Get current cash balance
$query_balance = "SELECT saldo FROM kas ORDER BY created_at DESC, id_kas DESC LIMIT 1";
$stmt_balance = $db->prepare($query_balance);
$stmt_balance->execute();
$current_balance = $stmt_balance->fetchColumn();
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Ringkasan Arus Kas</h3>
    <p class="text-sm text-gray-600">Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
</div>
<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4"><div class="text-sm font-medium text-green-800">Total Kas Masuk</div><div class="text-2xl font-bold text-green-600"><?php echo formatCurrency($period_inflow); ?></div></div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4"><div class="text-sm font-medium text-red-800">Total Kas Keluar</div><div class="text-2xl font-bold text-red-600"><?php echo formatCurrency($period_outflow); ?></div></div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4"><div class="text-sm font-medium text-blue-800">Arus Kas Bersih</div><div class="text-2xl font-bold <?php echo $period_net >= 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo formatCurrency($period_net); ?></div></div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4"><div class="text-sm font-medium text-purple-800">Saldo Kas Terkini</div><div class="text-2xl font-bold text-purple-600"><?php echo formatCurrency($current_balance ? $current_balance : 0); ?></div></div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kas Masuk</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kas Keluar</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Arus Bersih</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($daily_cash_flow)): ?>
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Tidak ada data arus kas untuk periode yang dipilih.</td></tr>
                <?php else: ?>
                    <?php foreach ($daily_cash_flow as $row): $net_flow_daily = $row['total_inflow'] - $row['total_outflow']; ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatDate($row['date']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium"><?php echo formatCurrency($row['total_inflow']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium"><?php echo formatCurrency($row['total_outflow']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium <?php echo $net_flow_daily >= 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo formatCurrency($net_flow_daily); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="bg-gray-50 font-bold">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TOTAL</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600"><?php echo formatCurrency($period_inflow); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600"><?php echo formatCurrency($period_outflow); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right <?php echo $period_net >= 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo formatCurrency($period_net); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>