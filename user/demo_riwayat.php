<?php

require_once "../dbconnection.php";
class Reservation
{
    private DBconnection $db;

    public function __construct(DBconnection $dbconnection)
    {
        $this->db = $dbconnection;
    }

    public function getBookedSlots(
        int|string $barber_id,
        string $date
    ): array {

        $query = "
            SELECT time_slot
            FROM reservations
            WHERE barber_id = $1
            AND reservation_date = $2
            AND status != 'cancelled'
        ";

        $result = $this->db->send_query(
            $query,
            [$barber_id, $date]
        );

        $booked_times = [];

        if ($result->status && !empty($result->data)) {
            foreach ($result->data as $row) {
                $booked_times[] = $row['time_slot'];
            }
        }

        return $booked_times;
    }

    public function add(
        int|string $user_id,
        int|string $barber_id,
        string $reservation_date,
        string $time_slot,
        ?string $notes = null
    ): Response {

        if (
            empty($user_id) ||
            empty($barber_id) ||
            empty($reservation_date) ||
            empty($time_slot)
        ) {
            return new Response(
                false,
                "Semua field wajib diisi."
            );
        }

        $checkQuery = "
            SELECT id
            FROM reservations
            WHERE barber_id = $1
            AND reservation_date = $2
            AND time_slot = $3
            AND status != 'cancelled'
        ";

        $checkResult = $this->db->send_query(
            $checkQuery,
            [
                $barber_id,
                $reservation_date,
                $time_slot
            ]
        );

        if (!empty($checkResult->data)) {
            return new Response(
                false,
                "Slot waktu sudah dipesan."
            );
        }

        $query = "
            INSERT INTO reservations
            (
                user_id,
                barber_id,
                reservation_date,
                time_slot,
                status,
                notes
            )
            VALUES
            (
                $1,
                $2,
                $3,
                $4,
                'pending',
                $5
            )
        ";

        $result = $this->db->send_query(
            $query,
            [
                $user_id,
                $barber_id,
                $reservation_date,
                $time_slot,
                $notes
            ]
        );

        if ($result->status) {
            return new Response(
                true,
                "Reservasi berhasil dibuat!"
            );
        }

        return new Response(
            false,
            $result->message
        );
    }

    public function getUserReservations(
        int|string $user_id
    ): Response {

        $query = "
            SELECT
                r.*,
                b.name AS barber_name
            FROM reservations r
            JOIN barbers b
                ON r.barber_id = b.id
            WHERE r.user_id = $1
            ORDER BY
                r.reservation_date DESC,
                r.time_slot DESC
        ";

        return $this->db->send_query(
            $query,
            [$user_id]
        );
    }

    public function getAll(): Response
    {
        $query = "
            SELECT
                r.*,
                b.name AS barber_name,
                u.username
            FROM reservations r
            JOIN barbers b
                ON r.barber_id = b.id
            JOIN users u
                ON r.user_id = u.id
            ORDER BY
                r.reservation_date DESC,
                r.time_slot DESC
        ";

        return $this->db->send_query(
            $query,
            []
        );
    }

    public function updateStatus(
        int|string $id,
        string $status
    ): Response {

        if (
            !in_array(
                $status,
                [
                    'pending',
                    'confirmed',
                    'cancelled'
                ],
                true
            )
        ) {
            return new Response(
                false,
                "Status tidak valid."
            );
        }

        $query = "
            UPDATE reservations
            SET status = $1
            WHERE id = $2
        ";

        $result = $this->db->send_query(
            $query,
            [
                $status,
                $id
            ]
        );

        if ($result->status) {
            return new Response(
                true,
                "Status reservasi berhasil diubah."
            );
        }

        return new Response(
            false,
            $result->message
        );
    }

    public function confirm(
        int|string $id
    ): Response {

        return $this->updateStatus(
            $id,
            'confirmed'
        );
    }

    public function cancel(
        int|string $id
    ): Response {

        return $this->updateStatus(
            $id,
            'cancelled'
        );
    }
}


/*
|--------------------------------------------------------------------------
| SESSION & CEK LOGIN
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| DATABASE INITIALIZATION
|--------------------------------------------------------------------------
*/

try {
    $dbconnection = new DBconnection();
    $reservation = new Reservation($dbconnection);
} catch (Exception $e) {
    die("Gagal terhubung ke database: " . htmlspecialchars($e->getMessage()));
}


/*
|--------------------------------------------------------------------------
| PROSES BATAL RESERVASI
|--------------------------------------------------------------------------
*/

