<?php
require_once '../dbconnection.php';
require_once 'barber.php';
session_name("admin_session");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_admin.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: admin_dashboard.php");
    exit();
}
else{
$db = new DBconnection();
$deletebarber = new Barber($db);
$deletebarber->delete($id);
header("Location: admin_dashboard.php#barber");
exit();
}
