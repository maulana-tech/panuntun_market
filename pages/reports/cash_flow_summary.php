<?php
// Cash Flow Summary Report - Detailed View

// Menggunakan view v_cash_flow_summary untuk mendapatkan detail setiap transaksi
$query_detailed = "
    SELECT 
        id_kas,
        tgl_transaksi,
        ket_transaksi,
        kas_masuk,
        kas_keluar
    FROM v_cash_flow_summary
    WHERE DATE(tgl_transaksi) BETWEEN :start_date AND :end_date 
    ORDER BY tgl_transaksi ASC, id_kas ASC
";

$stmt_detailed = $db->prepare($query_detailed);
$stmt_detailed->bindParam(':start_date', $start_date);
$stmt_detailed->bindParam(':end_date', $end_date);
$stmt_detailed->execute();
$detailed_transactions = $stmt_detailed->fetchAll(PDO::FETCH_ASSOC);

// Query untuk total periode tetap diperlukan untuk kartu ringkasan di atas
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

// Get current cash balance
$query_balance = "SELECT saldo FROM kas ORDER BY id_kas DESC LIMIT 1";
$stmt_balance = $db->prepare($query_balance);
$stmt_balance->execute();
$current_balance = $stmt_balance->fetchColumn();
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Laporan Arus Kas (Buku Kas)</h3>
    <p class="text-sm text-gray-600">Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
</div>
<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4"><div class="text-sm font-medium text-green-800">Total Kas Masuk</div><div class="text-2xl font-bold text-green-600"><?php echo formatCurrency($period_inflow); ?></div></div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4"><div class="text-sm font-medium text-red-800">Total Kas Keluar</div><div class="text-2xl font-bold text-red-600"><?php echo formatCurrency($period_outflow); ?></div></div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4"><div class="text-sm font-medium text-blue-800">Arus Kas Bersih</div><div class="text-2xl font-bold <?php echo ($period_inflow - $period_outflow) >= 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo formatCurrency($period_inflow - $period_outflow); ?></div></div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4"><div class="text-sm font-medium text-purple-800">Saldo Kas Terkini</div><div class="text-2xl font-bold text-purple-600"><?php echo formatCurrency($current_balance ?: 0); ?></div></div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="modern-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Kas</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kas Masuk</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kas Keluar</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($detailed_transactions)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">Tidak ada data transaksi untuk periode yang dipilih.</td></tr>
                <?php else: ?>
                    <?php foreach ($detailed_transactions as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatDate($row['tgl_transaksi']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">KAS-<?php echo str_pad($row['id_kas'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['ket_transaksi']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                <?php echo ($row['kas_masuk'] > 0) ? formatCurrency($row['kas_masuk']) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                <?php echo ($row['kas_keluar'] > 0) ? formatCurrency($row['kas_keluar']) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="bg-gray-100 font-bold">
                        <td colspan="3" class="px-6 py-4 text-right text-sm text-gray-900 uppercase">TOTAL</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 border-t-2 border-gray-300">
                            <?php echo formatCurrency($period_inflow); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 border-t-2 border-gray-300">
                            <?php echo formatCurrency($period_outflow); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>