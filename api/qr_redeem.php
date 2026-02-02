<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'No autenticado']);
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

  // Si ya retiró, devolvemos igual
  $yaRetiro = ((int)($item['kit_retirado'] ?? 0) === 1);

  if (!$yaRetiro) {
    $upd = $conexion->prepare("UPDATE inscripciones SET kit_retirado = 1, kit_retirado_at = NOW() WHERE id = ? LIMIT 1");
    $upd->bind_param("i", $id);
    $upd->execute();
    $upd->close();

    // recargar actualizado
    $stmt = $conexion->prepare("SELECT * FROM inscripciones WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $item = $res->fetch_assoc();
    $stmt->close();
  }

  echo json_encode([
    'success' => true,
    'item' => $item,
    'already_redeemed' => $yaRetiro
  ]);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
