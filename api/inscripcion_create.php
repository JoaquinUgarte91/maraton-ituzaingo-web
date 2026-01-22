<?php
// ✅ VERSIÓN DEFINITIVA - PROBADA EN XAMPP WINDOWS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Ruta absoluta para Windows - AJUSTA ESTA RUTA SI ES NECESARIO
$root_path = 'C:\\xampp\\htdocs\\maraton-ituzaingo-web';

// ✅ Manejo robusto de errores para toda la ejecución
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP ERROR [$errno]: $errstr in $errfile:$errline");
    if (error_reporting() & $errno) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    return false;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("SHUTDOWN ERROR: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error crítico del servidor: ' . $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);
        }
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Solo se acepta POST']);
    exit;
}

// ✅ Obtener datos con manejo de errores mejorado
$raw_input = file_get_contents('php://input');
if (!$raw_input) {
    error_log("❌ No se recibieron datos en la solicitud POST");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en la solicitud']);
    exit;
}

error_log("📊 Datos recibidos (raw): " . $raw_input);

$input = json_decode($raw_input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $error_msg = 'Error JSON: ' . json_last_error_msg() . '. Datos recibidos: ' . substr($raw_input, 0, 200);
    error_log("❌ " . $error_msg);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
}

if (!is_array($input)) {
    error_log("❌ Los datos decodificados no son un array. Tipo: " . gettype($input));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Los datos enviados no tienen el formato correcto']);
    exit;
}

error_log("✅ Datos JSON válidos recibidos: " . json_encode($input));

// ✅ Validar campos obligatorios con retroalimentación detallada
$required_fields = ['nombre', 'dni', 'email', 'carrera'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || trim($input[$field]) === '') {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    $missing_list = implode(', ', $missing_fields);
    error_log("❌ Campos obligatorios faltantes: " . $missing_list);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Faltan campos obligatorios: ' . $missing_list,
        'missing_fields' => $missing_fields,
        'received_data' => array_keys($input)
    ]);
    exit;
}

// Limpiar y validar datos
$nombre  = trim($input['nombre']);
$dni     = preg_replace('/[^0-9]/', '', trim($input['dni'])); // Solo números
$email   = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);
$carrera = trim($input['carrera']);

