<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_purchase') {
        // --- MODIFIKASI: Menyesuaikan nama variabel dan kolom ---
        $kode_barang = intval($_POST['kode_barang'] ?? 0);
        $id_supplier = intval($_POST['id_supplier'] ?? 0);
        $qty = intval($_POST['qty'] ?? 0);
        $harga = floatval($_POST['harga'] ?? 0);
        
        if ($kode_barang <= 0 || $id_supplier <= 0 || $qty <= 0 || $harga <= 0) {
            $error = 'Please fill all required fields with valid values.';
        } else {
            // Check if product and supplier exist
            // --- MODIFIKASI: Menggunakan kode_barang ---
            $query = "SELECT nama_barang FROM barang WHERE kode_barang = :kode_barang";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':kode_barang', $kode_barang);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $query = "SELECT nama_supplier FROM supplier WHERE id_supplier = :id_supplier";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_supplier', $id_supplier);
            $stmt->execute();
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $error = 'Product not found.';
            } elseif (!$supplier) {
                $error = 'Supplier not found.';
            } else {
                $total_pembelian = $qty * $harga;
                
                // --- MODIFIKASI: INSERT query disesuaikan dengan skema tabel pembelian ---
                // Menghapus id_pengguna, keterangan. Menambah nama_barang & tgl_beli.
                $query = "INSERT INTO pembelian (kode_barang, id_supplier, nama_barang, tgl_beli, qty, harga, total_pembelian) 
                          VALUES (:kode_barang, :id_supplier, :nama_barang, CURDATE(), :qty, :harga, :total_pembelian)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':kode_barang', $kode_barang);
                $stmt->bindParam(':id_supplier', $id_supplier);
                $stmt->bindParam(':nama_barang', $product['nama_barang']);
                $stmt->bindParam(':qty', $qty);
                $stmt->bindParam(':harga', $harga);
                $stmt->bindParam(':total_pembelian', $total_pembelian);
                
                if ($stmt->execute()) {
                    $success = "Purchase recorded successfully. Total: " . formatCurrency($total_pembelian);
                } else {
                    $error = 'Failed to record purchase.';
                }
            }
        }
    }
}

// --- MODIFIKASI: Query disesuaikan, harga_beli & satuan tidak ada di tabel barang ---
$query = "SELECT kode_barang, nama_barang FROM barang ORDER BY nama_barang ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all suppliers for dropdown
$query = "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- MODIFIKASI: Query disesuaikan, join ke pengguna dihapus, kolom disesuaikan ---
// INI ADALAH BAGIAN YANG MENYEBABKAN ERROR ANDA
$query = "SELECT p.*, b.nama_barang, s.nama_supplier 
          FROM pembelian p 
          JOIN barang b ON p.kode_barang = b.kode_barang 
          JOIN supplier s ON p.id_supplier = s.id_supplier 
          ORDER BY p.tgl_beli DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- MODIFIKASI: Kolom tanggal disesuaikan menjadi tgl_beli ---
$query = "SELECT COUNT(*) as total_transactions, SUM(total_pembelian) as total_amount 
          FROM pembelian 
          WHERE DATE(tgl_beli) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$today_summary = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Purchase Transactions';
include dirname(__DIR__) . '/components/header.php';

?>

