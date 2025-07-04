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
            <button onclick="openSaleModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
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
        <div class="bg-white overflow-hidden shadow-sm rounded-lg card-shadow">
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
        <div class="bg-white overflow-hidden shadow-sm rounded-lg card-shadow">
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
        <div class="bg-white overflow-hidden shadow-sm rounded-lg card-shadow">
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

    <div class="bg-white shadow-sm rounded-lg card-shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent Sales</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="modern-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recent_sales)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                <p class="mt-2 text-sm text-gray-500">No sales recorded yet</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_sales as $sale): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['nama_barang']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $sale['qty']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatCurrency($sale['harga']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                    <?php echo formatCurrency($sale['total_penjualan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                     <?php echo formatDate($sale['tgl_jual']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
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