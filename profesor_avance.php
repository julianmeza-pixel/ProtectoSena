<?php
require 'conexion.php';
require 'encabezado.php';

// El profesor registra el avance de cada deportista; el deportista lo ve en "Mi deporte"
requerir_rol(['profesor']);
$profesor_id = (int) $_SESSION['profesor_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = (int) ($_POST['usuario_id'] ?? 0);
    $fecha      = $_POST['fecha'] ?? date('Y-m-d');
    $nivel      = trim($_POST['nivel'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');

    if ($usuario_id > 0) {
        $stmt = $conexion->prepare("INSERT INTO avances (usuario_id, fecha, nivel, comentario, profesor_id) VALUES (?,?,?,?,?)");
        $stmt->bind_param('isssi', $usuario_id, $fecha, $nivel, $comentario, $profesor_id);
        $stmt->execute();
    }
    header('Location: profesor_avance.php');
    exit;
}

// Solo deportistas con cuenta activa (aprobados)
$deportistas = $conexion->query(
    "SELECT u.id, CONCAT(u.nombre, ' ', u.apellido) AS nombre, d.nombre AS deporte, c.nombre AS categoria
     FROM usuarios u
     JOIN deportes d ON d.id = u.deporte_id
     JOIN categorias c ON c.id = u.categoria_id
     WHERE u.estado = 'aprobado'
     ORDER BY d.nombre, c.edad_min, u.nombre"
)->fetch_all(MYSQLI_ASSOC);

$avances = $conexion->query(
    "SELECT a.*, CONCAT(u.nombre, ' ', u.apellido) AS deportista, d.nombre AS deporte
     FROM avances a
     JOIN usuarios u ON u.id = a.usuario_id
     JOIN deportes d ON d.id = u.deporte_id
     ORDER BY a.fecha DESC, a.id DESC LIMIT 50"
);

render_inicio('Avance deportivo');
?>
<div class="pagina">
    <h2>Avance deportivo</h2>
    <p class="subtitulo">Registra el progreso de cada deportista.</p>

    <div class="card">
        <h3>Registrar avance</h3>
        <form method="POST">
            <label>Deportista</label>
            <select name="usuario_id" required>
                <option value="" disabled selected>Selecciona</option>
                <?php foreach ($deportistas as $d): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo h($d['nombre'] . ' — ' . $d['deporte'] . ' · ' . $d['categoria']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Fecha</label>
            <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>

            <label>Nivel</label>
            <input type="text" name="nivel" placeholder="Ej. Básico, Intermedio, Avanzado">

            <label>Comentario</label>
            <textarea name="comentario" rows="3"></textarea>

            <button type="submit" class="btn">Guardar avance</button>
        </form>
    </div>

    <table>
        <tr><th>Fecha</th><th>Deportista</th><th>Deporte</th><th>Nivel</th><th>Comentario</th></tr>
        <?php while ($a = $avances->fetch_assoc()): ?>
        <tr>
            <td><?php echo h($a['fecha']); ?></td>
            <td><?php echo h($a['deportista']); ?></td>
            <td><?php echo h($a['deporte']); ?></td>
            <td><?php echo h($a['nivel']); ?></td>
            <td><?php echo h($a['comentario']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php render_fin(); ?>
