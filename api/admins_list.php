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

$sql = "SELECT id, username, role, scope_kit, is_active, created_at
        FROM admins
        WHERE is_active = 1
        ORDER BY created_at DESC";
$res = $conexion->query($sql);

$items = [];
while ($row = $res->fetch_assoc()) {
  $row['id'] = (int)$row['id'];
  $row['is_active'] = (int)$row['is_active'];
  $items[] = $row;
}

echo json_encode(['ok'=>true,'items'=>$items]);
