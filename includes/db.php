<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (
    isset($_SERVER['HTTP_HOST']) &&
    ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')
) {
    // ===== LOCAL (XAMPP) =====
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "maraton_db";
} else {
    // ===== PRODUCCIÓN (HOSTINGER) =====
    $host = "localhost";
    $user = "u849571447_maraton_user";
    $pass = "&BsKy!8Zp";
    $db   = "u849571447_maraton";
}

$conexion = new mysqli($host, $user, $pass, $db);
$conexion->set_charset("utf8mb4");
