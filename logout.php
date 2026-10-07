<?php
session_start();
// Empty the session variables
$_SESSION = array();
// Destroy the session completely
session_destroy();
// Redirect back to login screen
header("Location: index.php");
exit();
?>