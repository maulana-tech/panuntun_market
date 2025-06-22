<?php
// Suppliers Report

// Get all suppliers with transaction statistics
$query = "SELECT 
    s.id_supplier,
    s.nama_supplier,
    s.alamat,
    s.telepon,
    s.email,
    s.kontak_person,
    COUNT(p.id_pembelian) as total_transactions,
    COALESCE(SUM(p.total_pembelian), 0) as total_purchase_amount,
    COALESCE(AVG(p.total_pembelian), 0) as average_transaction,
    COALESCE(MAX(p.total_pembelian), 0) as largest_transaction,
    MAX(p.tanggal_pembelian) as last_transaction_date,
    COUNT(DISTINCT p.id_barang) as products_supplied
FROM supplier s
LEFT JOIN pembelian p ON s.id_supplier = p.id_supplier
GROUP BY s.id_supplier, s.nama_supplier, s.alamat, s.telepon, s.email, s.kontak_person
ORDER BY total_purchase_amount DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$suppliers_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get supplier summary statistics
$query = "SELECT 
    COUNT(*) as total_suppliers,
    COUNT(CASE WHEN p.id_supplier IS NOT NULL THEN 1 END) as active_suppliers,
    SUM(COALESCE(total_amount, 0)) as total_purchases
FROM supplier s
LEFT JOIN (
    SELECT id_supplier, SUM(total_pembelian) as total_amount
    FROM pembelian 
    GROUP BY id_supplier
) p ON s.id_supplier = p.id_supplier";

$stmt = $db->prepare($query);
$stmt->execute();
$supplier_summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get top products by supplier
$query = "SELECT 
    s.nama_supplier,
    b.nama_barang,
    COUNT(p.id_pembelian) as purchase_count,
    SUM(p.jumlah) as total_quantity,
    SUM(p.total_pembelian) as total_amount
FROM pembelian p
JOIN supplier s ON p.id_supplier = s.id_supplier
JOIN barang b ON p.id_barang = b.id_barang
GROUP BY s.id_supplier, s.nama_supplier, b.id_barang, b.nama_barang
ORDER BY total_amount DESC
LIMIT 20";

$stmt = $db->prepare($query);
$stmt->execute();
$top_supplier_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent supplier transactions
$query = "SELECT 
    s.nama_supplier,
    b.nama_barang,
    p.jumlah,
    p.harga_satuan,
    p.total_pembelian,
    p.tanggal_pembelian,
    u.nama as user_name
FROM pembelian p
JOIN supplier s ON p.id_supplier = s.id_supplier
JOIN barang b ON p.id_barang = b.id_barang
JOIN pengguna u ON p.id_pengguna = u.id_pengguna
ORDER BY p.tanggal_pembelian DESC
LIMIT 15";

$stmt = $db->prepare($query);
$stmt->execute();
$recent_supplier_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Suppliers Report</h3>
    <p class="text-sm text-gray-600">Comprehensive supplier information and transaction history as of <?php echo date('d/m/Y H:i:s'); ?></p>
</div>

<div class="p-6">
    <!-- Supplier Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-blue-800">Total Suppliers</div>
            <div class="text-2xl font-bold text-blue-600"><?php echo $supplier_summary['total_suppliers'] ?? 0; ?></div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-green-800">Active Suppliers</div>
            <div class="text-2xl font-bold text-green-600"><?php echo $supplier_summary['active_suppliers'] ?? 0; ?></div>
            <div class="text-sm text-green-600">With transactions</div>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-purple-800">Total Purchases</div>
            <div class="text-xl font-bold text-purple-600"><?php echo formatCurrency($supplier_summary['total_purchases'] ?? 0); ?></div>
        </div>
    </div>

    <!-- Detailed Suppliers Information -->
    <div class="mb-8">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Supplier Details & Performance</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier Information</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Details</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Transaction</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Transaction</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($suppliers_data)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No suppliers found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers_data as $supplier): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($supplier['nama_supplier']); ?></div>
                                    <?php if (!empty($supplier['kontak_person'])): ?>
                                        <div class="text-sm text-gray-500">Contact: <?php echo htmlspecialchars($supplier['kontak_person']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($supplier['alamat'])): ?>
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($supplier['alamat']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($supplier['telepon'])): ?>
                                        <div class="text-sm text-gray-500">📞 <?php echo htmlspecialchars($supplier['telepon']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($supplier['email'])): ?>
                                        <div class="text-sm text-gray-500">✉️ <?php echo htmlspecialchars($supplier['email']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $supplier['total_transactions']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $supplier['products_supplied']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                    <?php echo formatCurrency($supplier['total_purchase_amount']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                    <?php echo formatCurrency($supplier['average_transaction']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $supplier['last_transaction_date'] ? date('d/m/Y', strtotime($supplier['last_transaction_date'])) : 'No transactions'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Products by Supplier -->
    <?php if (!empty($top_supplier_products)): ?>
        <div class="mb-8">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Top Products by Supplier</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Purchases</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Quantity</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($top_supplier_products as $product): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($product['nama_supplier']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($product['nama_barang']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $product['purchase_count']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $product['total_quantity']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                    <?php echo formatCurrency($product['total_amount']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Supplier Transactions -->
    <?php if (!empty($recent_supplier_transactions)): ?>
        <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Recent Supplier Transactions</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_supplier_transactions as $transaction): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatDateTime($transaction['tanggal_pembelian']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($transaction['nama_supplier']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($transaction['nama_barang']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $transaction['jumlah']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                    <?php echo formatCurrency($transaction['harga_satuan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                    <?php echo formatCurrency($transaction['total_pembelian']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($transaction['user_name']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

