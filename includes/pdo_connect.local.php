<?php
$host = "mysql-fakhriyyaa.alwaysdata.net";
$dbname = "fakhriyyaa_hwproject";
$username = "443643_hwproject";
$password = "1x2y3z000";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
