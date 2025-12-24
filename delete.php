<?php
require "includes/pdo_connect.local.php";
define('SECRET_KEY', 'HWPROJECT2025');

if (!isset($_GET['code'])) {
    header("Location: dashboard.php");
    exit;
}

$decoded = base64_decode($_GET['code'], true);
if ($decoded === false) {
    die("Invalid code");
}

list($id, $key) = explode('|', $decoded);
if ($key !== SECRET_KEY) {
    die("Invalid access");
}

$id = (int) $id;

$stmt = $pdo->prepare("DELETE FROM experiences WHERE id = ?");
$stmt->execute([$id]);

header("Location: dashboard.php");
exit;
