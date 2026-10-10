<?php
require 'conexion.php';
require 'encabezado.php';

requerir_rol(['deportista']);

$id = (int) $_SESSION['usuario_id'];
$stmt = $conexion->prepare(
    "SELECT u.deporte_id, u.categoria_id, d.nombre AS deporte, c.nombre AS categoria
     FROM usuarios u
     JOIN deportes d ON d.id = u.deporte_id
     JOIN categorias c ON c.id = u.categoria_id
     WHERE u.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$yo = $stmt->get_result()->fetch_assoc();

// Solo los horarios de SU deporte y SU categoría
$stmt = $conexion->prepare("SELECT * FROM horarios WHERE deporte_id = ? AND categoria_id = ? ORDER BY orden, id");
$stmt->bind_param('ii', $yo['deporte_id'], $yo['categoria_id']);
$stmt->execute();
$horarios = $stmt->get_result();

render_inicio('Mis horarios');
?>
<div class="pagina" style="max-width:800px;">
    <h2>Mis horarios</h2>
    <p class="subtitulo"><?php echo h($yo['deporte']); ?> · Categoría <?php echo h($yo['categoria']); ?></p>

    <?php if ($horarios->num_rows > 0): ?>
        <?php while ($hr = $horarios->fetch_assoc()): ?>
            <div class="tarjeta-horario">
                <h3><?php echo h($hr['titulo']); ?></h3>
                <?php if ($hr['dato_extra'] !== ''): ?><div class="dato"><?php echo h($hr['dato_extra']); ?></div><?php endif; ?>
                <?php if ($hr['ubicacion'] !== ''): ?><div class="dato"><?php echo h($hr['ubicacion']); ?></div><?php endif; ?>
                <?php if (!empty($hr['descripcion'])): ?><div class="dato" style="margin-top:8px;"><?php echo h($hr['descripcion']); ?></div><?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="vacio">
            Aún no hay horarios publicados para <?php echo h($yo['deporte']); ?> · <?php echo h($yo['categoria']); ?>.<br>
            Vuelve a revisar más adelante.
        </div>
    <?php endif; ?>
</div>
<?php render_fin(); ?>
