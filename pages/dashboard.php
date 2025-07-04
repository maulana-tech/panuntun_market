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

<div class="fade-in">
    <!-- Stats overview -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="modern-card bg-blue-50 border-l-4 border-blue-500">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Saldo Kas</dt>
                            <dd class="text-xl font-semibold text-gray-900"><?php echo formatCurrency($stats['cash_balance']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="modern-card bg-green-50 border-l-4 border-green-500">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Pemasukan Hari Ini</dt>
                            <dd class="text-xl font-semibold text-gray-900"><?php echo formatCurrency($stats['today_inflow']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="modern-card bg-amber-50 border-l-4 border-amber-500">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Pengeluaran Hari Ini</dt>
                            <dd class="text-xl font-semibold text-gray-900"><?php echo formatCurrency($stats['today_outflow']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="modern-card bg-purple-50 border-l-4 border-purple-500">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Produk</dt>
                            <dd class="text-xl font-semibold text-gray-900"><?php echo number_format($stats['total_products']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 mb-8">
    
    <div class="lg:col-span-3 bg-white rounded-xl shadow-lg overflow-hidden card-shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Tren Arus Kas</h3>
            <p class="text-sm text-gray-500">Ringkasan pemasukan dan pengeluaran dalam 6 bulan terakhir.</p>
        </div>
        <div class="p-6">
            <canvas id="cashFlowChart" height="250"></canvas>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg card-shadow">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Aktivitas Terkini</h3>
            </div>
            <a href="reports.php?report_type=journal" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Lihat Semua</a>
        </div>
        <div class="flow-root">
            <ul role="list" class="divide-y divide-gray-200">
                <?php if (empty($recent_transactions)): ?>
                    <li class="p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada transaksi</h3>
                        <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat transaksi baru.</p>
                    </li>
                <?php else: ?>
                    <?php foreach ($recent_transactions as $transaction): ?>
                        <li class="p-4 hover:bg-gray-50 transition-colors duration-200">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <?php if ($transaction['type'] === 'sale'): ?>
                                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </div>
                                    <?php else: ?>
                                        <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate"><?php echo $transaction['description']; ?></p>
                                    <p class="text-sm text-gray-500 truncate"><?php echo formatDate($transaction['tanggal']); ?></p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap <?php echo $transaction['type'] === 'sale' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <p class="font-semibold">
                                        <?php echo ($transaction['type'] === 'sale' ? '+' : '-') . formatCurrency($transaction['amount']); ?>
                                    </p>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

    <!-- Low Stock Alert -->
    <?php if (!empty($low_stock)): ?>
        <div class="bg-white shadow-lg rounded-xl overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-white bg-opacity-20 rounded-full p-2">
                            <svg class="h-6 w-6 text-white" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-white">Peringatan Stok Menipis</h3>
                        <p class="text-yellow-100 text-sm">Beberapa produk membutuhkan perhatian segera</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4">
                <div class="space-y-4">
                    <?php foreach ($low_stock as $product): ?>
                        <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg border border-yellow-100">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['nama_barang']); ?></h4>
                                    <p class="text-sm text-gray-500">Sisa stok: <span class="font-semibold text-yellow-600"><?php echo $product['stok']; ?> unit</span></p>
                                </div>
                            </div>
                            <a href="products.php" class="inline-flex items-center px-3 py-1 border border-yellow-300 text-sm font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                Tambah Stok
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="bg-white shadow-sm rounded-lg card-shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="sales.php" class="group relative rounded-lg p-6 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 transition-all duration-200 transform hover:scale-105">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-white bg-opacity-20">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-white">New Sale</h3>
                        <p class="mt-2 text-sm text-blue-100">Record a new sales transaction</p>
                    </div>
                </a>

                <a href="purchases.php" class="group relative rounded-lg p-6 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 transition-all duration-200 transform hover:scale-105">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-white bg-opacity-20">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-white">New Purchase</h3>
                        <p class="mt-2 text-sm text-green-100">Record a new purchase transaction</p>
                    </div>
                </a>

                <a href="products.php" class="group relative rounded-lg p-6 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 transition-all duration-200 transform hover:scale-105">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-white bg-opacity-20">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-white">Manage Products</h3>
                        <p class="mt-2 text-sm text-purple-100">Add or update product inventory</p>
                    </div>
                </a>

                <a href="./reports/cash_inflow.php" class="group relative rounded-lg p-6 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 transition-all duration-200 transform hover:scale-105">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-white bg-opacity-20">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-white">View Reports</h3>
                        <p class="mt-2 text-sm text-orange-100">Generate financial reports</p>
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

