<?php
// Inventory Report

// Get all products with current stock levels and values
$query = "SELECT 
    b.id_barang,
    b.nama_barang,
    b.kategori,
    b.harga_beli,
    b.harga_jual,
    b.stok,
    b.stok_minimum,
    b.satuan,
    b.deskripsi,
    (b.stok * b.harga_beli) as total_purchase_value,
    (b.stok * b.harga_jual) as total_selling_value,
    ((b.harga_jual - b.harga_beli) / b.harga_beli * 100) as profit_margin,
    CASE 
        WHEN b.stok <= b.stok_minimum THEN 'Low Stock'
        WHEN b.stok = 0 THEN 'Out of Stock'
        ELSE 'In Stock'
    END as stock_status
FROM barang b
ORDER BY b.kategori ASC, b.nama_barang ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$inventory_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get inventory summary statistics
$query = "SELECT 
    COUNT(*) as total_products,
    SUM(stok) as total_items,
    SUM(stok * harga_beli) as total_purchase_value,
    SUM(stok * harga_jual) as total_selling_value,
    COUNT(CASE WHEN stok <= stok_minimum THEN 1 END) as low_stock_items,
    COUNT(CASE WHEN stok = 0 THEN 1 END) as out_of_stock_items
FROM barang";

$stmt = $db->prepare($query);
$stmt->execute();
$inventory_summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get category breakdown
$query = "SELECT 
    COALESCE(kategori, 'Uncategorized') as kategori,
    COUNT(*) as product_count,
    SUM(stok) as total_stock,
    SUM(stok * harga_beli) as category_purchase_value,
    SUM(stok * harga_jual) as category_selling_value
FROM barang 
GROUP BY kategori
ORDER BY category_selling_value DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$category_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent stock movements (sales and purchases in the last 30 days)
$query = "
    SELECT 
        'Sale' as movement_type,
        b.nama_barang,
        -p.jumlah as quantity_change,
        p.tanggal_penjualan as movement_date,
        'Stock Reduction' as movement_description
    FROM penjualan p
    JOIN barang b ON p.id_barang = b.id_barang
    WHERE p.tanggal_penjualan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    
    UNION ALL
    
    SELECT 
        'Purchase' as movement_type,
        b.nama_barang,
        pm.jumlah as quantity_change,
        pm.tanggal_pembelian as movement_date,
        'Stock Addition' as movement_description
    FROM pembelian pm
    JOIN barang b ON pm.id_barang = b.id_barang
    WHERE pm.tanggal_pembelian >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    
    ORDER BY movement_date DESC
    LIMIT 20
";

$stmt = $db->prepare($query);
$stmt->execute();
$recent_movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Inventory Report</h3>
    <p class="text-sm text-gray-600">Current stock levels and inventory valuation as of <?php echo date('d/m/Y H:i:s'); ?></p>
</div>

<div class="p-6">
    <!-- Inventory Summary -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-blue-800">Total Products</div>
            <div class="text-xl font-bold text-blue-600"><?php echo $inventory_summary['total_products'] ?? 0; ?></div>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-purple-800">Total Items</div>
            <div class="text-xl font-bold text-purple-600"><?php echo $inventory_summary['total_items'] ?? 0; ?></div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-green-800">Purchase Value</div>
            <div class="text-lg font-bold text-green-600"><?php echo formatCurrency($inventory_summary['total_purchase_value'] ?? 0); ?></div>
        </div>
        
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-emerald-800">Selling Value</div>
            <div class="text-lg font-bold text-emerald-600"><?php echo formatCurrency($inventory_summary['total_selling_value'] ?? 0); ?></div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-yellow-800">Low Stock</div>
            <div class="text-xl font-bold text-yellow-600"><?php echo $inventory_summary['low_stock_items'] ?? 0; ?></div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-red-800">Out of Stock</div>
            <div class="text-xl font-bold text-red-600"><?php echo $inventory_summary['out_of_stock_items'] ?? 0; ?></div>
        </div>
    </div>

    <!-- Category Breakdown -->
    <?php if (!empty($category_breakdown)): ?>
        <div class="mb-8">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Category Breakdown</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Stock</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Value</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Selling Value</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Potential Profit</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($category_breakdown as $category): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($category['kategori']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $category['product_count']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $category['total_stock']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                    <?php echo formatCurrency($category['category_purchase_value']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                    <?php echo formatCurrency($category['category_selling_value']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-blue-600 font-medium">
                                    <?php echo formatCurrency($category['category_selling_value'] - $category['category_purchase_value']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Detailed Inventory -->
    <div class="mb-8">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Detailed Inventory</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Min Stock</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Price</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Selling Price</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Value</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Margin %</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($inventory_data)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                No products found in inventory.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory_data as $product): ?>
                            <tr class="hover:bg-gray-50 <?php echo $product['stock_status'] === 'Low Stock' ? 'bg-yellow-50' : ($product['stock_status'] === 'Out of Stock' ? 'bg-red-50' : ''); ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['nama_barang']); ?></div>
                                    <?php if (!empty($product['deskripsi'])): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($product['deskripsi']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($product['kategori'] ?? 'Uncategorized'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $product['stok']; ?> <?php echo htmlspecialchars($product['satuan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                    <?php echo $product['stok_minimum']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                    <?php echo formatCurrency($product['harga_beli']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                    <?php echo formatCurrency($product['harga_jual']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                    <?php echo formatCurrency($product['total_selling_value']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo number_format($product['profit_margin'], 1); ?>%
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php
                                    $status_colors = [
                                        'In Stock' => 'bg-green-100 text-green-800',
                                        'Low Stock' => 'bg-yellow-100 text-yellow-800',
                                        'Out of Stock' => 'bg-red-100 text-red-800'
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_colors[$product['stock_status']]; ?>">
                                        <?php echo $product['stock_status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Stock Movements -->
    <?php if (!empty($recent_movements)): ?>
        <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Recent Stock Movements (Last 30 Days)</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Movement Type</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Change</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_movements as $movement): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y H:i', strtotime($movement['movement_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($movement['nama_barang']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $movement['movement_type'] === 'Sale' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo $movement['movement_type']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium <?php echo $movement['quantity_change'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $movement['quantity_change'] > 0 ? '+' : ''; ?><?php echo $movement['quantity_change']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $movement['movement_description']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

