<?php
session_set_cookie_params(0, '/');
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Faltan credenciales']);
  exit;
}

// Traemos role y scope_kit también
$stmt = $conexion->prepare("SELECT id, username, password_hash, role, scope_kit, is_active FROM admins WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();

if (
  !$admin ||
  (int)$admin['is_active'] !== 1 ||
  !password_verify($password, $admin['password_hash'])
) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'Usuario o contraseña incorrectos']);
  exit;
}

session_regenerate_id(true);

// Sesión base
$_SESSION['admin_id'] = (int)$admin['id'];
$_SESSION['admin_username'] = $admin['username'];

// ✅ Sesión para permisos
$_SESSION['admin_role'] = $admin['role'];                 // admin_total | admin_visualizador | admin_externo
$_SESSION['admin_scope_kit'] = $admin['scope_kit'] ?? null; // null o '10k'

echo json_encode([
  'ok' => true,
  'username' => $admin['username'],
  'role' => $admin['role'],
  'scope_kit' => $admin['scope_kit'] ?? null
]);
