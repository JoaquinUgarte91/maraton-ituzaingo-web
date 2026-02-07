<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

// ✅ BLOQUEO REAL (BACK) — solo admin_total puede crear
if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'No autenticado']);
  exit;
}

if (($_SESSION['admin_role'] ?? '') !== 'admin_total') {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Sin permisos']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success'=>false,'message'=>'Solo se acepta POST']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'JSON inválido']);
  exit;
}

$nombre  = trim($input['nombre'] ?? '');
$dni     = trim($input['dni'] ?? '');
$email   = trim($input['email'] ?? '');
$carrera = trim($input['carrera'] ?? '');

if ($nombre==='' || $dni==='' || $email==='' || $carrera==='') {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Nombre, DNI, Email y Carrera son obligatorios']);
  exit;
}

$fecha_nacimiento    = $input['fecha_nacimiento'] ?? null;
$talle_remera        = $input['talle_remera'] ?? '';
$cobertura_medica    = $input['cobertura_medica'] ?? '';
$numero_afiliado     = $input['numero_afiliado'] ?? '';
$telefono_emergencia = $input['telefono_emergencia'] ?? '';

try {
  // 1) Validar DNI duplicado
  $st = $conexion->prepare("SELECT id FROM inscripciones WHERE dni = ? LIMIT 1");
  $st->bind_param("s", $dni);
  $st->execute();
  $res = $st->get_result();
  if ($res->fetch_assoc()) {
    http_response_code(409);
    echo json_encode(['success'=>false,'message'=>'DNI ya registrado']);
    exit;
  }

  // 2) Insert
  $sql = "INSERT INTO inscripciones
    (nombre,dni,email,carrera,fecha_nacimiento,talle_remera,cobertura_medica,numero_afiliado,telefono_emergencia,fecha_inscripcion)
    VALUES (?,?,?,?,?,?,?,?,?,NOW())";

  $st = $conexion->prepare($sql);
  $st->bind_param(
    "sssssssss",
    $nombre,
    $dni,
    $email,
    $carrera,
    $fecha_nacimiento,
    $talle_remera,
    $cobertura_medica,
    $numero_afiliado,
    $telefono_emergencia
  );
  $st->execute();

  echo json_encode(['success'=>true,'id'=>(int)$conexion->insert_id]);
  exit;

} catch (Throwable $e) {
  error_log("inscripcion_create.php: ".$e->getMessage());
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Error interno']);
  exit;
}
