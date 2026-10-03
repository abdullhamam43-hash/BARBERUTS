<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'user_dashboard.php') === false) {
    header("Location: user_dashboard.php#about");
    exit();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Barbershop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                        infoBoxBg: '#c3c3c3',
                        textPrimary: '#0f172a',
                        textSecondary: '#475569',
                        textMuted: '#64748b',
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
<body class="min-h-screen bg-pageBg text-textPrimary antialiased flex flex-col">

    <header class="w-full bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="relative flex items-center justify-center">
                    <svg class="w-8 h-8" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 6L24 6M12 30L24 30" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round"/>
                        <rect x="14" y="6" width="8" height="24" rx="2" fill="#e2e8f0" stroke="#0f172a" stroke-width="2"/>
                        <path d="M14 9L22 13M14 15L22 19M14 21L22 25" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                        <path d="M14 12L22 16M14 18L22 22" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
                        <path d="M7 10L12 14M7 26L12 22" stroke="#0f172a" stroke-width="2" stroke-linecap="round"/>
                        <path d="M29 10L24 14M29 26L24 22" stroke="#0f172a" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="font-extrabold tracking-widest text-lg text-textPrimary uppercase">BARBERSHOP</span>
            </div>

            <nav class="bg-gray-100 p-1 rounded-md flex items-center space-x-1 border border-gray-200">
                <a href="about.php" class="bg-white text-textPrimary font-semibold px-4 py-1.5 rounded text-sm shadow-sm transition-all">About</a>
                <a href="reservasi.php" class="text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all">Reservasi</a>
                <a href="riwayat_reservasi.php" class="text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all">Riwayat</a>
                <a href="../logout.php" class="text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all">Logout</a>
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-10 flex-grow w-full">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-textPrimary tracking-tight">Tentang Barbershop</h1>
            <p class="text-sm text-textMuted mt-1">Dokumentasi profil usaha dan sistem informasi manajemen reservasi barbershop.</p>
        </div>

        <hr class="border-gray-200 mb-8" />

        <section class="space-y-4 mb-10">
            <h2 class="text-base font-bold text-textPrimary uppercase tracking-wide">PROFIL BARBERSHOP</h2>

            <p class="text-sm text-gray-700 leading-relaxed">
                Barbershop kami merupakan usaha jasa pangkas rambut pria independen yang berdedikasi menghadirkan layanan potong rambut berkualitas, presisi, dan terjangkau. Berdiri di tengah kawasan aktivitas mahasiswa dan warga sekitar, kami mengedepankan standar kerapian tinggi serta kebersihan peralatan demi kenyamanan setiap pelanggan yang datang.
            </p>

            <p class="text-sm text-gray-700 leading-relaxed">
                Didukung oleh tenaga kapster berpengalaman, kami menyediakan berbagai opsi potongan rambut klasik maupun modern, pencucian rambut, hingga perawatan jenggot dan kumis. Kami percaya bahwa penampilan yang rapi dapat meningkatkan kepercayaan diri dalam menjalani aktivitas harian.
            </p>
        </section>

        <div class="bg-[#b3b3b3] border border-gray-400 rounded-lg p-6 text-textPrimary shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-wider mb-6 text-gray-800">INFORMASI OPERASIONAL & LOKASI</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-start space-x-3">
                    <div class="bg-textPrimary text-white p-2.5 rounded-full flex-shrink-0 mt-0.5">
                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">ALAMAT</h4>
                        <p class="text-xs font-bold text-textPrimary mt-1">Jl. Dharmahusada No. 127</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="border-2 border-textPrimary text-textPrimary p-2 rounded-full flex-shrink-0 mt-0.5">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">JAM OPERASIONAL</h4>
                        <p class="text-xs font-bold text-textPrimary mt-1">Setiap Hari 09.00 - 21.00 WIB</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="border-2 border-textPrimary text-textPrimary p-2 rounded-full flex-shrink-0 mt-0.5">
                        <i data-lucide="phone" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">KONTAK</h4>
                        <p class="text-xs font-bold text-textPrimary mt-1">0812-3456-7890</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>