<?php
require_once __DIR__ . '/../dbconnection.php';
require_once __DIR__ . '/BarberSchedule.php';
session_name("admin_session");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: admin_dashboard.php");
    exit();
}

$db = new DBconnection();
$schedule = new BarberSchedule($db);
$schedule->delete($id);
$db->close_connection();

header("Location: admin_dashboard.php#jadwal");
exit();
?>