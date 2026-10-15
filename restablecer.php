<?php
// restablecer.php - formulario para elegir la nueva contraseña a partir del enlace del correo
require 'conexion.php';
require 'cuentas_recuperacion.php';

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$hash  = hash('sha256', (string) $token);

$stmt = $conexion->prepare("SELECT id, rol, correo FROM recuperacion_password WHERE token_hash = ? AND expira > NOW() LIMIT 1");
$stmt->bind_param('s', $hash);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();

$error = '';
$listo = false;

if ($registro && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password  = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';
    if (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $tabla = TABLAS_RECUPERACION[$registro['rol']];
        $nuevo = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("UPDATE $tabla SET password = ? WHERE correo = ?");
        $stmt->bind_param('ss', $nuevo, $registro['correo']);
        $stmt->execute();

        // El enlace sirve una sola vez
        $stmt = $conexion->prepare("DELETE FROM recuperacion_password WHERE rol = ? AND correo = ?");
        $stmt->bind_param('ss', $registro['rol'], $registro['correo']);
        $stmt->execute();
        $listo = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Sport Integration - Nueva contraseña</title>
    <link rel="stylesheet" href="home_proyecto.css">
</head>
<body class="auth">
<div class="card">
    <img src="uploads/logo%20plataforma.PNG" alt="Match Sport Integration" class="logo-auth">
    <div class="brand-title">Match Sport Integration</div>
    <div class="section-title">Nueva contraseña</div>

    <?php if ($listo): ?>
        <div class="aviso ok" style="text-align:left;">Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.</div>
        <a href="inicio_sesion.html" class="btn-submit" style="display:block;text-decoration:none;">Iniciar sesión</a>
    <?php elseif (!$registro): ?>
        <div class="aviso error" style="text-align:left;">El enlace no es válido o ya venció. Solicita uno nuevo.</div>
        <div class="card-links"><a href="recuperar.php">Solicitar otro enlace</a></div>
    <?php else: ?>
        <div class="section-desc">Elige una contraseña nueva para <?php echo e($registro['correo']); ?>.</div>
        <?php if ($error): ?><div class="aviso error" style="text-align:left;"><?php echo e($error); ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo e($token); ?>">
            <div class="input-group"><input type="password" name="password" placeholder="Nueva contraseña (mín. 6 caracteres)" minlength="6" required></div>
            <div class="input-group"><input type="password" name="confirmar_password" placeholder="Confirmar contraseña" minlength="6" required></div>
            <button type="submit" class="btn-submit">Guardar contraseña</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
