<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user = getCurrentUser();
$pageTitle = 'Dashboard';

// Tentukan peran pengguna
$isAdmin = ($user && $user['jabatan'] === 'Admin');

// --- Pengambilan Data Umum ---
$cash_balance = $db->query("SELECT saldo FROM kas ORDER BY id_kas DESC LIMIT 1")->fetchColumn() ?: 0;
$query_cash_flow = "
    SELECT DATE_FORMAT(tgl, '%Y-%m') as month, SUM(inflow) as total_inflow, SUM(outflow) as total_outflow
    FROM (
        SELECT tgl_transaksi as tgl, jumlah as inflow, 0 as outflow FROM kas_masuk
        UNION ALL
        SELECT tgl_transaksi as tgl, 0 as inflow, jumlah as outflow FROM kas_keluar
    ) as transactions
    WHERE tgl >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month ASC
";
$cash_flow_data = $db->query($query_cash_flow)->fetchAll(PDO::FETCH_ASSOC);

// --- Blok Logika Berdasarkan Peran ---
if ($isAdmin) {
    // --- Data Khusus untuk ADMIN ---
    $stats = [
        'today_inflow' => $db->query("SELECT COALESCE(SUM(jumlah), 0) FROM kas_masuk WHERE DATE(tgl_transaksi) = CURDATE()")->fetchColumn(),
        'today_outflow' => $db->query("SELECT COALESCE(SUM(jumlah), 0) FROM kas_keluar WHERE DATE(tgl_transaksi) = CURDATE()")->fetchColumn(),
        'total_products' => $db->query("SELECT COUNT(*) FROM barang")->fetchColumn(),
        'low_stock_count' => $db->query("SELECT COUNT(*) FROM barang WHERE stok <= 10")->fetchColumn()
    ];
    $recent_transactions = $db->query("(SELECT 'Penjualan' as type, total_penjualan as amount, tgl_jual as date, nama_barang FROM penjualan ORDER BY id_penjualan DESC LIMIT 4) UNION ALL (SELECT 'Pembelian' as type, total_pembelian as amount, tgl_beli as date, nama_barang FROM pembelian ORDER BY id_pembelian DESC LIMIT 4) ORDER BY date DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
} else { // Jika dia Owner
    // --- Data Khusus untuk OWNER ---
    $start_month = date('Y-m-01');
    $end_month = date('Y-m-t');

    $sales_this_month = $db->query("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan WHERE tgl_jual BETWEEN '$start_month' AND '$end_month'")->fetchColumn();
    $purchases_this_month = $db->query("SELECT COALESCE(SUM(total_pembelian), 0) FROM pembelian WHERE tgl_beli BETWEEN '$start_month' AND '$end_month'")->fetchColumn();

    // PERBAIKAN: Memasukkan hasil query ke dalam array $insights
    $insights = [
        'profit_this_month' => $sales_this_month - $purchases_this_month,
        'monthly_revenue' => $sales_this_month,
        'total_transactions' => $db->query("SELECT COUNT(*) FROM kas WHERE tgl_transaksi BETWEEN '$start_month' AND '$end_month'")->fetchColumn() ?: 0
    ];

    $top_selling_products = $db->query("SELECT nama_barang, SUM(qty) as total_qty FROM penjualan WHERE tgl_jual BETWEEN '$start_month' AND '$end_month' GROUP BY kode_barang, nama_barang ORDER BY total_qty DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
}

include dirname(__DIR__) . '/components/header.php';
?>

<style>
    body {
        background-color: #f7f8fc;
    }

    .main-content-wrapper {
        padding: 2.5rem;
    }

    .dashboard-header {
        margin-bottom: 2.5rem;
    }

    .dashboard-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1a202c;
    }

    .dashboard-subtitle {
        font-size: 1.125rem;
        color: #718096;
    }

    /* Kartu KPI */
    .kpi-card {
        background-color: white;
        border-radius: 1.25rem;
        padding: 1.75rem;
        box-shadow: 0 7px 25px -5px rgba(0, 0, 0, 0.04);
        border: 1px solid #f0f0f0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.3s ease;
    }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.07);
    }

    .kpi-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .kpi-label {
        font-size: 1rem;
        font-weight: 500;
        color: #64748b;
        /* slate-500 */
    }

    .kpi-icon {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .kpi-value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1.2;
        margin-top: 0.75rem;
        align-items: center;
        justify-content: center;
        color: #1e293b;
        /* slate-800 */
    }

    .kpi-footer {
        margin-top: 1.5rem;
    }

    .kpi-indicator {
        height: 5px;
        border-radius: 9999px;
        width: 100%;
    }

    /* Kartu Utama (Grafik & List) */
    .main-card {
        background-color: white;
        border-radius: 1.25rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
    }

    .main-card-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .main-card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2d3748;
    }

    .main-card-body {
        padding: 2rem;
    }

    .list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 0;
    }

    .list-item:not(:last-child) {
        border-bottom: 1px solid #f7fafc;
    }
