<?php
// File: pages/reports/inventory.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Cek jika variabel $db belum ada, lalu buat koneksi
if (!isset($db)) {
    require_once dirname(__DIR__, 2) . '/includes/functions.php';
    $database = new Database();
    $db = $database->getConnection();
}

// --- Logika Paginasi ---
$limit = 15; // Jumlah item per halaman
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Menghitung total barang untuk paginasi
$total_products_stmt = $db->query("SELECT COUNT(*) FROM barang");
$total_products = $total_products_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);
// --- Akhir Logika Paginasi ---

// Mengambil data barang dengan LIMIT dan OFFSET untuk halaman saat ini
$query_inventory = "
    SELECT 
        kode_barang,
        nama_barang,
        stok,
        updated_at as tanggal
    FROM barang
    ORDER BY nama_barang ASC
    LIMIT :limit OFFSET :offset
";

$stmt_inventory = $db->prepare($query_inventory);
$stmt_inventory->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt_inventory->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt_inventory->execute();
$inventory_data = $stmt_inventory->fetchAll(PDO::FETCH_ASSOC);

// Mengambil statistik ringkasan (Total Jenis dan Total Stok)
$query_summary = "SELECT COUNT(*) as total_products, SUM(stok) as total_stock FROM barang";
$stmt_summary = $db->prepare($query_summary);
$stmt_summary->execute();
$summary_stats = $stmt_summary->fetch(PDO::FETCH_ASSOC);

?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Laporan Daftar Barang (Inventaris)</h3>
    <p class="text-sm text-gray-600">Menampilkan semua data barang dan stok saat ini dengan paginasi.</p>
</div>

<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-blue-800">Total Jenis Barang</div>
            <div class="text-xl font-bold text-blue-600"><?php echo $summary_stats['total_products'] ?? 0; ?></div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-green-800">Total Jumlah Stok</div>
            <div class="text-xl font-bold text-green-600"><?php echo number_format($summary_stats['total_stock'] ?? 0); ?></div>
        </div>
    </div>

    <div class="bg-white shadow-lg rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr class="border-b border-gray-200">
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal Update</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode Barang</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Barang</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($inventory_data)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                                    <h3 class="text-lg font-semibold">Tidak ada data barang.</h3>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory_data as $item): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($item['tanggal']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    BRG-<?php echo str_pad($item['kode_barang'], 4, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($item['nama_barang']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-900">
                                    <?php echo htmlspecialchars($item['stok']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200">
            <nav class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Halaman <span class="font-medium"><?php echo $page; ?></span> dari <span class="font-medium"><?php echo $total_pages; ?></span>
                </div>
                <div class="flex-1 flex justify-end">
                    <?php if ($page > 1): ?>
                        <a href="?report_type=inventory&page=<?php echo $page - 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Sebelumnya
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?report_type=inventory&page=<?php echo $page + 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Berikutnya
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>