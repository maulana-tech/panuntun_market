<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js for interactive components -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/custom.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    
    <!-- Custom JavaScript -->
    <script src="../assets/js/ui-enhancements.js" defer></script>
    <script src="../assets/js/main.js" defer></script>

</head>
<body class="h-full">
    <div x-data="{ sidebarOpen: false }" class="min-h-full">
        <?php if (isLoggedIn()): ?>
            <!-- Sidebar for mobile -->
            <div x-show="sidebarOpen" class="relative z-50 lg:hidden" x-description="Off-canvas menu for mobile, show/hide based on off-canvas menu state.">
                <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/80"></div>
                
                <div class="fixed inset-0 flex">
                    <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative mr-16 flex w-full max-w-xs flex-1">
                        <div x-show="sidebarOpen" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute left-full top-0 flex w-16 justify-center pt-5">
                            <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                                <span class="sr-only">Close sidebar</span>
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Sidebar component for mobile -->
                        <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-2">
                            <div class="flex h-16 shrink-0 items-center">
                                <h1 class="text-xl font-bold text-primary-600"><?php echo APP_NAME; ?></h1>
                            </div>
                            <nav class="flex flex-1 flex-col">
                                <?php 
                                // Determine correct path for navigation.php based on actual directory structure
                                $navPath = '';
                                $currentScript = $_SERVER['PHP_SELF'];
                                if (basename(__FILE__) === 'header.php') {
                                    // header.php is in components/, so navigation.php is in same directory
                                    $navPath = dirname(__FILE__) . '/navigation.php';
                                } else {
                                    // Fallback: try different paths based on current script location
                                    if (strpos($currentScript, '/pages/reports/') !== false) {
                                        $navPath = '../../components/navigation.php';
                                    } elseif (strpos($currentScript, '/pages/') !== false) {
                                        $navPath = '../components/navigation.php';
                                    } elseif (strpos($currentScript, '/auth/') !== false) {
                                        $navPath = '../components/navigation.php';
                                    } else {
                                        // Root level
                                        $navPath = 'components/navigation.php';
                                    }
                                }
                                
                                // Include with error handling
                                if (file_exists($navPath)) {
                                    include $navPath;
                                } else {
                                    echo "<!-- Navigation file not found at: $navPath -->";
                                    echo '<div class="text-red-500 text-sm p-4">Navigation menu unavailable<br><small>Looking for: ' . htmlspecialchars($navPath) . '</small></div>';
                                }
                                ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Static sidebar for desktop -->
            <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
                <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6">
                    <div class="flex h-16 shrink-0 items-center">
                        <h1 class="text-xl font-bold text-primary-600"><?php echo APP_NAME; ?></h1>
                    </div>
                    <nav class="flex flex-1 flex-col">
                        <?php 
                        // Use the same navigation path logic for desktop sidebar
                        if (file_exists($navPath)) {
                            include $navPath;
                        } else {
                            echo "<!-- Navigation file not found at: $navPath -->";
                            echo '<div class="text-red-500 text-sm p-4">Navigation menu unavailable<br><small>Looking for: ' . htmlspecialchars($navPath) . '</small></div>';
                        }
                        ?>
                    </nav>
                </div>
            </div>
            
            <!-- Main content area -->
            <div class="lg:pl-72">
                <!-- Top navigation -->
                <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                    <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <div class="h-6 w-px bg-gray-900/10 lg:hidden" aria-hidden="true"></div>

                    <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                        <div class="relative flex flex-1">
                            
                        </div>
                        <div class="flex items-center gap-x-4 lg:gap-x-6">
                            <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
                                <span class="sr-only">View notifications</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                            </button>

                            <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-900/10" aria-hidden="true"></div>

                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" type="button" class="-m-1.5 flex items-center p-1.5">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="h-8 w-8 rounded-full bg-gray-50" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
                                    <span class="hidden lg:flex lg:items-center">
                                        <span class="ml-4 text-sm font-semibold leading-6 text-gray-900" aria-hidden="true"><?php echo getCurrentUser()['nama']; ?></span>
                                        <svg class="ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>
                                <div x-show="open" @click.away="open = false" class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none" x-transition>
                                    <a href="#" class="block px-3 py-1 text-sm leading-6 text-gray-900">Your profile</a>
                                    <?php 
                                        $logoutPath = '';
                                        if (strpos($_SERVER['PHP_SELF'], '/auth/') !== false) {
                                            $logoutPath = 'logout.php';
                                        } elseif (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
                                            $logoutPath = '../auth/logout.php';
                                        } else {
                                            $logoutPath = 'auth/logout.php';
                                        }
                                    ?>
                                    <a href="<?php echo $logoutPath; ?>" class="block px-3 py-1 text-sm leading-6 text-gray-900">Sign out</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Page content -->
                <main class="py-6 lg:py-8">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <?php else: ?>
            <!-- Login page layout -->
            <main class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <?php endif; ?>