<?php
session_name("admin_session");
session_start();
require_once '../dbconnection.php';
require_once 'reservation_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    
    try {
        $db = new DBconnection();
        $reservationObj = new Reservation($db);
        
        $res_id = $_POST['reservation_id'];
        $action = $_POST['action'];

        if ($action === 'confirm') {
            $reservationObj->confirm($res_id);
        } elseif ($action === 'cancel') {
            $reservationObj->cancel($res_id);
        }elseif ($action === 'delete') {
            $reservationObj->delete($res_id);
        }
    } catch (DBException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

header("Location: admin_dashboard.php#reservasi");
exit();
?>