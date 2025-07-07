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
                <h1 class="text-2xl font-bold text-gray-900">Manajemen Pemasok</h1>
                <p class="mt-1 text-sm text-gray-600">Kelola informasi pemasok dan vendor</p>
            </div>
            <button onclick="openAddModal()" class="enhanced-button inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Pemasok
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 transform hover:scale-102 transition-transform duration-200">
            <div class="rounded-lg bg-red-50 p-4 border-l-4 border-red-400 shadow-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400 animate-pulse" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-red-800"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 transform hover:scale-102 transition-transform duration-200">
            <div class="rounded-lg bg-green-50 p-4 border-l-4 border-green-400 shadow-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400 animate-bounce" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L8.23 10.661a.75.75 0 00-1.06 1.06l2.5 2.5a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-green-800"><?php echo $success; ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (empty($suppliers)): ?>
            <div class="col-span-full">
                <div class="text-center py-16 bg-white rounded-xl shadow-lg border border-gray-100">
                    <svg class="mx-auto h-16 w-16 text-gray-400 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">No suppliers found</h3>
                    <p class="mt-2 text-sm text-gray-500">Get started by adding your first supplier.</p>
                    <div class="mt-8">
                        <button onclick="openAddModal()" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transform hover:scale-105 transition-all duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add First Supplier
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($suppliers as $supplier): ?>
                <div class="group relative bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-300 hover:shadow-lg hover:border-indigo-400 overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-8 -mr-8 w-40 h-40">
                        <svg class="w-full h-full text-indigo-50 opacity-80" fill="none" viewBox="0 0 144 144" xmlns="http://www.w3.org/2000/svg">
                            <path d="M126 36.5c0 49.426-40.074 89.5-89.5 89.5S-23 85.926-23 36.5-63.074-53-13.5-53S126-12.926 126 36.5z" stroke="currentColor" stroke-width="2"></path>
                        </svg>
                    </div>

                    <div class="relative p-6 z-10">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12 rounded-xl bg-indigo-600 shadow-lg flex items-center justify-center">
                                    <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-base font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($supplier['nama_supplier']); ?></h3>
                                </div>
                            </div>
                            <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode($supplier, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)' class="p-2 rounded-full bg-white/60 backdrop-blur-sm text-gray-500 hover:bg-gray-200 hover:text-indigo-600 shadow-sm" title="Edit Pemasok">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"></path></svg>
                                </button>
                                <button onclick='confirmDelete(<?php echo $supplier["id_supplier"]; ?>, "<?php echo htmlspecialchars(addslashes($supplier['nama_supplier'])); ?>")' class="p-2 rounded-full bg-white/60 backdrop-blur-sm text-gray-500 hover:bg-red-100 hover:text-red-600 shadow-sm" title="Hapus Pemasok">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path></svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-6 border-t border-gray-200 pt-5 space-y-3">
                            <div class="flex items-start text-sm">
                                <svg class="w-5 h-5 text-gray-400 mr-3 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($supplier['alamat']); ?></span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="w-5 h-5 text-gray-400 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                </svg>
                                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($supplier['no_tlp'] ?? 'Tidak ada nomor'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 backdrop-blur-sm">
    <div class="relative top-20 mx-auto p-8 border w-[32rem] shadow-2xl rounded-2xl bg-white transform transition-all">
        <div class="mt-2">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Add New Supplier</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label for="add_nama_supplier" class="block text-sm font-medium text-gray-700 mb-2">Supplier Name *</label>
                    <input type="text" id="add_nama_supplier" name="nama_supplier" required 
                           class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                </div>
                
                <div>
                    <label for="add_alamat" class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                    <textarea id="add_alamat" name="alamat" required rows="3"
                              class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"></textarea>
                </div>
                
                <div>
                    <label for="add_no_tlp" class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                    <input type="text" id="add_no_tlp" name="no_tlp" required 
                           class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                </div>
                
                <div class="flex justify-end space-x-4 pt-6">
                    <button type="button" onclick="closeAddModal()" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-3 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                        Add Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 backdrop-blur-sm">
    <div class="relative top-20 mx-auto p-8 border w-[32rem] shadow-2xl rounded-2xl bg-white transform transition-all">
        <div class="mt-2">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Edit Supplier</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id_supplier" name="id_supplier">
                
                <div>
                    <label for="edit_nama_supplier" class="block text-sm font-medium text-gray-700 mb-2">Supplier Name *</label>
                    <input type="text" id="edit_nama_supplier" name="nama_supplier" required 
                           class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                </div>
                
                <div>
                    <label for="edit_alamat" class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                    <textarea id="edit_alamat" name="alamat" required rows="3"
                              class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"></textarea>
                </div>
                
                <div>
                    <label for="edit_no_tlp" class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                    <input type="text" id="edit_no_tlp" name="no_tlp" required 
                           class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                </div>
                
                <div class="flex justify-end space-x-4 pt-6">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-3 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                        Update Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 backdrop-blur-sm">
    <div class="relative top-20 mx-auto p-8 border w-[32rem] shadow-2xl rounded-2xl bg-white transform transition-all">
        <div class="mt-2 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Hapus Pemasok</h3>
            <p class="text-base text-gray-500 mb-6">Apakah Anda yakin ingin menghapus <span id="deleteSupplierName" class="font-semibold"></span>? Tindakan ini tidak dapat dibatalkan.</p>
            <form method="POST" class="flex justify-center space-x-4">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id_supplier" name="id_supplier">
                
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-6 py-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-3 text-base font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                    Hapus
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