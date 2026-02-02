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
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'Falta id']);
  exit;
}

// Evitar borrarte a vos mismo (opcional pero recomendado)
if ($id === (int)$_SESSION['admin_id']) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'No podés eliminar tu propio usuario']);
  exit;
}

$stmt = $conexion->prepare("DELETE FROM admins WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(['ok'=>true]);
