<?php
$host = 'localhost';
$dbname = 'pos_system';
$username = 'root'; // The default username for XAMPP
$password = '';     // The default password is blank

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   // echo "Database connected successfully!";
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
