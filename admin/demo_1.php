<?php
session_start();
require_once '../dbconnection.php';
require_once 'reservation_admin.php';

// 1. Inisialisasi Database dan Class
$db = new DBconnection();
$reservationObj = new Reservation($db);

// 2. Menangkap Aksi (Konfirmasi / Batalkan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    $res_id = $_POST['reservation_id'];
    
    if ($_POST['action'] === 'confirm') {
        $reservationObj->confirm($res_id);
    } elseif ($_POST['action'] === 'cancel') {
        $reservationObj->cancel($res_id);
    }
    
    // Refresh halaman agar form tidak ter-submit ulang saat browser di-refresh
    header("Location: admin_dashboard.php#reservasi");
    exit();
}

// 3. Ambil Semua Data Reservasi
$resResult = $reservationObj->getAll();
$reservationsData = $resResult->status ? $resResult->data : [];

// 4. Hitung Statistik
$total_reservasi = count($reservationsData);
$menunggu_konfirmasi = 0;
$selesai_dikerjakan = 0;

foreach ($reservationsData as $r) {
    if ($r['status'] === 'pending') {
        $menunggu_konfirmasi++;
    } elseif ($r['status'] === 'confirmed') {
        $selesai_dikerjakan++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style> 
        body { font-family: sans-serif; background-color: #f1f5f9; } 
    </style>
</head>
<body class="text-gray-900 pb-10">

    <!-- Navbar Asli Anda -->
    <nav class="bg-black text-white p-4 mx-4 mt-4 rounded-md flex justify-between items-center shadow-lg">
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold tracking-wide">Admin Dashboard</h1>
            <a href="#pencukur" class="bg-white text-black px-4 py-2 rounded text-sm font-semibold">Kelola Pencukur</a>
            <a href="#jadwal" class="bg-gray-300 text-black px-4 py-2 rounded text-sm font-semibold">Kelola Jadwal</a>
            <a href="#reservasi" class="bg-gray-500 text-black px-4 py-2 rounded text-sm font-semibold">Pantau Reservasi</a>
        </div>
        <div class="flex items-center gap-4">
            <span class="font-bold text-sm">admin2</span>
            <a href="../logout.php" class="bg-red-600 text-white px-4 py-2 rounded text-sm font-bold">Logout</a>
        </div>
    </nav>

    <!-- Main Content Asli Anda -->
    <main class="bg-white mx-4 mt-6 p-8 rounded-lg shadow-sm border border-gray-200">
        
        <div class="mb-8">
            <h2 class="text-2xl font-bold mb-2">Log Reservasi Pelanggan (Admin)</h2>
            <p class="text-gray-500 text-sm">Pantau seluruh aktivitas transaksi pemesanan jadwal cukur.</p>
        </div>

        <!-- Kartu Statistik Asli Anda -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-50 rounded p-5 border-l-4 border-gray-800">
                <p class="text-xs text-gray-500 font-bold tracking-wider mb-1 uppercase">Total Reservasi</p>
                <p class="text-3xl font-bold"><?= $total_reservasi ?></p>
            </div>
            <div class="bg-gray-50 rounded p-5 border-l-4 border-yellow-500">
                <p class="text-xs text-gray-500 font-bold tracking-wider mb-1 uppercase">Menunggu Konfirmasi</p>
                <p class="text-3xl font-bold"><?= $menunggu_konfirmasi ?></p>
            </div>
            <div class="bg-gray-50 rounded p-5 border-l-4 border-green-500">
                <p class="text-xs text-gray-500 font-bold tracking-wider mb-1 uppercase">Selesai Dikerjakan</p>
                <p class="text-3xl font-bold"><?= $selesai_dikerjakan ?></p>
            </div>
        </div>

        <!-- Filter Asli Anda -->
        <div class="flex gap-4 mb-6">
            <div class="flex-1">
                <label class="block text-xs font-bold mb-2">Status</label>
                <select class="w-full border border-gray-300 rounded p-2 text-sm bg-gray-50">
                    <option>Semua Status</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold mb-2">Filter Tanggal</label>
                <input type="date" class="w-full border border-gray-300 rounded p-2 text-sm bg-white text-gray-400">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold mb-2">Cari Nama Pemesan</label>
                <input type="text" placeholder="Ketik nama pemesan..." class="w-full border border-gray-300 rounded p-2 text-sm bg-white">
            </div>
        </div>

        <!-- Tabel Asli Anda (Hanya bagian AKSI yang diselipkan Form) -->
        <div class="overflow-x-auto border border-gray-200">
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-gray-800 text-white font-bold text-xs">
                    <tr>
                        <th class="p-3 border-r border-gray-700 w-12">NO</th>
                        <th class="p-3 border-r border-gray-700">ID BOOKING</th>
                        <th class="p-3 border-r border-gray-700">NAMA USER</th>
                        <th class="p-3 border-r border-gray-700">BARBER</th>
                        <th class="p-3 border-r border-gray-700">TANGGAL</th>
                        <th class="p-3 border-r border-gray-700">JAM</th>
                        <th class="p-3 border-r border-gray-700">STATUS RESERVASI</th>
                        <th class="p-3">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservationsData)): ?>
                        <tr><td colspan="8" class="p-4 text-center text-gray-500">Belum ada reservasi.</td></tr>
                    <?php else: ?>
                        <?php foreach($reservationsData as $index => $row): 
                            // Format ID
                            $booking_id = "#RSV-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
                            
                            // Format Status sesuai gambar UI Anda
                            $status_label = "Menunggu";
                            $status_bg = "bg-yellow-100 text-yellow-800";
                            
                            if($row['status'] == 'confirmed') {
                                $status_label = "Dikonfirmasi";
                                $status_bg = "bg-green-100 text-green-800";
                            } elseif($row['status'] == 'cancelled') {
                                $status_label = "Dibatalkan";
                                $status_bg = "bg-red-100 text-red-800";
                            }
                        ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="p-3 border-r border-gray-200"><?= $index + 1 ?></td>
                            <td class="p-3 border-r border-gray-200"><?= $booking_id ?></td>
                            <td class="p-3 border-r border-gray-200"><?= htmlspecialchars($row['username']) ?></td>
                            <td class="p-3 border-r border-gray-200"><?= htmlspecialchars($row['barber_name']) ?></td>
                            <td class="p-3 border-r border-gray-200"><?= htmlspecialchars($row['reservation_date']) ?></td>
                            <td class="p-3 border-r border-gray-200"><?= substr($row['time_slot'], 0, 8) ?></td>
                            <td class="p-3 border-r border-gray-200">
                                <span class="px-3 py-1 rounded text-xs <?= $status_bg ?>"><?= $status_label ?></span>
                            </td>
                            <td class="p-3 flex gap-1">
                                <!-- Tombol Konfirmasi -->
                                <form method="POST" action="admin_dashboard.php#reservasi" class="m-0 inline">
                                    <input type="hidden" name="reservation_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="action" value="confirm">
                                    <button type="submit" class="bg-green-700 text-white px-3 py-1 rounded text-xs font-semibold cursor-pointer <?= $row['status'] !== 'pending' ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $row['status'] !== 'pending' ? 'disabled' : '' ?>>
                                        Konfirmasi
                                    </button>
                                </form>

                                <!-- Tombol Detail -->
                                <button type="button" 
                                    class="bg-cyan-500 text-white px-3 py-1 rounded text-xs font-semibold cursor-pointer"
                                    onclick="openDetailModal('<?= $booking_id ?>', '<?= htmlspecialchars($row['username']) ?>', '<?= htmlspecialchars($row['barber_name']) ?>', '<?= htmlspecialchars($row['reservation_date']) ?>', '<?= htmlspecialchars($row['time_slot']) ?>', '<?= $status_label ?>', '<?= htmlspecialchars($row['notes'] ?? '-') ?>')">
                                    Detail
                                </button>

                                <!-- Tombol Batalkan -->
                                <form method="POST" action="admin_dashboard.php#reservasi" class="m-0 inline" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?');">
                                    <input type="hidden" name="reservation_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold cursor-pointer <?= $row['status'] === 'cancelled' ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $row['status'] === 'cancelled' ? 'disabled' : '' ?>>
                                        Batalkan
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 text-right">
            <span class="text-xs text-gray-400">Menampilkan <?= count($reservationsData) ?> dari <?= count($reservationsData) ?> transaksi reservasi</span>
        </div>
    </main>

    <!-- ========================================================= -->
    <!-- MODAL POP-UP DETAIL -->
    <!-- ========================================================= -->
    <div id="detailModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
            
            <div class="flex justify-between items-center border-b border-gray-200 pb-3 mb-4">
                <h3 class="text-lg font-bold text-gray-800">Detail Reservasi</h3>
                <button onclick="closeDetailModal()" class="text-gray-400 hover:text-black font-bold text-xl">&times;</button>
            </div>
            
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">ID Booking:</span> 
                    <span id="modal-id" class="font-bold bg-gray-100 px-2 py-0.5 rounded text-xs text-gray-800"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Nama Pemesan:</span> 
                    <span id="modal-user" class="font-bold text-gray-800"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Pencukur (Barber):</span> 
                    <span id="modal-barber" class="font-bold text-gray-800"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Tanggal:</span> 
                    <span id="modal-date" class="font-bold text-gray-800"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Slot Jam:</span> 
                    <span id="modal-time" class="font-bold text-gray-800"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <span class="text-gray-500">Status Saat Ini:</span> 
                    <span id="modal-status" class="font-bold text-xs"></span>
                </div>
                <div class="flex flex-col pt-1">
                    <span class="text-gray-500 mb-1">Catatan Tambahan:</span> 
                    <p id="modal-notes" class="text-gray-700 bg-gray-50 p-2 rounded border border-gray-200 text-xs"></p>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button onclick="closeDetailModal()" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-black text-sm font-bold">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // Fungsi Membuka Modal Detail
        function openDetailModal(id, user, barber, date, time, status, notes) {
            document.getElementById('modal-id').innerText = id;
            document.getElementById('modal-user').innerText = user;
            document.getElementById('modal-barber').innerText = barber;
            document.getElementById('modal-date').innerText = date;
            document.getElementById('modal-time').innerText = time + ' WIB';
            
            // Pewarnaan teks status di Modal
            const statusEl = document.getElementById('modal-status');
            statusEl.innerText = status;
            if (status === 'Dikonfirmasi') statusEl.className = 'font-bold text-green-600 bg-green-100 px-2 py-0.5 rounded text-xs';
            else if (status === 'Dibatalkan') statusEl.className = 'font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded text-xs';
            else statusEl.className = 'font-bold text-yellow-600 bg-yellow-100 px-2 py-0.5 rounded text-xs';

            document.getElementById('modal-notes').innerText = notes;
            document.getElementById('detailModal').classList.remove('hidden');
        }

        // Fungsi Menutup Modal Detail
        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }
    </script>
</body>
</html>