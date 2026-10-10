<?php
require 'encabezado.php';
require 'conexion.php';

// Lo editan administradores y profesores; lo ven los deportistas en horarios_categoria.php
$rol = requerir_rol(['admin', 'profesor']);
$inicio = $rol === 'admin' ? 'home_admin.php' : 'home_profesor.php';

// Deportes y categorías salen de sus tablas (así siempre hay una relación válida)
$deportes_disponibles = $conexion->query("SELECT id, nombre FROM deportes ORDER BY orden, id")->fetch_all(MYSQLI_ASSOC);
$categorias = $conexion->query("SELECT id, nombre FROM categorias ORDER BY edad_min")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = (int) ($_POST['id'] ?? 0);
    $deporte_id   = (int) ($_POST['deporte_id'] ?? 0);
    $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
    $titulo       = trim($_POST['titulo'] ?? '');
    $ubicacion    = trim($_POST['ubicacion'] ?? '');
    $dato_extra   = trim($_POST['dato_extra'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');

    if ($id > 0) {
        $stmt = $conexion->prepare("UPDATE horarios SET deporte_id=?, categoria_id=?, titulo=?, ubicacion=?, dato_extra=?, descripcion=? WHERE id=?");
        $stmt->bind_param('iissssi', $deporte_id, $categoria_id, $titulo, $ubicacion, $dato_extra, $descripcion, $id);
    } else {
        $stmt = $conexion->prepare("INSERT INTO horarios (deporte_id, categoria_id, titulo, ubicacion, dato_extra, descripcion, orden) VALUES (?,?,?,?,?,?, 99)");
        $stmt->bind_param('iissss', $deporte_id, $categoria_id, $titulo, $ubicacion, $dato_extra, $descripcion);
    }
    $stmt->execute();
    header('Location: admin_horarios.php');
    exit;
}

if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    $conexion->query("DELETE FROM horarios WHERE id=$id");
    header('Location: admin_horarios.php');
    exit;
}

$editando = ['id' => 0, 'deporte_id' => $deportes_disponibles[0]['id'] ?? 0, 'categoria_id' => $categorias[0]['id'] ?? 0,
             'titulo' => '', 'ubicacion' => '', 'dato_extra' => '', 'descripcion' => ''];
if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $res = $conexion->query("SELECT * FROM horarios WHERE id=$id");
    if ($res && $res->num_rows > 0) $editando = $res->fetch_assoc();
}

// Filtro opcional de la lista por deporte
$filtro_deporte = (int) ($_GET['filtro_deporte'] ?? 0);
$where = $filtro_deporte > 0 ? "WHERE h.deporte_id = $filtro_deporte" : '';
$horarios = $conexion->query(
    "SELECT h.*, d.nombre AS deporte, c.nombre AS categoria
     FROM horarios h
     JOIN deportes d ON d.id = h.deporte_id
     JOIN categorias c ON c.id = h.categoria_id
     $where ORDER BY d.nombre, c.edad_min, h.orden, h.id"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrar Horarios</title>
    <link rel="stylesheet" href="home_proyecto.css">
</head>
<body>
    <?php render_barra_menu(); ?>

    <div class="contenido">
        <h2>Horarios</h2>
        <p class="subtitulo">Publica los horarios y avisos por deporte y categoría.</p>
        <div class="card">
            <h3><?php echo $editando['id'] > 0 ? 'Editar horario' : 'Agregar nuevo horario/aviso'; ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo (int) $editando['id']; ?>">

                <div class="fila-form">
                    <div>
                        <label>Deporte</label>
                        <select name="deporte_id" required>
                            <?php foreach ($deportes_disponibles as $dep): ?>
                                <option value="<?php echo $dep['id']; ?>" <?php echo (int) $editando['deporte_id'] === (int) $dep['id'] ? 'selected' : ''; ?>><?php echo h($dep['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Categoría</label>
                        <select name="categoria_id" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (int) $editando['categoria_id'] === (int) $cat['id'] ? 'selected' : ''; ?>><?php echo h($cat['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label>Título</label>
                <input type="text" name="titulo" value="<?php echo h($editando['titulo']); ?>" placeholder="Ej. Entrenamiento de Fútbol" required>

                <label>Ubicación</label>
                <input type="text" name="ubicacion" value="<?php echo h($editando['ubicacion']); ?>" placeholder="Ej. Cancha principal">

                <label>Horario / dato extra</label>
                <input type="text" name="dato_extra" value="<?php echo h($editando['dato_extra']); ?>" placeholder="Ej. Lunes y miércoles 4:00pm-6:00pm">

                <label>Descripción</label>
                <textarea name="descripcion"><?php echo h($editando['descripcion']); ?></textarea>

                <button type="submit"><?php echo $editando['id'] > 0 ? 'Guardar cambios' : 'Agregar'; ?></button>
                <?php if ($editando['id'] > 0): ?>
                    <a href="admin_horarios.php" class="btn btn-secundario">Cancelar edición</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="filtros">
            <a href="admin_horarios.php" class="<?php echo $filtro_deporte === 0 ? 'activo' : ''; ?>">Todos</a>
            <?php foreach ($deportes_disponibles as $dep): ?>
                <a href="?filtro_deporte=<?php echo $dep['id']; ?>" class="<?php echo $filtro_deporte === (int) $dep['id'] ? 'activo' : ''; ?>"><?php echo h($dep['nombre']); ?></a>
            <?php endforeach; ?>
        </div>

        <table>
            <tr><th>Deporte</th><th>Categoría</th><th>Título</th><th>Ubicación</th><th>Horario</th><th>Acciones</th></tr>
            <?php while ($hr = $horarios->fetch_assoc()): ?>
            <tr>
                <td><span class="etiqueta"><?php echo h($hr['deporte']); ?></span></td>
                <td><?php echo h($hr['categoria']); ?></td>
                <td><?php echo h($hr['titulo']); ?></td>
                <td><?php echo h($hr['ubicacion']); ?></td>
                <td><?php echo h($hr['dato_extra']); ?></td>
                <td class="acciones">
                    <a class="editar" href="admin_horarios.php?editar=<?php echo $hr['id']; ?>">Editar</a>
                    <a class="eliminar" href="admin_horarios.php?eliminar=<?php echo $hr['id']; ?>" onclick="return confirm('¿Eliminar este horario?');">Eliminar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
