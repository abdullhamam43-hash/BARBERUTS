```php
<?php
session_start();
require_once '../dbconnection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_pelanggan = $_SESSION['username'] ?? 'Pelanggan';

$barber_id = filter_input(INPUT_POST, 'barber_id', FILTER_VALIDATE_INT);
$tanggal = trim((string) ($_POST['tanggal'] ?? ''));
$waktu = trim((string) ($_POST['waktu'] ?? ''));

if (!$barber_id || $tanggal === '' || $waktu === '') {
    header("Location: reservasi.php");
    exit();
}

try {
    $db = new DBconnection();

    $queryBarber = "SELECT name, specialization FROM barbers WHERE id = $1";
    $resBarber = $db->send_query($queryBarber, [$barber_id]);

    $nama_barber = "-";
    $spesialisasi = "-";

    if ($resBarber->status && !empty($resBarber->data)) {
        $nama_barber = $resBarber->data[0]['name'];
        $spesialisasi = $resBarber->data[0]['specialization'];
    }
} catch (Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

$error_message = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['konfirmasi'])) {
    $checkQuery = "
        SELECT id
        FROM reservations
        WHERE barber_id = $1
        AND reservation_date = $2
        AND time_slot = $3
        AND status != 'cancelled'
    ";

    $checkResult = $db->send_query(
        $checkQuery,
        [$barber_id, $tanggal, $waktu]
    );

    if (!empty($checkResult->data)) {
        $error_message = "Mohon maaf, slot waktu ini baru saja dipesan oleh pelanggan lain. Silakan ubah jadwal Anda.";
    } else {
        $insertQuery = "
            INSERT INTO reservations
            (user_id, barber_id, reservation_date, time_slot, status, notes)
            VALUES ($1, $2, $3, $4, 'pending', $5)
        ";

        $insertResult = $db->send_query($insertQuery, [
            $user_id,
            $barber_id,
            $tanggal,
            $waktu,
            "Pembayaran di Kasir"
        ]);

        if ($insertResult->status) {
            header("Location: riwayat_reservasi.php");
            exit();
        } else {
            $error_message = "Gagal menyimpan reservasi: " . $insertResult->message;
        }
    }
}

$tanggal_kunjungan = date('d F Y', strtotime($tanggal));
$jam_waktu = htmlspecialchars($waktu) . " WIB";
$status_pembayaran = "Bayar di Kasir (Offline)";
$biaya = "Rp 45.000";
?>
```


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Reservasi - Barbershop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style> body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body class="min-h-screen text-gray-900">
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i data-lucide="scissors" class="w-6 h-6 text-red-600"></i>
            <span class="font-bold tracking-wider">BARBERSHOP</span>
        </div>
        <div class="flex gap-6 text-sm font-medium">
            <a href="about.php" class="text-gray-500 hover:text-black">About</a>
            <a href="reservasi.php" class="text-black border-b-2 border-black pb-1">Reservasi</a>
            <a href="riwayat_reservasi.php" class="text-gray-500 hover:text-black">Riwayat</a>
            <a href="../logout.php" class="text-gray-500 hover:text-black">Logout</a>
        </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 py-12">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold mb-2">Konfirmasi Data Reservasi</h1>
            <p class="text-gray-500 text-sm">Harap periksa kembali detail pesanan jadwal Anda sebelum mengonfirmasi.</p>
        </div>

        <?php if($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 flex items-center">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                <span class="text-sm font-semibold"><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6 shadow-sm">
            <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                <span class="text-xs font-semibold text-gray-500 tracking-wider">DETAIL BOOKING</span>
                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded">MENUNGGU KONFIRMASI</span>
            </div>

            <div class="space-y-4 mt-4">
                <div class="flex justify-between border-b border-gray-100 pb-4">
                    <span class="text-sm text-gray-500">Nama Pelanggan</span>
                    <span class="text-sm font-semibold"><?= htmlspecialchars($nama_pelanggan) ?></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-4">
                    <span class="text-sm text-gray-500">Barber / Kapster</span>
                    <div class="text-right">
                        <span class="text-sm font-semibold block"><?= htmlspecialchars($nama_barber) ?></span>
                        <span class="text-xs text-gray-400"><?= htmlspecialchars($spesialisasi) ?></span>
                    </div>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-4">
                    <span class="text-sm text-gray-500">Tanggal Kunjungan</span>
                    <span class="text-sm font-semibold"><?= $tanggal_kunjungan ?></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-4">
                    <span class="text-sm text-gray-500">Jam / Slot Waktu</span>
                    <span class="text-sm font-semibold"><?= $jam_waktu ?></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-4">
                    <span class="text-sm text-gray-500">Status Pembayaran</span>
                    <span class="text-sm font-semibold"><?= $status_pembayaran ?></span>
                </div>
                <div class="flex justify-between bg-gray-50 p-4 rounded-md">
                    <span class="text-sm font-semibold">Biaya Estimasi</span>
                    <span class="text-sm font-bold text-lg"><?= $biaya ?></span>
                </div>
            </div>

            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-md p-4 flex gap-3 items-start">
                <i data-lucide="info" class="w-5 h-5 text-gray-500 flex-shrink-0"></i>
                <p class="text-xs text-gray-600 leading-relaxed">Harap hadir 10 menit sebelum jadwal Anda dimulai. Keterlambatan lebih dari 15 menit dapat membatalkan nomor antrean.</p>
            </div>
        </div>

        <form method="POST" action="" class="flex justify-between gap-4">
            <button type="button" onclick="window.location.href='reservasi.php'" class="px-6 py-2.5 border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50 transition">
                Batal / Ubah Jadwal
            </button>
            <button type="submit" name="konfirmasi" class="px-6 py-2.5 bg-black text-white rounded-md text-sm font-medium hover:bg-gray-800 transition">
                Konfirmasi Reservasi
            </button>
        </form>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>