<?php
// Inventory Report
// Ditulis ulang sepenuhnya untuk menggunakan view 'v_inventory_status' dari db.sql

$query = "SELECT * FROM v_inventory_status";
$stmt = $db->prepare($query);
$stmt->execute();
$inventory_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query_summary = "SELECT COUNT(*) as total_products, SUM(stok) as total_items FROM barang";
$stmt_summary = $db->prepare($query_summary);
$stmt_summary->execute();
$inventory_summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Laporan Inventaris</h3>
    <p class="text-sm text-gray-600">Status stok barang per tanggal <?php echo date('d/m/Y H:i:s'); ?></p>
</div>
<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center"><div class="text-sm font-medium text-blue-800">Total Jenis Produk</div><div class="text-xl font-bold text-blue-600"><?php echo $inventory_summary['total_products'] ?? 0; ?></div></div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center"><div class="text-sm font-medium text-purple-800">Total Unit Barang</div><div class="text-xl font-bold text-purple-600"><?php echo $inventory_summary['total_items'] ?? 0; ?></div></div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Barang</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Saat Ini</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status Stok</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($inventory_data)): ?>
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Tidak ada data inventaris.</td></tr>
                <?php else: ?>
                    <?php foreach ($inventory_data as $product): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $product['kode_barang']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['nama_barang']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900"><?php echo $product['stok']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php
                                $status_colors = ['Low Stock' => 'bg-red-100 text-red-800', 'Medium Stock' => 'bg-yellow-100 text-yellow-800', 'Good Stock' => 'bg-green-100 text-green-800'];
                                $status_text = $product['stock_status'];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_colors[$status_text] ?? 'bg-gray-100'; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>