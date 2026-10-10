<?php
require 'encabezado.php';
require 'conexion.php';

// Lo editan administradores y profesores; lo ven todos en calendario.php
$rol = requerir_rol(['admin', 'profesor']);
$inicio = $rol === 'admin' ? 'home_admin.php' : 'home_profesor.php';

$dias_validos = ['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'];
$colores_validos = ['bg-sky' => 'Celeste', 'bg-orange' => 'Naranja', 'bg-green' => 'Verde', 'bg-yellow' => 'Amarillo', 'bg-pink' => 'Rosa', 'bg-purple' => 'Morado', 'bg-lightred' => 'Rojo'];

$deportes   = $conexion->query("SELECT id, nombre FROM deportes ORDER BY orden, id")->fetch_all(MYSQLI_ASSOC);
$categorias = $conexion->query("SELECT id, nombre FROM categorias ORDER BY edad_min")->fetch_all(MYSQLI_ASSOC);
$profesores = $conexion->query("SELECT id, IF(nombre = '', correo, nombre) AS nombre FROM profesores ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// 0 en el formulario significa "todos" y se guarda como NULL
function id_o_null($valor) {
    $valor = (int) $valor;
    return $valor > 0 ? $valor : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = (int) ($_POST['id'] ?? 0);
    $dia          = in_array($_POST['dia'] ?? '', $dias_validos, true) ? $_POST['dia'] : 'Lunes';
    $hora         = preg_match('/^\d{2}:\d{2}$/', $_POST['hora'] ?? '') ? $_POST['hora'] . ':00' : '00:00:00';
    $materia      = trim($_POST['materia'] ?? '');
    $color        = array_key_exists($_POST['color'] ?? '', $colores_validos) ? $_POST['color'] : 'bg-sky';
    $deporte_id   = id_o_null($_POST['deporte_id'] ?? 0);
    $categoria_id = id_o_null($_POST['categoria_id'] ?? 0);
    $profesor_id  = id_o_null($_POST['profesor_id'] ?? 0);

    if ($id > 0) {
        $stmt = $conexion->prepare("UPDATE calendario_clases SET dia=?, hora=?, materia=?, color=?, deporte_id=?, categoria_id=?, profesor_id=? WHERE id=?");
        $stmt->bind_param('ssssiiii', $dia, $hora, $materia, $color, $deporte_id, $categoria_id, $profesor_id, $id);
    } else {
        $stmt = $conexion->prepare("INSERT INTO calendario_clases (dia, hora, materia, color, deporte_id, categoria_id, profesor_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('ssssiii', $dia, $hora, $materia, $color, $deporte_id, $categoria_id, $profesor_id);
    }
    $stmt->execute();
    header('Location: admin_calendario.php');
    exit;
}

if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    $conexion->query("DELETE FROM calendario_clases WHERE id=$id");
    header('Location: admin_calendario.php');
    exit;
}

$editando = ['id' => 0, 'dia' => 'Lunes', 'hora' => '', 'materia' => '', 'color' => 'bg-sky', 'deporte_id' => null, 'categoria_id' => null, 'profesor_id' => null];
if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $res = $conexion->query("SELECT * FROM calendario_clases WHERE id=$id");
    if ($res && $res->num_rows > 0) $editando = $res->fetch_assoc();
}

$clases = $conexion->query(
    "SELECT cc.*, d.nombre AS deporte, c.nombre AS categoria, IF(p.nombre = '', p.correo, p.nombre) AS profesor
     FROM calendario_clases cc
     LEFT JOIN deportes d ON d.id = cc.deporte_id
     LEFT JOIN categorias c ON c.id = cc.categoria_id
     LEFT JOIN profesores p ON p.id = cc.profesor_id
     ORDER BY FIELD(cc.dia,'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'), cc.hora"
);

