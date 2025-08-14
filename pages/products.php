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
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manajemen Produk</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola inventaris dan informasi produk Anda.</p>
        </div>
        <button onclick="openAddModal()" class="inline-flex items-center gap-x-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
            </svg>
            <span>Tambah Produk</span>
        </button>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200">
            <div class="flex">
                <div class="flex-shrink-0"><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg></div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800"><?php echo $error; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0"><svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L8.23 10.661a.75.75 0 00-1.06 1.06l2.5 2.5a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                    </svg></div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800"><?php echo $success; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mb-6">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"><svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg></div><input type="text" id="searchInput" placeholder="Cari berdasarkan nama produk..." class="block w-full rounded-lg border-gray-300 py-2.5 pl-10 pr-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
    </div>

    <div class="bg-white shadow-lg rounded-xl border border-gray-100 overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50/50">
                <tr class="border-b border-gray-200">
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode Barang</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Produk</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Stok</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status Stok</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody id="productsTableBody" class="divide-y divide-gray-200">
                <?php if (empty($products)): ?>
                    <tr class="product-row">
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center"><svg class="h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-800">Produk tidak ditemukan</h3>
                                <p class="mt-1 text-sm text-gray-500">Silakan tambahkan produk baru untuk memulai.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr class="product-row hover:bg-gray-50/50 transition-colors duration-200" data-name="<?php echo strtolower(htmlspecialchars($product['nama_barang'])); ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-mono font-semibold text-gray-900">#<?php echo str_pad($product['kode_barang'], 6, '0', STR_PAD_LEFT); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-100 rounded-lg flex items-center justify-center"><svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                        </svg></div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($product['nama_barang']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-lg font-bold text-gray-800"><?php echo $product['stok']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php
                                                                    $statusClass = '';
                                                                    if ($product['stock_status'] === 'Low Stock') $statusClass = 'bg-red-100 text-red-800';
                                                                    elseif ($product['stock_status'] === 'Medium Stock') $statusClass = 'bg-yellow-100 text-yellow-800';
                                                                    else $statusClass = 'bg-green-100 text-green-800';
                                                                    ?><span class="inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-1 text-xs font-medium <?php echo $statusClass; ?>"><svg class="h-1.5 w-1.5 fill-current" viewBox="0 0 6 6">
                                        <circle cx="3" cy="3" r="3" />
                                    </svg><?php echo $product['stock_status']; ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode($product, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)' class="p-2 rounded-full text-gray-400 hover:bg-gray-200 hover:text-gray-700" title="Edit Produk"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg></button>
                                <button onclick='confirmDelete(<?php echo $product["kode_barang"]; ?>, "<?php echo htmlspecialchars(addslashes($product['nama_barang'])); ?>")' class="p-2 rounded-full text-gray-400 hover:bg-red-100 hover:text-red-600" title="Hapus Produk"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-2xl rounded-xl bg-white">
        <div class="flex items-start justify-between p-5 border-b rounded-t">
            <h3 class="text-xl font-semibold text-gray-900">Tambah Produk Baru</h3>
            <button onclick="closeAddModal()" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-6">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="add_nama_barang" class="block mb-2 text-sm font-medium text-gray-900">Nama Produk *</label>
                <input type="text" id="add_nama_barang" name="nama_barang" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" placeholder="Contoh: Beras Premium 5kg">
            </div>
            <div>
                <label for="add_stok" class="block mb-2 text-sm font-medium text-gray-900">Stok Awal</label>
                <input type="number" id="add_stok" name="stok" min="0" value="0" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
            </div>
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 rounded-b">
                <button type="button" onclick="closeAddModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-indigo-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Batal</button>
                <button type="submit" class="ml-3 text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Tambah Produk</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-2xl rounded-xl bg-white">
        <div class="flex items-start justify-between p-5 border-b rounded-t">
            <h3 class="text-xl font-semibold text-gray-900">Edit Produk</h3>
            <button onclick="closeEditModal()" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-6">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_kode_barang" name="kode_barang">
            <div>
                <label for="edit_nama_barang" class="block mb-2 text-sm font-medium text-gray-900">Nama Produk *</label>
                <input type="text" id="edit_nama_barang" name="nama_barang" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
            </div>
            <div>
                <label for="edit_stok" class="block mb-2 text-sm font-medium text-gray-900">Stok Saat Ini</label>
                <input type="number" id="edit_stok" name="stok" min="0" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
            </div>
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 rounded-b">
                <button type="button" onclick="closeEditModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-indigo-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Batal</button>
                <button type="submit" class="ml-3 text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-2xl rounded-xl bg-white">
        <div class="p-6 text-center">
            <svg class="mx-auto mb-4 h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <h3 class="mb-5 text-lg font-normal text-gray-500">Anda yakin ingin menghapus produk <br><strong id="deleteProductName" class="text-gray-800"></strong>?</h3>
            <form method="POST" class="inline-block">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_kode_barang" name="kode_barang">
                <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center mr-2">Ya, saya yakin</button>
                <button type="button" onclick="closeDeleteModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Tidak, batalkan</button>
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