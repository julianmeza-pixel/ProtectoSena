<?php
require 'encabezado.php';
require 'conexion.php';

// Solo un administrador puede crear cuentas de profesor
requerir_rol(['admin']);

$error = '';
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';

    if ($nombre === '' || $correo === '' || $password === '' || $confirmar === '') {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $nombre_esc = $conexion->real_escape_string($nombre);
        $correo_esc = $conexion->real_escape_string($correo);

        $res = $conexion->query("SELECT id FROM profesores WHERE correo = '$correo_esc'");
        if ($res && $res->num_rows > 0) {
            $error = 'Ya existe un profesor con ese correo.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $hash_esc = $conexion->real_escape_string($hash);
            $conexion->query("INSERT INTO profesores (correo, nombre, password) VALUES ('$correo_esc', '$nombre_esc', '$hash_esc')");
            $exito = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Sport Integration - Crear Profesor</title>
    <link rel="stylesheet" href="home_proyecto.css">
</head>
<body>
<?php render_barra_menu(); ?>
<div class="pagina" style="max-width:520px;">
    <h2>Crear profesor</h2>
    <p class="subtitulo">Registra una cuenta para un profesor de la escuela deportiva.</p>

    <?php if ($exito): ?>
        <div class="aviso ok">Profesor creado correctamente.</div>
        <p><a class="btn" href="crear_profesor.php">Crear otro profesor</a> <a class="btn btn-secundario" href="admin_usuarios.php?tab=profesores">Ver profesores</a></p>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="aviso error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="card">
            <label>Nombre completo</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>

            <label>Correo del profesor</label>
            <input type="email" name="correo" value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>" placeholder="nombre.apellido@sportlink.com" required>

            <label>Contraseña</label>
            <input type="password" name="password" required minlength="6">

            <label>Confirmar contraseña</label>
            <input type="password" name="confirmar_password" required minlength="6">

            <button type="submit">Crear profesor</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>