<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "maraton_db");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Recibir datos del formulario
$nombre = $_POST['nombre'];
$dni = $_POST['dni'];
$email = $_POST['email'];
$carrera = $_POST['carrera'];

// Insertar datos en la base de datos
$sql = "INSERT INTO inscripciones (nombre, dni, email, carrera) VALUES (?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssss", $nombre, $dni, $email, $carrera);
$stmt->execute();

// Obtener el ID insertado
$id_inscripcion = $stmt->insert_id;

$stmt->close();
$conexion->close();

// Generar datos para el QR
$datos_qr = "ID: $id_inscripcion\nNombre: $nombre\nDNI: $dni\nEmail: $email\nCarrera: $carrera";

// Devolver los datos al cliente para generar el QR
echo json_encode(['qr_data' => $datos_qr]);
?>
