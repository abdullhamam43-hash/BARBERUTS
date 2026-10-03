<?php
require_once '../dbconnection.php';
require_once 'barber.php';
require_once 'BarberSchedule.php';
require_once 'reservation_admin.php';
session_name("admin_session");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

$db = new DBconnection();
$barber = new Barber($db);
$schedule = new BarberSchedule($db);
$reservation = new Reservation($db);

$barbersResult = $barber->getAll();
$barbers = $barbersResult->data ?? [];

$schedulesResult = $schedule->getAll();
$schedules = $schedulesResult->data ?? [];

$reservationsResult = $reservation->getAll();
$reservations = $reservationsResult->data ?? [];

// add barber
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_barber'])) {
    $name = $_POST["name"];
    $specialization = $_POST["specialization"] ?? null;
    $phone = $_POST["phone"];
    $email = $_POST["email"] ?? null;

    $addResult = $barber->add($name, $specialization, $phone, $email);
    if ($addResult->status) {
        $_SESSION['pesan_sukses'] = "Berhasil: " . $addResult->message;
    } else {
        $_SESSION['pesan_error'] = "Gagal: " . $addResult->message;
    }

    header("Location: admin_dashboard.php#pencukur");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule'])) {
    $barber_id = $_POST['barber_id'];
    $schedule_date = $_POST['schedule_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $slot_quota = $_POST['slot_quota'];
    $query = "INSERT INTO barber_schedules (barber_id, schedule_date, start_time, end_time, slot_quota, is_active) 
              VALUES ($1, $2, $3, $4, $5, true)";
    $db->send_query($query, [$barber_id, $schedule_date, $start_time, $end_time, $slot_quota]);

    header("Location: admin_dashboard.php#jadwal");
    exit();
}

