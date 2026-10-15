<?php
// correo.php - envío de correos por SMTP (PHPMailer) + registro en logs/correos.log
// La configuración está en config_correo.php (ver config_correo.ejemplo.php).
require_once __DIR__ . '/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function registrar_correo($estado, $para, $asunto, $detalle = '') {
    $carpeta = __DIR__ . '/logs';
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
        file_put_contents($carpeta . '/.htaccess', "Require all denied\n");
    }
    file_put_contents(
        $carpeta . '/correos.log',
        date('Y-m-d H:i:s') . " | $estado | $para | $asunto" . ($detalle !== '' ? " | $detalle" : '') . "\n",
        FILE_APPEND
    );
}

function enviar_correo($para, $asunto, $mensaje) {
    $archivo = __DIR__ . '/config_correo.php';
    if (!is_file($archivo)) {
        registrar_correo('FALLO', $para, $asunto, 'Falta config_correo.php');
        return false;
    }
    $cfg = require $archivo;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->Port       = $cfg['puerto'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['usuario'];
        $mail->Password   = $cfg['clave'];
        $mail->SMTPSecure = $cfg['puerto'] == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout    = 15;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['remitente'], $cfg['nombre']);
        $mail->addAddress($para);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;

        $mail->send();
        registrar_correo('ENVIADO', $para, $asunto);
        return true;
    } catch (Exception $e) {
        registrar_correo('FALLO', $para, $asunto, $mail->ErrorInfo);
        return false;
    }
}
