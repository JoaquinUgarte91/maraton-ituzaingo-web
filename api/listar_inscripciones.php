<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 🔐 PROTEGER LA API (solo admins logueados)
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// ✅ Rol del admin logueado
$adminRole = $_SESSION['admin_role'] ?? null;

// Configuración del evento (opcional, por consistencia)
include '../includes/config.php';
// Conexión DB
require_once __DIR__ . '/../includes/db.php';

// Parámetros
$limit  = isset($_GET['limit'])  ? max(1, (int)$_GET['limit'])  : 20;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$q      = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? 'fecha_inscripcion';
$order  = strtoupper($_GET['order'] ?? 'DESC');

// ✅ NUEVO: filtro kit
$kit = $_GET['kit'] ?? ''; // '', 'pendiente', 'retirado'

// Campos permitidos para ordenar (seguridad)
$sortWhitelist = ['nombre', 'dni', 'email', 'carrera', 'fecha_inscripcion'];
if (!in_array($sort, $sortWhitelist)) {
    $sort = 'fecha_inscripcion';
}
$order = $order === 'ASC' ? 'ASC' : 'DESC';

// WHERE dinámico (búsqueda + kit)
$whereParts = [];
$params = [];
$types = '';

// 🔒 FORZAR 10k PARA admin_externo (BACK REAL)
if ($adminRole === 'admin_externo') {
    $whereParts[] = "carrera = ?";
    $params[] = '10km';
    $types .= 's';
}


// q
if ($q !== '') {
    $whereParts[] = "(nombre LIKE ? OR dni LIKE ? OR email LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

// kit
if ($kit === 'retirado') {
    $whereParts[] = "kit_retirado = 1";
} elseif ($kit === 'pendiente') {
    $whereParts[] = "(kit_retirado IS NULL OR kit_retirado = 0)";
}

$where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

// Total de registros (para paginación)
$sqlTotal = "SELECT COUNT(*) as total FROM inscripciones $where";
$stmt = $conexion->prepare($sqlTotal);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Listado
$sql = "
    SELECT id, nombre, dni, email, carrera, fecha_inscripcion, kit_retirado, kit_retirado_at
    FROM inscripciones
    $where
    ORDER BY $sort $order
    LIMIT ? OFFSET ?
";

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
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'items'   => $items,
    'total'   => (int)$total
]);
