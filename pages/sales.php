<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_sale') {
        // --- MODIFIKASI: Menggunakan kode_barang ---
        $kode_barang = intval($_POST['kode_barang'] ?? 0);
        // --- MODIFIKASI: Menggunakan qty dan harga ---
        $qty = intval($_POST['qty'] ?? 0);
        $harga = floatval($_POST['harga'] ?? 0);
        
        // --- Validasi disesuaikan ---
        if ($kode_barang <= 0 || $qty <= 0 || $harga <= 0) {
            $error = 'Please fill all required fields with valid values.';
        } else {
            // --- MODIFIKASI: Query disesuaikan dengan skema ---
            $query = "SELECT nama_barang, stok FROM barang WHERE kode_barang = :kode_barang";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':kode_barang', $kode_barang);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $error = 'Product not found.';
            } elseif ($product['stok'] < $qty) {
                $error = "Insufficient stock. Available: {$product['stok']}, Requested: {$qty}";
            } else {
                $total_penjualan = $qty * $harga;
                
                // --- MODIFIKASI: INSERT query disesuaikan dengan kolom tabel penjualan ---
                // Menghapus id_pengguna, keterangan. Mengganti nama kolom. Menambah nama_barang & tgl_jual.
                $query = "INSERT INTO penjualan (kode_barang, nama_barang, tgl_jual, harga, qty, total_penjualan) 
                          VALUES (:kode_barang, :nama_barang, CURDATE(), :harga, :qty, :total_penjualan)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':kode_barang', $kode_barang);
                $stmt->bindParam(':nama_barang', $product['nama_barang']); // Mengambil nama barang dari produk
                $stmt->bindParam(':harga', $harga);
                $stmt->bindParam(':qty', $qty);
                $stmt->bindParam(':total_penjualan', $total_penjualan);
                
                if ($stmt->execute()) {
                    $success = "Sale recorded successfully. Total: " . formatCurrency($total_penjualan);
                } else {
                    $error = 'Failed to record sale.';
                }
            }
        }
    }
}

// --- MODIFIKASI: Query disesuaikan, harga_jual & satuan tidak ada di tabel barang ---
$query = "SELECT kode_barang, nama_barang, stok FROM barang WHERE stok > 0 ORDER BY nama_barang ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- MODIFIKASI: Query disesuaikan, join ke pengguna dihapus, kolom disesuaikan ---
$query = "SELECT p.*, b.nama_barang
          FROM penjualan p 
          JOIN barang b ON p.kode_barang = b.kode_barang 
          ORDER BY p.tgl_jual DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- MODIFIKASI: Kolom tanggal disesuaikan menjadi tgl_jual ---
$query = "SELECT COUNT(*) as total_transactions, SUM(total_penjualan) as total_amount 
          FROM penjualan 
          WHERE DATE(tgl_jual) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$today_summary = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Sales Transactions';
include dirname(__DIR__) . '/components/header.php';

?>

<div class="fade-in">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sales Transactions</h1>
                <p class="mt-1 text-sm text-gray-600">Record sales and manage transactions</p>
            </div>
            <button onclick="openSaleModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Sale
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
                </div>
                <div class="ml-3"><p class="text-sm font-medium text-red-800"><?php echo $error; ?></p></div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L8.23 10.661a.75.75 0 00-1.06 1.06l2.5 2.5a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                </div>
                <div class="ml-3"><p class="text-sm font-medium text-green-800"><?php echo $success; ?></p></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white border-l-4 border-blue-500 overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center"><svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg></div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Today's Revenue</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($today_summary['total_amount'] ?? 0); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white border-l-4 border-blue-500 overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg></div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Today's Transactions</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $today_summary['total_transactions'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white border-l-4 border-blue-500 overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center"><svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg></div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Available Products</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count($products); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-lg rounded-xl border border-gray-100 overflow-hidden card-shadow">
        <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Riwayat Penjualan Terkini</h3>
                <p class="text-sm text-gray-500">10 transaksi penjualan terakhir yang tercatat.</p>
            </div>
            <a href="reports.php?report_type=cash_inflow" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
                Lihat Laporan &rarr;
            </a>
        </div>

        <div class="flow-root">
            <ul role="list" class="divide-y divide-gray-200">
                <?php if (empty($recent_sales)): ?>
                    <li class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05l.857-6a2.25 2.25 0 00-2.227-1.932m-16.5 0A2.25 2.25 0 015.625 7.5h12.75a2.25 2.25 0 012.25 2.276m-16.5 0v12c0 .621.504 1.125 1.125 1.125h14.25c.621 0 1.125-.504 1.125-1.125v-12" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Belum ada penjualan</h3>
                            <p class="mt-1 text-sm text-gray-500">Setiap transaksi penjualan yang Anda catat akan muncul di sini.</p>
                        </div>
                    </li>
                <?php else: ?>
                    <?php foreach ($recent_sales as $sale): ?>
                        <li class="p-4 hover:bg-gray-50/50 transition-colors duration-200">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75-.75v-.75m0 0l-1.125-1.5a1.125 1.125 0 010-1.5l1.125-1.5m-16.5 4.5L12 12m0 0l4.5 4.5m-4.5-4.5L7.5 7.5m4.5 4.5l-4.5 4.5M3 12h18" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($sale['nama_barang']); ?></p>
                                    <p class="text-sm text-gray-500">
                                        <span><?php echo $sale['qty']; ?> pcs</span>
                                        <span class="mx-1">&middot;</span>
                                        <span><?php echo formatCurrency($sale['harga']); ?></span>
                                    </p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap">
                                    <p class="font-bold text-green-600">
                                        <?php echo formatCurrency($sale['total_penjualan']); ?>
                                    </p>
                                    <p class="text-gray-500">
                                        <?php echo formatDate($sale['tgl_jual']); ?>
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

<div id="saleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">New Sale Transaction</h3>
                <button onclick="closeSaleModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_sale">
                
                <div>
                    <label for="kode_barang" class="block text-sm font-medium text-gray-700">Product *</label>
                    <select id="kode_barang" name="kode_barang" required
                            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['kode_barang']; ?>"
                                    data-stock="<?php echo $product['stok']; ?>">
                                <?php echo htmlspecialchars($product['nama_barang']); ?> 
                                (Stock: <?php echo $product['stok']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="qty" class="block text-sm font-medium text-gray-700">Quantity *</label>
                        <input type="number" id="qty" name="qty" min="1" required onchange="calculateTotal()"
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <div>
                        <label for="harga" class="block text-sm font-medium text-gray-700">Unit Price *</label>
                        <input type="number" id="harga" name="harga" step="0.01" min="0" required onchange="calculateTotal()"
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Amount</label>
                    <div id="totalAmount" class="mt-1 text-lg font-bold text-green-600">Rp 0</div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeSaleModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Record Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// --- MODIFIKASI: JavaScript disederhanakan karena harga diinput manual ---
$additionalJS = "
<script>
function openSaleModal() {
    document.getElementById('saleModal').classList.remove('hidden');
}

function closeSaleModal() {
    document.getElementById('saleModal').classList.add('hidden');
    document.getElementById('saleModal').querySelector('form').reset();
    document.getElementById('totalAmount').textContent = 'Rp 0';
}

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('qty').value) || 0;
    const unitPrice = parseFloat(document.getElementById('harga').value) || 0;
    const total = quantity * unitPrice;
    
    document.getElementById('totalAmount').textContent = formatCurrency(total);
}

function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('saleModal');
    if (event.target === modal) {
        closeSaleModal();
    }
}
</script>
";

include dirname(__DIR__) . '/components/footer.php';

?>