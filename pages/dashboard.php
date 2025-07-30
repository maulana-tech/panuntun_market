<?php

/**
 * Dashboard - Tampilan Beranda
 * 
 * REFACTOR: Logika yang benar dan sederhana
 * - Total Pemasukan = SUM(total_penjualan)
 * - Total Pengeluaran = SUM(total_pembelian) 
 * - Saldo Kas = Total Pemasukan - Total Pengeluaran
 * - Tidak ada perhitungan harian yang membingungkan
 * 
 * PERBAIKAN CHART:
 * - Label yang benar: "Pemasukan Bulanan" dan "Pengeluaran Bulanan" (bukan Total)
 * - "Saldo Kas Bulanan" untuk chart saldo kas
 * - Tooltip informatif dengan status surplus/defisit
 * - Konsistensi antara data chart dan data KPI
 */
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user = getCurrentUser();
$pageTitle = 'Dashboard';

// Tentukan peran pengguna
$isAdmin = ($user && $user['jabatan'] === 'Admin');

// --- PENGAMBILAN DATA UTAMA ---
try {
    // Data utama untuk dashboard
    $total_pemasukan = (int) $db->query("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan")->fetchColumn();
    $total_pengeluaran = (int) $db->query("SELECT COALESCE(SUM(total_pembelian), 0) FROM pembelian")->fetchColumn();
    $saldo_kas = abs($total_pemasukan - $total_pengeluaran); // Always positive

    // Debug: Periksa nilai aktual
    error_log("=== DEBUG SALDO KAS ===");
    error_log("Total Pemasukan: " . $total_pemasukan . " (type: " . gettype($total_pemasukan) . ")");
    error_log("Total Pengeluaran: " . $total_pengeluaran . " (type: " . gettype($total_pengeluaran) . ")");
    error_log("Saldo Kas Calculated: " . $saldo_kas . " (type: " . gettype($saldo_kas) . ")");
    error_log("======================");

    // Data tambahan
    $total_products = $db->query("SELECT COUNT(*) FROM barang")->fetchColumn();
    $low_stock_count = $db->query("SELECT COUNT(*) FROM barang WHERE stok <= 10")->fetchColumn();

    // Data hari ini (opsional untuk informasi tambahan)
    $today_sales = $db->query("SELECT COALESCE(SUM(total_penjualan), 0) FROM penjualan WHERE DATE(tgl_jual) = CURDATE()")->fetchColumn();
    $today_purchases = $db->query("SELECT COALESCE(SUM(total_pembelian), 0) FROM pembelian WHERE DATE(tgl_beli) = CURDATE()")->fetchColumn();

    // Debug log
    error_log("Dashboard Data - Pemasukan: " . $total_pemasukan . ", Pengeluaran: " . $total_pengeluaran . ", Saldo: " . $saldo_kas);
} catch (Exception $e) {
    // Fallback jika ada error
    $total_pemasukan = 0;
    $total_pengeluaran = 0;
    $saldo_kas = 0;
    $total_products = 0;
    $low_stock_count = 0;
    $today_sales = 0;
    $today_purchases = 0;
    error_log("Dashboard Error: " . $e->getMessage());
}

// --- DATA ARUS KAS 7 HARI TERAKHIR ---
$query_cash_flow = "
    SELECT 
        DATE(transaction_date) as day,
        COALESCE(SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END), 0) as total_pemasukan,
        COALESCE(SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END), 0) as total_pengeluaran,
        COALESCE(SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE -amount END), 0) as saldo_hari
    FROM (
        SELECT 
            tgl_jual as transaction_date,
            'pemasukan' as type,
            total_penjualan as amount
        FROM penjualan 
        WHERE tgl_jual >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        
        UNION ALL
        
        SELECT 
            tgl_beli as transaction_date,
            'pengeluaran' as type,
            total_pembelian as amount
        FROM pembelian 
        WHERE tgl_beli >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    ) as all_transactions
    GROUP BY day 
    ORDER BY day ASC
";

try {
    $cash_flow_data = $db->query($query_cash_flow)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cash_flow_data = [];
}

// Jika tidak ada data, buat data kosong untuk 7 hari terakhir
if (empty($cash_flow_data)) {
    $cash_flow_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $cash_flow_data[] = [
            'day' => $day,
            'total_pemasukan' => 0,
            'total_pengeluaran' => 0,
            'saldo_hari' => 0
        ];
    }
}

