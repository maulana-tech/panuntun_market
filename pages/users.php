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

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Format email tidak valid.'];
        header('Location: users.php');
        exit();
    }

    // Cek email duplikat
    $checkEmailStmt = $db->prepare("SELECT id_pengguna FROM pengguna WHERE email = ? AND id_pengguna != ?");
    $checkEmailStmt->execute([$email, $id ?? 0]);
    if ($checkEmailStmt->rowCount() > 0) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Email sudah digunakan oleh pengguna lain.'];
        header('Location: users.php');
        exit();
    }

    // Logika untuk EDIT
    if ($id) {
        $password = $_POST['password'];
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE pengguna SET nama = ?, email = ?, jabatan = ?, password = ? WHERE id_pengguna = ?");
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
    <main class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8 px-4 sm:px-0">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?= $pageTitle ?></h1>
                <p class="mt-2 text-sm text-gray-600">Kelola data pengguna sistem, peran, dan akses.</p>
            </div>
            <button @click="openAddModal()" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors duration-200">
                <svg class="-ml-0.5 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Tambah Pengguna
            </button>
        </div>

        <div class="bg-white shadow-lg rounded-xl border border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jabatan</th>
                            <th scope="col" class="relative px-6 py-4"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($users)) : ?>
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <h3 class="text-lg font-semibold">Pengguna tidak ditemukan</h3>
                                        <p>Silakan tambahkan pengguna baru untuk memulai.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($users as $index => $user) : ?>
                                <tr class="hover:bg-gray-50/50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $index + 1 ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <span class="text-base font-semibold text-indigo-700"><?= strtoupper(substr($user['nama'], 0, 1)) ?></span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($user['nama']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full <?= $user['jabatan'] === 'Admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                            <?= htmlspecialchars($user['jabatan']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center space-x-2">
                                            <button @click="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)"
                                                class="inline-flex items-center p-2 rounded-full text-gray-400 hover:bg-gray-200 hover:text-gray-700 transition-colors duration-200"
                                                title="Edit Pengguna">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                            </button>
                                            <?php if ($user['id_pengguna'] != $_SESSION['user_id']) : ?>
                                                <a href="users.php?delete=<?= $user['id_pengguna'] ?>"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')"
                                                    class="inline-flex items-center p-2 rounded-full text-gray-400 hover:bg-red-100 hover:text-red-600 transition-colors duration-200"
                                                    title="Hapus Pengguna">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div x-show="isModalOpen"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
        style="display: none;">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity"
                @click="isModalOpen = false"
                aria-hidden="true">
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="isModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <form method="POST" action="users.php">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" :value="formData.id">

                    <div class="px-6 py-5 bg-white border-b border-gray-200">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900" x-text="editMode ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru'"></h3>
                                    <p class="mt-1 text-sm text-gray-500">Lengkapi informasi pengguna di bawah ini.</p>
                                </div>
                            </div>
                            <button type="button" @click="isModalOpen = false" class="p-2 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-500">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-6">
                        <div class="space-y-6">
                            <div>
                                <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                <div class="mt-1">
                                    <input type="text" name="nama" id="nama" x-model="formData.nama" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Masukkan nama lengkap">
                                </div>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                                <div class="mt-1">
                                    <input type="email" name="email" id="email" x-model="formData.email" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="nama@example.com">
                                </div>
                            </div>
                            <div>
                                <label for="jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                                <select name="jabatan" id="jabatan" x-model="formData.jabatan" class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm shadow-sm">
                                    <option value="Admin">Admin</option>
                                    <option value="Owner">Owner</option>
                                </select>
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <input type="password" name="password" id="password" :required="!editMode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :placeholder="editMode ? 'Kosongkan jika tidak ingin diubah' : 'Wajib diisi'">
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 flex flex-row-reverse">
                        <button type="submit" name="submit_user" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm" x-text="editMode ? 'Simpan Perubahan' : 'Tambah Pengguna'"></button>
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
                this.formData = {
                    id: null,
                    nama: '',
                    email: '',
                    jabatan: 'Owner'
                };
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