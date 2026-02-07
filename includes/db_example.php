<?php
// ============================================================
// PLANTILLA DE CONEXIÓN A BASE DE DATOS
// ============================================================
// Instrucciones para el técnico de la Municipalidad:
// 1. Renombrar este archivo de 'db.example.php' a 'db.php'.
// 2. Reemplazar los valores de abajo con las credenciales reales del servidor.
// ============================================================

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// DEFINIR CREDENCIALES
$db_host = "localhost";        // Generalmente es 'localhost' o la IP del servidor de BD
$db_user = "USUARIO_DB_AQUI";  // Ej: maraton_user (NO usar root en producción)
$db_pass = "PASSWORD_DB_AQUI"; // La contraseña de la base de datos
$db_name = "maraton_db";       // Nombre de la base de datos

try {
    $conexion = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Evita mostrar la contraseña en pantalla si falla la conexión
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error interno de conexión al servidor.");
}
?>