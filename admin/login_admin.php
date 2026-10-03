<?php
ob_start();
session_name("admin_session");
session_start();
require_once '../dbconnection.php';
$result = null;
$oldUsername = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["username"]) || empty($_POST["password"])) {
        $result = new Response(false, "Username dan password harus diisi!");
    } else {
        $username = trim($_POST["username"]);
        $password = $_POST["password"];
        $oldUsername = $username;

        try {
            $db = new DBconnection();
            $query = "SELECT * FROM users WHERE username = $1";
            $result_query = $db->send_query($query, [$username]);

            if (!$result_query->status || empty($result_query->data)) {
                $result = new Response(false, "Username atau password salah!");
            } else {
                $user = $result_query->data[0];
                if ($user['role'] !== 'admin') {
                    $result = new Response(false, "Anda tidak memiliki akses admin!");
                } elseif ($password === $user['password']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $result = new Response(false, "Username atau password salah!");
                }
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
    <title>Login Admin - Barbershop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        pageBg: '#f8f9fa',
                        cardBg: '#ffffff',
                        cardBorder: '#e5e7eb',
                        inputBorder: '#e2e8f0',
                        textPrimary: '#0f172a',
                        textSecondary: '#64748b',
                        textMuted: '#94a3b8',
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
<body class="min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 bg-pageBg text-textPrimary antialiased">

    <?php if ($result !== null): ?>
        <div class="fixed top-5 right-5 z-50 bg-gray-900 text-white px-5 py-3 rounded-lg shadow-lg text-sm font-medium">
            <?php echo htmlspecialchars($result->message); ?>
        </div>
    <?php endif; ?>

    <div class="w-full flex-grow flex items-center justify-center py-8">
        <div class="w-full max-w-[440px]">

            <div id="card-login-admin" class="bg-cardBg rounded-lg border border-cardBorder shadow-[0_2px_8px_rgba(0,0,0,0.04)] p-6 sm:p-8">
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold text-textPrimary tracking-tight">Login Admin</h1>
                </div>

                <div class="border-b border-gray-100 my-4"></div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label for="admin-id" class="block text-xs font-semibold text-textPrimary mb-1.5">Username Admin</label>
                        <input type="text" id="admin-id" name="username" required
                            value="<?php echo htmlspecialchars($oldUsername); ?>"
                            class="w-full px-3 py-2 text-sm border border-inputBorder rounded-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors"
                            placeholder="">
                    </div>

                    <div>
                        <label for="admin-password" class="block text-xs font-semibold text-textPrimary mb-1.5">Password</label>
                        <div class="relative">
                            <input type="password" id="admin-password" name="password" required
                                class="w-full pl-3 pr-10 py-2 text-sm border border-inputBorder rounded-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors"
                                placeholder="">
                            <button type="button" onclick="togglePasswordVisibility('admin-password', 'eye-icon-admin')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                <i id="eye-icon-admin" data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full bg-black hover:bg-gray-800 text-white font-medium py-2.5 px-4 rounded-md text-sm transition-colors duration-150 flex items-center justify-center space-x-2 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-black">
                            <span>Masuk sebagai Admin</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>

                <div class="relative my-6 flex items-center justify-center">
                    <div class="border-t border-gray-200 w-full"></div>
                    <span class="bg-cardBg px-3 text-[11px] font-medium text-textMuted uppercase tracking-wider absolute">ATAU</span>
                </div>

                <div>
                    <a href="/BARBERDEMO/login.php"
                        class="w-full bg-white hover:bg-gray-50 text-textPrimary font-medium py-2.5 px-4 rounded-md text-sm border border-gray-300 transition-colors duration-150 flex items-center justify-center space-x-2">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Kembali ke Login Pelanggan</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <footer class="py-4 text-center text-xs font-semibold tracking-widest text-textMuted uppercase">
        BarberShop
    </footer>

    <script>
        lucide.createIcons();

        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>