<?php
session_start();
require_once '../dbconnection.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); 
    exit();
}


$db = new DBconnection();

$queryBarber = "SELECT id, name, specialization FROM barbers WHERE status = 'active' ORDER BY name ASC";
$resultBarber =$db->send_query($queryBarber);$barbers = $resultBarber->status ? $resultBarber->data : [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['temp_reservasi'] = [
        'barber_id' => $_POST['barber_id'],
        'tanggal' => $_POST['tanggal'],
        'waktu' => $_POST['waktu']
    ];
    
    header("Location: konfirmasi_reservasi.php");
    exit();
}
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'user_dashboard.php') === false) {
    header("Location: user_dashboard.php#reservasi");
    exit();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Jadwal Cukur - Barbershop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style> body {font-family: 'Inter', sans-serif;background-color: #f8f9fa;}
    html::-webkit-scrollbar {
        display: none;
    }
    html {
        scrollbar-width: none;
    }
    </style>
</head>
<body class="min-h-screen text-gray-900 pb-24">
    
    <nav class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <i data-lucide="scissors" class="w-6 h-6 text-red-600"></i>
            <span class="font-bold tracking-wider uppercase">Barbershop</span>
        </div>
        <div class="flex gap-6 text-sm font-medium">
            <a href="about.php" class="text-gray-500 hover:text-black">About</a>
            <a href="reservasi.php" class="text-black border-b-2 border-black pb-1">Reservasi</a>
            <a href="riwayat_reservasi.php" class="text-gray-500 hover:text-black">Riwayat</a>
            <a href="../logout.php" class="text-gray-500 hover:text-black">Logout</a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-10">
        <div class="mb-10">
            <h1 class="text-3xl font-bold mb-2">Form Reservasi Jadwal Cukur</h1>
            <p class="text-gray-500 text-sm">Silakan pilih barber, tentukan tanggal, dan pilih slot jam layanan yang tersedia.</p>
        </div>

        <form method="POST" action="reservasi.php">
            
            <div class="mb-10">
                <div class="flex justify-between items-end mb-4">
                    <h2 class="text-lg font-bold">1. Pilih Barber / Kapster</h2>
                    <span class="text-xs text-gray-500 font-medium" id="text-barber-terpilih">Terpilih: -</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach($barbers as $index =>$b): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="barber_id" value="<?= $b['id'] ?>" data-name="<?= htmlspecialchars($b['name']) ?>" class="peer hidden" <?= $index === 0 ? 'checked' : '' ?> onchange="fetchSlots()" required>
                            
                            <div class="bg-white border border-gray-200 rounded-lg p-6 flex flex-col items-center hover:border-gray-300 transition peer-checked:border-black peer-checked:ring-1 peer-checked:ring-black shadow-sm">
                                <div class="w-20 h-20 bg-gray-100 rounded-full mb-4 flex items-center justify-center">
                                    <i data-lucide="user" class="text-gray-400 w-10 h-10"></i>
                                </div>
                                <h3 class="font-semibold text-center mb-1"><?= htmlspecialchars($b['name']) ?></h3>
                                <p class="text-xs text-gray-500 text-center mb-4"><?= htmlspecialchars($b['specialization'] ?? 'Kapster') ?></p>
                                
                                <div class="w-full text-center py-2 text-sm font-medium rounded bg-gray-100 text-gray-600 peer-checked:bg-black peer-checked:text-white transition-colors barber-btn">
                                    Pilih Barber
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-10 bg-white p-8 border border-gray-200 rounded-xl shadow-sm">
                <h2 class="text-lg font-bold mb-6">2. Pilih Tanggal & Waktu</h2>
                
                <div class="mb-8 max-w-sm">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Tanggal</label>
                    <input type="date" name="tanggal" id="input-tanggal" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:ring-black focus:border-black" onchange="fetchSlots()" required>
                </div>

                <div>
                    <div class="flex justify-between items-end mb-4">
                        <label class="block text-sm font-medium text-gray-700">Pilihan Jam Tersedia</label>
                        <span class="text-xs text-gray-500 font-medium" id="text-jam-terpilih">Jam: - WIB</span>
                    </div>
                    
                    <div id="slot-container" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    </div>
                </div>
            </div>

            <div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 p-4 px-8 z-50 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                <div class="max-w-5xl mx-auto flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-500 font-semibold tracking-wider mb-1">RINGKASAN SEMENTARA</p>
                        <p class="text-sm font-bold text-black" id="ringkasan-teks">Pilih Barber dan Waktu</p>
                    </div>
                    <button type="submit" id="btn-lanjut" class="bg-black text-white px-8 py-3 rounded-md text-sm font-medium hover:bg-gray-800 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Lanjut ke Konfirmasi
                    </button>
                </div>
            </div>
        </form>
    </main>

    <script>
        lucide.createIcons();

        function formatTanggal(dateString) {
            if (!dateString) return '-';
            const options = { day: 'numeric', month: 'long', year: 'numeric' };
            return new Date(dateString).toLocaleDateString('id-ID', options);
        }

        async function fetchSlots() {
            const barberEl = document.querySelector('input[name="barber_id"]:checked');
            const barberId = barberEl ? barberEl.value : null;
            const tanggal = document.getElementById('input-tanggal').value;
            const container = document.getElementById('slot-container');
            
            const barberName = barberEl ? barberEl.getAttribute('data-name') : '-';
            document.getElementById('text-barber-terpilih').innerText = 'Terpilih: ' + barberName;
            document.querySelectorAll('.barber-btn').forEach(btn => btn.innerText = 'Pilih Barber');
            if (barberEl) barberEl.closest('label').querySelector('.barber-btn').innerText = 'Terpilih';

            if (!barberId || !tanggal) return;

            container.innerHTML = '<p class="col-span-full text-sm text-gray-500 flex items-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Memuat jadwal...</p>';
            lucide.createIcons();
            document.getElementById('text-jam-terpilih').innerText = 'Jam: - WIB';
            updateRingkasan(); 

            try {
                const response = await fetch(`get_slots.php?barber_id=${barberId}&tanggal=${tanggal}`);
                const result = await response.json();

                container.innerHTML = ''; 

                if (result.status && result.data.length > 0) {
                    result.data.forEach(slot => {
                        if (slot.is_full || slot.is_past) {
                            container.innerHTML += `
                                <label class="cursor-not-allowed opacity-60">
                                    <div class="bg-gray-100 border border-gray-200 text-center py-3 rounded-md font-medium text-sm text-gray-400">
                                        ${slot.waktu} (${slot.is_past ? 'Sudah lewat' : 'Penuh'})
                                    </div>
                                </label>
                            `;
                        } else {
                            container.innerHTML += `
                                <label class="cursor-pointer">
                                    <input type="radio" name="waktu" value="${slot.waktu}" class="peer hidden" onchange="updateRingkasan()" required>
                                    <div class="bg-white border border-gray-200 text-center py-3 rounded-md hover:border-gray-300 peer-checked:bg-black peer-checked:text-white peer-checked:border-black transition-colors font-medium text-sm">
                                        ${slot.waktu}
                                    </div>
                                </label>
                            `;
                        }
                    });
                } else {
                    container.innerHTML = `<p class="col-span-full text-sm text-red-500 font-medium">${result.message || 'Tidak ada jadwal tersedia.'}</p>`;
                }
            } catch (error) {
                container.innerHTML = '<p class="col-span-full text-sm text-red-500">Gagal mengambil data jadwal dari server.</p>';
            }
        }

        function updateRingkasan() {
            const selectedBarber = document.querySelector('input[name="barber_id"]:checked');
            const barberName = selectedBarber ? selectedBarber.getAttribute('data-name') : '-';
            const tanggalValue = document.getElementById('input-tanggal').value;
            const tanggalFormat = formatTanggal(tanggalValue);
            const selectedJam = document.querySelector('input[name="waktu"]:checked');
            const jamValue = selectedJam ? selectedJam.value : '-';
            const btnSubmit = document.getElementById('btn-lanjut');
            
            document.getElementById('text-jam-terpilih').innerText = 'Jam: ' + (jamValue !== '-' ? jamValue + ' WIB' : '-');

            if(barberName !== '-' && tanggalValue !== '' && jamValue !== '-') {
                document.getElementById('ringkasan-teks').innerText = `${barberName}  |  ${tanggalFormat}  |  ${jamValue} WIB`;
                btnSubmit.disabled = false;
            } else {
                document.getElementById('ringkasan-teks').innerText = "Mohon lengkapi pilihan Anda";
                btnSubmit.disabled = true;
            }
        }
        
        window.onload = () => fetchSlots();
    </script>
</body>
</html>