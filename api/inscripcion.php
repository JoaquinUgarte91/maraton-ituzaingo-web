<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Solo se acepta POST']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
  exit;
}

// ========================================
// ✅ VERIFICACIÓN DE CAPTCHA - RECAPTCHA v2
// ========================================

if (!isset($input['captcha_token']) || empty($input['captcha_token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'CAPTCHA requerido - verifica que no eres un robot']);
    exit;
}

$captcha_token = $input['captcha_token'];

// ⚠️ TU SECRET KEY DE GOOGLE RECAPTCHA
// Obtén tu clave secreta en: https://www.google.com/recaptcha/admin    
require_once __DIR__ . '/../includes/recaptcha_config.php';
$secret_key = RECAPTCHA_SECRET_KEY;

// Verificar con Google (CORREGIDO: sin espacio extra)
$captcha_verify_url = "https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_token}";
$captcha_verify = file_get_contents($captcha_verify_url);

if ($captcha_verify === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar CAPTCHA con Google']);
    exit;
}

$captcha_result = json_decode($captcha_verify);

if (!$captcha_result->success) {
    // Opcional: puedes ver qué error específico ocurrió
    $error_codes = $captcha_result->{'error-codes'} ?? ['desconocido'];
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Verificación CAPTCHA fallida',
        'error_codes' => $error_codes
    ]);
    exit;
}

// ✅ CAPTCHA VALIDADO - Continuar con el procesamiento normal

// ========================================
// CONFIGURACIÓN Y CONEXIÓN A BASE DE DATOS
// ========================================

// Configuración del evento
require_once __DIR__ . '/../includes/config.php';
// Conexión DB centralizada
require_once __DIR__ . '/../includes/db.php';
// ✅ Helper de cifrado QR (debe existir)
require_once __DIR__ . '/../includes/qr_crypto.php';
// ✅ PHPMailer para envío de emails
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';

// Declaraciones use (DEBEN estar aquí, al inicio)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ========================================
// VALIDACIÓN DE CAMPOS OBLIGATORIOS
// ========================================

$campos_obligatorios = ['nombre', 'dni', 'email', 'carrera', 'fecha_nacimiento', 'talle_remera'];
foreach ($campos_obligatorios as $campo) {
  if (empty($input[$campo])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "$campo es obligatorio"]);
    exit;
  }
}

// ========================================
// VERIFICAR DNI DUPLICADO
// ========================================

$stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE dni = ? LIMIT 1");
$stmt->bind_param("s", $input['dni']);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  $stmt->close();
  http_response_code(409);
  echo json_encode(['success' => false, 'message' => 'DNI ya registrado']);
  exit;
}
$stmt->close();

// ========================================
// VALIDAR EDAD
// ========================================

try {
  $fecha_nac = new DateTime($input['fecha_nacimiento']);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Fecha de nacimiento inválida']);
  exit;
}

$hoy  = new DateTime();
$edad = $hoy->diff($fecha_nac)->y;

if (($input['carrera'] === 'Kids') && $edad > 12) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Kids es solo para menores de 13 años']);
  exit;
}
if (($input['carrera'] !== 'Kids') && $edad < 13) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Carreras adultas son para mayores de 12 años']);
  exit;
}

// ========================================
// VALIDAR CARRERA
// ========================================

$carreras_validas = array_keys(MARATON_2026_CONFIG['limites_inscripciones']);
if (!in_array($input['carrera'], $carreras_validas, true)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Carrera no válida']);
  exit;
}

// ========================================
// CAMPOS OPCIONALES
// ========================================

$cobertura_medica     = $input['cobertura_medica'] ?? '';
$numero_afiliado      = $input['numero_afiliado'] ?? '';
$telefono_emergencia  = $input['telefono_emergencia'] ?? '';

// ========================================
// INSERTAR INSCRIPCIÓN EN BASE DE DATOS
// ========================================

$sql = "INSERT INTO inscripciones (
  nombre, dni, email, carrera, fecha_nacimiento, talle_remera,
  cobertura_medica, numero_afiliado, telefono_emergencia, fecha_inscripcion
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
  "sssssssss",
  $input['nombre'],
  $input['dni'],
  $input['email'],
  $input['carrera'],
  $input['fecha_nacimiento'],
  $input['talle_remera'],
  $cobertura_medica,
  $numero_afiliado,
  $telefono_emergencia
);

if (!$stmt->execute()) {
  $stmt->close();
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'No se pudo guardar la inscripción']);
  exit;
}

$id = (int)$stmt->insert_id;
$stmt->close();

// ========================================
// GENERAR TOKEN DE QR ENCRIPTADO
// ========================================

/**
 * ✅ QR ENCRIPTADO
 * Payload mínimo (ideal: solo id + version + timestamp).
 * NO metas datos sensibles (dni, mail, etc.) dentro del token si no es necesario.
 */
$payload = [
  'v'  => 1,
  'id' => $id,
  'ts' => time()
];

/**
 * ✅ Importante:
 * - Si tu qr_crypto.php tiene: qr_encrypt_token($payload) → usalo.
 * - Si en tu archivo se llama distinto (qr_encrypt), cambialo acá.
 */
$qr_token = qr_encrypt_payload($payload);

