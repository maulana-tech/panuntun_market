<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nama_barang = sanitizeInput($_POST['nama_barang'] ?? '');
                $kategori = sanitizeInput($_POST['kategori'] ?? '');
                $harga_beli = floatval($_POST['harga_beli'] ?? 0);
                $harga_jual = floatval($_POST['harga_jual'] ?? 0);
                $stok = intval($_POST['stok'] ?? 0);
                $stok_minimum = intval($_POST['stok_minimum'] ?? 0);
                $satuan = sanitizeInput($_POST['satuan'] ?? '');
                $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
                
                if (empty($nama_barang) || empty($kategori) || $harga_beli <= 0 || $harga_jual <= 0) {
                    $error = 'Product name, category, purchase price, and selling price are required.';
                } else {
                    $query = "INSERT INTO barang (nama_barang, kategori, harga_beli, harga_jual, stok, stok_minimum, satuan, deskripsi) 
                              VALUES (:nama_barang, :kategori, :harga_beli, :harga_jual, :stok, :stok_minimum, :satuan, :deskripsi)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nama_barang', $nama_barang);
                    $stmt->bindParam(':kategori', $kategori);
                    $stmt->bindParam(':harga_beli', $harga_beli);
                    $stmt->bindParam(':harga_jual', $harga_jual);
                    $stmt->bindParam(':stok', $stok);
                    $stmt->bindParam(':stok_minimum', $stok_minimum);
                    $stmt->bindParam(':satuan', $satuan);
                    $stmt->bindParam(':deskripsi', $deskripsi);
                    
                    if ($stmt->execute()) {
                        $success = 'Product added successfully.';
                    } else {
                        $error = 'Failed to add product.';
                    }
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id'] ?? 0);
                $nama_barang = sanitizeInput($_POST['nama_barang'] ?? '');
                $kategori = sanitizeInput($_POST['kategori'] ?? '');
                $harga_beli = floatval($_POST['harga_beli'] ?? 0);
                $harga_jual = floatval($_POST['harga_jual'] ?? 0);
                $stok = intval($_POST['stok'] ?? 0);
                $stok_minimum = intval($_POST['stok_minimum'] ?? 0);
                $satuan = sanitizeInput($_POST['satuan'] ?? '');
                $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
                
                if (empty($nama_barang) || empty($kategori) || $harga_beli <= 0 || $harga_jual <= 0) {
                    $error = 'Product name, category, purchase price, and selling price are required.';
                } else {
                    $query = "UPDATE barang SET nama_barang = :nama_barang, kategori = :kategori, harga_beli = :harga_beli, 
                              harga_jual = :harga_jual, stok = :stok, stok_minimum = :stok_minimum, satuan = :satuan, 
                              deskripsi = :deskripsi WHERE id_barang = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nama_barang', $nama_barang);
                    $stmt->bindParam(':kategori', $kategori);
                    $stmt->bindParam(':harga_beli', $harga_beli);
                    $stmt->bindParam(':harga_jual', $harga_jual);
                    $stmt->bindParam(':stok', $stok);
                    $stmt->bindParam(':stok_minimum', $stok_minimum);
                    $stmt->bindParam(':satuan', $satuan);
                    $stmt->bindParam(':deskripsi', $deskripsi);
                    $stmt->bindParam(':id', $id);
                    
                    if ($stmt->execute()) {
                        $success = 'Product updated successfully.';
                    } else {
                        $error = 'Failed to update product.';
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                
                // Check if product has related transactions
                $query = "SELECT COUNT(*) as count FROM penjualan WHERE id_barang = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $error = 'Cannot delete product. There are existing sales records associated with this product.';
                } else {
                    $query = "DELETE FROM barang WHERE id_barang = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    
                    if ($stmt->execute()) {
                        $success = 'Product deleted successfully.';
                    } else {
                        $error = 'Failed to delete product.';
                    }
                }
                break;
        }
    }
}

