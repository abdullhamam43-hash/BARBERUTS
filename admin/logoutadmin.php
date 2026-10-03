<?php
session_start();
session_destroy();
ob_end_clean();
header("Location: login_admin.php");
exit();
?>