if (isset($_GET['batal']) && !empty($_GET['batal'])) {
    $reservation_id = $_GET['batal'];

    // Pastikan reservasi milik user yang sedang login
    $checkQuery = "
        SELECT id
        FROM reservations
        WHERE id = $1
        AND user_id = $2
        AND status = 'pending'
    ";

    $checkResult = $dbconnection->send_query(
        $checkQuery,
        [$reservation_id, $user_id]
    );

    if ($checkResult->status && !empty($checkResult->data)) {
        $cancelResult = $reservation->cancel($reservation_id);
        if ($cancelResult->status) {
            header("Location: riwayat_reservasi.php?cancel=success");
            exit;
        }
    }

    header("Location: riwayat_reservasi.php?cancel=failed");
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA RESERVASI USER
|--------------------------------------------------------------------------
*/

$reservationResult = $reservation->getUserReservations($user_id);


/*
|--------------------------------------------------------------------------
| FUNGSI STATUS
|--------------------------------------------------------------------------
*/

function getStatusText(string $status): string
{
    if ($status === 'pending') return 'Menunggu';
    if ($status === 'confirmed') return 'Selesai';
    if ($status === 'cancelled') return 'Dibatalkan';
    return ucfirst($status);
}

function getStatusClass(string $status): string
{
    if ($status === 'pending') return 'status-menunggu';
    if ($status === 'confirmed') return 'status-selesai';
    if ($status === 'cancelled') return 'status-batal';
    return 'status-menunggu';
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Reservasi - Barbershop</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
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
        /* Menerjemahkan class status lama ke tampilan Tailwind */
        .status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .status-menunggu { background-color: #fef08a; color: #854d0e; }
        .status-selesai { background-color: #bbf7d0; color: #166534; } 
        .status-batal { background-color: #fecaca; color: #991b1b; }   
    </style>
</head>
<body class="min-h-screen bg-pageBg text-textPrimary antialiased flex flex-col">

    <!-- Header Navigation -->
    <header class="w-full bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <!-- Logo & Brand Name -->
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

            <!-- Navigation Links -->
            <nav class="bg-gray-100 p-1 rounded-md flex items-center space-x-1 border border-gray-200">
                <a href="about.php" class="text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all">About</a>
                <a href="reservasi.php" class="text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all">Reservasi</a>
                <!-- Tab Riwayat Aktif -->
                <a href="riwayat_reservasi.php" class="bg-white text-textPrimary font-semibold px-4 py-1.5 rounded text-sm shadow-sm transition-all">Riwayat</a>
                <a href="../logout.php" class="text-textSecondary hover:text-textPrimary font-medium px-4 py-1.5 rounded text-sm transition-all">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-5xl mx-auto px-6 py-10 flex-grow w-full">
        
        <!-- Alerts (Notifikasi Batal) -->
        <?php if (isset($_GET['cancel']) && $_GET['cancel'] === 'success') { ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-semibold flex items-center shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> Reservasi berhasil dibatalkan.
            </div>
        <?php } ?>

        <?php if (isset($_GET['cancel']) && $_GET['cancel'] === 'failed') { ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm font-semibold flex items-center shadow-sm">
                <i data-lucide="x-circle" class="w-5 h-5 mr-2"></i> Reservasi tidak dapat dibatalkan.
            </div>
        <?php } ?>

        <!-- Page Title & Subtitle -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-textPrimary tracking-tight">Riwayat Reservasi Saya</h1>
            <p class="text-sm text-textMuted mt-1">Daftar riwayat pemesanan jadwal cukur Anda.</p>
        </div>

        <!-- Table Section -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-6 py-4">NO</th>
                            <th class="px-6 py-4">BARBER</th>
                            <th class="px-6 py-4">TANGGAL</th>
                            <th class="px-6 py-4">JAM</th>
                            <th class="px-6 py-4">STATUS</th>
                            <th class="px-6 py-4">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php
                        // Mulai Looping Data
                        if ($reservationResult->status && !empty($reservationResult->data)) {
                            $no = 1;
                            foreach ($reservationResult->data as $row) {
                                $status = strtolower($row['status']);
                        ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        <?= $no ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 text-sm text-gray-800 font-medium">
                                        <?= htmlspecialchars($row['barber_name']) ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php
                                        if (!empty($row['reservation_date'])) {
                                            echo date('d F Y', strtotime($row['reservation_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php
                                        if (!empty($row['time_slot'])) {
                                            echo htmlspecialchars($row['time_slot']) . " WIB";
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    
                                    <td class="px-6 py-4">
                                        <span class="status <?= getStatusClass($status) ?>">
                                            <?= htmlspecialchars(getStatusText($status)) ?>
                                        </span>
                                    </td>
                                    
                                    <td class="px-6 py-4 text-sm">
                                        <?php if ($status === 'pending') { ?>
                                            <a href="riwayat_reservasi.php?batal=<?= urlencode($row['id']) ?>" 
                                               class="text-red-600 font-semibold hover:text-red-800 hover:underline transition-colors"
                                               onclick="return confirm('Yakin ingin membatalkan reservasi ini?');">
                                                Batal
                                            </a>
                                        <?php } elseif ($status === 'confirmed') { ?>
                                            <a href="reservasi.php?detail=<?= urlencode($row['id']) ?>" 
                                               class="text-blue-600 font-semibold hover:text-blue-800 hover:underline transition-colors">
                                                Detail
                                            </a>
                                        <?php } else { ?>
                                            <span class="text-gray-400">-</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                        <?php
                                $no++;
                            }
                        } else {
                        ?>
                            <!-- Tampilan Jika Data Kosong -->
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i data-lucide="inbox" class="w-10 h-10 text-gray-300 mb-3"></i>
                                        Belum ada riwayat reservasi.
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Init Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>