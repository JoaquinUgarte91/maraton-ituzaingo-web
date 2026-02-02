<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Solo se acepta POST']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
  exit;
}

// Configuración del evento
require_once __DIR__ . '/../includes/config.php';
// Conexión DB centralizada
require_once __DIR__ . '/../includes/db.php';
// ✅ Helper de cifrado QR (debe existir)
require_once __DIR__ . '/../includes/qr_crypto.php';

$campos_obligatorios = ['nombre', 'dni', 'email', 'carrera', 'fecha_nacimiento', 'talle_remera'];
foreach ($campos_obligatorios as $campo) {
  if (empty($input[$campo])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "$campo es obligatorio"]);
    exit;
  }
}

// Verificar DNI duplicado
$stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE dni = ? LIMIT 1");
$stmt->bind_param("s", $input['dni']);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  $stmt->close();
  http_response_code(409);
  echo json_encode(['success' => false, 'message' => 'DNI ya registrado']);
  exit;
}
$stmt->close();

// Validar edad
try {
  $fecha_nac = new DateTime($input['fecha_nacimiento']);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Fecha de nacimiento inválida']);
  exit;
}

$hoy  = new DateTime();
$edad = $hoy->diff($fecha_nac)->y;

if (($input['carrera'] === 'Kids') && $edad > 12) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Kids es solo para menores de 13 años']);
  exit;
}
if (($input['carrera'] !== 'Kids') && $edad < 13) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Carreras adultas son para mayores de 12 años']);
  exit;
}

// Validar carrera
$carreras_validas = array_keys(MARATON_2026_CONFIG['limites_inscripciones']);
if (!in_array($input['carrera'], $carreras_validas, true)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Carrera no válida']);
  exit;
}

// Campos opcionales
$cobertura_medica     = $input['cobertura_medica'] ?? '';
$numero_afiliado      = $input['numero_afiliado'] ?? '';
$telefono_emergencia  = $input['telefono_emergencia'] ?? '';

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

if (!$stmt->execute()) {
  $stmt->close();
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'No se pudo guardar la inscripción']);
  exit;
}

$id = (int)$stmt->insert_id;
$stmt->close();

/**
 * ✅ QR ENCRIPTADO
 * Payload mínimo (ideal: solo id + version + timestamp).
 * NO metas datos sensibles (dni, mail, etc.) dentro del token si no es necesario.
 */
$payload = [
  'v'  => 1,
  'id' => $id,
  'ts' => time()
];

/**
 * ✅ Importante:
 * - Si tu qr_crypto.php tiene: qr_encrypt_token($payload) → usalo.
 * - Si en tu archivo se llama distinto (qr_encrypt), cambialo acá.
 */
$qr_token = qr_encrypt_payload($payload);

echo json_encode([
  'success'        => true,
  // ✅ ahora el QR contiene el token cifrado (esto es lo que vas a dibujar en el QR)
  'qr_token'       => $qr_token,
  'numero_corredor'=> $id
]);
