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

// 1. Mengambil data transaksi asli (tidak ada perubahan di query ini)
$query_transactions = "
    (SELECT 'Sale' as transaction_type, tgl_jual as transaction_date, total_penjualan as total_amount, nama_barang FROM penjualan WHERE DATE(tgl_jual) BETWEEN :start_date AND :end_date)
    UNION ALL
    (SELECT 'Purchase' as transaction_type, tgl_beli as transaction_date, total_pembelian as total_amount, nama_barang FROM pembelian WHERE DATE(tgl_beli) BETWEEN :start_date AND :end_date)
    ORDER BY transaction_date ASC
";
$stmt_transactions = $db->prepare($query_transactions);
$stmt_transactions->bindParam(':start_date', $start_date);
$stmt_transactions->bindParam(':end_date', $end_date);
$stmt_transactions->execute();
$original_entries = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);

// 2. Mengubah data transaksi menjadi format Jurnal Umum (Debit/Kredit)
$journal_display_entries = [];
$total_debit = 0;
$total_kredit = 0;

foreach ($original_entries as $entry) {
    $amount = floatval($entry['total_amount']);
    if ($entry['transaction_type'] === 'Sale') {
        // Penjualan: Kas (Debit), Pendapatan (Kredit)
        $journal_display_entries[] = [
            'date' => $entry['transaction_date'],
            'keterangan' => 'Kas',
            'debit' => $amount,
            'kredit' => 0,
            'notes' => 'Penjualan ' . $entry['nama_barang']
        ];
        $journal_display_entries[] = [
            'date' => null, // Tanggal tidak ditampilkan di baris kedua
            'keterangan' => '&nbsp;&nbsp;&nbsp;Pendapatan Penjualan',
            'debit' => 0,
            'kredit' => $amount,
            'notes' => ''
        ];
        $total_debit += $amount;
        $total_kredit += $amount;
    } elseif ($entry['transaction_type'] === 'Purchase') {
        // Pembelian: Beban/Persediaan (Debit), Kas (Kredit)
        $journal_display_entries[] = [
            'date' => $entry['transaction_date'],
            'keterangan' => 'Beban Pokok Penjualan / Persediaan',
            'debit' => $amount,
            'kredit' => 0,
            'notes' => 'Pembelian ' . $entry['nama_barang']
        ];
        $journal_display_entries[] = [
            'date' => null,
            'keterangan' => '&nbsp;&nbsp;&nbsp;Kas',
            'debit' => 0,
            'kredit' => $amount,
            'notes' => ''
        ];
        $total_debit += $amount;
        $total_kredit += $amount;
    }
}
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Laporan Jurnal Umum</h3>
    <p class="text-sm text-gray-600">Riwayat transaksi lengkap untuk periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
</div>

<div class="p-6">
    <div class="overflow-x-auto">
        <table class="modern-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kredit</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($journal_display_entries)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            Tidak ada transaksi pada periode yang dipilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($journal_display_entries as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $row['date'] ? date('d/m/Y', strtotime($row['date'])) : ''; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo $row['keterangan']; ?></div>
                                <?php if (!empty($row['notes'])): ?>
                                    <div class="text-xs text-gray-500 italic"><?php echo htmlspecialchars($row['notes']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                <?php echo $row['debit'] > 0 ? formatCurrency($row['debit']) : ''; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                <?php echo $row['kredit'] > 0 ? formatCurrency($row['kredit']) : ''; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <tr class="bg-gray-100 font-bold">
                        <td colspan="2" class="px-6 py-4 text-sm text-right text-gray-900 uppercase">TOTAL</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 border-t-2 border-gray-300">
                            <?php echo formatCurrency($total_debit); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 border-t-2 border-gray-300">
                            <?php echo formatCurrency($total_kredit); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>