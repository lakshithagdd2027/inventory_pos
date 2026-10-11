<?php
require_once 'db.php';
// Encrypt the passwords
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$cashierPass = password_hash('cashier123', PASSWORD_DEFAULT);

$pdo->query("UPDATE Users SET password = '$adminPass' WHERE username = 'admin'");
$pdo->query("UPDATE Users SET password = '$cashierPass' WHERE username = 'cashier'");
echo "Passwords successfully secured! You can now delete this setup.php file.";
?>