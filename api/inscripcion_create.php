<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
  echo json_encode(['success' => false, 'message' => 'Solo se acepta POST']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'JSON inválido']);
  exit;
}

$nombre  = trim($input['nombre'] ?? '');
$dni     = trim($input['dni'] ?? '');
$email   = trim($input['email'] ?? '');
$carrera = trim($input['carrera'] ?? '');

if ($nombre === '' || $dni === '' || $email === '' || $carrera === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Nombre, DNI, Email y Carrera son obligatorios']);
  exit;
}

$fecha_nacimiento    = $input['fecha_nacimiento'] ?? null;
$talle_remera        = $input['talle_remera'] ?? '';
$cobertura_medica    = $input['cobertura_medica'] ?? '';
$numero_afiliado     = $input['numero_afiliado'] ?? '';
$telefono_emergencia = $input['telefono_emergencia'] ?? '';

try {
  $pdo = new PDO("mysql:host=localhost;dbname=maraton_db;charset=utf8", "root", "");
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // evitar DNI duplicado
  $st = $pdo->prepare("SELECT id FROM inscripciones WHERE dni = ? LIMIT 1");
  $st->execute([$dni]);
  if ($st->fetch()) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'DNI ya registrado']);
    exit;
  }

  $sql = "INSERT INTO inscripciones
    (nombre,dni,email,carrera,fecha_nacimiento,talle_remera,cobertura_medica,numero_afiliado,telefono_emergencia,fecha_inscripcion)
    VALUES (?,?,?,?,?,?,?,?,?,NOW())";

  $st = $pdo->prepare($sql);
  $st->execute([
    $nombre,
    $dni,
    $email,
    $carrera,
    $fecha_nacimiento,
    $talle_remera,
    $cobertura_medica,
    $numero_afiliado,
    $telefono_emergencia
  ]);

  echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
  exit;

} catch (Exception $e) {
  error_log("inscripcion_create.php: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Error interno']);
  exit;
}
