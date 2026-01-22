<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-API-KEY");
header("Access-Control-Max-Age: 3600");

// Manejar preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ✅ SISTEMA DE AUTENTICACIÓN FLEXIBLE PARA DESARROLLO Y PRODUCCIÓN
$modo_desarrollo = true; // Cambia a false en producción
$clave_temporal = 'admin123'; // Cambia esta clave para producción

if ($modo_desarrollo) {
    // ✅ AUTENTICACIÓN TEMPORAL PARA DESARROLLO (acepta token en header o parámetro)
    $token_recibido = $_SERVER['HTTP_X_API_KEY'] ?? $_POST['api_key'] ?? $_GET['api_key'] ?? '';
    
    if ($token_recibido !== $clave_temporal) {
        // Intentar autenticación por sesión como fallback
        session_start();
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Acceso no autorizado. Necesitas autenticarte como administrador.',
                'modo' => 'desarrollo',
                'auth_required' => true
            ]);
            exit;
        }
    }
} else {
    // ✅ AUTENTICACIÓN PARA PRODUCCIÓN (sistema normal)
    $authPath = __DIR__ . '/../includes/auth.php';
    if (file_exists($authPath)) {
        require_once $authPath;
    } else {
        session_start();
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
            exit;
        }
    }
}

// Conexión a la base de datos
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Solo POST']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !isset($input['qr_data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falta el dato qr_data en la solicitud']);
    exit;
}

// Validar y extraer token del QR
$qr_data = json_decode($input['qr_data'], true);
$token = $qr_data['token'] ?? '';
if (empty($token) || strlen($token) !== 32 || !ctype_xdigit($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token de QR inválido']);
    exit;
}

try {
    // Verificar si el participante existe y no tiene kit retirado
    $check_stmt = $conexion->prepare("SELECT id, kit_retirado FROM inscripciones WHERE kit_token = ?");
    if (!$check_stmt) {
        throw new Exception("Error en preparación de consulta: " . $conexion->error);
    }
    
    $check_stmt->bind_param("s", $token);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Participante no encontrado']);
        exit;
    }
    
    $participante = $result->fetch_assoc();
    $check_stmt->close();
    
    if ($participante['kit_retirado'] == 1) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'El kit ya fue retirado anteriormente']);
        exit;
    }

    // Actualizar estado del kit
    $update_stmt = $conexion->prepare("UPDATE inscripciones SET kit_retirado = 1, kit_retirado_at = NOW() WHERE kit_token = ?");
    if (!$update_stmt) {
        throw new Exception("Error en preparación de actualización: " . $conexion->error);
    }
    
    $update_stmt->bind_param("s", $token);
    $update_stmt->execute();
    $affected_rows = $update_stmt->affected_rows;
    $update_stmt->close();

    if ($affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado del kit']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Kit retirado correctamente',
        'participante_id' => (int)$participante['id'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("kit_toggle.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}