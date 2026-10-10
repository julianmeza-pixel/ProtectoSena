<?php
// superadmin.php - acceso exclusivo del super administrador: crea y elimina administradores.
require 'auth.php';
require 'conexion.php';

$error = '';
$exito = '';
$es_super = rol_actual() === 'superadmin';

// ---------- Inicio de sesión del super administrador ----------
if (!$es_super && ($_POST['accion'] ?? '') === 'login') {
    $correo = trim($_POST['correo'] ?? '');
    $stmt = $conexion->prepare("SELECT * FROM superadmins WHERE correo = ? LIMIT 1");
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $sa = $stmt->get_result()->fetch_assoc();

    if ($sa && password_verify($_POST['password'] ?? '', $sa['password'])) {
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['superadmin_id']     = $sa['id'];
        $_SESSION['superadmin_nombre'] = $sa['nombre'];
        header('Location: superadmin.php');
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}

// ---------- Acciones (solo con sesión de super administrador) ----------
if ($es_super && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre    = trim($_POST['nombre'] ?? '');
        $correo    = trim($_POST['correo'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar_password'] ?? '';

        if ($nombre === '' || $correo === '' || $password === '') {
            $error = 'Todos los campos son obligatorios.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo no tiene un formato válido.';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($password !== $confirmar) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $stmt = $conexion->prepare("SELECT id FROM admins WHERE correo = ?");
            $stmt->bind_param('s', $correo);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Ya existe un administrador con ese correo.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("INSERT INTO admins (correo, nombre, password) VALUES (?,?,?)");
                $stmt->bind_param('sss', $correo, $nombre, $hash);
                $stmt->execute();
                $exito = "Administrador $nombre creado correctamente.";
            }
        }
    } elseif ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conexion->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $exito = 'Administrador eliminado.';
    } elseif ($accion === 'cambiar_password') {
        $nueva     = $_POST['nueva_password'] ?? '';
        $confirmar = $_POST['confirmar_nueva'] ?? '';
        if (strlen($nueva) < 6 || $nueva !== $confirmar) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres y coincidir con la confirmación.';
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $id = (int) $_SESSION['superadmin_id'];
            $stmt = $conexion->prepare("UPDATE superadmins SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $id);
            $stmt->execute();
            $exito = 'Tu contraseña fue actualizada.';
        }
    }
}

$admins = $es_super ? $conexion->query("SELECT id, nombre, correo FROM admins ORDER BY id")->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super administrador - Match Sport Integration</title>
    <link rel="stylesheet" href="home_proyecto.css">
</head>
<body class="auth">

<?php if (!$es_super): ?>
    <div class="card">
        <h1>Acceso super administrador</h1>
        <?php if ($error): ?><div class="error"><?php echo h($error); ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="accion" value="login">
            <label>Correo</label>
            <input type="email" name="correo" required>
            <label>Contraseña</label>
            <input type="password" name="password" required>
            <button type="submit" class="btn-submit">Entrar</button>
        </form>
    </div>

<?php else: ?>
    <div class="card">
        <div class="top">
            <h1><?php echo h($_SESSION['superadmin_nombre']); ?></h1>
            <a href="logout.php">Cerrar sesión</a>
        </div>
        <?php if ($error): ?><div class="error"><?php echo h($error); ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="exito"><?php echo h($exito); ?></div><?php endif; ?>

        <h2>Crear administrador</h2>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="accion" value="crear">
            <label>Nombre completo</label>
            <input type="text" name="nombre" required>
            <label>Correo</label>
            <input type="email" name="correo" required>
            <label>Contraseña (mínimo 6 caracteres)</label>
            <input type="password" name="password" minlength="6" required>
            <label>Confirmar contraseña</label>
            <input type="password" name="confirmar_password" minlength="6" required>
            <button type="submit">Crear administrador</button>
        </form>
    </div>

    <div class="card">
        <h2>Administradores actuales</h2>
        <table>
            <?php foreach ($admins as $a): ?>
            <tr>
                <td><strong><?php echo h($a['nombre']); ?></strong><br><?php echo h($a['correo']); ?></td>
                <td style="text-align:right;">
                    <form method="POST" onsubmit="return confirm('¿Eliminar a este administrador?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                        <button type="submit" class="rojo">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$admins): ?><tr><td>No hay administradores.</td></tr><?php endif; ?>
        </table>
    </div>

    <div class="card">
        <h2>Cambiar mi contraseña</h2>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="accion" value="cambiar_password">
            <label>Nueva contraseña</label>
            <input type="password" name="nueva_password" minlength="6" required>
            <label>Confirmar nueva contraseña</label>
            <input type="password" name="confirmar_nueva" minlength="6" required>
            <button type="submit">Actualizar contraseña</button>
        </form>
    </div>
<?php endif; ?>

</body>
</html>