<div class="fade-in">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transaksi Pembelian</h1>
                <p class="mt-1 text-sm text-gray-600">Catat pembelian dan kelola transaksi pemasok</p>
            </div>
            <button onclick="openPurchaseModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Pembelian Baru
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 rounded-md bg-red-50 p-4"><div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg></div><div class="ml-3"><p class="text-sm font-medium text-red-800"><?php echo $error; ?></p></div></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="mb-6 rounded-md bg-green-50 p-4"><div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L8.23 10.661a.75.75 0 00-1.06 1.06l2.5 2.5a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg></div><div class="ml-3"><p class="text-sm font-medium text-green-800"><?php echo $success; ?></p></div></div></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6"><div class="flex items-center"><div class="flex-shrink-0"><div class="w-8 h-8 bg-red-100 rounded-md flex items-center justify-center"><svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg></div></div><div class="ml-5 w-0 flex-1"><dl><dt class="text-sm font-medium text-gray-500 truncate">Pengeluaran Hari Ini</dt><dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($today_summary['total_amount'] ?? 0); ?></dd></dl></div></div></div>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6"><div class="flex items-center"><div class="flex-shrink-0"><div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg></div></div><div class="ml-5 w-0 flex-1"><dl><dt class="text-sm font-medium text-gray-500 truncate">Pembelian Hari Ini</dt><dd class="text-lg font-medium text-gray-900"><?php echo $today_summary['total_transactions'] ?? 0; ?></dd></dl></div></div></div>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6"><div class="flex items-center"><div class="flex-shrink-0"><div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center"><svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg></div></div><div class="ml-5 w-0 flex-1"><dl><dt class="text-sm font-medium text-gray-500 truncate">Pemasok Aktif</dt><dd class="text-lg font-medium text-gray-900"><?php echo count($suppliers); ?></dd></dl></div></div></div>
        </div>
    </div>
    
    <div class="lg:col-span-2 bg-white shadow-lg rounded-xl border border-gray-100">
    <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Riwayat Pembelian</h3>
            <p class="text-sm text-gray-500">Daftar transaksi pembelian terakhir.</p>
        </div>
        <a href="reports.php?report_type=cash_outflow" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
            Laporan Lengkap &rarr;
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50/50">
                <tr class="border-b border-gray-200">
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produk</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pemasok</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Kuantitas</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($recent_purchases)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-800">Belum ada pembelian</h3>
                                <p class="mt-1 text-sm text-gray-500">Setiap transaksi pembelian akan muncul di tabel ini.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach (array_slice($recent_purchases, 0, 10) as $purchase): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900" title="<?php echo htmlspecialchars($purchase['nama_barang']); ?>"><?php echo htmlspecialchars($purchase['nama_barang']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600"><?php echo htmlspecialchars($purchase['nama_supplier']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-medium text-gray-800"><?php echo $purchase['qty']; ?></span>
                                <span class="text-xs text-gray-500">pcs</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-red-600">- <?php echo formatCurrency($purchase['total_pembelian']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo formatDate($purchase['tgl_beli']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mb-8 p-4">

</div>
</div>

<div id="purchaseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Transaksi Pembelian Baru</h3>
                <button onclick="closePurchaseModal()" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_purchase">
                
                <div>
                    <label for="id_supplier" class="block text-sm font-medium text-gray-700">Pemasok *</label>
                    <select id="id_supplier" name="id_supplier" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Pilih Pemasok</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id_supplier']; ?>"><?php echo htmlspecialchars($supplier['nama_supplier']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="kode_barang" class="block text-sm font-medium text-gray-700">Produk *</label>
                    <select id="kode_barang" name="kode_barang" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Pilih Produk</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['kode_barang']; ?>"><?php echo htmlspecialchars($product['nama_barang']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="qty" class="block text-sm font-medium text-gray-700">Quantity *</label>
                        <input type="number" id="qty" name="qty" min="1" required onchange="calculatePurchaseTotal()" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="harga" class="block text-sm font-medium text-gray-700">Harga Satuan *</label>
                        <input type="number" id="harga" name="harga" step="0.01" min="0" required onchange="calculatePurchaseTotal()" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Pembelian</label>
                    <div id="purchaseTotalAmount" class="mt-1 text-lg font-bold text-blue-600">Rp 0</div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closePurchaseModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Catat Pembelian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// --- MODIFIKASI: JavaScript disesuaikan dengan form baru ---
$additionalJS = "
<script>
function openPurchaseModal() {
    document.getElementById('purchaseModal').classList.remove('hidden');
}

function closePurchaseModal() {
    document.getElementById('purchaseModal').classList.add('hidden');
    document.getElementById('purchaseModal').querySelector('form').reset();
    document.getElementById('purchaseTotalAmount').textContent = 'Rp 0';
}

function calculatePurchaseTotal() {
    const quantity = parseFloat(document.getElementById('qty').value) || 0;
    const unitPrice = parseFloat(document.getElementById('harga').value) || 0;
    const total = quantity * unitPrice;
    
    document.getElementById('purchaseTotalAmount').textContent = formatCurrency(total);
}

function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

window.onclick = function(event) {
    const modal = document.getElementById('purchaseModal');
    if (event.target === modal) {
        closePurchaseModal();
    }
}
</script>
";

include dirname(__DIR__) . '/components/footer.php';
?>