// ✅ Conexión a base de datos con manejo de errores detallado
try {
    error_log("🔌 Intentando conectar a la base de datos...");
    
    $pdo = new PDO("mysql:host=localhost;dbname=maraton_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    error_log("✅ Conexión a base de datos exitosa");
    
    // Verificar DNI duplicado
    $st = $pdo->prepare("SELECT id FROM inscripciones WHERE dni = ?");
    $st->execute([$dni]);
    if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        error_log("❌ DNI ya registrado: $dni (ID existente: " . $row['id'] . ")");
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'DNI ya registrado en el sistema',
            'existing_id' => $row['id']
        ]);
        exit;
    }

    // Generar token seguro
    $kit_token = bin2hex(random_bytes(16));
    error_log("🔑 Token de kit generado: " . $kit_token);

    // ✅ Insertar inscripción con manejo de transacciones
    $pdo->beginTransaction();
    
    $sql = "INSERT INTO inscripciones (
        nombre, dni, email, carrera, kit_token,
        fecha_nacimiento, talle_remera, cobertura_medica,
        numero_afiliado, telefono_emergencia, fecha_inscripcion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $st = $pdo->prepare($sql);
    $result = $st->execute([
        $nombre,
        $dni,
        $email,
        $carrera,
        $kit_token,
        $input['fecha_nacimiento'] ?? null,
        $input['talle_remera'] ?? '',
        $input['cobertura_medica'] ?? '',
        $input['numero_afiliado'] ?? '',
        $input['telefono_emergencia'] ?? ''
    ]);

    if (!$result) {
        $error_info = $st->errorInfo();
        throw new Exception("Error SQL: " . $error_info[2]);
    }

    $new_id = (int)$pdo->lastInsertId();
    $pdo->commit();
    
    error_log("✅ Inscripción creada exitosamente. ID: $new_id");

    // ✅ Sistema de QR dual - Primero intentar disco, luego base64
    $qr_dir = $root_path . '\\public\\qrs\\';
    $qr_saved_to_disk = false;
    $qr_base64 = '';
    $qr_file = "qr_{$new_id}.png";
    
    // Crear directorio si no existe
    if (!file_exists($qr_dir)) {
        error_log("📁 Directorio QR no existe. Intentando crear: " . $qr_dir);
        if (mkdir($qr_dir, 0777, true)) {
            error_log("✅ Directorio QR creado exitosamente");
            // Asegurar permisos
            chmod($qr_dir, 0777);
        } else {
            error_log("❌ Error al crear directorio QR. Permisos: " . substr(sprintf('%o', fileperms(dirname($qr_dir))), -4));
        }
    }

    // Verificar permisos de escritura
    if (is_writable($qr_dir)) {
        error_log("✅ Directorio QR es escribible");
    } else {
        error_log("❌ Directorio QR NO es escribible. Permisos actuales: " . substr(sprintf('%o', fileperms($qr_dir)), -4));
        chmod($qr_dir, 0777);
        if (is_writable($qr_dir)) {
            error_log("✅ Permisos corregidos, ahora es escribible");
        } else {
            error_log("❌ Permisos no se pudieron corregir");
        }
    }

    // Intentar incluir librería QR
    $qr_lib_path = $root_path . '\\vendor\\phpqrcode\\qrlib.php';
    if (file_exists($qr_lib_path)) {
        error_log("📚 Librería QR encontrada en: " . $qr_lib_path);
        try {
            require_once $qr_lib_path;
            error_log("✅ Librería QR cargada correctamente");
        } catch (Exception $e) {
            error_log("❌ Error al incluir librería QR: " . $e->getMessage());
        }
    } else {
        error_log("❌ Librería QR NO encontrada en: " . $qr_lib_path);
    }

    // ✅ Generar QR - Intento 1: Guardar en disco
    if (class_exists('QRcode')) {
        try {
            $qr_path = $qr_dir . $qr_file;
            $qr_data = json_encode(["token" => $kit_token]);
            
            error_log("🖼️ Intentando generar QR en disco: " . $qr_path);
            error_log("📋 Datos del QR: " . $qr_data);
            
            QRcode::png($qr_data, $qr_path, QR_ECLEVEL_L, 5);
            
            if (file_exists($qr_path)) {
                $qr_saved_to_disk = true;
                $file_size = filesize($qr_path);
                error_log("✅ QR guardado en disco exitosamente. Tamaño: {$file_size} bytes");
            } else {
                error_log("❌ Archivo QR no se creó en disco");
            }
            
        } catch (Exception $e) {
            error_log("❌ Error al generar QR en disco: " . $e->getMessage());
        }
    } else {
        error_log("❌ Clase QRcode no está disponible");
    }

    // ✅ Fallback: Generar QR en memoria como base64
    if (!$qr_saved_to_disk && class_exists('QRcode')) {
        try {
            error_log("🔄 Intentando generar QR en memoria como base64...");
            ob_start();
            $qr_data = json_encode(["token" => $kit_token]);
            QRcode::png($qr_data, null, QR_ECLEVEL_L, 5);
            $qr_image = ob_get_clean();
            
            if ($qr_image && strlen($qr_image) > 0) {
                $qr_base64 = 'data:image/png;base64,' . base64_encode($qr_image);
                error_log("✅ QR generado en memoria como base64. Tamaño: " . strlen($qr_image) . " bytes");
            } else {
                error_log("❌ QR en memoria está vacío");
            }
        } catch (Exception $e) {
            error_log("❌ Error al generar QR en memoria: " . $e->getMessage());
        }
    }

    // ✅ Preparar respuesta final
    $response = [
        'success' => true,
        'id' => $new_id,
        'kit_token' => $kit_token,
        'message' => 'Inscripción exitosa'
    ];

    if ($qr_saved_to_disk) {
        $qr_url = "http://localhost/maraton-ituzaingo-web/public/qrs/" . $qr_file;
        $response['qr_url'] = $qr_url;
        $response['file_path'] = $qr_path;
        error_log("🌐 URL pública del QR: " . $qr_url);
    }
    
    if ($qr_base64) {
        $response['qr_base64'] = $qr_base64;
        error_log("💾 QR base64 generado para fallback");
    }

    // ✅ Si no se pudo generar ningún QR, aún devolver éxito
    if (!$qr_saved_to_disk && !$qr_base64) {
        error_log("⚠️ ADVERTENCIA: No se pudo generar ningún QR, pero la inscripción fue exitosa");
        $response['warning'] = 'No se pudo generar el QR. Contacta al administrador y proporciona tu ID de inscripción.';
    }

    echo json_encode($response);
    exit;

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("❌ Error de base de datos (PDOException): " . $e->getMessage());
    error_log("🔍 SQLSTATE: " . $e->getCode());
    error_log("📋 Detalles: " . print_r($e->errorInfo, true));
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'sql_state' => $e->getCode(),
        'error_details' => $e->errorInfo[2] ?? 'Sin detalles'
    ]);
    exit;
} catch (Exception $e) {
    error_log("❌ Error general (Exception): " . $e->getMessage());
    error_log("🔍 Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage(),
        'error_type' => get_class($e)
    ]);
    exit;
}