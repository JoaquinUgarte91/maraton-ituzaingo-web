<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'No autorizado']);
  exit;
}

// Solo admin_total puede asignar número
if (($_SESSION['admin_role'] ?? '') !== 'admin_total') {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Sin permisos']);
  exit;
}

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Solo POST']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'JSON inválido']);
  exit;
}

$id = (int)($input['id'] ?? 0);

// numero puede venir null/vacío para “limpiar”
$raw = $input['numero_corredor'] ?? null;
$numero = null;

if ($raw !== null && $raw !== '' && $raw !== false) {
  $numero = (int)$raw;
  if ($numero < 1 || $numero > 10000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Número inválido (1 a 10000)']);
    exit;
  }
}

if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'ID inválido']);
  exit;
}

try {
  // Si querés un error más “lindo” antes del UNIQUE, chequeamos repetido
  if ($numero !== null) {
    $chk = $conexion->prepare("SELECT id FROM inscripciones WHERE numero_corredor = ? AND id <> ? LIMIT 1");
    $chk->bind_param("ii", $numero, $id);
    $chk->execute();
    $r = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($r) {
      http_response_code(409);
      echo json_encode(['success' => false, 'message' => 'Ese número de corredor ya está asignado a otro inscripto']);
      exit;
    }
  }

  $stmt = $conexion->prepare("UPDATE inscripciones SET numero_corredor = ? WHERE id = ?");
  // bind_param no acepta null directo en "i", usamos truco:
  if ($numero === null) {
    // set NULL
    $stmt = $conexion->prepare("UPDATE inscripciones SET numero_corredor = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
  } else {
    $stmt->bind_param("ii", $numero, $id);
  }

  $stmt->execute();
  $updated = (int)$stmt->affected_rows;
  $stmt->close();

  echo json_encode([
    'success' => true,
    'updated' => $updated,
    'id' => $id,
    'numero_corredor' => $numero
  ]);
} catch (Throwable $e) {
  // Si por alguna razón choca con el UNIQUE igual:
  // error_log($e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Error interno']);
}
