<?php
// recuperar.php - pide rol + correo y envía un enlace para restablecer la contraseña
require 'conexion.php';
require 'correo.php';
require 'cuentas_recuperacion.php';

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

$enviado = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rol    = $_POST['rol'] ?? '';
    $correo = trim($_POST['correo'] ?? '');

    if (!isset(TABLAS_RECUPERACION[$rol]) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Selecciona tu rol e ingresa un correo válido.';
    } else {
        $tabla  = TABLAS_RECUPERACION[$rol];
        $filtro = $rol === 'deportista' ? "AND estado = 'aprobado'" : '';
        $stmt = $conexion->prepare("SELECT nombre FROM $tabla WHERE correo = ? $filtro LIMIT 1");
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $cuenta = $stmt->get_result()->fetch_assoc();

        // Máximo un enlace por minuto y cuenta
        $stmt = $conexion->prepare("SELECT 1 FROM recuperacion_password WHERE rol = ? AND correo = ? AND creado > (NOW() - INTERVAL 1 MINUTE) LIMIT 1");
        $stmt->bind_param('ss', $rol, $correo);
        $stmt->execute();
        $reciente = $stmt->get_result()->num_rows > 0;

        if ($cuenta && !$reciente) {
            $token = bin2hex(random_bytes(32));
            $hash  = hash('sha256', $token);

            $stmt = $conexion->prepare("DELETE FROM recuperacion_password WHERE rol = ? AND correo = ?");
            $stmt->bind_param('ss', $rol, $correo);
            $stmt->execute();
            $stmt = $conexion->prepare("INSERT INTO recuperacion_password (rol, correo, token_hash, expira) VALUES (?, ?, ?, NOW() + INTERVAL 1 HOUR)");
            $stmt->bind_param('sss', $rol, $correo, $hash);
            $stmt->execute();

            $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $enlace  = $esquema . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\')
                     . '/restablecer.php?token=' . $token;

            $nombre  = trim($cuenta['nombre']) !== '' ? $cuenta['nombre'] : 'usuario';
            enviar_correo(
                $correo,
                'Restablece tu contraseña en Match Sport Integration',
                "Hola $nombre,\n\n"
                . "Recibimos una solicitud para restablecer tu contraseña.\n"
                . "Entra al siguiente enlace (válido por 1 hora):\n\n$enlace\n\n"
                . "Si no fuiste tú, ignora este mensaje: tu contraseña no cambiará.\n\n"
                . "Equipo Match Sport Integration"
            );
        }
        // Misma respuesta exista o no la cuenta, para no revelar qué correos están registrados
        $enviado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Sport Integration - Recuperar contraseña</title>
    <link rel="stylesheet" href="home_proyecto.css">
</head>
<body class="auth">
<div class="card">
    <img src="uploads/logo%20plataforma.PNG" alt="Match Sport Integration" class="logo-auth">
    <div class="brand-title">Match Sport Integration</div>
    <div class="section-title">Recuperar contraseña</div>
    <div class="section-desc">Te enviaremos un enlace a tu correo para crear una nueva contraseña.</div>

    <?php if ($enviado): ?>
        <div class="aviso ok" style="text-align:left;">Si los datos corresponden a una cuenta activa, enviamos un enlace a <strong><?php echo e($correo); ?></strong>. Revisa tu bandeja y la carpeta de spam. El enlace vence en 1 hora.</div>
    <?php else: ?>
        <?php if ($error): ?><div class="aviso error" style="text-align:left;"><?php echo e($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="input-group">
                <select name="rol" required>
                    <option value="" disabled <?php echo empty($_POST['rol']) ? 'selected' : ''; ?>>Selecciona tu rol</option>
                    <option value="deportista" <?php echo ($_POST['rol'] ?? '') === 'deportista' ? 'selected' : ''; ?>>Deportista</option>
                    <option value="profesor" <?php echo ($_POST['rol'] ?? '') === 'profesor' ? 'selected' : ''; ?>>Profesor</option>
                    <option value="admin" <?php echo ($_POST['rol'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                </select>
            </div>
            <div class="input-group">
                <input type="email" name="correo" placeholder="Correo Electrónico" value="<?php echo e($_POST['correo'] ?? ''); ?>" required>
            </div>
            <button type="submit" class="btn-submit">Enviar enlace</button>
        </form>
    <?php endif; ?>

    <div class="card-links"><a href="inicio_sesion.html">Volver a iniciar sesión</a></div>
</div>
</body>
</html>
