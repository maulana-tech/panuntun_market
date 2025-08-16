<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Initialize session cart if not exists
if (!isset($_SESSION['temp_sales_cart'])) {
    $_SESSION['temp_sales_cart'] = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'remove_item':
                $kode_barang = intval($_POST['kode_barang'] ?? 0);
                if (isset($_SESSION['temp_sales_cart'][$kode_barang])) {
                    unset($_SESSION['temp_sales_cart'][$kode_barang]);
                    $success = 'Item berhasil dihapus dari keranjang.';
                }
                break;

            case 'update_qty':
                $kode_barang = intval($_POST['kode_barang'] ?? 0);
                $new_qty = intval($_POST['qty'] ?? 0);

                if (isset($_SESSION['temp_sales_cart'][$kode_barang]) && $new_qty > 0) {
                    // Cek stok tersedia
                    $query = "SELECT stok FROM barang WHERE kode_barang = :kode_barang";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':kode_barang', $kode_barang);
                    $stmt->execute();
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($product && $product['stok'] >= $new_qty) {
                        $_SESSION['temp_sales_cart'][$kode_barang]['qty'] = $new_qty;
                        $_SESSION['temp_sales_cart'][$kode_barang]['total'] =
                            $new_qty * $_SESSION['temp_sales_cart'][$kode_barang]['harga'];
                        $success = 'Quantity berhasil diupdate.';
                    } else {
                        $error = 'Stok tidak mencukupi. Stok tersedia: ' . ($product['stok'] ?? 0);
                    }
                } elseif ($new_qty <= 0) {
                    // Hapus item jika qty = 0
                    unset($_SESSION['temp_sales_cart'][$kode_barang]);
                    $success = 'Item berhasil dihapus dari keranjang.';
                }
                break;

            case 'clear_cart':
                $_SESSION['temp_sales_cart'] = [];
                $success = 'Keranjang berhasil dikosongkan.';
                break;

            case 'checkout':
                if (!empty($_SESSION['temp_sales_cart'])) {
                    $db->beginTransaction();
                    try {
                        $total_transactions = 0;

                        foreach ($_SESSION['temp_sales_cart'] as $item) {
                            // Cek stok sekali lagi sebelum menyimpan
                            $query = "SELECT stok FROM barang WHERE kode_barang = :kode_barang";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':kode_barang', $item['kode_barang']);
                            $stmt->execute();
                            $product = $stmt->fetch(PDO::FETCH_ASSOC);

                            if (!$product || $product['stok'] < $item['qty']) {
                                throw new Exception("Stok tidak mencukupi untuk {$item['nama_barang']}");
                            }

                            // Insert penjualan
                            $query = "INSERT INTO penjualan (kode_barang, nama_barang, tgl_jual, harga, qty, total_penjualan) 
                                      VALUES (:kode_barang, :nama_barang, CURDATE(), :harga, :qty, :total_penjualan)";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':kode_barang', $item['kode_barang']);
                            $stmt->bindParam(':nama_barang', $item['nama_barang']);
                            $stmt->bindParam(':harga', $item['harga']);
                            $stmt->bindParam(':qty', $item['qty']);
                            $stmt->bindParam(':total_penjualan', $item['total']);

                            if (!$stmt->execute()) {
                                throw new Exception("Gagal menyimpan transaksi untuk {$item['nama_barang']}");
                            }

                            $total_transactions++;
                        }

                        $db->commit();

                        // Hitung total amount
                        $total_amount = array_sum(array_column($_SESSION['temp_sales_cart'], 'total'));

                        // Clear cart
                        $_SESSION['temp_sales_cart'] = [];

                        $success = "Checkout berhasil! {$total_transactions} transaksi telah disimpan. Total: " . formatCurrency($total_amount);
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error = 'Checkout gagal: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Keranjang kosong. Tidak ada yang bisa di-checkout.';
                }
                break;
        }
    }
}

