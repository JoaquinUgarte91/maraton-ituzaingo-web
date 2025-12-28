<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'No autorizado']);
  exit;
}

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Solo POST']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

$id = isset($input['id']) ? (int)$input['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'ID inválido']);
  exit;
}

// Campos editables (ajustá si querés menos)
$nombre = trim((string)($input['nombre'] ?? ''));
$dni    = trim((string)($input['dni'] ?? ''));
$email  = trim((string)($input['email'] ?? ''));
$carrera= trim((string)($input['carrera'] ?? ''));

$fecha_nacimiento   = trim((string)($input['fecha_nacimiento'] ?? ''));
$talle_remera       = trim((string)($input['talle_remera'] ?? ''));
$cobertura_medica   = trim((string)($input['cobertura_medica'] ?? ''));
$numero_afiliado    = trim((string)($input['numero_afiliado'] ?? ''));
$telefono_emergencia= trim((string)($input['telefono_emergencia'] ?? ''));

// Validaciones mínimas
if ($nombre === '' || $dni === '' || $email === '' || $carrera === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'nombre, dni, email y carrera son obligatorios']);
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Email inválido']);
  exit;
}

// Evitar DNI duplicado (en otra fila)
$chk = $conexion->prepare("SELECT id FROM inscripciones WHERE dni = ? AND id <> ? LIMIT 1");
$chk->bind_param("si", $dni, $id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
  $chk->close();
  http_response_code(409);
  echo json_encode(['success' => false, 'message' => 'DNI ya registrado en otra inscripción']);
  exit;
}
$chk->close();

// UPDATE
$sql = "UPDATE inscripciones
        SET nombre=?, dni=?, email=?, carrera=?,
            fecha_nacimiento=?, talle_remera=?, cobertura_medica=?,
            numero_afiliado=?, telefono_emergencia=?
        WHERE id=?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
  "sssssssssi",
  $nombre, $dni, $email, $carrera,
  $fecha_nacimiento, $talle_remera, $cobertura_medica,
  $numero_afiliado, $telefono_emergencia,
  $id
);

$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$ok]);
