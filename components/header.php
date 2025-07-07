<?php
// Diasumsikan BASE_URL dan fungsi-fungsi lain sudah dimuat
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navigation.css">

    <script src="<?php echo BASE_URL; ?>/assets/js/ui-enhancements.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js" defer></script>

</head>

<body class="h-full">
    <div x-data="{ sidebarOpen: false }" class="min-h-full">
        <?php if (isLoggedIn()): ?>
            <div x-show="sidebarOpen" class="relative z-50 lg:hidden">
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
                        <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-2">
                            <div class="flex h-16 shrink-0 items-center">
                                <h1 class="text-xl font-bold text-indigo-600"><?php echo APP_NAME; ?></h1>
                            </div>
                            <nav class="flex flex-1 flex-col">
                                <?php include __DIR__ . '/navigation.php'; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar ala vertical minimalis modern -->
            <aside class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-20 xl:w-64 lg:flex-col bg-white border-r border-gray-200 drop-shadow-lg">
                <div class="flex flex-col h-full">
                    <!-- Logo/Header -->
                    <div class="flex items-center justify-center h-16 border-b border-gray-100">
                        <span class="text-2xl font-extrabold text-indigo-600 tracking-tight xl:block hidden"><?php echo APP_NAME; ?></span>
                        <span class="text-2xl font-extrabold text-indigo-600 tracking-tight xl:hidden block">PM</span>
                    </div>
                    <!-- Navigation -->
                    <nav class="flex-1 flex flex-col py-4 px-2 xl:px-4 gap-y-2">
                        <?php include __DIR__ . '/navigation.php'; ?>
                    </nav>
                    <!-- More/Menu bottom -->
                    <div class="mt-auto p-2 m-6 rounded-lg flex flex-col items-center xl:items-center bg-red-50">
                        <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="flex items-center text-center justify-center gap-1 px-3 py-2 rounded-lg text-gray-500  hover:text-red-600 transition w-12 xl:w-32">
                            <span class="hidden xl:block text-sm font-semibold">Logout</span>
                            <svg class="h-6 w-6 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1" />
                            </svg>
                        </a>
                    </div>
                </div>
            </aside>

            <div class="lg:pl-72">
                <div class="sticky top-0 z-40 flex h-20 shrink-0 items-center gap-x-4 px-4 sm:gap-x-6 sm:px-6 lg:px-8">
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
                        <div class="flex items-center gap-x-2 lg:gap-x-4 bg-white border border-gray-200 rounded-xl shadow-sm p-2.5 text-gray-300 backdrop-blur-md  m-3">
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" type="button" class="-m-1.5 flex items-center p-1.5">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="h-8 w-8 rounded-full bg-gray-50" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
                                    <span class="hidden lg:flex lg:items-center">
                                        <span class="ml-4 text-sm font-semibold leading-6 text-gray-900" aria-hidden="true"><?php echo htmlspecialchars(getCurrentUser()['nama']); ?></span>
                                       
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <main>
                    <div class="px-4 sm:px-6 lg:px-8">
                    <?php else: ?>
                        <main class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
                            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                            <?php endif; ?>