// Muestra un <select> con la opción "Todos" (valor 0) + las filas de una tabla
function select_relacion($nombre, $filas, $seleccionado, $texto_todos) {
    echo "<select name=\"$nombre\"><option value=\"0\">" . h($texto_todos) . "</option>";
    foreach ($filas as $f) {
        $sel = (int) $seleccionado === (int) $f['id'] ? 'selected' : '';
        echo "<option value=\"{$f['id']}\" $sel>" . h($f['nombre']) . "</option>";
    }
    echo "</select>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrar Calendario</title>
    <link rel="stylesheet" href="home_proyecto.css">
</head>
<body>
    <?php render_barra_menu(); ?>

    <div class="contenido">
        <h2>Calendario de clases</h2>
        <p class="subtitulo">Agrega, edita o elimina las clases programadas.</p>
        <div class="card">
            <h3><?php echo $editando['id'] > 0 ? 'Editar clase' : 'Agregar nueva clase'; ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo (int) $editando['id']; ?>">
                <div class="fila-form">
                    <div>
                        <label>Día</label>
                        <select name="dia">
                            <?php foreach ($dias_validos as $d): ?>
                                <option value="<?php echo $d; ?>" <?php echo $editando['dia'] === $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Hora</label>
                        <input type="time" name="hora" value="<?php echo h(substr((string) $editando['hora'], 0, 5)); ?>" required>
                    </div>
                    <div>
                        <label>Color</label>
                        <select name="color">
                            <?php foreach ($colores_validos as $clase_css => $nombre): ?>
                                <option value="<?php echo $clase_css; ?>" <?php echo $editando['color'] === $clase_css ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="fila-form">
                    <div>
                        <label>Actividad</label>
                        <input type="text" name="materia" value="<?php echo h($editando['materia']); ?>" placeholder="Ej. Entrenamiento táctico" required>
                    </div>
                    <div>
                        <label>Deporte</label>
                        <?php select_relacion('deporte_id', $deportes, $editando['deporte_id'], 'Todos los deportes'); ?>
                    </div>
                    <div>
                        <label>Categoría</label>
                        <?php select_relacion('categoria_id', $categorias, $editando['categoria_id'], 'Todas las categorías'); ?>
                    </div>
                    <div>
                        <label>Profesor (opcional)</label>
                        <?php select_relacion('profesor_id', $profesores, $editando['profesor_id'], 'Sin asignar'); ?>
                    </div>
                </div>
                <button type="submit"><?php echo $editando['id'] > 0 ? 'Guardar cambios' : 'Agregar'; ?></button>
                <?php if ($editando['id'] > 0): ?>
                    <a href="admin_calendario.php" class="btn btn-secundario">Cancelar edición</a>
                <?php endif; ?>
            </form>
        </div>

        <table>
            <tr><th>Día</th><th>Hora</th><th>Actividad</th><th>Deporte</th><th>Categoría</th><th>Profesor</th><th>Color</th><th>Acciones</th></tr>
            <?php while ($c = $clases->fetch_assoc()): ?>
            <tr>
                <td><?php echo h($c['dia']); ?></td>
                <td><?php echo h(date('h:i a', strtotime($c['hora']))); ?></td>
                <td><?php echo h($c['materia']); ?></td>
                <td><?php echo h($c['deporte'] ?? 'Todos'); ?></td>
                <td><?php echo h($c['categoria'] ?? 'Todas'); ?></td>
                <td><?php echo h($c['profesor'] ?? '—'); ?></td>
                <td><span class="etiqueta-color <?php echo h($c['color']); ?>"><?php echo h($colores_validos[$c['color']] ?? $c['color']); ?></span></td>
                <td class="acciones">
                    <a class="editar" href="admin_calendario.php?editar=<?php echo $c['id']; ?>">Editar</a>
                    <a class="eliminar" href="admin_calendario.php?eliminar=<?php echo $c['id']; ?>" onclick="return confirm('¿Eliminar esta clase?');">Eliminar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
