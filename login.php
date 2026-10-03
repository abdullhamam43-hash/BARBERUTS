<?php
ob_start();
session_start();
require_once __DIR__. '/dbconnection.php';
require_once __DIR__. '/user/users.php';

$result = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["username"]) || empty($_POST["password"])) {
        $result = new Response(false, "Username dan password harus diisi!");
    } else {
        $username = trim($_POST["username"]);
        $password = $_POST["password"];

        try {
            $db = new DBconnection();
            $user = new User($db);

            $loginResult = $user->login($username, $password);

            if ($loginResult->status) {
                $userData = $loginResult->data;

                if (($userData['role'] ?? '') !== 'user') {
                    $result = new Response(false, "Akun ini bukan akun pelanggan!");
                } else {
                    $_SESSION['user_id'] = $userData['id'];
                    $_SESSION['username'] = $userData['username'];
                    $_SESSION['email'] = $userData['email'];
                    $_SESSION['role'] = $userData['role'];

                    header("Location: user/user_dashboard.php");
                    exit();
                }
            } else {
                $result = $loginResult;
            }

            $db->close_connection();
        } catch (DBException $e) {
            $result = new Response(false, "Error: " . htmlspecialchars($e->getMessage()));
        }

    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbershop - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 bg-gray-50 text-gray-900 antialiased">

    <?php if ($result !== null): ?>
        <div class="fixed top-5 right-5 z-50 bg-gray-900 text-white px-5 py-3 rounded-lg shadow-lg text-sm font-medium">
            <?php echo htmlspecialchars($result->message); ?>
        </div>
    <?php endif; ?>

    <div class="w-full flex-grow flex items-center justify-center py-8">
        <div class="w-full max-w-[440px]">

            <div id="card-login-customer" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Login Akun</h1>
                    <p class="text-xs text-gray-600 mt-2">Silakan masukkan username dan password Anda</p>
                </div>

                <div class="border-b border-gray-100 my-4"></div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-900 mb-1.5">Username</label>
                        <input type="text" name="username" required
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors"
                            placeholder="">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-900 mb-1.5">Password</label>
                        <input type="password" name="password" required
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors"
                            placeholder="">
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full bg-black hover:bg-gray-800 text-white font-medium py-2.5 px-4 rounded-md text-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-black">
                            Login
                        </button>
                    </div>

                    <div>
                        <a href='admin/login_admin.php'
                            class="w-full bg-white hover:bg-gray-50 text-gray-900 font-medium py-2.5 px-4 rounded-md text-sm border border-gray-300 transition-colors duration-150 block text-center">
                            Masuk sebagai Admin
                        </a>
                    </div>
                </form>

                <div class="border-b border-gray-100 my-6"></div>

                <div class="text-center text-xs text-gray-600">
                    Belum punya akun?
                    <a href="register.php" class="font-bold text-gray-900 hover:underline">Daftar di sini</a>
                </div>
            </div>

        </div>
    </div>

    <footer class="py-4 text-center text-xs font-semibold tracking-widest text-gray-500 uppercase">
        BarberShop
    </footer>

    <script>
        lucide.createIcons();

    </script>
</body>
</html>