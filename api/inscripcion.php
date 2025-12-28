<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Solo se acepta POST']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Configuración del evento
include '../includes/config.php';
// Conexión DB centralizada
require_once __DIR__ . '/../includes/db.php';

$campos_obligatorios = ['nombre', 'dni', 'email', 'carrera', 'fecha_nacimiento', 'talle_remera'];
foreach ($campos_obligatorios as $campo) {
    if (empty($input[$campo])) {
        echo json_encode(['success' => false, 'message' => "$campo es obligatorio"]);
        exit;
    }
}

// Verificar DNI duplicado
$stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE dni = ?");
$stmt->bind_param("s", $input['dni']);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'DNI ya registrado']);
    exit;
}
$stmt->close();

// Validar edad
$fecha_nac = new DateTime($input['fecha_nacimiento']);
$hoy = new DateTime();
$edad = $hoy->diff($fecha_nac)->y;

if ($input['carrera'] === 'Kids' && $edad > 12) {
    echo json_encode(['success' => false, 'message' => 'Kids es solo para menores de 13 años']);
    exit;
}
if ($input['carrera'] !== 'Kids' && $edad < 13) {
    echo json_encode(['success' => false, 'message' => 'Carreras adultas son para mayores de 12 años']);
    exit;
}

// Validar carrera
$carreras_validas = array_keys(MARATON_2026_CONFIG['limites_inscripciones']);
if (!in_array($input['carrera'], $carreras_validas)) {
    echo json_encode(['success' => false, 'message' => 'Carrera no válida']);
    exit;
}

// Campos opcionales
$cobertura_medica = $input['cobertura_medica'] ?? '';
$numero_afiliado = $input['numero_afiliado'] ?? '';
$telefono_emergencia = $input['telefono_emergencia'] ?? '';

// Insertar inscripción
$sql = "INSERT INTO inscripciones (
    nombre, dni, email, carrera, fecha_nacimiento, talle_remera,
    cobertura_medica, numero_afiliado, telefono_emergencia, fecha_inscripcion
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "sssssssss",
    $input['nombre'],
    $input['dni'],
    $input['email'],
    $input['carrera'],
    $input['fecha_nacimiento'],
    $input['talle_remera'],
    $cobertura_medica,
    $numero_afiliado,
    $telefono_emergencia
);

$stmt->execute();
$id = $stmt->insert_id;
$stmt->close();

// QR
$qr = "MUNICIPIO ITUZAINGO\nMARATON 2026\nID: $id\nDNI: {$input['dni']}\nCARRERA: {$input['carrera']}";

echo json_encode([
    'success' => true,
    'qr_data' => $qr,
    'numero_corredor' => $id
]);
