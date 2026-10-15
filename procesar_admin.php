<?php
require 'auth.php';
require 'conexion.php';
require 'correo.php';

requerir_rol(['admin']);

$id     = (int) ($_POST['id'] ?? 0);
$accion = $_POST['accion'] ?? '';

if ($id <= 0 || !in_array($accion, ['aprobar', 'rechazar'], true)) {
    header('Location: admin.php');
    exit;
}

// Solo se puede resolver una inscripción que siga pendiente
$stmt = $conexion->prepare("SELECT nombre, apellido, correo FROM usuarios WHERE id = ? AND estado = 'pendiente'");
$stmt->bind_param('i', $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
if (!$usuario) {
    header('Location: admin.php');
    exit;
}

// Aprobar = activar la cuenta del deportista (el login solo deja entrar a "aprobado")
$nuevo_estado = $accion === 'aprobar' ? 'aprobado' : 'rechazado';
$stmt = $conexion->prepare("UPDATE usuarios SET estado = ?, fecha_revision = NOW() WHERE id = ?");
$stmt->bind_param('si', $nuevo_estado, $id);
$stmt->execute();

$nombre_completo = $usuario['nombre'] . ' ' . $usuario['apellido'];

if ($accion === 'aprobar') {
    $asunto  = 'Tu cuenta en Match Sport Integration ya está activa';
    $mensaje = "Hola $nombre_completo,\n\n"
             . "Tu inscripción fue aprobada y tu cuenta ya se encuentra activa.\n\n"
             . "Ya puedes iniciar sesión en la plataforma con tu correo ({$usuario['correo']}) "
             . "y la contraseña que registraste en el formulario.\n\n"
             . "¡Bienvenido a la escuela deportiva!\n\n"
             . "Equipo Match Sport Integration";
} else {
    $asunto  = 'Resultado de tu inscripción en Match Sport Integration';
    $mensaje = "Hola $nombre_completo,\n\n"
             . "No fue posible completar tu inscripción.\n\n"
             . "Te invitamos a acercarte a un punto físico de la escuela deportiva "
             . "para recibir más información sobre tu caso.\n\n"
             . "Equipo Match Sport Integration";
}

$enviado = enviar_correo($usuario['correo'], $asunto, $mensaje);

$_SESSION['flash'] = [
    'tipo'  => $enviado ? 'ok' : 'info',
    'texto' => ($accion === 'aprobar' ? 'Inscripción aprobada; cuenta activada. ' : 'Inscripción rechazada. ')
             . ($enviado ? 'Se envió el correo a ' . $usuario['correo'] . '.'
                         : 'No se pudo enviar el correo (revisa config_correo.php; queda registro en logs/correos.log).'),
];

header('Location: admin.php');
exit;
