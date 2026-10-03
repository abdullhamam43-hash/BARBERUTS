<?php
require_once '../dbconnection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if ($user_id === null) {
    header("Location: logout.php");
    exit();
}

try {
    $db = new DBconnection();
} catch (Exception $e) {
    die("Koneksi database gagal: " . htmlspecialchars($e->getMessage()));
}

if (!empty($_GET['batal'])) {
    $cancel = $db->send_query(
        "UPDATE reservations SET status = 'cancelled'
         WHERE id = $1 AND user_id = $2 AND status = 'pending'",
        [$_GET['batal'], $user_id]
    );
    $hasil = $cancel->status ? 'success' : 'failed';
    header("Location: user_dashboard.php?cancel=$hasil#riwayat");
    exit();
}

$reservationResult = $db->send_query(
    "SELECT r.id, r.reservation_date, r.time_slot, r.status, r.notes, b.name AS barber_name
     FROM reservations r
     JOIN barbers b ON r.barber_id = b.id
     WHERE r.user_id = $1
     ORDER BY r.reservation_date DESC, r.time_slot DESC",
    [$user_id]
);
$riwayat = ($reservationResult->status && !empty($reservationResult->data)) ? $reservationResult->data : [];

$flash = $_SESSION['flash_reservasi'] ?? null;
unset($_SESSION['flash_reservasi']);

