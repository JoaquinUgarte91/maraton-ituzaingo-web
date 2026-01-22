<?php
session_set_cookie_params(0, '/');
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'No autenticado']);
  exit;
}

echo json_encode([
  'ok' => true,
  'admin_id' => $_SESSION['admin_id'],
  'username' => $_SESSION['admin_username'] ?? ''
]);
