<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Memastikan hanya user yang sudah login yang dapat mengakses
requireLogin();
// Memastikan hanya admin yang dapat mengakses halaman ini
requireAdmin();

// ===== PERBAIKAN: Inisialisasi koneksi database =====
$database = new Database();
$db = $database->getConnection();

$pageTitle = "Manajemen Pengguna";

// Proses Tambah & Edit Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_user'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed.');
    }

    $nama = sanitizeInput($_POST['nama']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $jabatan = sanitizeInput($_POST['jabatan']);
    $id = $_POST['id'] ?? null;

    // Logika untuk EDIT
    if ($id) {
        $password = $_POST['password'];
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE pengguna SET nama = ?, email = ?, jabatan = ?, pass = ? WHERE id_pengguna = ?");
            $stmt->execute([$nama, $email, $jabatan, $hashedPassword, $id]);
        } else {
            $stmt = $db->prepare("UPDATE pengguna SET nama = ?, email = ?, jabatan = ? WHERE id_pengguna = ?");
            $stmt->execute([$nama, $email, $jabatan, $id]);
        }
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Data pengguna berhasil diperbarui.'];
    } 
    // Logika untuk TAMBAH
    else {
        $password = $_POST['password'];
        if (empty($password)) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Password wajib diisi untuk pengguna baru.'];
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO pengguna (nama, email, jabatan, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama, $email, $jabatan, $hashedPassword]);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Pengguna baru berhasil ditambahkan.'];
        }
    }
    header('Location: users.php');
    exit();
}

// Proses Hapus Pengguna
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Pencegahan menghapus diri sendiri
    if ($id == $_SESSION['user_id']) {
         $_SESSION['alert'] = ['type' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'];
    } else {
        $stmt = $db->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
        $stmt->execute([$id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Pengguna berhasil dihapus.'];
    }
    header('Location: users.php');
    exit();
}


// Mengambil semua data pengguna untuk ditampilkan
$stmt = $db->query("SELECT id_pengguna, nama, jabatan, email FROM pengguna ORDER BY nama ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCSRFToken();
?>

<?php include '../components/header.php'; ?>

<div x-data="userPage()">
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6 px-4 sm:px-0">
            <h1 class="text-2xl font-bold text-gray-900"><?= $pageTitle ?></h1>
            <button @click="openAddModal()" class="inline-flex items-center justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"></path></svg>
                Tambah Pengguna
            </button>
        </div>

        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
            <div class="overflow-x-auto">
                <table class="modern-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($users)) : ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Tidak ada data pengguna.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($users as $index => $user) : ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $index + 1 ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($user['nama']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['jabatan'] === 'Admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                            <?= htmlspecialchars($user['jabatan']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" class="text-primary-600 hover:text-primary-900">Edit</button>
                                        <?php if ($user['id_pengguna'] != $_SESSION['user_id']) : ?>
                                            <a href="users.php?delete=<?= $user['id_pengguna'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')" class="text-red-600 hover:text-red-900 ml-4">Hapus</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div 
    x-show="isModalOpen" 
    class="fixed inset-0 z-50 overflow-y-auto" 
    aria-labelledby="modal-title" 
    role="dialog" 
    aria-modal="true" 
    style="display: none;"
>
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div 
            x-show="isModalOpen" 
            x-transition:enter="ease-out duration-300" 
            x-transition:enter-start="opacity-0" 
            x-transition:enter-end="opacity-100" 
            x-transition:leave="ease-in duration-200" 
            x-transition:leave-start="opacity-100" 
            x-transition:leave-end="opacity-0" 
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
            @click="isModalOpen = false" 
            aria-hidden="true">
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div 
            x-show="isModalOpen" 
            x-transition:enter="ease-out duration-300" 
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
            x-transition:leave="ease-in duration-200" 
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
        >
            <form method="POST" action="users.php">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" :value="formData.id">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-primary-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title" x-text="editMode ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru'"></h3>
                            <p class="text-sm text-gray-500">
                                Isi detail pengguna pada formulir di bawah ini.
                            </p>
                        </div>
                    </div>
                     <button type="button" @click="isModalOpen = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-4 py-5 sm:p-6">
                    <div class="space-y-4">
                        <div>
                            <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input type="text" name="nama" id="nama" x-model="formData.nama" required class="block w-full pr-10 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md" placeholder="Contoh: Budi Santoso">
                            </div>
                        </div>
                         <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                             <div class="mt-1 relative rounded-md shadow-sm">
                                <input type="email" name="email" id="email" x-model="formData.email" required class="block w-full pr-10 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md" placeholder="contoh@email.com">
                            </div>
                        </div>
                         <div>
                            <label for="jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                            <select name="jabatan" id="jabatan" x-model="formData.jabatan" class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm shadow-sm">
                                <option>Admin</option>
                                <option>Owner</option>
                            </select>
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" id="password" :required="!editMode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" :placeholder="editMode ? 'Kosongkan jika tidak ingin diubah' : 'Wajib diisi'">
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="submit_user" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm" x-text="editMode ? 'Simpan Perubahan' : 'Tambah Pengguna'"></button>
                    <button type="button" @click="isModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
function userPage() {
    return {
        isModalOpen: false,
        editMode: false,
        formData: {
            id: null,
            nama: '',
            email: '',
            jabatan: 'Owner'
        },
        openAddModal() {
            this.editMode = false;
            this.formData = { id: null, nama: '', email: '', jabatan: 'Owner' };
            this.isModalOpen = true;
        },
        openEditModal(user) {
            this.editMode = true;
            this.formData = {
                id: user.id_pengguna,
                nama: user.nama,
                email: user.email,
                jabatan: user.jabatan
            };
            this.isModalOpen = true;
        }
    }
}
</script>

<?php include '../components/footer.php'; ?>