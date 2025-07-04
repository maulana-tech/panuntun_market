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
                $nama_supplier = sanitizeInput($_POST['nama_supplier'] ?? '');
                $alamat = sanitizeInput($_POST['alamat'] ?? '');
                $no_tlp = sanitizeInput($_POST['no_tlp'] ?? '');
                
                if (empty($nama_supplier) || empty($alamat) || empty($no_tlp)) {
                    $error = 'Supplier name, address, and phone are required.';
                } else {
                    $query = "INSERT INTO supplier (nama_supplier, alamat, no_tlp) VALUES (:nama_supplier, :alamat, :no_tlp)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nama_supplier', $nama_supplier);
                    $stmt->bindParam(':alamat', $alamat);
                    $stmt->bindParam(':no_tlp', $no_tlp);
                    
                    if ($stmt->execute()) {
                        $success = 'Supplier added successfully.';
                    } else {
                        $error = 'Failed to add supplier.';
                    }
                }
                break;
                
            case 'edit':
                $id_supplier = intval($_POST['id_supplier'] ?? 0);
                $nama_supplier = sanitizeInput($_POST['nama_supplier'] ?? '');
                $alamat = sanitizeInput($_POST['alamat'] ?? '');
                $no_tlp = sanitizeInput($_POST['no_tlp'] ?? '');
                
                if (empty($nama_supplier) || empty($alamat) || empty($no_tlp) || $id_supplier === 0) {
                    $error = 'Supplier name, address, and phone are required.';
                } else {
                    $query = "UPDATE supplier SET nama_supplier = :nama_supplier, alamat = :alamat, no_tlp = :no_tlp, updated_at = CURRENT_TIMESTAMP WHERE id_supplier = :id_supplier";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nama_supplier', $nama_supplier);
                    $stmt->bindParam(':alamat', $alamat);
                    $stmt->bindParam(':no_tlp', $no_tlp);
                    $stmt->bindParam(':id_supplier', $id_supplier);
                    
                    if ($stmt->execute()) {
                        $success = 'Supplier updated successfully.';
                    } else {
                        $error = 'Failed to update supplier.';
                    }
                }
                break;
                
            case 'delete':
                $id_supplier = intval($_POST['id_supplier'] ?? 0);
                
                // Check if supplier has related purchases
                $query = "SELECT COUNT(*) as count FROM pembelian WHERE id_supplier = :id_supplier";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_supplier', $id_supplier);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $error = 'Cannot delete supplier. There are existing purchase records associated with this supplier.';
                } else {
                    $query = "DELETE FROM supplier WHERE id_supplier = :id_supplier";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id_supplier', $id_supplier);
                    
                    if ($stmt->execute()) {
                        $success = 'Supplier deleted successfully.';
                    } else {
                        $error = 'Failed to delete supplier.';
                    }
                }
                break;
        }
    }
}

// Get all suppliers
// Simplified query according to the new schema. 
// For purchase details, it's better to query the v_purchase_details view separately if needed.
$query = "SELECT id_supplier, nama_supplier, no_tlp, alamat 
          FROM supplier 
          ORDER BY nama_supplier ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Supplier Management';
include dirname(__DIR__) . '/components/header.php';

?>

<div class="fade-in">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Supplier Management</h1>
                <p class="mt-1 text-sm text-gray-600">Manage suppliers and vendor information</p>
            </div>
            <button onclick="openAddModal()" class="enhanced-button inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Supplier
            </button>
        </div>
    </div>

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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($suppliers)): ?>
            <div class="col-span-full">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No suppliers</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding a new supplier.</p>
                    <div class="mt-6">
                        <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Supplier
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($suppliers as $supplier): ?>
                <div class="modern-card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($supplier['nama_supplier']); ?></h3>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($supplier)); ?>)" 
                                        class="text-primary-600 hover:text-primary-900 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="confirmDelete(<?php echo $supplier['id_supplier']; ?>, '<?php echo htmlspecialchars($supplier['nama_supplier']); ?>')" 
                                        class="text-red-600 hover:text-red-900 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <svg class="w-4 h-4 text-gray-400 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($supplier['alamat']); ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($supplier['no_tlp'] ?? ''); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add New Supplier</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label for="add_nama_supplier" class="block text-sm font-medium text-gray-700">Supplier Name *</label>
                    <input type="text" id="add_nama_supplier" name="nama_supplier" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label for="add_alamat" class="block text-sm font-medium text-gray-700">Address *</label>
                    <textarea id="add_alamat" name="alamat" required rows="3"
                              class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>
                
                <div>
                    <label for="add_no_tlp" class="block text-sm font-medium text-gray-700">Phone *</label>
                    <input type="text" id="add_no_tlp" name="no_tlp" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeAddModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Add Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Supplier</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id_supplier" name="id_supplier">
                
                <div>
                    <label for="edit_nama_supplier" class="block text-sm font-medium text-gray-700">Supplier Name *</label>
                    <input type="text" id="edit_nama_supplier" name="nama_supplier" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label for="edit_alamat" class="block text-sm font-medium text-gray-700">Address *</label>
                    <textarea id="edit_alamat" name="alamat" required rows="3"
                              class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>
                
                <div>
                    <label for="edit_no_tlp" class="block text-sm font-medium text-gray-700">Phone *</label>
                    <input type="text" id="edit_no_tlp" name="no_tlp" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Update Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Supplier</h3>
            <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete <span id="deleteSupplierName" class="font-medium"></span>? This action cannot be undone.</p>
            
            <form method="POST" class="flex justify-center space-x-3">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id_supplier" name="id_supplier">
                
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
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addModal').querySelector('form').reset();
}

function openEditModal(supplier) {
    document.getElementById('edit_id_supplier').value = supplier.id_supplier;
    document.getElementById('edit_nama_supplier').value = supplier.nama_supplier;
    document.getElementById('edit_alamat').value = supplier.alamat;
    document.getElementById('edit_no_tlp').value = supplier.no_tlp;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').querySelector('form').reset();
}

function confirmDelete(id, name) {
    document.getElementById('delete_id_supplier').value = id;
    document.getElementById('deleteSupplierName').textContent = name;
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