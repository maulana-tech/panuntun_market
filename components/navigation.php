<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();
$isAdmin = $user && $user['jabatan'] === 'Admin';

// Improved base path determination
$currentScript = $_SERVER['PHP_SELF'];
$basePath = '';

if (strpos($currentScript, '/auth/') !== false) {
    $basePath = '../pages/';
} elseif (strpos($currentScript, '/pages/reports/') !== false) {
    $basePath = '../';
} elseif (strpos($currentScript, '/pages/') !== false) {
    $basePath = '';
} else {
    // Root level
    $basePath = 'pages/';
}

// For reports subfolder, we need special handling
$reportsBasePath = '';
if (strpos($currentScript, '/pages/reports/') !== false) {
    $reportsBasePath = '';
} elseif (strpos($currentScript, '/pages/') !== false) {
    $reportsBasePath = 'reports/';
} elseif (strpos($currentScript, '/auth/') !== false) {
    $reportsBasePath = '../pages/reports/';
} else {
    $reportsBasePath = 'pages/reports/';
}

$navigation = [
    [
        'name' => 'Dashboard',
        'href' => $basePath . 'dashboard.php',
        'icon' => 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z',
        'current' => $currentPage === 'dashboard'
    ],
    [
        'name' => 'Data Management',
        'href' => '#',
        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'current' => false,
        'children' => [
            [
                'name' => 'Users',
                'href' => $basePath . 'users.php',
                'current' => $currentPage === 'users',
                'admin_only' => true
            ],
            [
                'name' => 'Suppliers',
                'href' => $basePath . 'suppliers.php',
                'current' => $currentPage === 'suppliers'
            ],
            [
                'name' => 'Products',
                'href' => $basePath . 'products.php',
                'current' => $currentPage === 'products'
            ]
        ]
    ],
    [
        'name' => 'Transactions',
        'href' => '#',
        'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
        'current' => false,
        'children' => [
            [
                'name' => 'Sales',
                'href' => $basePath . 'sales.php',
                'current' => $currentPage === 'sales'
            ],
            [
                'name' => 'Purchases',
                'href' => $basePath . 'purchases.php',
                'current' => $currentPage === 'purchases'
            ]
        ]
    ],
    [
        'name' => 'Reports',
        'href' => '#',
        'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'current' => false,
        'children' => [
            [
                'name' => 'Cash Flow',
                'href' => $basePath . 'reports.php',
                'current' => $currentPage === 'reports'
            ],
            [
                'name' => 'Sales Report',
                'href' => $reportsBasePath . 'cash_inflow.php',
                'current' => $currentPage === 'cash_inflow'
            ],
            [
                'name' => 'Purchase Report',
                'href' => $reportsBasePath . 'cash_outflow.php',
                'current' => $currentPage === 'cash_outflow'
            ],
            [
                'name' => 'Journal',
                'href' => $reportsBasePath . 'journal.php',
                'current' => $currentPage === 'journal'
            ]
        ]
    ]
];
?>

<nav class="nav-container" x-data="{ openSubmenu: null }">
<ul role="list" class="space-y-0.5">
    <?php foreach ($navigation as $index => $item): ?>
        <?php if (isset($item['children'])): ?>
            <!-- Submenu item -->
            <li class="nav-item <?php echo $item['current'] ? 'active' : ''; ?>" x-data="{ isHovered: false }" @mouseenter="isHovered = true" @mouseleave="isHovered = false">
                <button type="button" 
                        class="nav-button <?php echo $item['current'] ? 'active' : ''; ?>"
                        @click="openSubmenu = openSubmenu === <?php echo $index; ?> ? null : <?php echo $index; ?>">
                    <svg class="nav-icon" :class="{ 'text-indigo-600': isHovered || <?php echo $item['current'] ? 'true' : 'false'; ?> }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $item['icon']; ?>" />
                    </svg>
                    <span class="nav-text"><?php echo $item['name']; ?></span>
                    <svg class="submenu-indicator" 
                         :class="{ 'open': openSubmenu === <?php echo $index; ?>, 'text-indigo-600': isHovered || <?php echo $item['current'] ? 'true' : 'false'; ?> }"
                         viewBox="0 0 20 20" fill="currentColor"
                         :class="{ 'text-indigo-600': isHovered || <?php echo $item['current'] ? 'true' : 'false'; ?> }">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </button>
                <ul x-show="openSubmenu === <?php echo $index; ?>" 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="submenu" style="display: none" x-show="openSubmenu === <?php echo $index; ?>">
                    <?php foreach ($item['children'] as $child): ?>
                        <?php if (!isset($child['admin_only']) || ($child['admin_only'] && $isAdmin)): ?>
                            <li class="submenu-item">
                                <a href="<?php echo $child['href']; ?>" 
                                   class="submenu-link <?php echo $child['current'] ? 'active' : ''; ?>">
                                    <?php echo $child['name']; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php else: ?>
            <!-- Single item -->
            <li class="nav-item <?php echo $item['current'] ? 'active' : ''; ?>" x-data="{ isHovered: false }" @mouseenter="isHovered = true" @mouseleave="isHovered = false">
                <a href="<?php echo $item['href']; ?>" 
                   class="nav-button <?php echo $item['current'] ? 'active' : ''; ?>">
                    <svg class="nav-icon" :class="{ 'text-indigo-600': isHovered || <?php echo $item['current'] ? 'true' : 'false'; ?> }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $item['icon']; ?>" />
                    </svg>
                    <?php echo $item['name']; ?>
                </a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
</nav>

<!-- Debug info (hapus setelah testing) -->
<!-- 
Current Script: <?php echo $currentScript; ?>
Base Path: <?php echo $basePath; ?>
Reports Base Path: <?php echo $reportsBasePath; ?>
Current Page: <?php echo $currentPage; ?>
-->