// --- DATA BERDASARKAN PERAN ---
if ($isAdmin) {
    // Data untuk Admin
    $recent_transactions = $db->query("
        SELECT 
            type,
            amount,
            transaction_date,
            nama_barang,
            additional_info
        FROM (
            SELECT 
                'Penjualan' as type,
                total_penjualan as amount,
                tgl_jual as transaction_date,
                nama_barang,
                CONCAT('Qty: ', qty, ' @ Rp ', FORMAT(harga, 0)) as additional_info
            FROM penjualan 
            
            UNION ALL
            
            SELECT 
                'Pembelian' as type,
                total_pembelian as amount,
                tgl_beli as transaction_date,
                nama_barang,
                CONCAT('Qty: ', qty, ' @ Rp ', FORMAT(harga, 0)) as additional_info
            FROM pembelian
        ) as all_transactions 
        ORDER BY transaction_date DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Data untuk Owner
    $start_month = date('Y-m-01');
    $end_month = date('Y-m-t');

    try {
        // Data bulan ini
        $stmt = $db->prepare("
            SELECT 
                COALESCE(SUM(total_penjualan), 0) as total_sales,
                COUNT(*) as sales_count
            FROM penjualan 
            WHERE tgl_jual BETWEEN ? AND ?
        ");
        $stmt->execute([$start_month, $end_month]);
        $sales_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT 
                COALESCE(SUM(total_pembelian), 0) as total_purchases,
                COUNT(*) as purchase_count
            FROM pembelian 
            WHERE tgl_beli BETWEEN ? AND ?
        ");
        $stmt->execute([$start_month, $end_month]);
        $purchase_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $monthly_data = [
            'pemasukan_bulan' => $sales_data['total_sales'],
            'pengeluaran_bulan' => $purchase_data['total_purchases'],
            'saldo_bulan' => $sales_data['total_sales'] - $purchase_data['total_purchases'],
            'total_transactions' => $sales_data['sales_count'] + $purchase_data['purchase_count']
        ];

        // Produk terlaris bulan ini
        $stmt = $db->prepare("
            SELECT nama_barang, SUM(qty) as total_qty 
            FROM penjualan 
            WHERE tgl_jual BETWEEN ? AND ? 
            GROUP BY kode_barang, nama_barang 
            ORDER BY total_qty DESC 
            LIMIT 5
        ");
        $stmt->execute([$start_month, $end_month]);
        $top_selling_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $monthly_data = [
            'pemasukan_bulan' => 0,
            'pengeluaran_bulan' => 0,
            'saldo_bulan' => 0,
            'total_transactions' => 0
        ];
        $top_selling_products = [];
    }
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
                            <p class="kpi-label">Total Pemasukan</p>
                            <div class="kpi-icon bg-green-100 text-green-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value text-green-600"><?= formatCurrency($total_pemasukan) ?></p>
                        <p class="text-xs text-gray-500 mt-1">(Semua penjualan)</p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-green-500"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Total Pengeluaran</p>
                            <div class="kpi-icon bg-red-100 text-red-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value text-red-600"><?= formatCurrency($total_pengeluaran) ?></p>
                        <p class="text-xs text-gray-500 mt-1">(Semua pembelian)</p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-red-500"></div>
                    </div>
                </div>

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
                        <p class="kpi-value <?= $total_pemasukan >= $total_pengeluaran ? 'text-green-600' : 'text-red-600' ?>"><?= formatCurrency($saldo_kas) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator <?= $total_pemasukan >= $total_pengeluaran ? 'bg-green-500' : 'bg-red-500' ?>"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Total Jenis Produk</p>
                            <div class="kpi-icon bg-purple-100 text-purple-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2H5a2 2 0 00-2 2v2M7 7h10"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value"><?= number_format($total_products) ?></p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-purple-500"></div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 main-card">
                    <div class="main-card-header">
                        <h3 class="main-card-title">Tren Pemasukan & Pengeluaran (7 Hari Terakhir)</h3>
                    </div>
                    <div class="main-card-body">
                        <div style="height: 350px;"><canvas id="cashFlowChart"></canvas></div>
                    </div>
                </div>
                <div class="main-card">
                    <div class="main-card-header">
                        <h3 class="main-card-title">Aktivitas Terkini</h3>
                    </div>
                    <div class="main-card-body pt-2">
                        <?php if (empty($recent_transactions)): ?>
                            <div class="text-center py-10">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-gray-500 mt-2">Belum ada transaksi</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_transactions as $tx): ?>
                                <div class="list-item">
                                    <div class="text-lg">
                                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($tx['nama_barang']) ?></p>
                                        <p class="text-sm text-gray-500">
                                            <?= $tx['type'] ?> • <?= formatDate($tx['transaction_date']) ?>
                                            <br>
                                            <span class="text-xs text-gray-400"><?= $tx['additional_info'] ?></span>
                                        </p>
                                    </div>
                                    <p class="font-semibold text-lg <?= $tx['type'] === 'Penjualan' ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= formatCurrency($tx['amount']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                            <p class="kpi-label">Total Pemasukan</p>
                            <div class="kpi-icon bg-green-100 text-green-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value text-green-600"><?= formatCurrency($total_pemasukan) ?></p>
                        <p class="text-xs text-gray-500 mt-1">(Semua penjualan)</p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-green-500"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Total Pengeluaran</p>
                            <div class="kpi-icon bg-red-100 text-red-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value text-red-600"><?= formatCurrency($total_pengeluaran) ?></p>
                        <p class="text-xs text-gray-500 mt-1">(Semua pembelian)</p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-red-500"></div>
                    </div>
                </div>

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
                        <p class="kpi-value <?= $total_pemasukan >= $total_pengeluaran ? 'text-green-600' : 'text-red-600' ?>"><?= formatCurrency($saldo_kas) ?></p>
                        <p class="text-xs text-gray-500 mt-1">(Pemasukan - Pengeluaran)</p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator <?= $total_pemasukan >= $total_pengeluaran ? 'bg-green-500' : 'bg-red-500' ?>"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-header">
                            <p class="kpi-label">Pemasukan Bulan Ini</p>
                            <div class="kpi-icon bg-yellow-100 text-yellow-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="kpi-value text-yellow-600"><?= formatCurrency($monthly_data['pemasukan_bulan']) ?></p>
                        <p class="text-xs text-gray-500 mt-1">(Bulan ini saja)</p>
                    </div>
                    <div class="kpi-footer">
                        <div class="kpi-indicator bg-yellow-500"></div>
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
                        <h3 class="main-card-title">Analisis Saldo Kas (6 Bulan)</h3>
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
                    <?php if (empty($top_selling_products)): ?>
                        <div class="col-span-full text-center py-10">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2H5a2 2 0 00-2 2v2M7 7h10"></path>
                            </svg>
                            <p class="text-gray-500 mt-2">Belum ada penjualan bulan ini</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($top_selling_products as $p): ?>
                            <div class="text-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <p class="font-bold text-gray-800 text-lg truncate"><?= htmlspecialchars($p['nama_barang']) ?></p>
                                <p class="font-semibold text-indigo-600 text-2xl mt-2">
                                    <?= number_format($p['total_qty']) ?>
                                    <span class="text-base text-gray-500 font-normal">unit</span>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// --- JavaScript untuk Grafik ---
$js_data = [];
$js_data['labels'] = array_map(function ($d) {
    return date('d M', strtotime($d['day']));
}, $cash_flow_data);
$js_data['pemasukan'] = array_map(function ($val) {
    return floatval($val ?: 0);
}, array_column($cash_flow_data, 'total_pemasukan'));
$js_data['pengeluaran'] = array_map(function ($val) {
    return floatval($val ?: 0);
}, array_column($cash_flow_data, 'total_pengeluaran'));
$js_data['saldo'] = array_map(function ($val) {
    return floatval($val ?: 0);
}, array_column($cash_flow_data, 'saldo_hari'));



$additionalJS = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan Chart.js sudah dimuat
    if (typeof Chart === 'undefined') {
        console.error('Chart.js tidak ditemukan');
        return;
    }

    const chartData = " . json_encode($js_data) . ";
    const isAdminView = " . ($isAdmin ? 'true' : 'false') . ";

    // Chart untuk Admin: Tren Pemasukan & Pengeluaran
    const cashFlowChartElement = document.getElementById('cashFlowChart');
    if (isAdminView && cashFlowChartElement) {
        try {
            new Chart(cashFlowChartElement.getContext('2d'), {
                type: 'line',
                data: { 
                    labels: chartData.labels, 
                    datasets: [
                        { 
                            label: 'Pemasukan Bulanan', 
                            data: chartData.pemasukan, 
                            backgroundColor: 'rgba(34, 197, 94, 0.1)', 
                            borderColor: 'rgba(34, 197, 94, 1)', 
                            borderWidth: 3, 
                            tension: 0.4, 
                            fill: true, 
                            pointRadius: 6, 
                            pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                            pointBorderColor: 'white',
                            pointBorderWidth: 2
                        },
                        { 
                            label: 'Pengeluaran Bulanan', 
                            data: chartData.pengeluaran, 
                            backgroundColor: 'rgba(239, 68, 68, 0.1)', 
                            borderColor: 'rgba(239, 68, 68, 1)', 
                            borderWidth: 3, 
                            tension: 0.4, 
                            fill: true, 
                            pointRadius: 6, 
                            pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                            pointBorderColor: 'white',
                            pointBorderWidth: 2
                        }
                    ] 
                }, 
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value/1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return 'Rp ' + (value/1000).toFixed(0) + 'K';
                                    }
                                    return 'Rp ' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: 20
                        },
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });
        } catch (error) {
            console.error('Error creating admin cash flow chart:', error);
            // Fallback: tampilkan pesan error
            cashFlowChartElement.innerHTML = '<div class=\"text-center py-10\"><p class=\"text-red-500\">Error loading chart: ' + error.message + '</p></div>';
        }
    }

    // Chart untuk Owner: Analisis Arus Kas
    if (!isAdminView && cashFlowChartElement) {
        try {
            new Chart(cashFlowChartElement.getContext('2d'), {
                type: 'line',
                data: { 
                    labels: chartData.labels, 
                    datasets: [
                        { 
                            label: 'Pemasukan Bulanan', 
                            data: chartData.pemasukan, 
                            backgroundColor: 'rgba(34, 197, 94, 0.1)', 
                            borderColor: 'rgba(34, 197, 94, 1)', 
                            borderWidth: 3, 
                            tension: 0.4, 
                            fill: true, 
                            pointRadius: 6, 
                            pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                            pointBorderColor: 'white',
                            pointBorderWidth: 2
                        },
                        { 
                            label: 'Pengeluaran Bulanan', 
                            data: chartData.pengeluaran, 
                            backgroundColor: 'rgba(239, 68, 68, 0.1)', 
                            borderColor: 'rgba(239, 68, 68, 1)', 
                            borderWidth: 3, 
                            tension: 0.4, 
                            fill: true, 
                            pointRadius: 6, 
                            pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                            pointBorderColor: 'white',
                            pointBorderWidth: 2
                        }
                    ] 
                }, 
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value/1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return 'Rp ' + (value/1000).toFixed(0) + 'K';
                                    }
                                    return 'Rp ' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Analisis Arus Kas (6 Bulan Terakhir)',
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: 20
                        },
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });
        } catch (error) {
            console.error('Error creating owner cash flow chart:', error);
            cashFlowChartElement.innerHTML = '<div class=\"text-center py-10\"><p class=\"text-red-500\">Error loading chart: ' + error.message + '</p></div>';
        }
    }

    // Chart Saldo Kas (hanya untuk Owner)
    const profitChartElement = document.getElementById('profitChart');
    if (!isAdminView && profitChartElement) {
        try {
            new Chart(profitChartElement.getContext('2d'), {
                type: 'line',
                data: { 
                    labels: chartData.labels, 
                    datasets: [{ 
                        label: 'Saldo Kas Bulanan', 
                        data: chartData.saldo, 
                        borderColor: 'rgba(59, 130, 246, 1)', 
                        tension: 0.4,
                        fill: true, 
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3, 
                        pointRadius: 6, 
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2
                    }]
                }, 
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value/1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return 'Rp ' + (value/1000).toFixed(0) + 'K';
                                    }
                                    return 'Rp ' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Analisis Saldo Kas (6 Bulan Terakhir)',
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: 20
                        },
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                    return label;
                                },
                                afterLabel: function(context) {
                                    // Tambahan informasi untuk saldo kas
                                    if (context.dataset.label === 'Saldo Kas Bulanan') {
                                        const value = context.parsed.y;
                                        if (value > 0) {
                                            return ' (Surplus)';
                                        } else if (value < 0) {
                                            return ' (Defisit)';
                                        } else {
                                            return ' (Seimbang)';
                                        }
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });
        } catch (error) {
            console.error('Error creating profit chart:', error);
            profitChartElement.innerHTML = '<div class=\"text-center py-10\"><p class=\"text-red-500\">Error loading chart: ' + error.message + '</p></div>';
        }
    }
});
</script>
";

include dirname(__DIR__) . '/components/footer.php';
?>