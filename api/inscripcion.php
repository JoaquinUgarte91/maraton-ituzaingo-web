<?php
/**
 * MARATÓN ITUZAINGÓ 2026 - API INSCRIPCIÓN
 * Versión: FINAL HÍBRIDA (Auto-detecta Local vs Producción)
 * Soluciona: Permisos de QR, SSL de Recaptcha y Bloqueo de imágenes en Email.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// ==========================================
// 0. DETECTOR DE ENTORNO INTELIGENTE
// ==========================================
// Si el servidor es localhost, 127.0.0.1 o una IP local, estamos en DESARROLLO
$whitelist = ['127.0.0.1', '::1', 'localhost'];
$es_local = in_array($_SERVER['HTTP_HOST'], $whitelist);

if ($es_local) {
    // CONFIGURACIÓN LOCAL (XAMPP/Windows)
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    $ssl_verify = false; // Ignorar SSL para que Recaptcha no falle en local
} else {
    // CONFIGURACIÓN PRODUCCIÓN (Servidor Municipal)
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    $ssl_verify = true; // Forzar SSL seguro en producción
}

// Iniciamos buffer para proteger la respuesta JSON de errores visuales
ob_start();

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

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
        'PHPQRCode/qrlib.php', 'email_config.php',
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

    // ==========================================
    // 3. CAPTCHA (Configuración Dinámica SSL)
    // ==========================================
    if (empty($input['captcha_token'])) throw new Exception('Falta CAPTCHA', 400);
    
    $ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY, 'response' => $input['captcha_token']
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Aquí usamos la variable detectada automáticamente
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
    $captcha_res = json_decode(curl_exec($ch));
    curl_close($ch);

    if (!$captcha_res || !$captcha_res->success) throw new Exception('Error CAPTCHA', 400);

    // ==========================================
    // 4. BASE DE DATOS
    // ==========================================
    // Validar duplicado
    $stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE dni = ? LIMIT 1");
    $stmt->bind_param("s", $input['dni']);
    $stmt->execute();
    if ($stmt->fetch()) { $stmt->close(); throw new Exception('DNI ya registrado', 409); }
    $stmt->close();

    // Insertar
    $sql = "INSERT INTO inscripciones (nombre, dni, email, carrera, fecha_nacimiento, talle_remera, cobertura_medica, numero_afiliado, telefono_emergencia, fecha_inscripcion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conexion->prepare($sql);
    $cobertura = $input['cobertura_medica'] ?? '';
    $afiliado = $input['numero_afiliado'] ?? '';
    $telefono = $input['telefono_emergencia'] ?? '';
    $stmt->bind_param("sssssssss", $input['nombre'], $input['dni'], $input['email'], $input['carrera'], $input['fecha_nacimiento'], $input['talle_remera'], $cobertura, $afiliado, $telefono);
    
    if (!$stmt->execute()) throw new Exception('Error DB', 500);
    $id = $stmt->insert_id;
    $stmt->close();

    // ==========================================
    // 5. GENERACIÓN DE QR (RUTA INTELIGENTE)
    // ==========================================
    $payload = ['id' => $id, 'dni' => $input['dni'], 'ts' => time()];
    $qr_token = function_exists('qr_encrypt_payload') ? qr_encrypt_payload($payload) : base64_encode(json_encode($payload));

    // Intentamos usar la carpeta /public/temp (Mejor práctica)
    $temp_dir = __DIR__ . '/../public/temp';
    
    // Si la carpeta no existe, intentamos crearla
    if (!is_dir($temp_dir)) {
        if (!@mkdir($temp_dir, 0777, true)) {
            // Si falla (por permisos en servidor estricto), usamos la carpeta actual (API) como plan B
            $temp_dir = __DIR__;
        }
    }

    // Nombre único para evitar colisiones
    $qrFileName = 'qr_' . md5(uniqid(rand(), true)) . '.png';
    $qrFilePath = $temp_dir . '/' . $qrFileName;

    // Generamos el PNG físico temporalmente
    QRcode::png($qr_token, $qrFilePath, QR_ECLEVEL_H, 6, 2);

    // Validación final de escritura
    if (!file_exists($qrFilePath)) {
        throw new Exception("Error: No se pudo generar la imagen QR temporal (Revise permisos de carpeta).");
    }

    // ==========================================
    // 6. ENVÍO DE EMAIL PROFESIONAL
    // ==========================================
    $email_enviado = false;
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = EMAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_USERNAME;
        $mail->Password   = EMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = EMAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Si es local, relajamos seguridad de correo. Si es producción, es estricta.
        if ($es_local) {
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'allow_self_signed' => true]];
        }

        $mail->setFrom(EMAIL_FROM, 'Municipalidad de Ituzaingó');
        $mail->addAddress($input['email'], $input['nombre']);
        $mail->addReplyTo(EMAIL_REPLY_TO, 'Deportes Ituzaingó');
        
        // ADJUNTAR IMAGEN EMBEBIDA (Evita la X roja en Outlook/Gmail)
        $mail->addEmbeddedImage($qrFilePath, 'qr_img', 'codigo_qr.png');

        $mail->Subject = 'Confirmación Oficial - Maratón Ituzaingó 2026';
        
        $carrera_display = ($input['carrera'] === 'Kids') ? '1K Kids' : $input['carrera'];
        $doc_link = ($input['carrera'] === 'Kids') 
            ? 'https://www.miituzaingo.gov.ar/maraton-ituzaingo-web/public/documentacion/Autorizacion_menores2026.pdf' 
            : 'https://www.miituzaingo.gov.ar/maraton-ituzaingo-web/public/documentacion/Deslinde2026.pdf';
        $doc_nombre = ($input['carrera'] === 'Kids') ? 'Autorización de Menores' : 'Deslinde de Responsabilidad';

        // HTML ESTILO INSTITUCIONAL
        $mail->isHTML(true);
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                .header { background-color: #7b3487; padding: 30px 20px; text-align: center; color: white; }
                .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
                .content { padding: 30px; color: #333333; line-height: 1.6; }
                .data-box { background-color: #f8f9fa; border-left: 5px solid #7b3487; padding: 15px; margin: 20px 0; }
                .data-row { display: flex; justify-content: space-between; border-bottom: 1px solid #e0e0e0; padding: 8px 0; }
                .data-row:last-child { border-bottom: none; }
                .data-label { font-weight: bold; color: #555; }
                .qr-container { text-align: center; margin: 30px 0; background: #fff; border: 2px dashed #7b3487; padding: 20px; border-radius: 10px; }
                .btn { display: inline-block; background-color: #7b3487; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 10px; }
                .footer { background-color: #333; color: #aaa; text-align: center; padding: 20px; font-size: 12px; }
                .highlight { color: #7b3487; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Inscripción Confirmada</h1>
                    <p>13° Maratón "Corremos por más derechos"</p>
                </div>
                
                <div class="content">
                    <p>Hola <strong>' . htmlspecialchars($input['nombre']) . '</strong>,</p>
                    <p>¡Felicitaciones! Tu inscripción ha sido procesada con éxito. Ya eres parte de la edición 2026.</p>
                    
                    <div class="data-box">
                        <div class="data-row"><span class="data-label">Número de Corredor:</span> <span class="highlight" style="font-size:1.2em;">' . $id . '</span></div>
                        <div class="data-row"><span class="data-label">DNI:</span> <span>' . htmlspecialchars($input['dni']) . '</span></div>
                        <div class="data-row"><span class="data-label">Categoría:</span> <span>' . htmlspecialchars($carrera_display) . '</span></div>
                        <div class="data-row"><span class="data-label">Talle Remera:</span> <span>' . htmlspecialchars($input['talle_remera']) . '</span></div>
                    </div>

                    <div class="qr-container">
                        <p style="margin-top:0; font-weight:bold; color:#7b3487;">TU PASE DE ACCESO (QR)</p>
                        <img src="cid:qr_img" alt="Código QR" style="width: 200px; height: 200px; border:0; display:block; margin: 0 auto;">
                        <p style="font-size: 13px; color: #666; margin-bottom:0; margin-top:10px;">Presenta este código desde tu celular<br>al retirar el kit.</p>
                    </div>

                    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                        <p style="margin: 0 0 10px 0; color: #856404;"><strong>Importante:</strong> Para retirar tu kit debes presentar el siguiente documento firmado:</p>
                        <a href="' . $doc_link . '" class="btn">Descargar ' . $doc_nombre . '</a>
                    </div>

                    <div style="margin-top: 25px; border-top: 1px solid #eeeeee; padding-top: 20px;">
                        <h3 style="color: #7b3487; margin-top: 0; font-size: 16px; text-transform: uppercase;">📍 Retiro de Kits</h3>
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #7b3487;">
                            <p style="margin: 0 0 8px 0;"><strong>📅 Fechas:</strong> 4, 5 y 6 de Marzo de 2026</p>
                            <p style="margin: 0 0 8px 0;"><strong>⏰ Horario:</strong> De 09:00 a 18:00 hs</p>
                            <p style="margin: 0;"><strong>🏢 Lugar:</strong> Las Heras 30, Ituzaingó</p>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    <p><strong>Municipalidad de Ituzaingó</strong><br>
                    Consejo de Mujeres, Géneros, Diversidad, Niñeces y Adolescencias<br>
                    Las Heras 30, Ituzaingó</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->AltBody = "Confirmación Maratón Ituzaingó.\nNombre: {$input['nombre']}\nNro Corredor: $id\n(El código QR está adjunto en este correo)";

        $mail->send();
        $email_enviado = true;

    } catch (Exception $e) {
        // En producción puedes descomentar esto para logs internos
        // error_log("Error Email: " . $mail->ErrorInfo);
    }

    // 7. LIMPIEZA DEL ARCHIVO TEMPORAL
    if (file_exists($qrFilePath)) {
        unlink($qrFilePath);
    }

    // 8. RESPUESTA JSON FINAL
    ob_end_clean();
    
    // Generamos QR en base64 solo para mostrar en el navegador al instante (feedback usuario)
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
        'carrera' => $input['carrera']
    ]);

} catch (Exception $e) {
    ob_end_clean();
    // Limpieza de emergencia por si falló antes del envío
    if (isset($qrFilePath) && file_exists($qrFilePath)) unlink($qrFilePath);
    
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>