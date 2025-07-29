<?php
require_once dirname(__DIR__) . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location:/panuntun_market/pages/dashboard.php');
    exit();
}

$error = '';
$success = '';
$email = ''; // Initialize email variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi.';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT id_pengguna, nama, jabatan, email, password FROM pengguna WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id_pengguna'];
                $_SESSION['user_name'] = $user['nama'];
                $_SESSION['user_role'] = $user['jabatan'];
                $_SESSION['user_email'] = $user['email'];

                header('Location: /panuntun_market/pages/dashboard.php');
                exit();
            } else {
                $error = 'Email atau password yang Anda masukkan salah.';
            }
        } else {
            $error = 'Email atau password yang Anda masukkan salah.';
        }
    }
}

$pageTitle = 'Login';
// We don't include a standard header here to have full control over the page layout
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-white">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* You can define your primary colors here to match your app's theme */
        .bg-primary-600 {
            background-color: rgb(82, 80, 129);
        }

        .hover\:bg-primary-700:hover {
            background-color: #4338ca;
        }

        .text-primary-600 {
            color: #4f46e5;
        }

        .focus\:ring-primary-500:focus {
            --tw-ring-color: #6366f1;
        }

        .focus\:border-primary-500:focus {
            --tw-border-opacity: 1;
            border-color: #6366f1;
        }

        .card-shadow {
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
    </style>
</head>

<body class="h-full">

    <div class="flex min-h-full">
        <div class="relative hidden w-0 flex-1 lg:block">
            <img class="absolute inset-0 h-full w-full object-cover"
                src="https://images.unsplash.com/photo-1580913428023-02c695666d61?q=80&w=2584&auto=format&fit=crop"
                alt="Minimarket Aisle">
            <div class="absolute inset-0 bg-primary-600 opacity-75"></div>
            <div class="relative z-10 flex flex-col justify-center items-center h-full text-white text-center px-12">
                <h1 class="text-4xl font-bold tracking-tight">
                    Sistem Arus Kas Minimarket Panuntun Berbasis Websites
                </h1>
                <p class="mt-4 text-xl">
                    VERA ROSSYANA SALSABILLA
                </p>
            </div>
        </div>

        <div class="flex flex-1 flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900">Sign in to your account</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Selamat datang kembali!
                    </p>
                </div>

                <div class="mt-8">
                    <div class="mt-6">
                        <?php if ($error): ?>
                            <div class="mb-4 rounded-md bg-red-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-red-800"><?php echo $error; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form action="#" method="POST" class="space-y-6">
                            <div>
                                <label for="email" class="block text-sm font-medium leading-6 text-gray-900">Email address</label>
                                <div class="relative mt-2">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                                            <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                                        </svg>
                                    </div>
                                    <input id="email" name="email" type="email" autocomplete="email" required
                                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                        class="block w-full rounded-md border-0 py-2.5 pl-10 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-500 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium leading-6 text-gray-900">Password</label>
                                <div class="relative mt-2">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <input id="password" name="password" type="password" autocomplete="current-password" required
                                        class="block w-full rounded-md border-0 py-2.5 pl-10 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-500 sm:text-sm sm:leading-6">
                                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                                        <svg id="eye-icon" class="h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.432 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg id="eye-slash-icon" class="h-5 w-5 text-gray-500 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.572M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M.75 3l14.25 14.25" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <button type="submit"
                                    class="flex w-full justify-center rounded-md bg-primary-600 py-2.5 px-3 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 transition-colors duration-200">
                                    Sign in
                                </button>
                            </div>
                        </form>

                        <div class="mt-8">
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                    <div class="w-full border-t border-gray-200"></div>
                                </div>
                                <div class="relative flex justify-center text-sm font-medium leading-6">
                                    <span class="bg-white px-6 text-gray-900">Demo Accounts</span>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-4">
                                <div class="text-xs text-center text-gray-600 border p-2 rounded-md">
                                    <strong>Admin:</strong> admin@panuntun.com / <span class="font-mono">admin123</span>
                                </div>
                                <div class="text-xs text-center text-gray-600 border p-2 rounded-md">
                                    <strong>Owner:</strong> owner@panuntun.com / <span class="font-mono">owner123</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeSlashIcon = document.getElementById('eye-slash-icon');

            togglePassword.addEventListener('click', function(e) {
                // Toggle the type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

                // Toggle the icon
                eyeIcon.classList.toggle('hidden');
                eyeSlashIcon.classList.toggle('hidden');
            });
        });
    </script>

</body>

</html>