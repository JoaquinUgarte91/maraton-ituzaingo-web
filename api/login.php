<?php
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

$stmt = $conexion->prepare("SELECT id, username, password_hash, is_active FROM admins WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();

if (!$admin || (int)$admin['is_active'] !== 1 || !password_verify($password, $admin['password_hash'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'Usuario o contraseña incorrectos']);
  exit;
}

session_regenerate_id(true);
$_SESSION['admin_id'] = (int)$admin['id'];
$_SESSION['admin_username'] = $admin['username'];

echo json_encode(['ok' => true, 'username' => $admin['username']]);
