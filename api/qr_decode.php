<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'No autenticado']);
  exit;
}

$role  = $_SESSION['admin_role'] ?? '';
$scope = $_SESSION['admin_scope_kit'] ?? null;

// Permisos para escanear/validar
$canScan = in_array($role, ['admin_total','admin_visualizador','admin_externo'], true);
if (!$canScan) {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'Sin permisos']);
  exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/qr_crypto.php';

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
$token = trim((string)($body['token'] ?? ''));

if ($token === '') {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Falta token']);
  exit;
}

try {
  $payload = qr_decrypt_token($token);
  $id = (int)($payload['id'] ?? 0);
  if ($id <= 0) throw new Exception('Token inválido');

  // Traer inscripción
  $stmt = $conexion->prepare("SELECT * FROM inscripciones WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $item = $res->fetch_assoc();
  $stmt->close();

  if (!$item) {
    http_response_code(404);
    echo json_encode(['success'=>false,'message'=>'Inscripción no encontrada']);
    exit;
  }

  // Restricción para admin_externo por scope (ajustá si tu lógica es distinta)
  if ($role === 'admin_externo') {
    if ($scope === '10k' && ($item['carrera'] ?? '') !== '10km') {
      http_response_code(403);
      echo json_encode(['success'=>false,'message'=>'Este admin solo puede validar 10km']);
      exit;
    }
  }

  // Validar retiro de kit automáticamente (si estaba pendiente)
  $yaRetiro = ($item['kit_retirado'] == 1 || $item['kit_retirado'] === true);

  if (!$yaRetiro) {
    $upd = $conexion->prepare("UPDATE inscripciones SET kit_retirado = 1, kit_retirado_at = NOW() WHERE id = ? LIMIT 1");
    $upd->bind_param("i", $id);
    $upd->execute();
    $upd->close();

    // refrescar item actualizado
    $stmt2 = $conexion->prepare("SELECT * FROM inscripciones WHERE id = ? LIMIT 1");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $item = $res2->fetch_assoc();
    $stmt2->close();
  }

  echo json_encode([
    'success'=>true,
    'item'=>$item,
    'kit_validado'=> !$yaRetiro
  ]);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
