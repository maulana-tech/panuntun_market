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
        $id_barang = intval($_POST['id_barang'] ?? 0);
        $jumlah = intval($_POST['jumlah'] ?? 0);
        $harga_satuan = floatval($_POST['harga_satuan'] ?? 0);
        $keterangan = sanitizeInput($_POST['keterangan'] ?? '');
        
        if ($id_barang <= 0 || $jumlah <= 0 || $harga_satuan <= 0) {
            $error = 'Please fill all required fields with valid values.';
        } else {
            // Check stock availability
            $query = "SELECT nama_barang, stok FROM barang WHERE id_barang = :id_barang";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_barang', $id_barang);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $error = 'Product not found.';
            } elseif ($product['stok'] < $jumlah) {
                $error = "Insufficient stock. Available: {$product['stok']}, Requested: {$jumlah}";
            } else {
                $total_penjualan = $jumlah * $harga_satuan;
                
                $query = "INSERT INTO penjualan (id_barang, id_pengguna, jumlah, harga_satuan, total_penjualan, keterangan) 
                          VALUES (:id_barang, :id_pengguna, :jumlah, :harga_satuan, :total_penjualan, :keterangan)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_barang', $id_barang);
                $stmt->bindParam(':id_pengguna', $_SESSION['user_id']);
                $stmt->bindParam(':jumlah', $jumlah);
                $stmt->bindParam(':harga_satuan', $harga_satuan);
                $stmt->bindParam(':total_penjualan', $total_penjualan);
                $stmt->bindParam(':keterangan', $keterangan);
                
                if ($stmt->execute()) {
                    $success = "Sale recorded successfully. Total: " . formatCurrency($total_penjualan);
                } else {
                    $error = 'Failed to record sale.';
                }
            }
        }
    }
}

// Get all products for dropdown
$query = "SELECT id_barang, nama_barang, harga_jual, stok, satuan FROM barang WHERE stok > 0 ORDER BY nama_barang ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent sales with product and user information
$query = "SELECT p.*, b.nama_barang, b.satuan, u.nama as user_name 
          FROM penjualan p 
          JOIN barang b ON p.id_barang = b.id_barang 
          JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
          ORDER BY p.tanggal_penjualan DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's sales summary
$query = "SELECT COUNT(*) as total_transactions, SUM(total_penjualan) as total_amount 
          FROM penjualan 
          WHERE DATE(tanggal_penjualan) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$today_summary = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Sales Transactions';
include dirname(__DIR__) . '/components/header.php';

?>

<div class="fade-in">
    <!-- Page Header -->
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

    <!-- Alert Messages -->
    <?php if ($error): ?>
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800"><?php echo $error; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L8.23 10.661a.75.75 0 00-1.06 1.06l2.5 2.5a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800"><?php echo $success; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Today's Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
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
                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
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
                        <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
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

    <!-- Recent Sales -->
    <div class="bg-white shadow-sm rounded-lg card-shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent Sales</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recent_sales)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No sales recorded yet</p>
                                <div class="mt-6">
                                    <button onclick="openSaleModal()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Record First Sale
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_sales as $sale): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['nama_barang']); ?></div>
                                    <?php if (!empty($sale['keterangan'])): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($sale['keterangan']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $sale['jumlah']; ?> <?php echo htmlspecialchars($sale['satuan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatCurrency($sale['harga_satuan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                    <?php echo formatCurrency($sale['total_penjualan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDateTime($sale['tanggal_penjualan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($sale['user_name']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Sale Modal -->
<div id="saleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">New Sale Transaction</h3>
                <button onclick="closeSaleModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_sale">
                
                <div>
                    <label for="id_barang" class="block text-sm font-medium text-gray-700">Product *</label>
                    <select id="id_barang" name="id_barang" required onchange="updateProductInfo()" 
                            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id_barang']; ?>" 
                                    data-price="<?php echo $product['harga_jual']; ?>"
                                    data-stock="<?php echo $product['stok']; ?>"
                                    data-unit="<?php echo htmlspecialchars($product['satuan']); ?>">
                                <?php echo htmlspecialchars($product['nama_barang']); ?> 
                                (Stock: <?php echo $product['stok']; ?> <?php echo htmlspecialchars($product['satuan']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="productInfo" class="hidden bg-blue-50 border border-blue-200 rounded-md p-3">
                    <div class="text-sm text-blue-800">
                        <div>Selling Price: <span id="productPrice" class="font-medium"></span></div>
                        <div>Available Stock: <span id="productStock" class="font-medium"></span></div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="jumlah" class="block text-sm font-medium text-gray-700">Quantity *</label>
                        <input type="number" id="jumlah" name="jumlah" min="1" required onchange="calculateTotal()"
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <div>
                        <label for="harga_satuan" class="block text-sm font-medium text-gray-700">Unit Price *</label>
                        <input type="number" id="harga_satuan" name="harga_satuan" step="0.01" min="0" required onchange="calculateTotal()"
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Amount</label>
                    <div id="totalAmount" class="mt-1 text-lg font-bold text-green-600">Rp 0</div>
                </div>
                
                <div>
                    <label for="keterangan" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="keterangan" name="keterangan" rows="3"
                              class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-green-500 focus:border-green-500"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeSaleModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Record Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$additionalJS = "
<script>
function openSaleModal() {
    document.getElementById('saleModal').classList.remove('hidden');
}

function closeSaleModal() {
    document.getElementById('saleModal').classList.add('hidden');
    document.getElementById('saleModal').querySelector('form').reset();
    document.getElementById('productInfo').classList.add('hidden');
    document.getElementById('totalAmount').textContent = 'Rp 0';
}

function updateProductInfo() {
    const select = document.getElementById('id_barang');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        const price = selectedOption.dataset.price;
        const stock = selectedOption.dataset.stock;
        const unit = selectedOption.dataset.unit;
        
        document.getElementById('productPrice').textContent = formatCurrency(price);
        document.getElementById('productStock').textContent = stock + ' ' + unit;
        document.getElementById('harga_satuan').value = price;
        document.getElementById('productInfo').classList.remove('hidden');
        
        calculateTotal();
    } else {
        document.getElementById('productInfo').classList.add('hidden');
        document.getElementById('harga_satuan').value = '';
        document.getElementById('totalAmount').textContent = 'Rp 0';
    }
}

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('jumlah').value) || 0;
    const unitPrice = parseFloat(document.getElementById('harga_satuan').value) || 0;
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