// Calculate cart totals
$cart_total_qty = 0;
$cart_total_amount = 0;
foreach ($_SESSION['temp_sales_cart'] as $item) {
    $cart_total_qty += $item['qty'];
    $cart_total_amount += $item['total'];
}

$pageTitle = 'Rekap Penjualan';
include dirname(__DIR__) . '/components/header.php';
?>

<div class="fade-in">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Rekap Penjualan</h1>
                <p class="mt-1 text-sm text-gray-600">Kelola transaksi penjualan sebelum checkout</p>
            </div>
            <div class="flex space-x-3">
                <a href="sales.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Penjualan
                </a>
                <?php if (!empty($_SESSION['temp_sales_cart'])): ?>
                    <button onclick="clearCart()" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md shadow-sm text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Kosongkan Keranjang
                    </button>
                    <button onclick="checkout()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5H17"></path>
                        </svg>
                        Checkout (<?php echo $cart_total_qty; ?> items)
                    </button>
                <?php endif; ?>
            </div>
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

    <!-- Cart Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white border-l-4 border-blue-500 overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Items di Keranjang</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count($_SESSION['temp_sales_cart']); ?> jenis barang</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-green-500 overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Quantity</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $cart_total_qty; ?> pcs</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-purple-500 overflow-hidden shadow-sm rounded-lg card-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Amount</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($cart_total_amount); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Items -->
    <div class="bg-white shadow-lg rounded-xl border border-gray-100 overflow-hidden card-shadow">
        <div class="px-6 py-5 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Keranjang Penjualan</h3>
            <p class="text-sm text-gray-500">Daftar barang yang akan dijual. Anda dapat mengubah quantity atau menghapus item sebelum checkout.</p>
        </div>

        <?php if (empty($_SESSION['temp_sales_cart'])): ?>
            <div class="p-16 text-center">
                <div class="flex flex-col items-center">
                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Keranjang Kosong</h3>
                    <p class="text-gray-500 mb-4">Belum ada item yang ditambahkan ke keranjang penjualan.</p>
                    <a href="sales.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Tambah Item Sekarang
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($_SESSION['temp_sales_cart'] as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-10 h-10">
                                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                <span class="text-indigo-600 font-medium text-sm"><?php echo substr($item['nama_barang'], 0, 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['nama_barang']); ?></div>
                                            <div class="text-sm text-gray-500">Kode: <?php echo $item['kode_barang']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatCurrency($item['harga']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" class="inline-flex items-center space-x-2" onsubmit="return confirm('Ubah quantity item ini?')">
                                        <input type="hidden" name="action" value="update_qty">
                                        <input type="hidden" name="kode_barang" value="<?php echo $item['kode_barang']; ?>">
                                        <input type="number" name="qty" value="<?php echo $item['qty']; ?>" min="0" max="999"
                                            class="w-20 border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo formatCurrency($item['total']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form method="POST" class="inline" onsubmit="return confirm('Hapus item ini dari keranjang?')">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="kode_barang" value="<?php echo $item['kode_barang']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <th colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-900">Total Keseluruhan:</th>
                            <th class="px-6 py-3 text-left text-lg font-bold text-green-600"><?php echo formatCurrency($cart_total_amount); ?></th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden forms for JavaScript actions -->
<form id="clearCartForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="clear_cart">
</form>

<form id="checkoutForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="checkout">
</form>

<?php
$additionalJS = "
<script>
function clearCart() {
    if (confirm('Apakah Anda yakin ingin mengosongkan keranjang? Semua item akan dihapus.')) {
        document.getElementById('clearCartForm').submit();
    }
}

function checkout() {
    if (confirm('Proses checkout akan menyimpan semua transaksi ke database. Lanjutkan?')) {
        document.getElementById('checkoutForm').submit();
    }
}
</script>
";

include dirname(__DIR__) . '/components/footer.php';
?>