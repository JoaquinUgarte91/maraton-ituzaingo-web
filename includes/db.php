<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$hostHeader = $_SERVER['HTTP_HOST'] ?? '';

if ($hostHeader === 'localhost' || $hostHeader === '127.0.0.1') {
    // LOCAL (XAMPP)
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "maraton_db";
} elseif ($hostHeader === '10.10.55.100') {
    // VM (Ubuntu)
    $host = "localhost";
    $user = "maraton_user";
    $pass = "Ituz_2026+";
    $db   = "maraton";
} else {
    // HOSTINGER
    $host = "localhost";
    $user = "u849571447_maraton_user";
    $pass = "&BsKy!8Zp";
    $db   = "u849571447_maraton";
}

$conexion = new mysqli($host, $user, $pass, $db);
$conexion->set_charset("utf8mb4");
$conexion->query("SET time_zone = '-03:00'");
