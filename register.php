<?php
require_once 'dbconnection.php';
session_start();

$result = null;
$old = ['username' => '', 'email' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? '');
    $email    = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirm  = $_POST["confirm_password"] ?? '';

    $old = ['username' => $username, 'email' => $email];

    if ($username === '' || $email === '' || $password === '' || $confirm === '') {
        $result = new Response(false, "Semua field wajib diisi!");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result = new Response(false, "Format email tidak valid!");
    } elseif (strlen($password) < 6) {
        $result = new Response(false, "Password minimal 6 karakter!");
    } elseif ($password !== $confirm) {
        $result = new Response(false, "Konfirmasi password tidak cocok!");
    } else {
        try {
            $db = new DBconnection();

            $check = $db->send_query(
                "SELECT id FROM users WHERE username = $1 OR email = $2",
                [$username, $email]
            );

            if (!empty($check->data)) {
                $result = new Response(false, "Username atau email sudah terdaftar!");
            } else {
                $password = password_hash($password, PASSWORD_BCRYPT);
                $insert = $db->send_query(
                    "INSERT INTO users (username, email, password, role) VALUES ($1, $2, $3, 'user')",
                    [$username, $email, $password]
                );

                if ($insert->status) {
                    $result = new Response(true, "Pendaftaran berhasil! Silakan login.");
                } else {
                    $result = new Response(false, "Gagal registrasi: " . $insert->message);
                }
            }
            $db->close_connection();
        } catch (DBException $e) {
            $result = new Response(false, "Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbershop - Daftar Akun</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body class="min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 bg-gray-50 text-gray-900 antialiased">

    <?php if ($result !== null): ?>
        <div class="fixed top-5 right-5 z-50 bg-gray-900 text-white px-5 py-3 rounded-lg shadow-lg text-sm font-medium">
            <?php echo htmlspecialchars($result->message); ?>
        </div>
    <?php endif; ?>

    <div class="w-full flex-grow flex items-center justify-center py-8">
        <div class="w-full max-w-[440px]">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold tracking-tight">Daftar Akun Baru</h1>
                    <p class="text-xs text-gray-600 mt-2">Lengkapi data formulir pendaftaran di bawah ini</p>
                </div>

                <div class="border-b border-gray-100 my-4"></div>

                <?php if ($result !== null && $result->status): ?>
                    <div class="text-center space-y-4">
                        <p class="text-sm text-gray-700">Akun Anda sudah dibuat. Silakan masuk untuk melanjutkan.</p>
                        <a href="login.php"
                            class="block w-full bg-black hover:bg-gray-800 text-white font-medium py-2.5 px-4 rounded-md text-sm transition-colors duration-150">
                            Login Sekarang
                        </a>
                    </div>
                <?php else: ?>
                <form method="POST" action="register.php" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Username</label>
                        <input type="text" name="username" required
                            value="<?php echo htmlspecialchars($old['username']); ?>"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Email</label>
                        <input type="email" name="email" required
                            value="<?php echo htmlspecialchars($old['email']); ?>"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Password</label>
                        <div class="relative">
                            <input type="password" id="reg-password" name="password" required minlength="6"
                                class="w-full pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors">
                            <button type="button" onclick="togglePw('reg-password','eye1')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                <i id="eye1" data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Konfirmasi Password</label>
                        <div class="relative">
                            <input type="password" id="reg-confirm" name="confirm_password" required minlength="6"
                                class="w-full pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black transition-colors">
                            <button type="button" onclick="togglePw('reg-confirm','eye2')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                <i id="eye2" data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full bg-black hover:bg-gray-800 text-white font-medium py-2.5 px-4 rounded-md text-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-black">
                            Daftar Sekarang
                        </button>
                    </div>
                </form>
                <?php endif; ?>

                <div class="border-b border-gray-100 my-6"></div>

                <div class="text-center text-xs text-gray-600">
                    Sudah memiliki akun?
                    <a href="login.php" class="font-bold text-gray-900 hover:underline">Masuk ke halaman Login</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-4 text-center text-xs font-semibold tracking-widest text-gray-500 uppercase">
        BarberShop
    </footer>

    <script>
        lucide.createIcons();
        function togglePw(inputId, iconId) {
            const input = document.getElementById(inputId);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            document.getElementById(iconId).setAttribute('data-lucide', show ? 'eye-off' : 'eye');
            lucide.createIcons();
        }
    </script>
</body>
</html>