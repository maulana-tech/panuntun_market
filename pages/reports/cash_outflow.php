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
// Cash Outflow Report

// Mengambil data transaksi pembelian sesuai format yang diminta
$query_outflow = "
    SELECT 
        kk.tgl_transaksi as tanggal,
        p.id_pembelian,
        b.nama_barang,
        kk.keterangan,
        kk.jumlah as total
    FROM kas_keluar kk
    JOIN pembelian p ON kk.id_pembelian = p.id_pembelian
    JOIN barang b ON p.kode_barang = b.kode_barang
    WHERE DATE(kk.tgl_transaksi) BETWEEN :start_date AND :end_date
    ORDER BY kk.tgl_transaksi ASC
";

$stmt_outflow = $db->prepare($query_outflow);
$stmt_outflow->bindParam(':start_date', $start_date);
$stmt_outflow->bindParam(':end_date', $end_date);
$stmt_outflow->execute();
$cash_outflow_data = $stmt_outflow->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics untuk kartu di atas dan total di bawah
$query_summary = "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(jumlah) as total_amount
    FROM kas_keluar 
    WHERE DATE(tgl_transaksi) BETWEEN :start_date AND :end_date
";

$stmt_summary = $db->prepare($query_summary);
$stmt_summary->bindParam(':start_date', $start_date);
$stmt_summary->bindParam(':end_date', $end_date);
$stmt_summary->execute();
$summary_stats = $stmt_summary->fetch(PDO::FETCH_ASSOC);

?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Laporan Kas Keluar (Pembelian)</h3>
    <p class="text-sm text-gray-600">Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
</div>

<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-red-800">Total Transaksi Pembelian</div>
            <div class="text-xl font-bold text-red-600"><?php echo $summary_stats['total_transactions'] ?? 0; ?></div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-red-800">Total Pengeluaran</div>
            <div class="text-xl font-bold text-red-600"><?php echo formatCurrency($summary_stats['total_amount'] ?? 0); ?></div>
        </div>
    </div>

    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Rincian Transaksi Pembelian</h4>
        <div class="overflow-x-auto">
            <table class="modern-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Pembelian</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($cash_outflow_data)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                Tidak ada transaksi kas keluar pada periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cash_outflow_data as $transaction): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatDate($transaction['tanggal']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    PEM-<?php echo str_pad($transaction['id_pembelian'], 4, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($transaction['nama_barang']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($transaction['keterangan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                    <?php echo formatCurrency($transaction['total']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-gray-100 font-bold">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right text-sm text-gray-900 uppercase">
                            Total Kas Keluar
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-red-600">
                            <?php echo formatCurrency($summary_stats['total_amount'] ?? 0); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>