</style>

<div class="main-content-wrapper">
    <header class="dashboard-header">
        <h1 class="dashboard-title">Beranda</h1>
        <p class="dashboard-subtitle">Selamat datang kembali, <?php echo htmlspecialchars($user['nama']); ?>!</p>
    </header>

    <?php if ($isAdmin): ?>
        <div class="space-y-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Saldo Kas</p>
                            <div class="kpi-icon bg-blue-100 text-blue-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value"><?= formatCurrency($cash_balance) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-blue-500"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Pemasukan Hari Ini</p>
                            <div class="kpi-icon bg-green-100 text-green-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value"><?= formatCurrency($stats['today_inflow']) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-green-500"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Pengeluaran Hari Ini</p>
                            <div class="kpi-icon bg-red-100 text-red-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value"><?= formatCurrency($stats['today_outflow']) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-red-500"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Total Jenis Produk</p>
                            <div class="kpi-icon bg-purple-100 text-purple-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value"><?= number_format($stats['total_products']) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-purple-500"></div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 main-card">
                    <div class="main-card-header">
                        <h3 class="main-card-title">Tren Arus Kas (6 Bulan)</h3>
                    </div>
                    <div class="main-card-body">
                        <div style="height: 350px;"><canvas id="cashFlowChart"></canvas></div>
                    </div>
                </div>
                <div class="main-card">
                    <div class="main-card-header">
                        <h3 class="main-card-title">Aktivitas Terkini</h3>
                    </div>
                    <div class="main-card-body pt-2"><?php if (empty($recent_transactions)): ?><p class="text-gray-500 text-center py-10">Tidak ada transaksi.</p><?php else: foreach ($recent_transactions as $tx): ?>
                                <div class="list-item">
                                    <div class="text-lg">
                                        <p class="font-semibold text-gray-800"><?= $tx['type'] ?></p>
                                        <p class="text-sm text-gray-500"><?= formatDate($tx['date']) ?></p>
                                    </div>
                                    <p class="font-semibold text-lg <?= $tx['type'] === 'Penjualan' ? 'text-green-600' : 'text-red-600' ?>"><?= formatCurrency($tx['amount']) ?></p>
                                </div><?php endforeach;
                                                                                                                                                                endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php else: // Tampilan untuk Owner 
    ?>
        <div class="space-y-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Pendapatan Bulan Ini</p>
                            <div class="kpi-icon bg-green-100 text-green-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value text-green-600"><?= formatCurrency($insights['monthly_revenue']) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-green-500"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Keuntungan Bulan Ini</p>
                            <div class="kpi-icon bg-blue-100 text-blue-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value text-blue-600"><?= formatCurrency($insights['profit_this_month']) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-blue-500"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Total Transaksi (Bulan Ini)</p>
                            <div class="kpi-icon bg-purple-100 text-purple-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value text-purple-600"><?= number_format($insights['total_transactions']) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-purple-500"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Saldo Kas Saat Ini</p>
                            <div class="kpi-icon bg-gray-200 text-gray-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value text-gray-800"><?= formatCurrency($cash_balance) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-gray-500"></div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="main-card">
                    <div class="main-card-header">
                        <h3 class="main-card-title">Analisis Arus Kas (6 Bulan)</h3>
                    </div>
                    <div class="main-card-body">
                        <div style="height: 350px;"><canvas id="cashFlowChart"></canvas></div>
                    </div>
                </div>
                <div class="main-card">
                    <div class="main-card-header">
                        <h3 class="main-card-title">Analisis Tren Keuntungan (6 Bulan)</h3>
                    </div>
                    <div class="main-card-body">
                        <div style="height: 350px;"><canvas id="profitChart"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="main-card">
                <div class="main-card-header">
                    <h3 class="main-card-title">Produk Terlaris (Bulan Ini)</h3>
                </div>
                <div class="main-card-body grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                    <?php if (empty($top_selling_products)): ?><p class="text-gray-500 text-center py-6 col-span-full">Belum ada penjualan.</p><?php else: foreach ($top_selling_products as $p): ?>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <p class="font-bold text-gray-800 text-lg truncate"><?= htmlspecialchars($p['nama_barang']) ?></p>
                                <p class="font-semibold text-indigo-600 text-2xl mt-2"><?= $p['total_qty'] ?> <span class="text-base text-gray-500 font-normal">unit</span></p>
                            </div>
                    <?php endforeach;
                                                                                                                                            endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// --- JavaScript untuk Grafik ---
