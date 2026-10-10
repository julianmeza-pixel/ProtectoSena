<?php
require 'conexion.php';
require 'encabezado.php';

requerir_rol(['deportista']);

// Solo el deporte y la categoría del propio deportista (por su id de sesión)
$id = (int) $_SESSION['usuario_id'];
$stmt = $conexion->prepare(
    "SELECT d.nombre, d.descripcion, d.imagen, c.nombre AS categoria
     FROM usuarios u
     JOIN deportes d ON d.id = u.deporte_id
     JOIN categorias c ON c.id = u.categoria_id
     WHERE u.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$deporte = $stmt->get_result()->fetch_assoc();

$stmt = $conexion->prepare("SELECT fecha, nivel, comentario FROM avances WHERE usuario_id = ? ORDER BY fecha DESC, id DESC");
$stmt->bind_param('i', $id);
$stmt->execute();
$avances = $stmt->get_result();

render_inicio('Mi deporte');
?>
<div class="pagina" style="max-width:700px;">
    <h2>Mi deporte</h2>
    <p class="subtitulo">Tu disciplina y tu avance.</p>

    <div class="tarjeta-deporte">
        <?php if (!empty($deporte['imagen'])): ?>
            <img src="<?php echo h($deporte['imagen']); ?>" alt="<?php echo h($deporte['nombre']); ?>">
        <?php endif; ?>
        <div class="info">
            <h1><?php echo h($deporte['nombre']); ?></h1>
            <span class="etiqueta-categoria">Categoría: <?php echo h($deporte['categoria']); ?></span>
            <p class="descripcion"><?php echo h($deporte['descripcion']); ?></p>
            <a href="horarios_categoria.php" class="btn-horarios">Ver mis horarios</a>
        </div>
    </div>

    <?php if ($avances->num_rows > 0): ?>
        <h3 style="margin-top:32px;">Mi avance deportivo</h3>
        <?php while ($a = $avances->fetch_assoc()): ?>
            <div class="item-avance">
                <strong><?php echo h($a['fecha']); ?></strong>
                <?php if ($a['nivel'] !== ''): ?> · Nivel: <?php echo h($a['nivel']); ?><?php endif; ?>
                <div class="comentario"><?php echo h($a['comentario']); ?></div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
<?php render_fin(); ?>
