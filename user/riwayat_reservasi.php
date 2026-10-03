<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../dbconnection.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

if ($user_id === null) {
    header("Location: login.php");
    exit();
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'user_dashboard.php') === false) {
    header("Location: user_dashboard.php#riwayat");
    exit();
}

$riwayat = [];
$error_message = null;

function aman($nilai): string
{
    return htmlspecialchars(
        (string) ($nilai ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function tanggalIndonesia(string $tanggal): string
{
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];

    $tgl = DateTime::createFromFormat('!Y-m-d', $tanggal);

    if (!$tgl) {
        return $tanggal;
    }

    return $tgl->format('d') . ' '
        . $bulan[(int) $tgl->format('n')] . ' '
        . $tgl->format('Y');
}

try {
    $db = new DBconnection();

    // Ambil seluruh reservasi milik user yang sedang login
    $query = "
        SELECT
            r.id,
            r.reservation_date,
            r.time_slot,
            r.status,
            r.notes,
            b.name AS nama_barber
        FROM reservations r
        LEFT JOIN barbers b ON b.id = r.barber_id
        WHERE r.user_id = $1
        ORDER BY
            r.reservation_date DESC,
            r.time_slot DESC,
            r.id DESC
    ";

    $hasil = $db->send_query($query, [$user_id]);

    if ($hasil->status) {
        $riwayat = $hasil->data;
    } else {
        error_log('Query riwayat gagal: ' . $hasil->message);
        $error_message = 'Riwayat gagal dimuat. Silakan coba kembali.';
    }

    $db->close_connection();
} catch (Exception $e) {
    error_log('Error riwayat: ' . $e->getMessage());
    $error_message = 'Tidak dapat mengambil riwayat reservasi.';
}

// Pesan dari proses reservasi, jika tersedia
$flash = $_SESSION['flash_reservasi'] ?? null;
unset($_SESSION['flash_reservasi']);

$status_labels = [
    'pending'   => 'Menunggu Konfirmasi',
    'confirmed' => 'Dikonfirmasi',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan'
];

$status_colors = [
    'pending'   => 'bg-yellow-100 text-yellow-800',
    'confirmed' => 'bg-blue-100 text-blue-800',
    'completed' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Reservasi - Barbershop</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
    </style>
</head>

<body class="min-h-screen text-gray-900">
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="max-w-5xl mx-auto flex flex-wrap items-center justify-between gap-4">
            <a href="user_dashboard.php" class="font-bold tracking-wider">
                BARBERSHOP
            </a>

            <div class="flex flex-wrap gap-6 text-sm font-medium">
                <a href="about.php" class="text-gray-500 hover:text-black">
                    About
                </a>

                <a href="reservasi.php" class="text-gray-500 hover:text-black">
                    Reservasi
                </a>

                <a
                    href="riwayat_reservasi.php"
                    class="text-black border-b-2 border-black pb-1"
                    aria-current="page"
                >
                    Riwayat
                </a>

                <a href="logout.php" class="text-gray-500 hover:text-black">
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-10">

        <div class="flex flex-wrap justify-between items-start gap-4 mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    Riwayat Reservasi
                </h1>

                <p class="text-sm text-gray-500">
                    Lihat jadwal booking dan status reservasi Anda.
                </p>
            </div>

            <a
                href="reservasi.php"
                class="bg-black text-white px-5 py-2.5 rounded-md text-sm font-medium hover:bg-gray-800"
            >
                Buat Reservasi
            </a>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 rounded-md border px-4 py-3 text-sm
                <?= !empty($flash['ok'])
                    ? 'bg-green-50 border-green-200 text-green-800'
                    : 'bg-red-50 border-red-200 text-red-800' ?>"
                role="status"
            >
                <?= aman($flash['pesan'] ?? '') ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>

            <div
                class="bg-red-50 border border-red-200 text-red-800 rounded-md px-4 py-3"
                role="alert"
            >
                <?= aman($error_message) ?>
            </div>

        <?php elseif (empty($riwayat)): ?>

            <div class="bg-white border border-gray-200 rounded-xl p-10 text-center">
                <h2 class="text-lg font-semibold mb-2">
                    Belum Ada Reservasi
                </h2>

                <p class="text-sm text-gray-500 mb-6">
                    Booking Anda akan muncul di sini setelah dikonfirmasi
                    melalui halaman konfirmasi reservasi.
                </p>

                <a
                    href="reservasi.php"
                    class="inline-block bg-black text-white px-6 py-3 rounded-md text-sm font-medium hover:bg-gray-800"
                >
                    Reservasi Sekarang
                </a>
            </div>

        <?php else: ?>

            <p class="text-sm text-gray-500 mb-4">
                Total: <?= count($riwayat) ?> reservasi
            </p>

            <div class="space-y-4">
                <?php foreach ($riwayat as $booking): ?>
                    <?php
                    $status = strtolower((string) $booking['status']);

                    $label_status = $status_labels[$status]
                        ?? ucfirst($status);

                    $warna_status = $status_colors[$status]
                        ?? 'bg-gray-100 text-gray-800';

                    $jam = substr((string) $booking['time_slot'], 0, 5);
                    ?>

                    <article class="bg-white border border-gray-200 rounded-xl p-5 md:p-6 shadow-sm">

                        <div class="flex flex-wrap justify-between items-center gap-3 border-b border-gray-100 pb-4 mb-4">
                            <span class="text-sm font-semibold">
                                Booking #<?= aman($booking['id']) ?>
                            </span>

                            <span class="text-xs font-semibold px-3 py-1.5 rounded-full <?= aman($warna_status) ?>">
                                <?= aman($label_status) ?>
                            </span>
                        </div>

                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <div>
                                <dt class="text-xs text-gray-500 mb-1">
                                    Barber / Kapster
                                </dt>

                                <dd class="text-sm font-semibold">
                                    <?= aman($booking['nama_barber'] ?? 'Barber tidak tersedia') ?>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs text-gray-500 mb-1">
                                    Tanggal Kunjungan
                                </dt>

                                <dd class="text-sm font-semibold">
                                    <?= aman(tanggalIndonesia($booking['reservation_date'])) ?>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs text-gray-500 mb-1">
                                    Jam / Slot Waktu
                                </dt>

                                <dd class="text-sm font-semibold">
                                    <?= aman($jam) ?> WIB
                                </dd>
                            </div>
                        </dl>

                        <?php if (!empty($booking['notes'])): ?>
                            <div class="mt-5 bg-gray-50 rounded-md px-4 py-3">
                                <p class="text-xs text-gray-500 mb-1">
                                    Catatan
                                </p>

                                <p class="text-sm">
                                    <?= aman($booking['notes']) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                    </article>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </main>
</body>
</html>