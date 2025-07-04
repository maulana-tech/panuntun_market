<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats = [];

// Total cash balance (latest saldo from kas table)
$query = "SELECT COALESCE(saldo, 0) as total_balance FROM kas ORDER BY created_at DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['cash_balance'] = $result ? $result['total_balance'] : 0;

// Today's cash inflow
$query = "SELECT COALESCE(SUM(jumlah), 0) as today_inflow FROM kas_masuk WHERE DATE(tgl_transaksi) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['today_inflow'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_inflow'];

// Today's cash outflow
$query = "SELECT COALESCE(SUM(jumlah), 0) as today_outflow FROM kas_keluar WHERE DATE(tgl_transaksi) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['today_outflow'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_outflow'];

// Total products
$query = "SELECT COUNT(*) as total_products FROM barang";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

// Total suppliers
$query = "SELECT COUNT(*) as total_suppliers FROM supplier";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_suppliers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_suppliers'];

// Recent transactions
$query = "
    (SELECT 'sale' as type, id_penjualan as id, tgl_jual as tanggal, total_penjualan as amount, 'Penjualan' as description
     FROM penjualan ORDER BY tgl_jual DESC LIMIT 5)
    UNION ALL
    (SELECT 'purchase' as type, id_pembelian as id, tgl_beli as tanggal, total_pembelian as amount, 'Pembelian' as description
     FROM pembelian ORDER BY tgl_beli DESC LIMIT 5)
    ORDER BY tanggal DESC LIMIT 10
";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$query = "SELECT nama_barang, stok FROM barang WHERE stok < 10 ORDER BY stok ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly cash flow data for chart
$query = "
    SELECT 
        DATE_FORMAT(tgl_transaksi, '%Y-%m') as month,
        SUM(jumlah) as inflow,
        0 as outflow
    FROM kas_masuk 
    WHERE tgl_transaksi >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tgl_transaksi, '%Y-%m')
    
    UNION ALL
    
    SELECT 
        DATE_FORMAT(tgl_transaksi, '%Y-%m') as month,
        0 as inflow,
        SUM(jumlah) as outflow
    FROM kas_keluar 
    WHERE tgl_transaksi >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tgl_transaksi, '%Y-%m')
    
    ORDER BY month
";
$stmt = $db->prepare($query);
$stmt->execute();
$cash_flow_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard';
include dirname(__DIR__) . '/components/header.php';
?>

<div class="fade-in font-sans">
    <!-- Stats overview -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Cash Balance -->
        <div class="card stats-card">
            <div class="card-body">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="icon-bg bg-green-100">
                            <svg class="icon-svg text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Cash Balance</dt>
                            <dd class="text-xl font-semibold text-gray-900"><?php echo formatCurrency($stats['cash_balance']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Inflow -->
        <div class="card stats-card">
            <div class="card-body">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="icon-bg bg-blue-100">
                            <svg class="icon-svg text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Today's Inflow</dt>
                            <dd class="text-xl font-semibold text-gray-900"><?php echo formatCurrency($stats['today_inflow']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Outflow -->
        <div class="card stats-card">
            <div class="card-body">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="icon-bg bg-red-100">
                            <svg class="icon-svg text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Today's Outflow</dt>
                            <dd class="text-xl font-semibold text-gray-900"><?php echo formatCurrency($stats['today_outflow']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Products -->
        <div class="card stats-card">
            <div class="card-body">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="icon-bg bg-purple-100">
                            <svg class="icon-svg text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Products</dt>
                            <dd class="text-xl font-semibold text-gray-900"><?php echo number_format($stats['total_products']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Cash Flow Chart (Larger) -->
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="card-title">Cash Flow Trend (Last 6 Months)</h3>
            </div>
            <div class="card-body">
                <canvas id="cashFlowChart" height="250"></canvas> <!-- Adjusted height -->
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Transactions</h3>
            </div>
            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                <?php if (empty($recent_transactions)): ?>
                    <div class="card-body text-center text-gray-500">
                        No recent transactions found.
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_transactions as $transaction): ?>
                        <div class="p-4 hover:bg-gray-50 transition-colors duration-150">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <?php if ($transaction['type'] === 'sale'): ?>
                                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                                                </svg>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($transaction['description']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo formatDate($transaction['tanggal']); ?></p>
                                    </div>
                                </div>
                                <div class="text-sm font-medium <?php echo $transaction['type'] === 'sale' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo ($transaction['type'] === 'sale' ? '+' : '-') . formatCurrency($transaction['amount']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if (!empty($low_stock)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-r-lg p-6 mb-8 shadow-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-md font-semibold text-yellow-800">Low Stock Alert</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>The following products are running low on stock:</p>
                        <ul class="mt-2 list-disc list-inside space-y-1">
                            <?php foreach ($low_stock as $product): ?>
                                <li>
                                    <span class="font-medium"><?php echo htmlspecialchars($product['nama_barang']); ?></span>:
                                    <span class="text-yellow-800 font-semibold"><?php echo $product['stok']; ?> remaining</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <a href="sales.php" class="quick-action-card bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700">
                    <div class="icon-wrapper">
                        <svg class="icon-svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                        </svg>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-semibold text-white">New Sale</h3>
                        <p class="mt-1 text-sm text-blue-100">Record a new sales transaction</p>
                    </div>
                </a>

                <a href="purchases.php" class="quick-action-card bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700">
                    <div class="icon-wrapper">
                        <svg class="icon-svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-semibold text-white">New Purchase</h3>
                        <p class="mt-1 text-sm text-green-100">Record a new purchase transaction</p>
                    </div>
                </a>

                <a href="products.php" class="quick-action-card bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700">
                    <div class="icon-wrapper">
                        <svg class="icon-svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-semibold text-white">Manage Products</h3>
                        <p class="mt-1 text-sm text-purple-100">Add or update product inventory</p>
                    </div>
                </a>

                <a href="./reports/cash_inflow.php" class="quick-action-card bg-gradient-to-br from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700">
                    <div class="icon-wrapper">
                        <svg class="icon-svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-semibold text-white">View Reports</h3>
                        <p class="mt-1 text-sm text-orange-100">Generate financial reports</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = "
<script>
// Cash Flow Chart
const ctx = document.getElementById('cashFlowChart').getContext('2d');
const cashFlowChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Cash Inflow',
            data: [],
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4
        }, {
            label: 'Cash Outflow',
            data: [],
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});

// Process cash flow data
const cashFlowData = " . json_encode($cash_flow_data) . ";
const months = [];
const inflows = [];
const outflows = [];

// Group data by month
const monthlyData = {};
cashFlowData.forEach(item => {
    if (!monthlyData[item.month]) {
        monthlyData[item.month] = { inflow: 0, outflow: 0 };
    }
    monthlyData[item.month].inflow += parseFloat(item.inflow);
    monthlyData[item.month].outflow += parseFloat(item.outflow);
});

// Convert to arrays for chart
Object.keys(monthlyData).sort().forEach(month => {
    const date = new Date(month + '-01');
    months.push(date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }));
    inflows.push(monthlyData[month].inflow);
    outflows.push(monthlyData[month].outflow);
});

// Update chart data
cashFlowChart.data.labels = months;
cashFlowChart.data.datasets[0].data = inflows;
cashFlowChart.data.datasets[1].data = outflows;
cashFlowChart.update();
</script>
";

include dirname(__DIR__) . '/components/footer.php';
?>