$js_data = [];
$js_data['labels'] = array_map(fn($d) => date('M Y', strtotime($d['month'])), $cash_flow_data);
$js_data['inflows'] = array_column($cash_flow_data, 'total_inflow');
$js_data['outflows'] = array_column($cash_flow_data, 'total_outflow');

$additionalJS = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = " . json_encode($js_data) . ";
    const isAdminView = " . ($isAdmin ? 'true' : 'false') . ";

    const baseOptions = {
        responsive: true, maintainAspectRatio: false,
        scales: { 
            y: { beginAtZero: true, grid: { drawBorder: false }, ticks: { padding: 10, callback: (v) => 'Rp ' + (v/1000) + 'k' } },
            x: { grid: { display: false } }
        },
        plugins: { 
            tooltip: { callbacks: { label: (c) => c.dataset.label + ': Rp ' + c.parsed.y.toLocaleString('id-ID') }, padding: 12, titleFont: {size: 14}, bodyFont: {size: 12} },
            legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 10, padding: 20 } }
        },
        interaction: { mode: 'index', intersect: false }
    };

    if (document.getElementById('cashFlowChart')) {
        new Chart(document.getElementById('cashFlowChart').getContext('2d'), {
            type: 'bar',
            data: { labels: chartData.labels, datasets: [
                { label: 'Pemasukan', data: chartData.inflows, backgroundColor: 'rgba(75, 192, 192, 0.7)', borderColor: 'rgba(75, 192, 192, 1)', borderRadius: 6 },
                { label: 'Pengeluaran', data: chartData.outflows, backgroundColor: 'rgba(255, 99, 132, 0.7)', borderColor: 'rgba(255, 99, 132, 1)', borderRadius: 6 }
            ] }, options: baseOptions
        });
    }

    if (!isAdminView && document.getElementById('profitChart')) {
        const profits = chartData.inflows.map((inflow, i) => inflow - chartData.outflows[i]);
        new Chart(document.getElementById('profitChart').getContext('2d'), {
            type: 'line',
            data: { labels: chartData.labels, datasets: [{ 
                label: 'Keuntungan', data: profits, borderColor: 'rgba(54, 162, 235, 1)', tension: 0.4,
                fill: true, backgroundColor: 'rgba(54, 162, 235, 0.1)', borderWidth: 3, pointRadius: 5, pointBackgroundColor: 'rgba(54, 162, 235, 1)'
            }]}, options: baseOptions
        });
    }
});
</script>
";

include dirname(__DIR__) . '/components/footer.php';
?>