function statusInfo(string $status): array
{
    switch ($status) {
        case 'confirmed': return ['Selesai',     'bg-green-100 text-green-800'];
        case 'cancelled': return ['Dibatalkan',  'bg-red-100 text-red-700'];
        default:          return ['Menunggu',    'bg-yellow-100 text-yellow-800'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbershop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        pageBg: '#f8f9fa',
                        textPrimary: '#0f172a',
                        textSecondary: '#475569',
                        textMuted: '#64748b',
                    }
                }
            }
        }
    </script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body class="min-h-screen bg-pageBg text-textPrimary antialiased flex flex-col">

    <header class="w-full bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="text-3xl leading-none">💈</span>
                <span class="font-extrabold tracking-widest text-lg text-textPrimary uppercase">BARBERSHOP</span>
            </div>

            <nav class="bg-gray-100 p-1 rounded-md flex items-center space-x-1 border border-gray-200">
                <a href="#about"     data-tab="about"     class="bg-white text-textPrimary font-semibold px-4 py-1.5 rounded text-sm shadow-sm transition-all">About</a>
                <a href="#reservasi" data-tab="reservasi" class="text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all">Reservasi</a>
                <a href="#riwayat"   data-tab="riwayat"   class="text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all">Riwayat</a>
                <a href="logout.php" class="text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all">Logout</a>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10 flex-grow w-full">

        <section id="tab-about">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-textPrimary tracking-tight">Tentang Barbershop</h1>
                <p class="text-sm text-textMuted mt-1">Dokumentasi profil usaha dan sistem informasi manajemen reservasi barbershop.</p>
            </div>

            <hr class="border-gray-200 mb-8" />

            <div class="space-y-4 mb-10">
                <h2 class="text-base font-bold text-textPrimary uppercase tracking-wide">PROFIL BARBERSHOP</h2>
                <p class="text-sm text-gray-700 leading-relaxed">
                    Barbershop kami merupakan usaha jasa pangkas rambut pria independen yang berdedikasi menghadirkan layanan potong rambut berkualitas, presisi, dan terjangkau. Berdiri di tengah kawasan aktivitas mahasiswa dan warga sekitar, kami mengedepankan standar kerapian tinggi serta kebersihan peralatan demi kenyamanan setiap pelanggan yang datang.
                </p>
                <p class="text-sm text-gray-700 leading-relaxed">
                    Didukung oleh tenaga kapster berpengalaman, kami menyediakan berbagai opsi potongan rambut klasik maupun modern, pencucian rambut, hingga perawatan jenggot dan kumis. Kami percaya bahwa penampilan yang rapi dapat meningkatkan kepercayaan diri dalam menjalani aktivitas harian.
                </p>
            </div>

            <div class="bg-[#b3b3b3] border border-gray-400 rounded-lg p-6 text-textPrimary shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider mb-6 text-gray-800">INFORMASI OPERASIONAL & LOKASI</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-start space-x-3">
                        <div class="bg-textPrimary text-white p-2.5 rounded-full flex-shrink-0 mt-0.5"><i data-lucide="map-pin" class="w-5 h-5"></i></div>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">ALAMAT</h4>
                            <p class="text-xs font-bold text-textPrimary mt-1">Jl. Dharmahusada No. 127</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="border-2 border-textPrimary text-textPrimary p-2 rounded-full flex-shrink-0 mt-0.5"><i data-lucide="clock" class="w-5 h-5"></i></div>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">JAM OPERASIONAL</h4>
                            <p class="text-xs font-bold text-textPrimary mt-1">Setiap Hari 09.00 - 21.00 WIB</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="border-2 border-textPrimary text-textPrimary p-2 rounded-full flex-shrink-0 mt-0.5"><i data-lucide="phone" class="w-5 h-5"></i></div>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">KONTAK</h4>
                            <p class="text-xs font-bold text-textPrimary mt-1">0812-3456-7890</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="tab-reservasi" class="hidden">
            <?php if ($flash && !$flash['ok']): ?>
                <div class="mb-4 px-4 py-3 rounded-md bg-red-50 border border-red-200 text-sm text-red-700"><?= htmlspecialchars($flash['pesan']) ?></div>
            <?php endif; ?>
            <iframe id="frame-reservasi" title="Reservasi" class="w-full block border-0" style="height: 600px;"></iframe>
        </section>

        <section id="tab-riwayat" class="hidden">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-textPrimary tracking-tight">Riwayat Reservasi Saya</h1>
                <p class="text-sm text-textMuted mt-1">Daftar riwayat pemesanan jadwal cukur Anda.</p>
            </div>

            <hr class="border-gray-200 mb-8" />

            <?php if ($flash && $flash['ok']): ?>
                <div class="mb-4 px-4 py-3 rounded-md bg-green-50 border border-green-200 text-sm text-green-800"><?= htmlspecialchars($flash['pesan']) ?></div>
            <?php endif; ?>

            <?php if (($_GET['cancel'] ?? '') === 'success'): ?>
                <div class="mb-4 px-4 py-3 rounded-md bg-green-50 border border-green-200 text-sm text-green-800">Reservasi berhasil dibatalkan.</div>
            <?php elseif (($_GET['cancel'] ?? '') === 'failed'): ?>
                <div class="mb-4 px-4 py-3 rounded-md bg-red-50 border border-red-200 text-sm text-red-700">Reservasi tidak dapat dibatalkan.</div>
            <?php endif; ?>

            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-textSecondary">
                        <tr>
                            <th class="px-5 py-3 font-semibold">No</th>
                            <th class="px-5 py-3 font-semibold">Barber</th>
                            <th class="px-5 py-3 font-semibold">Tanggal</th>
                            <th class="px-5 py-3 font-semibold">Jam</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($riwayat)): ?>
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-textMuted">Belum ada riwayat reservasi.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat as $i => $row):
                                $status = strtolower($row['status']);
                                [$label, $badge] = statusInfo($status);
                                $tanggal = !empty($row['reservation_date']) ? date('d F Y', strtotime($row['reservation_date'])) : '-';
                                $jam = !empty($row['time_slot']) ? $row['time_slot'] . ' WIB' : '-';
                                $detail = "Reservasi dengan {$row['barber_name']}\n$tanggal, $jam";
                            ?>
                                <tr>
                                    <td class="px-5 py-4"><?= $i + 1 ?></td>
                                    <td class="px-5 py-4 font-medium"><?= htmlspecialchars($row['barber_name']) ?></td>
                                    <td class="px-5 py-4"><?= htmlspecialchars($tanggal) ?></td>
                                    <td class="px-5 py-4"><?= htmlspecialchars($jam) ?></td>
                                    <td class="px-5 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $badge ?>"><?= $label ?></span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <?php if ($status === 'pending'): ?>
                                            <a href="user_dashboard.php?batal=<?= urlencode($row['id']) ?>"
                                               onclick="return confirm('Yakin ingin membatalkan reservasi ini?');"
                                               class="font-semibold text-red-600 hover:underline">Batal</a>
                                        <?php elseif ($status === 'confirmed'): ?>
                                            <a href="#riwayat"
                                               onclick='alert(<?= htmlspecialchars(json_encode($detail), ENT_QUOTES) ?>); return false;'
                                               class="font-semibold text-textPrimary hover:underline">Detail</a>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    
    <div id="konfirmasiModal" class="hidden fixed inset-0 z-50 bg-black/60 items-center justify-center p-4" onclick="if (event.target === this) closeKonfirmasi()">
        <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-[440px] p-8 max-h-[95vh] overflow-y-auto">
            <button type="button" onclick="closeKonfirmasi()" class="absolute top-4 right-4 text-xl font-bold text-gray-400 hover:text-red-600 leading-none">×</button>

            <h2 class="text-2xl font-bold mb-1">Konfirmasi Reservasi</h2>
            <p class="text-xs text-textMuted mb-6">Harap periksa kembali detail pesanan jadwal Anda sebelum mengonfirmasi.</p>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                    <span class="text-gray-500">Nama Pelanggan</span>
                    <span class="font-semibold"><?= htmlspecialchars($_SESSION['username'] ?? '-') ?></span>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                    <span class="text-gray-500">Barber / Kapster</span>
                    <div class="text-right">
                        <span id="kf-barber" class="font-semibold block"></span>
                        <span id="kf-spesialisasi" class="text-xs text-gray-400"></span>
                    </div>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                    <span class="text-gray-500">Tanggal Kunjungan</span>
                    <span id="kf-tanggal" class="font-semibold text-right"></span>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                    <span class="text-gray-500">Jam / Slot Waktu</span>
                    <span id="kf-jam" class="font-semibold"></span>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                    <span class="text-gray-500">Status Pembayaran</span>
                    <span class="font-semibold">Bayar di Kasir (Offline)</span>
                </div>
                <div class="flex justify-between bg-gray-50 p-3 rounded-md">
                    <span class="font-semibold">Biaya</span>
                    <span class="font-bold">Rp 45.000</span>
                </div>
            </div>

            <p class="text-xs text-gray-500 leading-relaxed mt-4">Harap hadir 10 menit sebelum jadwal Anda dimulai. Keterlambatan lebih dari 15 menit dapat membatalkan nomor antrean.</p>

            <form method="POST" action="konfirmasi_reservasi.php" class="mt-6">
                <input type="hidden" name="konfirmasi" value="1">
                <input type="hidden" name="barber_id" id="kf-barber-id">
                <input type="hidden" name="tanggal" id="kf-tanggal-val">
                <input type="hidden" name="waktu" id="kf-waktu-val">
                <button type="submit" class="w-full bg-black text-white py-3 rounded-md text-sm font-medium hover:bg-gray-700 transition">Konfirmasi Reservasi</button>
            </form>
        </div>
    </div>

    <footer class="border-t border-gray-200 py-4 text-center text-xs font-semibold tracking-widest text-textMuted uppercase">
        BARBERSHOP
    </footer>

    <script>
        lucide.createIcons();

        const TABS = ['about', 'reservasi', 'riwayat'];
        const ACTIVE = 'bg-white text-textPrimary font-semibold px-4 py-1.5 rounded text-sm shadow-sm transition-all';
        const IDLE   = 'text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all';

        function showTab(name) {
            if (!TABS.includes(name)) name = 'about';
            TABS.forEach(t => {
                document.getElementById('tab-' + t).classList.toggle('hidden', t !== name);
                document.querySelector('[data-tab="' + t + '"]').className = (t === name) ? ACTIVE : IDLE;
            });
            if (name === 'reservasi') {
                if (!frameReservasi.getAttribute('src')) frameReservasi.setAttribute('src', 'reservasi.php');
                else fitReservasi();
            }
        }

