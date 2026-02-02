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

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) $body = [];

$id = (int)($body['id'] ?? 0);
$newPassword = (string)($body['password'] ?? '');

if ($id <= 0 || $newPassword === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'Faltan datos (id/password)']);
  exit;
}

if (strlen($newPassword) < 6) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'La contraseña debe tener al menos 6 caracteres']);
  exit;
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conexion->prepare("UPDATE admins SET password_hash = ? WHERE id = ? LIMIT 1");
$stmt->bind_param("si", $hash, $id);
$stmt->execute();

if ($stmt->affected_rows < 0) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'No se pudo actualizar']);
  exit;
}

echo json_encode(['ok'=>true]);
