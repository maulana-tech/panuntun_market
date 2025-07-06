<?php
// File: pages/reports/users.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Cek jika variabel $db belum ada, lalu buat koneksi
if (!isset($db)) {
    require_once dirname(__DIR__, 2) . '/includes/functions.php';
    $database = new Database();
    $db = $database->getConnection();
}

// Mengambil semua data pengguna dari database
$query_users = "
    SELECT 
        id_pengguna,
        nama,
        jabatan,
        email
    FROM pengguna
    ORDER BY id_pengguna ASC
";

$stmt_users = $db->prepare($query_users);
$stmt_users->execute();
$users_data = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// Mengambil statistik ringkasan jumlah pengguna
$query_summary = "
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN jabatan = 'Admin' THEN 1 END) as admin_count,
        COUNT(CASE WHEN jabatan = 'Owner' THEN 1 END) as owner_count
    FROM pengguna
";
$stmt_summary = $db->prepare($query_summary);
$stmt_summary->execute();
$summary_stats = $stmt_summary->fetch(PDO::FETCH_ASSOC);

?>

<div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Laporan Daftar Pengguna</h3>
    <p class="text-sm text-gray-600">Menampilkan semua pengguna yang terdaftar di sistem.</p>
</div>

<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-blue-800">Total Pengguna</div>
            <div class="text-xl font-bold text-blue-600"><?php echo $summary_stats['total_users'] ?? 0; ?></div>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-purple-800">Jumlah Admin</div>
            <div class="text-xl font-bold text-purple-600"><?php echo $summary_stats['admin_count'] ?? 0; ?></div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-sm font-medium text-green-800">Jumlah Owner</div>
            <div class="text-xl font-bold text-green-600"><?php echo $summary_stats['owner_count'] ?? 0; ?></div>
        </div>
    </div>

    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Rincian Data Pengguna</h4>
        <div class="overflow-x-auto">
            <table class="modern-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Pengguna</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users_data)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                Tidak ada data pengguna yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users_data as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    P-<?php echo str_pad($user['id_pengguna'], 4, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($user['nama']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['jabatan'] === 'Admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo htmlspecialchars($user['jabatan']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>