<?php
// Users Report

// Get all users with activity statistics
$query = "SELECT 
    u.id_pengguna,
    u.nama,
    u.email,
    u.role,
    u.tanggal_dibuat,
    u.terakhir_login,
    COUNT(DISTINCT p.id_penjualan) as total_sales,
    COUNT(DISTINCT pm.id_pembelian) as total_purchases,
    COALESCE(SUM(p.total_penjualan), 0) as total_sales_amount,
    COALESCE(SUM(pm.total_pembelian), 0) as total_purchases_amount,
    (COUNT(DISTINCT p.id_penjualan) + COUNT(DISTINCT pm.id_pembelian)) as total_transactions
FROM pengguna u
LEFT JOIN penjualan p ON u.id_pengguna = p.id_pengguna
LEFT JOIN pembelian pm ON u.id_pengguna = pm.id_pengguna
GROUP BY u.id_pengguna, u.nama, u.email, u.role, u.tanggal_dibuat, u.terakhir_login
ORDER BY total_transactions DESC, u.nama ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$users_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user summary statistics
$query = "SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_users,
    COUNT(CASE WHEN role = 'owner' THEN 1 END) as owner_users,
    COUNT(CASE WHEN terakhir_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_users_30d,
    COUNT(CASE WHEN terakhir_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_users_7d
FROM pengguna";

$stmt = $db->prepare($query);
$stmt->execute();
$user_summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent user activities
$query = "
    SELECT 
        'Sale' as activity_type,
        u.nama as user_name,
        u.role,
        p.tanggal_penjualan as activity_date,
        CONCAT('Sold ', b.nama_barang, ' (Qty: ', p.jumlah, ')') as activity_description,
        p.total_penjualan as amount
    FROM penjualan p
    JOIN pengguna u ON p.id_pengguna = u.id_pengguna
    JOIN barang b ON p.id_barang = b.id_barang
    WHERE p.tanggal_penjualan >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    
    UNION ALL
    
    SELECT 
        'Purchase' as activity_type,
        u.nama as user_name,
        u.role,
        pm.tanggal_pembelian as activity_date,
        CONCAT('Purchased ', b.nama_barang, ' from ', s.nama_supplier, ' (Qty: ', pm.jumlah, ')') as activity_description,
        pm.total_pembelian as amount
    FROM pembelian pm
    JOIN pengguna u ON pm.id_pengguna = u.id_pengguna
    JOIN barang b ON pm.id_barang = b.id_barang
    JOIN supplier s ON pm.id_supplier = s.id_supplier
    WHERE pm.tanggal_pembelian >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    
    ORDER BY activity_date DESC
    LIMIT 20
";

$stmt = $db->prepare($query);
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user performance by month
$query = "
    SELECT 
        u.nama as user_name,
        DATE_FORMAT(activity_date, '%Y-%m') as month,
        SUM(CASE WHEN activity_type = 'Sale' THEN 1 ELSE 0 END) as monthly_sales,
        SUM(CASE WHEN activity_type = 'Purchase' THEN 1 ELSE 0 END) as monthly_purchases,
        SUM(CASE WHEN activity_type = 'Sale' THEN amount ELSE 0 END) as monthly_sales_amount,
        SUM(CASE WHEN activity_type = 'Purchase' THEN amount ELSE 0 END) as monthly_purchases_amount
    FROM (
        SELECT 
            'Sale' as activity_type,
            u.id_pengguna,
            u.nama,
            p.tanggal_penjualan as activity_date,
            p.total_penjualan as amount
        FROM penjualan p
        JOIN pengguna u ON p.id_pengguna = u.id_pengguna
        WHERE p.tanggal_penjualan >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        
        UNION ALL
        
        SELECT 
            'Purchase' as activity_type,
            u.id_pengguna,
            u.nama,
            pm.tanggal_pembelian as activity_date,
            pm.total_pembelian as amount
        FROM pembelian pm
        JOIN pengguna u ON pm.id_pengguna = u.id_pengguna
        WHERE pm.tanggal_pembelian >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    ) as user_activities
    JOIN pengguna u ON user_activities.id_pengguna = u.id_pengguna
    GROUP BY u.nama, month
    ORDER BY month DESC, monthly_sales_amount DESC
    LIMIT 30
";

$stmt = $db->prepare($query);
$stmt->execute();
$user_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Users Report</h3>
    <p class="text-sm text-gray-600">User management and activity analysis as of <?php echo date('d/m/Y H:i:s'); ?></p>
</div>

<div class="p-6">
    <!-- User Summary -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-blue-800">Total Users</div>
            <div class="text-2xl font-bold text-blue-600"><?php echo $user_summary['total_users'] ?? 0; ?></div>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-purple-800">Administrators</div>
            <div class="text-2xl font-bold text-purple-600"><?php echo $user_summary['admin_users'] ?? 0; ?></div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-green-800">Owners</div>
            <div class="text-2xl font-bold text-green-600"><?php echo $user_summary['owner_users'] ?? 0; ?></div>
        </div>
        
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-emerald-800">Active (7 days)</div>
            <div class="text-2xl font-bold text-emerald-600"><?php echo $user_summary['active_users_7d'] ?? 0; ?></div>
        </div>
        
        <div class="bg-teal-50 border border-teal-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-teal-800">Active (30 days)</div>
            <div class="text-2xl font-bold text-teal-600"><?php echo $user_summary['active_users_30d'] ?? 0; ?></div>
        </div>
    </div>

    <!-- Detailed User Information -->
    <div class="mb-8">
        <h4 class="text-lg font-medium text-gray-900 mb-4">User Details & Performance</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Information</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sales</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Purchases</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sales Amount</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member Since</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users_data)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                No users found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users_data as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['nama']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php
                                    $role_colors = [
                                        'admin' => 'bg-blue-100 text-blue-800',
                                        'owner' => 'bg-purple-100 text-purple-800'
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $role_colors[$user['role']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $user['total_sales']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $user['total_purchases']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                    <?php echo formatCurrency($user['total_sales_amount']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                    <?php echo formatCurrency($user['total_purchases_amount']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $user['terakhir_login'] ? formatDateTime($user['terakhir_login']) : 'Never'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($user['tanggal_dibuat'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Performance by Month -->
    <?php if (!empty($user_performance)): ?>
        <div class="mb-8">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Monthly User Performance (Last 6 Months)</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sales Count</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Count</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sales Amount</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($user_performance as $performance): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($performance['user_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo date('M Y', strtotime($performance['month'] . '-01')); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $performance['monthly_sales']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    <?php echo $performance['monthly_purchases']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                    <?php echo formatCurrency($performance['monthly_sales_amount']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                    <?php echo formatCurrency($performance['monthly_purchases_amount']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent User Activities -->
    <?php if (!empty($recent_activities)): ?>
        <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Recent User Activities (Last 30 Days)</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_activities as $activity): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatDateTime($activity['activity_date']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($activity['user_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $role_colors[$activity['role']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo ucfirst($activity['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $activity['activity_type'] === 'Sale' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo $activity['activity_type']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($activity['activity_description']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium <?php echo $activity['activity_type'] === 'Sale' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo formatCurrency($activity['amount']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

