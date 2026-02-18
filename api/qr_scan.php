<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log');
error_reporting(E_ALL);

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'No autorizado']);
  exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/qr_crypto.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Solo POST']);
  exit;
}

// ✅ LEER UNA SOLA VEZ
$raw = file_get_contents('php://input');
error_log("qr_scan raw: " . $raw);

$input = json_decode($raw, true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'JSON inválido']);
  exit;
}

$token = trim($input['token'] ?? '');
if ($token === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Falta token']);
  exit;
}

try {
  // ✅ ESTA ES LA FUNCIÓN REAL
  $payload = qr_decrypt_token($token);
} catch (Throwable $e) {
  error_log("qr_scan decrypt error: " . $e->getMessage());
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'QR inválido']);
  exit;
}

$id = (int)($payload['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'QR inválido (sin id)']);
  exit;
}

try {
  $conexion->begin_transaction();

  $stmt = $conexion->prepare("
    SELECT
      id, nombre, dni, email, carrera, talle_remera,
      kit_retirado, kit_retirado_at,
      remera_asignada, remera_retirada,
      qr_scan_count
    FROM inscripciones
    WHERE id = ?
    FOR UPDATE
  ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$row) {
    $conexion->rollback();
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Inscripción no encontrada']);
    exit;
  }

  $scanCount      = (int)($row['qr_scan_count'] ?? 0);
  $kitRetirado    = (int)($row['kit_retirado'] ?? 0);
  $asignadaRemera = (int)($row['remera_asignada'] ?? 0);

  if ($kitRetirado === 1) {
    $conexion->commit();
    echo json_encode([
      'success' => true,
      'step' => 'YA_VALIDADO',
      'message' => 'Este QR ya fue validado anteriormente ✅',
      'item' => $row
    ]);
    exit;
  }

  if ($scanCount === 0) {
    $upd = $conexion->prepare("UPDATE inscripciones SET qr_scan_count = 1 WHERE id = ?");
    $upd->bind_param("i", $id);
    $upd->execute();
    $upd->close();

    $conexion->commit();

    echo json_encode([
      'success' => true,
      'step' => 'INFO',
      'message' => $asignadaRemera ? '✅ Recibe remera' : '⚠️ No recibe remera',
      'remera_asignada' => $asignadaRemera,
      'item' => $row
    ]);
    exit;
  }

  $remeraRetiraAhora = $asignadaRemera ? 1 : 0;

  $upd = $conexion->prepare("
    UPDATE inscripciones
    SET
      kit_retirado = 1,
      kit_retirado_at = NOW(),
      remera_retirada = ?
    WHERE id = ?
  ");
  $upd->bind_param("ii", $remeraRetiraAhora, $id);
  $upd->execute();
  $upd->close();

  $stmt2 = $conexion->prepare("
    SELECT
      id, nombre, dni, email, carrera, talle_remera,
      kit_retirado, kit_retirado_at,
      remera_asignada, remera_retirada,
      qr_scan_count
    FROM inscripciones
    WHERE id = ?
  ");
  $stmt2->bind_param("i", $id);
  $stmt2->execute();
  $row2 = $stmt2->get_result()->fetch_assoc();
  $stmt2->close();

  $conexion->commit();

  echo json_encode([
    'success' => true,
    'step' => 'VALIDADO',
    'message' => $remeraRetiraAhora ? '✅ Retiro validado: KIT + REMERA' : '✅ Retiro validado: KIT (sin remera)',
    'item' => $row2
  ]);
  exit;

} catch (Throwable $e) {
  error_log("qr_scan.php: " . $e->getMessage());
  try { $conexion->rollback(); } catch (Throwable $x) {}
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Error interno']);
  exit;
}
