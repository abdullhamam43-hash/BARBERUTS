<?php
session_name("admin_session");
session_start();
session_destroy();
ob_end_clean();
header("Location: login_admin.php");
exit();
?>