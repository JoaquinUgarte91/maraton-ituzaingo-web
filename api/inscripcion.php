<?php
/**
 * MARATÓN ITUZAINGÓ 2026 - API INSCRIPCIÓN
 * Versión: FINAL DINÁMICA (Hostinger + Producción)
 * Soluciona:
 * 1. Rutas de PDF automáticas.
 * 2. Permisos de QR y SSL.
 * 3. Botonera de Email blindada para móviles.
 * 4. Lógica de aviso de Stock agotado en Email.
 * 5. VALIDACIÓN DE EMAIL DUPLICADO.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// ==========================================
// 0. DETECTOR DE ENTORNO INTELIGENTE
// ==========================================
$whitelist = ['127.0.0.1', '::1', 'localhost'];
$es_local = in_array($_SERVER['HTTP_HOST'], $whitelist);

if ($es_local) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    $ssl_verify = false; 
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    $ssl_verify = true; 
}

ob_start();

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// ==========================================
// LOGGER SIMPLE A ARCHIVO
// ==========================================
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) { @mkdir($log_dir, 0777, true); }
$log_file = $log_dir . '/inscripcion.log';

function log_insc($msg, $ctx = []) {
    global $log_file;
    $ts = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ctx_str = $ctx ? ' | ' . json_encode($ctx, JSON_UNESCAPED_UNICODE) : '';
    @file_put_contents($log_file, "[$ts] [$ip] $msg | UA: $ua$ctx_str\n", FILE_APPEND);
}

try {
    // ==========================================
    // 1. CARGA DE LIBRERÍAS Y VALIDACIÓN
    // ==========================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    $includes_dir = __DIR__ . '/../includes';
    $required_files = [
        'recaptcha_config.php', 'config.php', 'db.php', 'qr_crypto.php',
        'phpqrcode/qrlib.php', 'email_config.php',
        'PHPMailer/PHPMailer.php', 'PHPMailer/SMTP.php', 'PHPMailer/Exception.php'
    ];

    foreach ($required_files as $file) {
        if (!file_exists($includes_dir . '/' . $file)) throw new Exception("Error interno: Falta librería $file");
        require_once $includes_dir . '/' . $file;
    }

    // ==========================================
    // 2. PROCESAMIENTO DE DATOS
    // ==========================================
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) throw new Exception('Datos inválidos', 400);
    
    log_insc("Request recibido", [
  "dni" => $input["dni"] ?? null,
  "email" => $input["email"] ?? null,
  "carrera" => $input["carrera"] ?? null
]);


    // ==========================================
 // ==========================================
// 3. CAPTCHA
// ==========================================

// Detectar si viene desde Postman
$es_postman = isset($_SERVER['HTTP_USER_AGENT']) &&
              strpos($_SERVER['HTTP_USER_AGENT'], 'Postman') !== false;

if (!$es_postman) {

    if (empty($input['captcha_token'])) {
        throw new Exception('Falta CAPTCHA', 400);
    }

    $ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $input['captcha_token']
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);

    $captcha_res = json_decode(curl_exec($ch));
    curl_close($ch);

    if (!$captcha_res || !$captcha_res->success) {
        throw new Exception('Error CAPTCHA', 400);
    }
}

    // ==========================================
    // 4. VALIDACIONES DE BASE DE DATOS
    // ==========================================

    // 4.0 Validar duplicado DNI
    $stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE dni = ? LIMIT 1");
    $stmt->bind_param("s", $input['dni']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        throw new Exception('El DNI ingresado ya se encuentra registrado.', 409);
    }
    $stmt->close();

    // 4.1 Validar duplicado EMAIL (NUEVO)
    $stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $input['email']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        // Este mensaje se mostrará en la alerta roja del frontend
        throw new Exception('Este correo electrónico ya está registrado.', 409);
    }
    $stmt->close();

    // ==========================================
    // 5. STOCK REMERAS 
    // ==========================================
    $carrera = $input['carrera'];
    $remera_asignada = 0;

    $conexion->begin_transaction();

    try {
        // Intento de descuento atómico
        $stStock = $conexion->prepare("
            UPDATE stock_remeras
            SET stock_disponible = stock_disponible - 1
            WHERE carrera = ?
              AND stock_disponible > 0
        ");
        $stStock->bind_param("s", $carrera);
        $stStock->execute();

        if ($stStock->affected_rows === 1) {
            $remera_asignada = 1;
        }
        $stStock->close();

        // Insertar inscripción
        $sql = "INSERT INTO inscripciones
            (nombre, dni, email, carrera, fecha_nacimiento, talle_remera, cobertura_medica, numero_afiliado, telefono_emergencia, remera_asignada, fecha_inscripcion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conexion->prepare($sql);

        $cobertura = $input['cobertura_medica'] ?? '';
        $afiliado  = $input['numero_afiliado'] ?? '';
        $telefono  = $input['telefono_emergencia'] ?? '';

        $stmt->bind_param(
            "sssssssssi",
            $input['nombre'],
            $input['dni'],
            $input['email'],
            $input['carrera'],
            $input['fecha_nacimiento'],
            $input['talle_remera'],
            $cobertura,
            $afiliado,
            $telefono,
            $remera_asignada
        );

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('Error al guardar en la Base de Datos', 500);
        }

        $id = $stmt->insert_id;
        log_insc("Insert OK", ["id" => $id, "dni" => $input["dni"], "email" => $input["email"], "remera_asignada" => $remera_asignada]);

        $stmt->close();

        $conexion->commit();

    } catch (Exception $e) {
        $conexion->rollback();
        throw $e;
    }

    // ==========================================
    // 6. GENERACIÓN DE QR
    // ==========================================
    $payload = ['id' => $id, 'dni' => $input['dni'], 'ts' => time()];
    $qr_token = function_exists('qr_encrypt_payload') ? qr_encrypt_payload($payload) : base64_encode(json_encode($payload));

    $temp_dir = __DIR__ . '/../public/temp';
    
    if (!is_dir($temp_dir)) {
        if (!@mkdir($temp_dir, 0777, true)) {
            $temp_dir = __DIR__;
        }
    }

    $qrFileName = 'qr_' . md5(uniqid(rand(), true)) . '.png';
    $qrFilePath = $temp_dir . '/' . $qrFileName;

    QRcode::png($qr_token, $qrFilePath, QR_ECLEVEL_H, 6, 2);
    log_insc("QR generado", ["qrFilePath" => $qrFilePath, "exists" => file_exists($qrFilePath)]);


    if (!file_exists($qrFilePath)) {
        throw new Exception("Error: No se pudo generar la imagen QR temporal.");
    }

    // ==========================================
    // ==========================================
// 7. EMAIL: ENCOLAR (NO ENVIAR EN LÍNEA)
// ==========================================
$email_enqueued = false;

try {
    $toEmail = $input['email'] ?? '';
    $toName  = $input['nombre'] ?? '';

    if (empty($toEmail)) {
        throw new Exception('Email vacío - no se puede encolar');
    }

    // Guardamos todo lo necesario para reconstruir el email 1:1 en el worker
    $payload = [
        'nombre' => $input['nombre'] ?? '',
        'dni' => $input['dni'] ?? '',
        'email' => $toEmail,
        'carrera' => $input['carrera'] ?? '',
        'fecha_nacimiento' => $input['fecha_nacimiento'] ?? '',
        'talle_remera' => $input['talle_remera'] ?? '',
        'cobertura_medica' => $input['cobertura_medica'] ?? '',
        'numero_afiliado' => $input['numero_afiliado'] ?? '',
        'telefono_emergencia' => $input['telefono_emergencia'] ?? '',
        'remera_asignada' => (int)$remera_asignada,

        // Datos “derivados” que usabas en el HTML
        'numero_corredor' => (int)$id
    ];
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

    log_insc("Email: encolando", ["to" => $toEmail, "id" => $id]);

    $stQ = $conexion->prepare("
        INSERT INTO email_queue
          (inscripcion_id, to_email, to_name, qr_token, carrera, talle_remera, remera_asignada,
           payload_json, status, attempts, last_error, next_retry_at, updated_at)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, NULL, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          to_email=VALUES(to_email),
          to_name=VALUES(to_name),
          qr_token=VALUES(qr_token),
          carrera=VALUES(carrera),
          talle_remera=VALUES(talle_remera),
          remera_asignada=VALUES(remera_asignada),
          payload_json=VALUES(payload_json),
          status='pending',
          attempts=0,
          last_error=NULL,
          next_retry_at=NOW(),
          updated_at=NOW()
    ");

    $carrera = $input['carrera'] ?? '';
    $talle   = $input['talle_remera'] ?? null;

    $stQ->bind_param(
        "isssssiss",
        $id,
        $toEmail,
        $toName,
        $qr_token,
        $carrera,
        $talle,
        $remera_asignada,
        $payloadJson
    );

    $stQ->execute();
    $stQ->close();

    $email_enqueued = true;
    log_insc("Email encolado OK", ["to" => $toEmail, "id" => $id]);

} catch (\Throwable $e) {
    log_insc("ERROR encolando email", [
        "to" => $input["email"] ?? null,
        "id" => $id ?? null,
        "error" => $e->getMessage()
    ]);
}


    // 8. LIMPIEZA
    if (file_exists($qrFilePath)) {
        unlink($qrFilePath);
    }

    // 9. RESPUESTA JSON FINAL
    ob_end_clean();
    
    ob_start();
    QRcode::png($qr_token, null, QR_ECLEVEL_H, 5, 2);
    $qr_frontend = base64_encode(ob_get_contents());
    ob_end_clean();

    echo json_encode([
        'success' => true,
        'message' => 'Inscripción exitosa',
        'qr_token' => $qr_token,
        'qr_image' => 'data:image/png;base64,' . $qr_frontend,
        'numero_corredor' => $id,
        'carrera' => $input['carrera'],
        'remera_asignada' => (bool)$remera_asignada,
        'email_enviado' => $email_enviado,
        'stock_warning' => $remera_asignada ? null : 'Te inscribiste correctamente, pero ya no quedan remeras disponibles para esta categoría.',
        'email_enqueued' => $email_enqueued,
    ]);

} catch (Exception $e) {
    ob_end_clean();
    if (isset($qrFilePath) && file_exists($qrFilePath)) unlink($qrFilePath);
    
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>