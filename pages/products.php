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
                $stok = intval($_POST['stok'] ?? 0);
                
                if (empty($nama_barang)) {
                    $error = 'Product name is required.';
                } else {
                    $query = "INSERT INTO barang (nama_barang, stok) VALUES (:nama_barang, :stok)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nama_barang', $nama_barang);
                    $stmt->bindParam(':stok', $stok);
                    
                    if ($stmt->execute()) {
                        $success = 'Product added successfully.';
                    } else {
                        $error = 'Failed to add product.';
                    }
                }
                break;
                
            case 'edit':
                $kode_barang = intval($_POST['kode_barang'] ?? 0);
                $nama_barang = sanitizeInput($_POST['nama_barang'] ?? '');
                $stok = intval($_POST['stok'] ?? 0);
                
                if (empty($nama_barang) || $kode_barang === 0) {
                    $error = 'Product name and a valid ID are required.';
                } else {
                    // updated_at akan diperbarui secara otomatis oleh database jika skema diatur demikian,
                    // namun lebih baik menambahkannya secara eksplisit di sini.
                    $query = "UPDATE barang SET nama_barang = :nama_barang, stok = :stok, updated_at = CURRENT_TIMESTAMP WHERE kode_barang = :kode_barang";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nama_barang', $nama_barang);
                    $stmt->bindParam(':stok', $stok);
                    $stmt->bindParam(':kode_barang', $kode_barang);
                    
                    if ($stmt->execute()) {
                        $success = 'Product updated successfully.';
                    } else {
                        $error = 'Failed to update product.';
                    }
                }
                break;
                
            case 'delete':
                $kode_barang = intval($_POST['kode_barang'] ?? 0);
                
                // Check if product has related transactions (penjualan and pembelian)
                $query_check_penjualan = "SELECT COUNT(*) as count FROM penjualan WHERE kode_barang = :kode_barang";
                $stmt_penjualan = $db->prepare($query_check_penjualan);
                $stmt_penjualan->bindParam(':kode_barang', $kode_barang);
                $stmt_penjualan->execute();
                $result_penjualan = $stmt_penjualan->fetch(PDO::FETCH_ASSOC);

                $query_check_pembelian = "SELECT COUNT(*) as count FROM pembelian WHERE kode_barang = :kode_barang";
                $stmt_pembelian = $db->prepare($query_check_pembelian);
                $stmt_pembelian->bindParam(':kode_barang', $kode_barang);
                $stmt_pembelian->execute();
                $result_pembelian = $stmt_pembelian->fetch(PDO::FETCH_ASSOC);
                
                if ($result_penjualan['count'] > 0 || $result_pembelian['count'] > 0) {
                    $error = 'Cannot delete product. There are existing sales or purchase records associated with this product.';
                } else {
                    $query = "DELETE FROM barang WHERE kode_barang = :kode_barang";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':kode_barang', $kode_barang);
                    
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

// Get all products with stock status, similar to v_inventory_status view
$query = "SELECT 
            kode_barang, 
            nama_barang, 
            stok,
            CASE 
                WHEN stok <= 5 THEN 'Low Stock'
                WHEN stok <= 15 THEN 'Medium Stock'
                ELSE 'Good Stock'
            END as stock_status
          FROM barang 
          ORDER BY nama_barang ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Product Management';
include dirname(__DIR__) . '/components/header.php';

?>

<div class="fade-in">
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

    <?php if ($error): ?>
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
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
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L8.23 10.661a.75.75 0 00-1.06 1.06l2.5 2.5a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800"><?php echo $success; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm">
        <div class="flex">
            <div class="flex-1">
                <input type="text" id="searchInput" placeholder="Search products by name..." 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
            </div>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Status</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200">
                <?php if (empty($products)): ?>
                    <tr class="product-row">
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No products found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding a new product.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr class="product-row" data-name="<?php echo strtolower(htmlspecialchars($product['nama_barang'])); ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['nama_barang']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $product['stok']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                    $statusClass = '';
                                    if ($product['stock_status'] === 'Low Stock') {
                                        $statusClass = 'bg-red-100 text-red-800';
                                    } elseif ($product['stock_status'] === 'Medium Stock') {
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                    } else {
                                        $statusClass = 'bg-green-100 text-green-800';
                                    }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo $product['stock_status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)' class="text-primary-600 hover:text-primary-900 mr-3">Edit</button>
                                <button onclick="confirmDelete(<?php echo $product['kode_barang']; ?>, '<?php echo htmlspecialchars($product['nama_barang']); ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add New Product</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label for="add_nama_barang" class="block text-sm font-medium text-gray-700">Product Name *</label>
                    <input type="text" id="add_nama_barang" name="nama_barang" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label for="add_stok" class="block text-sm font-medium text-gray-700">Initial Stock</label>
                    <input type="number" id="add_stok" name="stok" min="0" value="0" 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Product</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_kode_barang" name="kode_barang">
                
                <div>
                    <label for="edit_nama_barang" class="block text-sm font-medium text-gray-700">Product Name *</label>
                    <input type="text" id="edit_nama_barang" name="nama_barang" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label for="edit_stok" class="block text-sm font-medium text-gray-700">Current Stock</label>
                    <input type="number" id="edit_stok" name="stok" min="0" 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Product</h3>
            <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete <span id="deleteProductName" class="font-medium"></span>? This action cannot be undone.</p>
            
            <form method="POST" class="flex justify-center space-x-3">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_kode_barang" name="kode_barang">
                
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete</button>
            </form>
        </div>
    </div>
</div>

<?php
$additionalJS = "
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#productsTableBody .product-row');
    
    tableRows.forEach(row => {
        const name = row.dataset.name;
        if (name.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addModal').querySelector('form').reset();
}

function openEditModal(product) {
    document.getElementById('edit_kode_barang').value = product.kode_barang;
    document.getElementById('edit_nama_barang').value = product.nama_barang;
    document.getElementById('edit_stok').value = product.stok;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').querySelector('form').reset();
}

function confirmDelete(id, name) {
    document.getElementById('delete_kode_barang').value = id;
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
    
    if (event.target === addModal) closeAddModal();
    if (event.target === editModal) closeEditModal();
    if (event.target === deleteModal) closeDeleteModal();
}
</script>
";

include dirname(__DIR__) . '/components/footer.php';

?>