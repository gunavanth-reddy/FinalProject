<?php
session_start();
session_destroy();
error_log("Logout: User logged out");
header("Location: login.php");
exit();
?>