// ========================================
// ✅ ENVIAR EMAIL AL PARTICIPANTE
// ========================================

$email_enviado = false;
$email_error = '';

try {
    // Cargar configuración de email
    require_once __DIR__ . '/../includes/email_config.php';
    
    $mail = new PHPMailer(true);
    
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = EMAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = EMAIL_USERNAME;
    $mail->Password   = EMAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = EMAIL_PORT;

    $mail->CharSet = 'UTF-8';
    
    // Remitente y destinatario
    $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
    $mail->addAddress($input['email'], $input['nombre']);
    $mail->addReplyTo(EMAIL_REPLY_TO, 'Consejo de Mujeres Ituzaingó');
    
    // Contenido del email
    $mail->isHTML(true);
    $mail->Subject = '✅ Confirmación de Inscripción - Maratón Ituzaingó 2026';
    
    // Convertir carrera a texto legible
    $carrera_texto = [
        '10km' => '10 Kilómetros',
        '3km' => '3 Kilómetros',
        'Kids' => '1 Kilómetro Kids'
    ][$input['carrera']] ?? $input['carrera'];
    
    // Cuerpo del email HTML
    $mail->Body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #7b3487; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .info-item { margin: 15px 0; padding: 10px; background: white; border-left: 4px solid #7b3487; }
            .info-label { font-weight: bold; color: #7b3487; }
            .footer { margin-top: 20px; padding: 15px; background: #e8e8e8; font-size: 12px; text-align: center; }
            .btn { display: inline-block; background: #7b3487; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 ¡Inscripción Exitosa!</h1>
                <p>13° Maratón "Corremos Por Más Derechos y Más Igualdad 2026"</p>
            </div>
            <div class="content">
                <h2>Hola ' . htmlspecialchars($input['nombre']) . ' 👋</h2>
                <p>¡Gracias por inscribirte a la Maratón Ituzaingó 2026! Tu inscripción ha sido confirmada exitosamente.</p>
                
                <h3>📋 Tus datos de inscripción:</h3>
                <div class="info-item">
                    <span class="info-label">Número de corredor:</span><br>
                    <strong>' . $id . '</strong>
                </div>
                <div class="info-item">
                    <span class="info-label">Nombre:</span><br>
                    ' . htmlspecialchars($input['nombre']) . '
                </div>
                <div class="info-item">
                    <span class="info-label">DNI:</span><br>
                    ' . htmlspecialchars($input['dni']) . '
                </div>
                <div class="info-item">
                    <span class="info-label">Carrera:</span><br>
                    ' . htmlspecialchars($carrera_texto) . '
                </div>
                <div class="info-item">
                    <span class="info-label">Talle de remera:</span><br>
                    ' . htmlspecialchars($input['talle_remera']) . '
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha de nacimiento:</span><br>
                    ' . htmlspecialchars($input['fecha_nacimiento']) . '
                </div>
                <div class="info-item">
                    <span class="info-label">Cobertura médica:</span><br>
                    ' . htmlspecialchars($cobertura_medica) . '
                </div>
                <div class="info-item">
                    <span class="info-label">Número de afiliado:</span><br>
                    ' . htmlspecialchars($numero_afiliado) . '
                </div>
                <div class="info-item">
                    <span class="info-label">Teléfono de emergencia:</span><br>
                    ' . htmlspecialchars($telefono_emergencia) . '
                </div>
                
                <h3>📍 Próximos pasos:</h3>
                <p><strong>Fechas de retiro del kit:</strong> 4, 5 y 6 de marzo de 2026 de 9:00 a 18:00 hs</p>
                <p><strong>Lugar:</strong> A confirmar por email</p>
                
                <h3>📄 Documentación requerida:</h3>
                <ul>
                    <li>Deslinde de responsabilidad impreso y firmado</li>
                    <li>Fotocopia del DNI</li>
                    <li>Para menores: autorización y fotocopia del DNI del menor</li>
                </ul>
                
                <p style="text-align: center; margin: 20px 0;">
                    <a href="https://www.miituzaingo.gov.ar/" class="btn">Visitar sitio web</a>
                </p>
            </div>
            <div class="footer">
                <p><strong>Municipalidad de Ituzaingó</strong><br>
                Consejo de Mujeres, Géneros, Diversidad, Niñeces y Adolescencias<br>
                📧 Consejomujeresdeituzaingo@hotmail.com | 📞 4624-0898<br>
                📍 Las Heras 30, Ituzaingó</p>
                <p>© 2026 Municipalidad de Ituzaingó - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    $mail->AltBody = "Confirmacion de Inscripcion - Maraton Ituzaingo 2026\n\nHola " . $input['nombre'] . "\n\nTu numero de corredor es: " . $id . "\nCarrera: " . $carrera_texto . "\n\nGracias por participar!";
    
    $mail->send();
    $email_enviado = true;
    
} catch (Exception $e) {
    $email_error = $mail->ErrorInfo;
    $email_enviado = false;
    error_log("Error al enviar email: " . $email_error);
}

// ========================================
// RESPUESTA EXITOSA
// ========================================

echo json_encode([
  'success'        => true,
  'qr_token'       => $qr_token,
  'numero_corredor'=> $id,
  'email_enviado'  => $email_enviado,
  'email_error'    => $email_error
]);

?>