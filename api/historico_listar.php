<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'No autorizado']);
  exit;
}

require_once __DIR__ . '/../includes/db.php';

$limit  = isset($_GET['limit'])  ? max(1, (int)$_GET['limit'])  : 50;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$q      = trim($_GET['q'] ?? '');
$edicion = trim($_GET['edicion'] ?? ''); // puede venir vacío

$where = [];
$params = [];
$types = '';

if ($edicion !== '') {
  $where[] = "edicion = ?";
  $params[] = (int)$edicion;
  $types .= 'i';
}

if ($q !== '') {
  $where[] = "(nombre LIKE ? OR dni LIKE ? OR email LIKE ? OR carrera LIKE ?)";
  $like = "%$q%";
  array_push($params, $like, $like, $like, $like);
  $types .= 'ssss';
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// Total
$sqlTotal = "SELECT COUNT(*) as total FROM inscripciones_historicas $whereSql";
$stmt = $conexion->prepare($sqlTotal);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Items
$sql = "SELECT id, edicion, nombre, dni, email, carrera, fecha_inscripcion
        FROM inscripciones_historicas
        $whereSql
        ORDER BY edicion DESC, fecha_inscripcion DESC, id DESC
        LIMIT ? OFFSET ?";

$stmt = $conexion->prepare($sql);

if ($params) {
  $types2 = $types . 'ii';
  $params2 = array_merge($params, [$limit, $offset]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) $items[] = $row;
$stmt->close();

// Ediciones disponibles (para el select)
$edRes = $conexion->query("SELECT DISTINCT edicion FROM inscripciones_historicas ORDER BY edicion DESC");
$ediciones = [];
while ($r = $edRes->fetch_assoc()) $ediciones[] = (int)$r['edicion'];

echo json_encode([
  'success' => true,
  'items' => $items,
  'total' => (int)$total,
  'ediciones' => $ediciones
]);