// Get all products with stock status
$query = "SELECT *, 
          CASE 
              WHEN stok <= stok_minimum THEN 'low'
              WHEN stok <= (stok_minimum * 2) THEN 'medium'
              ELSE 'good'
          END as stock_status,
          (harga_jual - harga_beli) as profit_margin,
          ((harga_jual - harga_beli) / harga_beli * 100) as profit_percentage
          FROM barang 
          ORDER BY nama_barang ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$query = "SELECT DISTINCT kategori FROM barang WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Product Management';
include dirname(__DIR__) . '/components/header.php';

?>

<div class="fade-in">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Product Management</h1>
                <p class="mt-1 text-sm text-gray-600">Manage inventory and product information</p>
            </div>
            <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Product
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

    <!-- Filter and Search -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" id="searchInput" placeholder="Search products..." 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div class="sm:w-48">
                <select id="categoryFilter" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:w-48">
                <select id="stockFilter" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    <option value="">All Stock Levels</option>
                    <option value="low">Low Stock</option>
                    <option value="medium">Medium Stock</option>
                    <option value="good">Good Stock</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="productsGrid">
        <?php if (empty($products)): ?>
            <div class="col-span-full">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No products</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding a new product.</p>
                    <div class="mt-6">
                        <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Product
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card bg-white overflow-hidden shadow-sm rounded-lg card-shadow hover-scale" 
                     data-name="<?php echo strtolower($product['nama_barang']); ?>"
                     data-category="<?php echo strtolower($product['kategori']); ?>"
                     data-stock="<?php echo $product['stock_status']; ?>">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900 mb-1"><?php echo htmlspecialchars($product['nama_barang']); ?></h3>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($product['kategori']); ?>
                                </span>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                        class="text-primary-600 hover:text-primary-900 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="confirmDelete(<?php echo $product['id_barang']; ?>, '<?php echo htmlspecialchars($product['nama_barang']); ?>')" 
                                        class="text-red-600 hover:text-red-900 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Purchase Price</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo formatCurrency($product['harga_beli']); ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Selling Price</span>
                                <span class="text-sm font-medium text-green-600"><?php echo formatCurrency($product['harga_jual']); ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Profit Margin</span>
                                <span class="text-sm font-medium text-blue-600"><?php echo number_format($product['profit_percentage'], 1); ?>%</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Stock</span>
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900 mr-2"><?php echo $product['stok']; ?> <?php echo htmlspecialchars($product['satuan'] ?? 'pcs'); ?></span>
                                    <?php
                                    $stockClass = '';
                                    $stockIcon = '';
                                    switch ($product['stock_status']) {
                                        case 'low':
                                            $stockClass = 'text-red-500';
                                            $stockIcon = '⚠️';
                                            break;
                                        case 'medium':
                                            $stockClass = 'text-yellow-500';
                                            $stockIcon = '⚡';
                                            break;
                                        default:
                                            $stockClass = 'text-green-500';
                                            $stockIcon = '✅';
                                    }
                                    ?>
                                    <span class="<?php echo $stockClass; ?>"><?php echo $stockIcon; ?></span>
                                </div>
                            </div>
                            
                            <?php if ($product['stock_status'] === 'low'): ?>
                                <div class="bg-red-50 border border-red-200 rounded-md p-2">
                                    <p class="text-xs text-red-600">
                                        <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        Low stock alert! Minimum: <?php echo $product['stok_minimum']; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['deskripsi'])): ?>
                                <div class="pt-2 border-t border-gray-200">
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($product['deskripsi']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add New Product</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label for="add_nama_barang" class="block text-sm font-medium text-gray-700">Product Name *</label>
                        <input type="text" id="add_nama_barang" name="nama_barang" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="add_kategori" class="block text-sm font-medium text-gray-700">Category *</label>
                        <input type="text" id="add_kategori" name="kategori" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="add_satuan" class="block text-sm font-medium text-gray-700">Unit</label>
                        <input type="text" id="add_satuan" name="satuan" placeholder="pcs, kg, liter, etc." 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="add_harga_beli" class="block text-sm font-medium text-gray-700">Purchase Price *</label>
                        <input type="number" id="add_harga_beli" name="harga_beli" step="0.01" min="0" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="add_harga_jual" class="block text-sm font-medium text-gray-700">Selling Price *</label>
                        <input type="number" id="add_harga_jual" name="harga_jual" step="0.01" min="0" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="add_stok" class="block text-sm font-medium text-gray-700">Initial Stock</label>
                        <input type="number" id="add_stok" name="stok" min="0" value="0" 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="add_stok_minimum" class="block text-sm font-medium text-gray-700">Minimum Stock</label>
                        <input type="number" id="add_stok_minimum" name="stok_minimum" min="0" value="5" 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div class="col-span-2">
                        <label for="add_deskripsi" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="add_deskripsi" name="deskripsi" rows="3"
                                  class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeAddModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Product</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label for="edit_nama_barang" class="block text-sm font-medium text-gray-700">Product Name *</label>
                        <input type="text" id="edit_nama_barang" name="nama_barang" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="edit_kategori" class="block text-sm font-medium text-gray-700">Category *</label>
                        <input type="text" id="edit_kategori" name="kategori" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="edit_satuan" class="block text-sm font-medium text-gray-700">Unit</label>
                        <input type="text" id="edit_satuan" name="satuan" 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="edit_harga_beli" class="block text-sm font-medium text-gray-700">Purchase Price *</label>
                        <input type="number" id="edit_harga_beli" name="harga_beli" step="0.01" min="0" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="edit_harga_jual" class="block text-sm font-medium text-gray-700">Selling Price *</label>
                        <input type="number" id="edit_harga_jual" name="harga_jual" step="0.01" min="0" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="edit_stok" class="block text-sm font-medium text-gray-700">Current Stock</label>
                        <input type="number" id="edit_stok" name="stok" min="0" 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="edit_stok_minimum" class="block text-sm font-medium text-gray-700">Minimum Stock</label>
                        <input type="number" id="edit_stok_minimum" name="stok_minimum" min="0" 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div class="col-span-2">
                        <label for="edit_deskripsi" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="edit_deskripsi" name="deskripsi" rows="3"
                                  class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Product</h3>
            <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete <span id="deleteProductName" class="font-medium"></span>? This action cannot be undone.</p>
            
            <form method="POST" class="flex justify-center space-x-3">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id">
                
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$additionalJS = "
<script>
// Search and filter functionality
document.getElementById('searchInput').addEventListener('input', filterProducts);
document.getElementById('categoryFilter').addEventListener('change', filterProducts);
document.getElementById('stockFilter').addEventListener('change', filterProducts);

function filterProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value.toLowerCase();
    const stockFilter = document.getElementById('stockFilter').value;
    
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        const name = card.dataset.name;
        const category = card.dataset.category;
        const stock = card.dataset.stock;
        
        const matchesSearch = name.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        const matchesStock = !stockFilter || stock === stockFilter;
        
        if (matchesSearch && matchesCategory && matchesStock) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addModal').querySelector('form').reset();
}

function openEditModal(product) {
    document.getElementById('edit_id').value = product.id_barang;
    document.getElementById('edit_nama_barang').value = product.nama_barang;
    document.getElementById('edit_kategori').value = product.kategori;
    document.getElementById('edit_satuan').value = product.satuan || '';
    document.getElementById('edit_harga_beli').value = product.harga_beli;
    document.getElementById('edit_harga_jual').value = product.harga_jual;
    document.getElementById('edit_stok').value = product.stok;
    document.getElementById('edit_stok_minimum').value = product.stok_minimum;
    document.getElementById('edit_deskripsi').value = product.deskripsi || '';
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').querySelector('form').reset();
}

function confirmDelete(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === addModal) {
        closeAddModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}
</script>
";

include dirname(__DIR__) . '/components/footer.php';

?>

