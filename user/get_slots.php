<?php
error_reporting(0);
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once '../dbconnection.php';
header('Content-Type: application/json');
if (!isset($_GET['barber_id']) || !isset($_GET['tanggal'])) {
    echo json_encode(['status' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$barber_id = $_GET['barber_id'];
$tanggal = $_GET['tanggal']; 

try {
    $db = new DBconnection();
    $querySchedule = "SELECT start_time, end_time, slot_quota FROM barber_schedules WHERE barber_id = $1 AND schedule_date = $2 AND is_active = true";
    $resSchedule = $db->send_query($querySchedule, [$barber_id, $tanggal]);
    if (!$resSchedule->status || empty($resSchedule->data)) {
        ob_clean();
        echo json_encode(['status' => true, 'data' => [], 'message' => "Pencukur tidak memiliki jadwal pada tanggal ini."]);
        exit;
    }
    $schedule = $resSchedule->data[0];
    $start_time = strtotime($schedule['start_time']);
    $end_time = strtotime($schedule['end_time']);
    $quota = (int)$schedule['slot_quota'];
    $queryBooked = "SELECT time_slot FROM reservations WHERE barber_id = $1 AND reservation_date = $2 AND status != 'cancelled'";
    $resBooked = $db->send_query($queryBooked, [$barber_id, $tanggal]);
    $booked_counts = [];
    if ($resBooked->status && !empty($resBooked->data)) {
        foreach ($resBooked->data as $row) {
            $jam = substr($row['time_slot'], 0, 5); 
            if (!isset($booked_counts[$jam])) {
                $booked_counts[$jam] = 0;
            }
            $booked_counts[$jam]++;
        }
    }
$available_slots = [];
$current_time = $start_time;
$zona_waktu = new DateTimeZone('Asia/Jakarta');
$sekarang = new DateTimeImmutable('now', $zona_waktu);

while ($current_time < $end_time) {
    $jam_teks = date('H:i', $current_time);
    $terpakai = $booked_counts[$jam_teks] ?? 0;
    $is_full = ($terpakai >= $quota);
    $waktu_slot = new DateTimeImmutable(
        $tanggal . ' ' . $jam_teks . ':00',
        $zona_waktu
    );

    $is_past = ($waktu_slot <= $sekarang);

    $available_slots[] = [
        'waktu'   => $jam_teks,
        'is_full' => $is_full,
        'is_past' => $is_past
    ];
    $current_time = strtotime('+1 hour', $current_time);
}
    ob_clean();
    echo json_encode(['status' => true, 'data' => $available_slots]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => false, 'message' => 'Terjadi kesalahan sistem database.']);
}
?>