<?php
// worker_email.php - procesa la cola email_queue con rate limit y retry

$ROOT = dirname(__DIR__); // => public_html

require_once $ROOT . '/includes/db.php';
require_once $ROOT . '/includes/qr_crypto.php';
require_once $ROOT . '/includes/phpqrcode/qrlib.php';
require_once $ROOT . '/includes/email_config.php';
require_once $ROOT . '/includes/PHPMailer/PHPMailer.php';
require_once $ROOT . '/includes/PHPMailer/SMTP.php';
require_once $ROOT . '/includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

date_default_timezone_set('America/Argentina/Buenos_Aires');


// ===== CONFIG =====
$MAX_PER_RUN = 30;          // cuántos emails manda por corrida
$SLEEP_SECONDS = 2;         // rate limit: pausa entre envíos
$MAX_ATTEMPTS = 20;         // después de esto pasa a failed

$lockToken = bin2hex(random_bytes(16));
$now = date('Y-m-d H:i:s');

function calcBackoffSeconds(int $attempts): int {
    // 1m, 5m, 15m, 60m, 6h, 24h (cap)
    $mins = [1, 5, 15, 60, 360, 1440];
    $m = $mins[min($attempts, count($mins)-1)];
    return $m * 60;
}

function nextDayMorning(): string {
    // Reintentar mañana 08:00 AR (cuando se renueva cuota)
    $dt = new DateTime('tomorrow 08:00:00', new DateTimeZone('America/Argentina/Buenos_Aires'));
    return $dt->format('Y-m-d H:i:s');
}

function isDailyQuotaError(string $msg): bool {
    return stripos($msg, 'Daily user sending limit exceeded') !== false
        || stripos($msg, '5.4.5') !== false;
}

function isTransientError(string $msg): bool {
    // 4xx típicos o mensajes temporales
    return preg_match('/\b4\.\d\.\d\b/', $msg) === 1
        || stripos($msg, 'Temporary') !== false
        || stripos($msg, 'try again later') !== false
        || stripos($msg, '421') !== false
        || stripos($msg, '450') !== false
        || stripos($msg, '451') !== false
        || stripos($msg, '452') !== false;
}

// 1) Tomar pendientes/retry disponibles y lockearlos
$sqlSelect = "
    SELECT id, inscripcion_id, to_email, to_name, qr_token, carrera, talle_remera, remera_asignada, attempts
    FROM email_queue
    WHERE status IN ('pending','retry')
      AND (next_retry_at IS NULL OR next_retry_at <= NOW())
      AND (locked_at IS NULL OR locked_at < (NOW() - INTERVAL 10 MINUTE))
    ORDER BY created_at ASC
    LIMIT $MAX_PER_RUN
";

$res = $conexion->query($sqlSelect);
$rows = $res->fetch_all(MYSQLI_ASSOC);

if (!$rows) {
    echo "No hay emails para enviar.\n";
    exit;
}

// lock rows
$ids = array_column($rows, 'id');
$idList = implode(',', array_map('intval', $ids));
$conexion->query("
    UPDATE email_queue
    SET status='sending', locked_at=NOW(), lock_token='{$lockToken}', updated_at=NOW()
    WHERE id IN ($idList)
");

foreach ($rows as $row) {
    $qid = (int)$row['id'];
    $attempts = (int)$row['attempts'];

    // si superó intentos => failed
    if ($attempts >= $MAX_ATTEMPTS) {
        $conexion->query("
            UPDATE email_queue
            SET status='failed', last_error='Max attempts reached', updated_at=NOW()
            WHERE id={$qid} AND lock_token='{$lockToken}'
        ");
        continue;
    }

    $tmpQr = sys_get_temp_dir() . '/qr_' . $row['inscripcion_id'] . '_' . bin2hex(random_bytes(6)) . '.png';

    try {
        // generar QR desde qr_token guardado
        \QRcode::png($row['qr_token'], $tmpQr, QR_ECLEVEL_H, 6, 2);

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = EMAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_USERNAME;
        $mail->Password   = EMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = EMAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // DEBUG opcional (dejalo apagado en prod)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

        $mail->setFrom(EMAIL_FROM, defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Maratón Ituzaingó 2026');
        $mail->addAddress($row['to_email'], $row['to_name']);
        $mail->addReplyTo(EMAIL_REPLY_TO, 'Deportes Ituzaingó');

        $mail->addEmbeddedImage($tmpQr, 'qr_img', 'codigo_qr.png');

        $mail->Subject = 'Confirmación Oficial - Maratón Ituzaingó 2026 | Corredor #' . $row['inscripcion_id'];

        $mail->isHTML(true);
        $mail->Body = "
            <h2>Inscripción Confirmada</h2>
            <p>Hola <strong>".htmlspecialchars($row['to_name'])."</strong>,</p>
            <p>Tu número de corredor es: <strong>{$row['inscripcion_id']}</strong></p>
            <p>Categoría: <strong>".htmlspecialchars($row['carrera'])."</strong></p>
            <p>Talle: <strong>".htmlspecialchars((string)$row['talle_remera'])."</strong></p>
            <p><img src='cid:qr_img' alt='QR' style='width:200px;height:200px;'></p>
        ";

        $mail->AltBody = "Inscripción confirmada.\nCorredor: {$row['inscripcion_id']}\n";

        $mail->send();

        if (file_exists($tmpQr)) unlink($tmpQr);

        $conexion->query("
            UPDATE email_queue
            SET status='sent', sent_at=NOW(), last_error=NULL, updated_at=NOW(), locked_at=NULL, lock_token=NULL
            WHERE id={$qid} AND lock_token='{$lockToken}'
        ");

        sleep($SLEEP_SECONDS);

    } catch (\Throwable $e) {
        if (file_exists($tmpQr)) @unlink($tmpQr);

        $msg = $e->getMessage();
        $attemptsNext = $attempts + 1;

        if (isDailyQuotaError($msg)) {
            $nextRetry = nextDayMorning(); // mañana 08:00
        } else {
            $backoff = calcBackoffSeconds($attemptsNext);
            $nextRetry = date('Y-m-d H:i:s', time() + $backoff);
        }

        $safeMsg = $conexion->real_escape_string(substr($msg, 0, 2000));

        $conexion->query("
            UPDATE email_queue
            SET status='retry',
                attempts=attempts+1,
                last_error='{$safeMsg}',
                next_retry_at='{$nextRetry}',
                updated_at=NOW(),
                locked_at=NULL,
                lock_token=NULL
            WHERE id={$qid} AND lock_token='{$lockToken}'
        ");
    }
}

echo "OK - procesados: ".count($rows)."\n";
