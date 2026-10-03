<?php
require_once '../dbconnection.php';
require_once 'BarberSchedule.php';
session_name("admin_session");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $barber_id = $_POST["barber_id"] ?? null;
    $schedule_date = $_POST["schedule_date"] ?? null;
    $start_time = $_POST["start_time"] ?? null;
    $end_time = $_POST["end_time"] ?? null;
    $slot_quota = $_POST["slot_quota"] ?? null;

    $id = $_POST["id"] ?? null;
if (!empty($id)) { 
    if (empty($start_time) || empty($end_time) || empty($slot_quota)) {
        $_SESSION['error'] = "Semua field wajib diisi!";
    } elseif ($start_time >= $end_time) {
        $_SESSION['error'] = "Jam mulai harus lebih kecil dari jam selesai!";
    } else {
        $db = new DBconnection();
        $r = (new BarberSchedule($db))->edit($id, $start_time, $end_time, $slot_quota);
        $db->close_connection();
        $_SESSION[$r->status ? 'success' : 'error'] = $r->message;
    }
    header("Location: admin_dashboard.php#jadwal");
    exit();
}

    if (empty($barber_id) || empty($schedule_date) || empty($start_time) || empty($end_time) || empty($slot_quota)) {
        $_SESSION['error'] = "Semua field wajib diisi!";
    } elseif ($start_time >= $end_time) {
        $_SESSION['error'] = "Jam mulai harus lebih kecil dari jam selesai!";
    } else {
        try {
            $db = new DBconnection();
            $schedule = new BarberSchedule($db);
            
            $result = $schedule->add($barber_id, $schedule_date, $start_time, $end_time, $slot_quota);

            $db->close_connection();

            if ($result->status) {
                $_SESSION['success'] = "" . $result->message;
            } else {
                $_SESSION['error'] = " " . $result->message;
            }
        } catch (DBException $e) {
            $_SESSION['error'] = " Error: " . htmlspecialchars($e->getMessage());
        }
    }
}

header("Location: admin_dashboard.php#jadwal");
exit();
?>