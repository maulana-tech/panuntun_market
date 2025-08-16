<?php
// Diasumsikan BASE_URL dan fungsi-fungsi lain sudah dimuat

// Mendapatkan informasi pengguna dan menentukan peran
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser(); 
$isAdmin = ($user && $user['jabatan'] === 'Admin');

// --- Definisi Array Navigasi ---
$navigation = [
    [
        'name' => 'Beranda',
        'href' => BASE_URL . '/pages/dashboard.php',
        'icon' => 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z',
        'current' => $currentPage === 'dashboard',
        'roles' => ['Admin', 'Owner'] // Dapat diakses oleh semua peran
    ],
    [
        'name' => 'Manajemen Data',
        'href' => '#',
        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'current' => in_array($currentPage, ['users', 'suppliers', 'products']),
        'roles' => ['Admin'], // Hanya dapat diakses oleh Admin
        'children' => [
            ['name' => 'Pengguna', 'href' => BASE_URL . '/pages/users.php', 'current' => $currentPage === 'users'],
            ['name' => 'Pemasok', 'href' => BASE_URL . '/pages/suppliers.php', 'current' => $currentPage === 'suppliers'],
            ['name' => 'Produk', 'href' => BASE_URL . '/pages/products.php', 'current' => $currentPage === 'products']
        ]
    ],
    [
        'name' => 'Transaksi',
        'href' => '#',
        'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
        'current' => in_array($currentPage, ['sales', 'purchases']),
        'roles' => ['Admin'], // Hanya dapat diakses oleh Admin
        'children' => [
            ['name' => 'Penjualan', 'href' => BASE_URL . '/pages/sales.php', 'current' => $currentPage === 'sales'],
            ['name' => 'Pembelian', 'href' => BASE_URL . '/pages/purchases.php', 'current' => $currentPage === 'purchases'],
            ['name' => 'Rekap Penjualan', 'href' => BASE_URL . '/pages/RekapPenjualan.php', 'current' => $currentPage === 'RekapPenjualan']
        ]
    ],
    [
        'name' => 'Laporan',
        'href' => BASE_URL . '/pages/reports.php',
        'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'current' => $currentPage === 'reports',
        'roles' => ['Admin', 'Owner'] // Dapat diakses oleh semua peran
    ]
];
?>

<nav class="nav-container" x-data="{ openSubmenu: null }">
<ul role="list" class="space-y-0.5">
    <?php foreach ($navigation as $index => $item): ?>
        <?php 
            // Cek apakah peran pengguna saat ini diizinkan untuk melihat menu ini
            if (!in_array($user['jabatan'], $item['roles'])) {
                continue; // Lewati item menu ini jika tidak diizinkan
            }

            // Cek apakah ada anak menu yang aktif
            $isChildActive = false;
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    if ($child['current']) {
                        $isChildActive = true;
                        break;
                    }
                }
            }
            $isCurrent = $item['current'] || $isChildActive;
        ?>
        
        <?php if (isset($item['children'])): ?>
            <li class="nav-item <?php echo $isCurrent ? 'active' : ''; ?>">
                <button type="button" class="nav-button w-full flex items-center <?php echo $isCurrent ? 'active' : ''; ?>"
                        @click="openSubmenu = (openSubmenu === <?php echo $index; ?>) ? null : <?php echo $index; ?>">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $item['icon']; ?>" /></svg>
                    <span class="nav-text flex-1 text-left"><?php echo $item['name']; ?></span>
                    <svg class="submenu-indicator transition-transform" :class="{ 'rotate-90': openSubmenu === <?php echo $index; ?> }" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                </button>
                <ul x-show="openSubmenu === <?php echo $index; ?>" x-transition class="submenu" style="display: none;">
                    <?php foreach ($item['children'] as $child): ?>
                        <li class="submenu-item">
                            <a href="<?php echo $child['href']; ?>" class="submenu-link <?php echo $child['current'] ? 'active' : ''; ?>">
                                <?php echo $child['name']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item <?php echo $item['current'] ? 'active' : ''; ?>">
                <a href="<?php echo $item['href']; ?>" class="nav-button <?php echo $item['current'] ? 'active' : ''; ?>">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $item['icon']; ?>" /></svg>
                    <?php echo $item['name']; ?>
                </a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
</nav>