$db->close_connection();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .navbar {
            background: black;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        .navbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .navbar h2 { 
            margin: 0; 
            color: white;
            white-space: nowrap;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
        }
        .tab-btn {
            background: white;
            font-weight: bold;
            color: black;
            border: none;
            padding: 10px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
        }
        .tab-btn.active { background: gray; }
        .tab-btn:hover { background: gray; }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
            white-space: nowrap;
        }
        .logout-btn {
            background: red;
            font-weight: bold;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .logout-btn:hover { background: darkred; }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h2 { margin-bottom: 20px; color: #333; }
        
        .btn-primary {
            background: black;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 15px;
        }
        .btn-primary:hover { background: grey; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th { background: #333; color: white; }
        tr:hover { background: #f9f9f9; }
        
        .btn-sm {
            padding: 5px 10px;
            margin: 2px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            border-radius: 3px;
            cursor: pointer;
            border: none;
        }
        .btn-edit { background: black; color: white; }
        .btn-edit:hover { background: black; }
        .btn-delete { background: red; color: white; }
        .btn-delete:hover { background: darkred; }
        .btn-confirm { background: green; color: white; }
        .btn-confirm:hover { background: black; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .status-pending { background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 4px; }
        .status-confirmed { background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 4px; }
        .status-cancelled { background: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 4px; }
       
        .modal-overlay {
            display: none; 
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 15px; right: 15px;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
            color: #999;
            border: none;
            background: transparent;
        }
        .close-btn:hover { color: red; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; color: #333;}
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px;}
        .detail-label { color: #666; font-weight: bold; }
        .detail-value { color: #000; font-weight: bold; }
        .detail-notes { background: #f9f9f9; padding: 10px; border-radius: 4px; border: 1px solid #ddd; margin-top: 10px; font-size: 13px; color: #555; min-height: 50px; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <h2>Admin Dashboard</h2>
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('pencukur')">Kelola Pencukur</button>
                <button class="tab-btn" onclick="switchTab('jadwal')">Kelola Jadwal</button>
                <button class="tab-btn" onclick="switchTab('reservasi')">Pantau Reservasi</button>
            </div>
        </div>
        <div class="navbar-right">
            <span style="font-weight: bold;"><?php echo $_SESSION['username']; ?>  </span>
            <a href="logoutadmin.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div id="pencukur" class="tab-content active">
        <div class="container">
            <h2>Kelola Pencukur</h2>
            <button onclick="openBarberModal()" class="btn-primary">+ Tambah Pencukur Baru</button>
            
            <?php if (!empty($barbers)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAMA BARBER</th>
                            <th>SPESIALISASI</th>
                            <th>NO. TELEPON</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($barbers as$b): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $b['name']; ?></td>
                                <td><?php echo $b['specialization'] ?? '-'; ?></td>
                                <td><?php echo $b['phone']; ?></td>
                                <td>
                                    <a href="barbers_delete.php?id=<?php echo $b['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Yakin hapus?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Belum ada barber.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="jadwal" class="tab-content">
        <div class="container">
            <h2>Kelola Jadwal Pencukur</h2>

            <button onclick="openTambahJadwalModal()" class="btn-primary">+ Tambah Jadwal Baru</button>
            
            <?php if (!empty($schedules)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>PENCUKUR</th>
                            <th>TANGGAL</th>
                            <th>JAM MULAI</th>
                            <th>JAM SELESAI</th>
                            <th>KUOTA SLOT</th>
                            <th>STATUS</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($schedules as$s): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $s['name']; ?></td>
                                <td><?php echo $s['schedule_date']; ?></td>
                                <td><?php echo $s['start_time']; ?></td>
                                <td><?php echo $s['end_time']; ?></td>
                                <td><?php echo $s['slot_quota']; ?></td>
                                <td><?php echo $s['is_active'] ? 'Aktif' : 'Tidak'; ?></td>
                                <td>
                                    <a href="#" class="btn-sm btn-edit"
                                        onclick="openJadwalModal(<?php echo htmlspecialchars(json_encode($s), ENT_QUOTES); ?>); return false;">Edit</a>
                                    <a href="schedules_delete.php?id=<?php echo $s['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Yakin hapus?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Belum ada jadwal yang dikelola.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="reservasi" class="tab-content">
        <div class="container">
            <h2>Log Reservasi Pelanggan (Admin)</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>ID BOOKING</th>
                        <th>NAMA USER</th>
                        <th>BARBER</th>
                        <th>TANGGAL</th>
                        <th>JAM</th>
                        <th>STATUS RESERVASI</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 20px; color: gray;">Belum ada reservasi.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($reservations as$res): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>#RSV-<?php echo str_pad($res['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $res['username']; ?></td>
                                <td><?php echo $res['barber_name']; ?></td>
                                <td><?php echo $res['reservation_date']; ?></td>
                                <td><?php echo substr($res['time_slot'], 0, 5); ?></td>
                                <td>
                                    <span class="status-<?php echo $res['status']; ?>">
                                        <?php 
                                        if ($res['status'] === 'pending') echo 'Menunggu';
                                        elseif ($res['status'] === 'confirmed') echo 'Dikonfirmasi';
                                        elseif ($res['status'] === 'cancelled') echo 'Dibatalkan';
                                        ?>
                                    </span>
                                </td>
                                <td style="display: flex; gap: 5px;">
                                    
                                    <!-- Tombol Konfirmasi -->
                                    <?php if ($res['status'] === 'pending'): ?>
                                        <form method="POST" action="proses_reservasi.php" style="margin: 0; display: inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                            <input type="hidden" name="action" value="confirm">
                                            <button type="submit" class="btn-sm btn-confirm">Konfirmasi</button>
                                        </form>
                                    <?php endif; ?>

                                    <a href="#" class="btn-sm" style="background: #17a2b8; color: white;" onclick="openDetailModal(<?php echo htmlspecialchars(json_encode($res), ENT_QUOTES); ?>); return false;">Detail</a>

                                    <form method="POST" action="proses_reservasi.php" style="margin: 0; display: inline;" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?');">
                                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn-sm btn-delete" <?php echo $res['status'] === 'cancelled' ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>Batalkan</button>
                                    </form>
                                    <form method="POST" action="proses_reservasi.php" style="margin: 0; display: inline;" onsubmit="return confirm('Yakin ingin menghapus reservasi ini secara permanen?');">
                                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn-sm btn-delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 15px; text-align: right; font-size: 12px; color: #999;">
                Menampilkan <?php echo count($reservations); ?> dari <?php echo count($reservations); ?> transaksi reservasi
            </p>
        </div>
    </div>

    <div id="barberModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" onclick="closeBarberModal()">×</button>
            <h2 style="margin-bottom: 5px;">Tambah Barber Baru</h2><br>

            <form method="POST" action="">
                <input type="hidden" name="add_barber" value="1">

                <div class="form-group">
                    <label>Nama Barber</label>
                    <input type="text" name="name" required placeholder="Masukkan nama barber">
                </div>
                <div class="form-group">
                    <label>Spesialisasi</label>
                    <input type="text" name="specialization" placeholder="Contoh: Classic Cut & Fade">
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="phone" required placeholder="Contoh: 0812-1111-2222">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Contoh: barber@example.com">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px; margin-bottom: 0;">Simpan Barber</button>
            </form>
        </div>
    </div>

    <div id="tambahJadwalModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" onclick="closeTambahJadwalModal()">×</button>
            <h2 style="margin-bottom: 5px;">Tambah Jadwal Baru</h2><br>

            <form method="POST" action="">
                <input type="hidden" name="add_schedule" value="1">

                <div class="form-group">
                    <label>Pilih Barber</label>
                    <select name="barber_id" required>
                        <option value="">-- Pilih Barber --</option>
                        <?php foreach ($barbers as$b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo $b['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Jadwal</label>
                    <input type="date" name="schedule_date" required>
                </div>
                <div class="form-group">
                    <label>Jam Mulai</label>
                    <select name="start_time" required>
                        <?php for ($h = 9; $h < 21; $h++): ?>
                            <option value="<?php echo sprintf('%02d:00', $h); ?>"><?php echo sprintf('%02d:00', $h); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jam Selesai</label>
                    <select name="end_time" required>
                        <?php for ($h = 10; $h <= 21; $h++): ?>
                            <option value="<?php echo sprintf('%02d:00', $h); ?>"><?php echo sprintf('%02d:00', $h); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kuota Slot</label>
                    <input type="number" name="slot_quota" min="1" required placeholder="Contoh: 8">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px; margin-bottom: 0;">Simpan Jadwal</button>
            </form>
        </div>
    </div>

    <div id="jadwalModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" onclick="closeJadwalModal()">×</button>
            <h2 style="margin-bottom: 5px;">Edit Jadwal</h2>
            <p id="jadwal-info" style="font-size: 12px; color: #666; margin-bottom: 20px;"></p>

            <form method="POST" action="schedule_process.php">
                <input type="hidden" name="id" id="jadwal-id">

                <div class="form-group">
                    <label>Jam Mulai</label>
                    <select name="start_time" id="jadwal-start" required>
                        <?php for ($h = 9; $h < 21; $h++): ?>
                            <option value="<?php echo sprintf('%02d:00', $h); ?>"><?php echo sprintf('%02d:00', $h); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jam Selesai</label>
                    <select name="end_time" id="jadwal-end" required>
                        <?php for ($h = 10; $h <= 21; $h++): ?>
                            <option value="<?php echo sprintf('%02d:00', $h); ?>"><?php echo sprintf('%02d:00', $h); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kuota Slot</label>
                    <input type="number" name="slot_quota" id="jadwal-quota" min="1" required>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px; margin-bottom: 0;">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <div id="detailModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" onclick="closeDetailModal()">×</button>
            <h2 style="margin-bottom: 15px;">Detail Lengkap Reservasi</h2>
            
            <div>
                <div class="detail-row">
                    <span class="detail-label">ID Booking:</span>
                    <span class="detail-value" id="det-id"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nama User:</span>
                    <span class="detail-value" id="det-user"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Pencukur (Barber):</span>
                    <span class="detail-value" id="det-barber"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal Kunjungan:</span>
                    <span class="detail-value" id="det-date"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Jam / Slot Waktu:</span>
                    <span class="detail-value" id="det-time"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status Reservasi:</span>
                    <span class="detail-value" id="det-status" style="text-transform: uppercase;"></span>
                </div>
                <div style="margin-top: 15px;">
                    <span class="detail-label">Catatan Pelanggan:</span>
                    <div class="detail-notes" id="det-notes"></div>
                </div>
            </div>

            <button onclick="closeDetailModal()" class="btn-primary" style="width: 100%; margin-top: 20px; margin-bottom: 0;">Tutup Jendela</button>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

            document.getElementById(tabName).classList.add('active');
            document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`).classList.add('active');

            history.replaceState(null, '', '#' + tabName); 
        }

        const startTab = location.hash.replace('#', '');
        if (document.getElementById(startTab)) switchTab(startTab);

        // Modal Barber
        function openBarberModal() { document.getElementById('barberModal').classList.add('active'); }
        function closeBarberModal() { document.getElementById('barberModal').classList.remove('active'); }

        // Modal Tambah Jadwal Baru
        function openTambahJadwalModal() { document.getElementById('tambahJadwalModal').classList.add('active'); }
        function closeTambahJadwalModal() { document.getElementById('tambahJadwalModal').classList.remove('active'); }

        // Modal Edit Jadwal
        function openJadwalModal(s) {
            document.getElementById('jadwal-id').value = s.id;
            document.getElementById('jadwal-info').textContent = s.name + ' - ' + s.schedule_date;
            document.getElementById('jadwal-start').value = s.start_time.slice(0, 5);
            document.getElementById('jadwal-end').value = s.end_time.slice(0, 5);
            document.getElementById('jadwal-quota').value = s.slot_quota;
            document.getElementById('jadwalModal').classList.add('active');
        }
        function closeJadwalModal() { document.getElementById('jadwalModal').classList.remove('active'); }

        // Modal Detail Reservasi
        function openDetailModal(res) {
            document.getElementById('det-id').textContent = '#RSV-' + String(res.id).padStart(3, '0');
            document.getElementById('det-user').textContent = res.username;
            document.getElementById('det-barber').textContent = res.barber_name;
            document.getElementById('det-date').textContent = res.reservation_date;
            document.getElementById('det-time').textContent = res.time_slot.substring(0, 5) + ' WIB';
            
            let statusText = '';
            let statusColor = '';
            if (res.status === 'pending') { statusText = 'Menunggu'; statusColor = '#856404'; }
            else if (res.status === 'confirmed') { statusText = 'Dikonfirmasi'; statusColor = '#155724'; }
            else if (res.status === 'cancelled') { statusText = 'Dibatalkan'; statusColor = '#721c24'; }
            
            const detStatus = document.getElementById('det-status');
            detStatus.textContent = statusText;
            detStatus.style.color = statusColor;

            document.getElementById('det-notes').textContent = (res.notes && res.notes.trim() !== '') ? res.notes : 'Tidak ada catatan tambahan.';
            document.getElementById('detailModal').classList.add('active');
        }
        function closeDetailModal() { document.getElementById('detailModal').classList.remove('active'); }
    </script>
</body>
</html>