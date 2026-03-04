<?php
session_start();
// Clear session data
session_unset();
session_destroy();
setcookie('remember_token', '', time() - 3600, "/"); // Clear remember me cookie
header("Location: login.php");
?>