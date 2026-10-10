<?php
require 'encabezado.php';
require 'conexion.php';

requerir_rol(['admin']);

$mensaje = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Guardar (crear o actualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int) ($_POST['id'] ?? 0);
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $imagen      = trim($_POST['imagen'] ?? '');

    try {
        if ($id > 0) {
            $stmt = $conexion->prepare("UPDATE deportes SET nombre=?, descripcion=?, imagen=? WHERE id=?");
            $stmt->bind_param('sssi', $nombre, $descripcion, $imagen, $id);
        } else {
            $stmt = $conexion->prepare("INSERT INTO deportes (nombre, descripcion, imagen, orden) VALUES (?,?,?, 99)");
            $stmt->bind_param('sss', $nombre, $descripcion, $imagen);
        }
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        // nombre repetido (índice único)
        $_SESSION['flash'] = ['tipo' => 'error', 'texto' => 'Ya existe un deporte con ese nombre.'];
    }
    header('Location: admin_deportes.php');
    exit;
}

// Eliminar: la llave foránea impide borrar un deporte que tenga deportistas inscritos
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    try {
        $conexion->query("DELETE FROM deportes WHERE id=$id");
    } catch (mysqli_sql_exception $e) {
        $_SESSION['flash'] = ['tipo' => 'error', 'texto' => 'No se puede eliminar: hay deportistas inscritos en ese deporte.'];
    }
    header('Location: admin_deportes.php');
    exit;
}

// Cargar datos si se va a editar
$editando = ['id' => 0, 'nombre' => '', 'descripcion' => '', 'imagen' => ''];
if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $res = $conexion->query("SELECT * FROM deportes WHERE id=$id");
    if ($res && $res->num_rows > 0) $editando = $res->fetch_assoc();
}

$deportes = $conexion->query("SELECT * FROM deportes ORDER BY orden, id");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrar Deportes</title>
    <link rel="stylesheet" href="home_proyecto.css">
</head>
<body>
    <?php render_barra_menu(); ?>

    <div class="contenido">
        <h2>Deportes</h2>
        <p class="subtitulo">Administra los deportes que ofrece la escuela.</p>
        <?php if ($mensaje): ?>
            <div class="aviso error"><?php echo h($mensaje['texto']); ?></div>
        <?php endif; ?>
        <div class="card">
            <h3><?php echo $editando['id'] > 0 ? 'Editar deporte' : 'Agregar nuevo deporte'; ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $editando['id']; ?>">
                <label>Nombre del deporte</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($editando['nombre']); ?>" required>

                <label>Descripción</label>
                <textarea name="descripcion" required><?php echo htmlspecialchars($editando['descripcion']); ?></textarea>

                <label>Ruta o URL de la imagen</label>
                <input type="text" name="imagen" value="<?php echo htmlspecialchars($editando['imagen']); ?>" placeholder="img/futbol2.jpg" required>

                <button type="submit"><?php echo $editando['id'] > 0 ? 'Guardar cambios' : 'Agregar deporte'; ?></button>
                <?php if ($editando['id'] > 0): ?>
                    <a href="admin_deportes.php" class="btn btn-secundario">Cancelar edición</a>
                <?php endif; ?>
            </form>
        </div>

        <table>
            <tr><th>Imagen</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr>
            <?php while ($d = $deportes->fetch_assoc()): ?>
            <tr>
                <td><img class="miniatura" src="<?php echo htmlspecialchars($d['imagen']); ?>" alt=""></td>
                <td><?php echo htmlspecialchars($d['nombre']); ?></td>
                <td><?php echo htmlspecialchars(mb_strimwidth($d['descripcion'], 0, 80, '...')); ?></td>
                <td class="acciones">
                    <a class="editar" href="admin_deportes.php?editar=<?php echo $d['id']; ?>">Editar</a>
                    <a class="eliminar" href="admin_deportes.php?eliminar=<?php echo $d['id']; ?>" onclick="return confirm('¿Eliminar este deporte?');">Eliminar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
