<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'No autenticado']);
  exit;
}

if (($_SESSION['admin_role'] ?? '') !== 'admin_total') {
  http_response_code(403);
  echo json_encode(['ok'=>false,'message'=>'Sin permisos']);
  exit;
}

require_once __DIR__ . '/../includes/db.php';

// Acepta JSON o form-data
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
$input = [];
if (stripos($ct, 'application/json') !== false) {
  $input = json_decode(file_get_contents('php://input'), true) ?: [];
} else {
  $input = $_POST;
}

$username = trim($input['username'] ?? '');
$password = (string)($input['password'] ?? '');
$role = $input['role'] ?? 'admin_visualizador';

$roles_ok = ['admin_total','admin_visualizador','admin_externo'];
if ($username === '' || $password === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'Username y password requeridos']);
  exit;
}
if (!in_array($role, $roles_ok, true)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'Rol inválido']);
  exit;
}

$scope_kit = null;
if ($role === 'admin_externo') $scope_kit = '10k';

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
  $stmt = $conexion->prepare(
    "INSERT INTO admins (username, password_hash, role, scope_kit, is_active, created_at)
     VALUES (?, ?, ?, ?, 1, NOW())"
  );
  $stmt->bind_param("ssss", $username, $hash, $role, $scope_kit);
  $stmt->execute();

  echo json_encode(['ok'=>true]);
} catch (mysqli_sql_exception $e) {
  // Duplicado username
  if (($e->getCode() ?? 0) == 1062) {
    http_response_code(409);
    echo json_encode(['ok'=>false,'message'=>'El username ya existe']);
  } else {
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'Error al crear admin']);
  }
}
