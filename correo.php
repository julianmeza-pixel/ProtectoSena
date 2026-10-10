<?php
// correo.php - envío de correos + registro en logs/correos.log
// Para que mail() funcione en XAMPP hay que configurar el SMTP de Gmail
// (C:\xampp\sendmail\sendmail.ini y la línea sendmail_path en php.ini).
function enviar_correo($para, $asunto, $mensaje) {
    $cabeceras = "From: Match Sport Integration <no-responder@matchsport.com>\r\n"
               . "Content-Type: text/plain; charset=UTF-8";
    $asunto_codificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';

    $enviado = @mail($para, $asunto_codificado, $mensaje, $cabeceras);

    $carpeta = __DIR__ . '/logs';
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
        file_put_contents($carpeta . '/.htaccess', "Require all denied\n");
    }
    file_put_contents(
        $carpeta . '/correos.log',
        date('Y-m-d H:i:s') . ' | ' . ($enviado ? 'ENVIADO' : 'FALLO') . " | $para | $asunto\n",
        FILE_APPEND
    );
    return $enviado;
}
