<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['ok' => false, 'message' => 'Faltan datos']);
    exit;
}

// Buscar usuario activo
$stmt = $conexion->prepare("SELECT id, password_hash FROM admins WHERE username = ? AND is_active = 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    // Verificar contraseña (hash bcrypt)
    if (password_verify($password, $row['password_hash'])) {
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_user'] = $username;
        echo json_encode(['ok' => true]);
        exit;
    }
}

echo json_encode(['ok' => false, 'message' => 'Usuario o contraseña incorrectos']);