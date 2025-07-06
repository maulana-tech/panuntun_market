<?php
// File: pages/reports/suppliers.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Cek jika variabel $db belum ada, lalu buat koneksi
if (!isset($db)) {
    require_once dirname(__DIR__, 2) . '/includes/functions.php';
    $database = new Database();
    $db = $database->getConnection();
}

// Mengambil semua data pemasok dari database
$query_suppliers = "
    SELECT 
        id_supplier,
        nama_supplier,
        no_tlp,
        alamat
    FROM supplier
    ORDER BY id_supplier ASC
";

$stmt_suppliers = $db->prepare($query_suppliers);
$stmt_suppliers->execute();
$suppliers_data = $stmt_suppliers->fetchAll(PDO::FETCH_ASSOC);

// Mengambil statistik ringkasan jumlah pemasok
$query_summary = "SELECT COUNT(*) as total_suppliers FROM supplier";
$stmt_summary = $db->prepare($query_summary);
$stmt_summary->execute();
$summary_stats = $stmt_summary->fetch(PDO::FETCH_ASSOC);

?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Laporan Daftar Pemasok (Supplier)</h3>
    <p class="text-sm text-gray-600">Menampilkan semua data pemasok yang terdaftar.</p>
</div>

<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center col-span-3">
            <div class="text-sm font-medium text-blue-800">Total Pemasok Terdaftar</div>
            <div class="text-xl font-bold text-blue-600"><?php echo $summary_stats['total_suppliers'] ?? 0; ?></div>
        </div>
    </div>

    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Rincian Data Pemasok</h4>
        <div class="overflow-x-auto">
            <table class="modern-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Supplier</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Tlp</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($suppliers_data)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                Tidak ada data pemasok yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers_data as $supplier): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    SUP-<?php echo str_pad($supplier['id_supplier'], 4, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($supplier['nama_supplier']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($supplier['no_tlp']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($supplier['alamat']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>