const frameReservasi = document.getElementById('frame-reservasi');

function fitReservasi() {
    try {
        const doc = frameReservasi.contentDocument;
        if (doc && doc.documentElement) frameReservasi.style.height = doc.documentElement.scrollHeight + 'px';
    } catch (e) {}
}

frameReservasi.addEventListener('load', function () {
    const doc = frameReservasi.contentDocument;
    doc.head.insertAdjacentHTML('beforeend', '<base target="_top">');
    doc.head.insertAdjacentHTML('beforeend', '<style>body{margin:0}main{max-width:none!important;margin:0!important;padding:0 0 1rem!important}</style>');
    const nav = doc.querySelector('nav');
    if (nav) nav.style.display = 'none';
    const form = doc.querySelector('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const barber  = form.querySelector('input[name="barber_id"]:checked');
            const jam     = form.querySelector('input[name="waktu"]:checked');
            const tanggal = form.querySelector('input[name="tanggal"]').value;
            if (!barber || !jam || !tanggal) return;
            const spec = barber.closest('label').querySelector('p');
            openKonfirmasi({
                id: barber.value,
                nama: barber.dataset.name || '-',
                spesialisasi: spec ? spec.textContent.trim() : '-',
                tanggal: tanggal,
                jam: jam.value
            });
        });
    }
    new ResizeObserver(fitReservasi).observe(doc.documentElement);
    fitReservasi();
});

function openKonfirmasi(d) {
    document.getElementById('kf-barber').textContent = d.nama;
    document.getElementById('kf-spesialisasi').textContent = d.spesialisasi;
    document.getElementById('kf-tanggal').textContent =
        new Date(d.tanggal + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('kf-jam').textContent = d.jam + ' WIB';
    document.getElementById('kf-barber-id').value = d.id;
    document.getElementById('kf-tanggal-val').value = d.tanggal;
    document.getElementById('kf-waktu-val').value = d.jam;

    const m = document.getElementById('konfirmasiModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
}

function closeKonfirmasi() {
    const m = document.getElementById('konfirmasiModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeKonfirmasi(); });

window.addEventListener('hashchange', () => showTab(location.hash.slice(1)));
        showTab(location.hash.slice(1));
    </script>
</body>
</html>