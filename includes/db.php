<?php
// public_html/includes/db.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = new mysqli("localhost", "root", "", "maraton_db");
$conexion->set_charset("utf8mb4");
