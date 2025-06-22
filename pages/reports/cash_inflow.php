<?php
// --- BLOK KODE BARU UNTUK MEMBUAT FILE INI MANDIRI ---

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
// --- AKHIR BLOK KODE BARU ---


// Query laporan (tidak ada perubahan dari versi benar sebelumnya)
$query = "
    SELECT 
        km.tgl_transaksi, km.jumlah, km.keterangan, p.qty, p.harga
    FROM kas_masuk km
    JOIN penjualan p ON km.id_penjualan = p.id_penjualan
    WHERE DATE(km.tgl_transaksi) BETWEEN :start_date AND :end_date
    ORDER BY km.tgl_transaksi DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$cash_inflow_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query_summary = "
    SELECT COUNT(*) as total_transactions, SUM(jumlah) as total_amount, AVG(jumlah) as average_amount
    FROM kas_masuk 
    WHERE DATE(tgl_transaksi) BETWEEN :start_date AND :end_date";
$stmt_summary = $db->prepare($query_summary);
$stmt_summary->bindParam(':start_date', $start_date);
$stmt_summary->bindParam(':end_date', $end_date);
$stmt_summary->execute();
$summary_stats = $stmt_summary->fetch(PDO::FETCH_ASSOC);
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Laporan Kas Masuk (Penjualan)</h3>
    <p class="text-sm text-gray-600">Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
</div>
<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center"><div class="text-sm font-medium text-green-800">Total Transaksi</div><div class="text-xl font-bold text-green-600"><?php echo $summary_stats['total_transactions'] ?? 0; ?></div></div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center"><div class="text-sm font-medium text-green-800">Total Pendapatan</div><div class="text-xl font-bold text-green-600"><?php echo formatCurrency($summary_stats['total_amount'] ?? 0); ?></div></div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center"><div class="text-sm font-medium text-blue-800">Rata-rata Transaksi</div><div class="text-xl font-bold text-blue-600"><?php echo formatCurrency($summary_stats['average_amount'] ?? 0); ?></div></div>
    </div>
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Detail Transaksi Penjualan</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($cash_inflow_data)): ?>
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">Tidak ada transaksi kas masuk pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cash_inflow_data as $transaction): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatDateTime($transaction['tgl_transaksi']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($transaction['keterangan']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900"><?php echo $transaction['qty']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900"><?php echo formatCurrency($transaction['harga']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium"><?php echo formatCurrency($transaction['jumlah']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>