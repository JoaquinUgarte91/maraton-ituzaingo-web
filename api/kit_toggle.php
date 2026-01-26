<?php
header('Content-Type: application/json; charset=utf-8');

// auth opcional (no rompe si no existe)
$authPath = __DIR__ . '/../includes/auth.php';
if (file_exists($authPath)) {
  require_once $authPath;
}

// tu conexión mysqli
require_once __DIR__ . '/../includes/db.php'; // deja $conexion = new mysqli(...)

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
$value = (int)($input['value'] ?? -1); // 0 o 1

if ($id <= 0 || ($value !== 0 && $value !== 1)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
  exit;
}

try {
  // Si value=1 => kit_retirado_at = NOW()
  // Si value=0 => kit_retirado_at = NULL
  $sql = "UPDATE inscripciones
          SET kit_retirado = ?,
              kit_retirado_at = IF(?, NOW(), NULL)
          WHERE id = ?";

  $stmt = $conexion->prepare($sql);
  $stmt->bind_param("iii", $value, $value, $id);
  $stmt->execute();

  echo json_encode([
    'success' => true,
    'updated' => (int)$stmt->affected_rows,
    'id' => $id,
    'value' => $value
  ]);

} catch (Throwable $e) {
  error_log("kit_toggle.php: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Error interno']);
}
