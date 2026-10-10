<?php
// admin_editar_profesor.php - el administrador edita o elimina una cuenta de profesor
require 'encabezado.php';
require 'conexion.php';

requerir_rol(['admin']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $conexion->prepare("SELECT id, nombre, correo FROM profesores WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$prof = $stmt->get_result()->fetch_assoc();
if (!$prof) {
    $_SESSION['flash'] = ['tipo' => 'error', 'texto' => 'El profesor no existe.'];
    header('Location: admin_usuarios.php?tab=profesores');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['accion'] ?? '') === 'eliminar') {
        $stmt = $conexion->prepare("DELETE FROM profesores WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['flash'] = ['tipo' => 'ok', 'texto' => 'Profesor eliminado.'];
        header('Location: admin_usuarios.php?tab=profesores');
        exit;
    }

    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        $stmt = $conexion->prepare("SELECT id FROM profesores WHERE correo = ? AND id <> ?");
        $stmt->bind_param('si', $correo, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Ya existe otro profesor con ese correo.';
        } else {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("UPDATE profesores SET nombre = ?, correo = ?, password = ? WHERE id = ?");
                $stmt->bind_param('sssi', $nombre, $correo, $hash, $id);
            } else {
                $stmt = $conexion->prepare("UPDATE profesores SET nombre = ?, correo = ? WHERE id = ?");
                $stmt->bind_param('ssi', $nombre, $correo, $id);
            }
            $stmt->execute();
            $_SESSION['flash'] = ['tipo' => 'ok', 'texto' => 'Profesor actualizado.'];
            header('Location: admin_usuarios.php?tab=profesores');
            exit;
        }
    }
    $prof['nombre'] = $nombre;
    $prof['correo'] = $correo;
}

render_inicio('Editar profesor');
?>
<div class="pagina" style="max-width:520px;">
    <h2>Editar profesor</h2>
    <p class="subtitulo">Datos de acceso del profesor.</p>
    <?php if ($error): ?><div class="aviso error"><?php echo h($error); ?></div><?php endif; ?>
    <form method="POST" class="card">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <label>Nombre completo</label>
        <input type="text" name="nombre" value="<?php echo h($prof['nombre']); ?>" required>
        <label>Correo</label>
        <input type="email" name="correo" value="<?php echo h($prof['correo']); ?>" required>
        <label>Nueva contraseña (dejar vacío para no cambiarla)</label>
        <input type="password" name="password" minlength="6" autocomplete="new-password">
        <div style="margin-top:20px;">
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secundario" href="admin_usuarios.php?tab=profesores">Cancelar</a>
        </div>
    </form>
    <form method="POST" style="margin-top:15px;" onsubmit="return confirm('¿Eliminar este profesor? Esta acción no se puede deshacer.');">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="accion" value="eliminar">
        <button class="btn btn-rojo" type="submit">Eliminar profesor</button>
    </form>
</div>
<?php render_fin(); ?>
