<?php
session_start();
session_destroy();
ob_end_clean();
header("Location: ../login.php");
exit();
?>