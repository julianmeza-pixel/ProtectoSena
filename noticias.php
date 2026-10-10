<?php
require 'conexion.php';
require 'encabezado.php';

// Blog de noticias: lo leen los tres roles; solo el administrador lo edita (admin_noticias.php)
$rol = requerir_rol(['admin', 'profesor', 'deportista']);

$noticias = $conexion->query(
    "SELECT n.*, a.nombre AS autor
     FROM noticias n
     LEFT JOIN admins a ON a.id = n.admin_id
     ORDER BY n.fecha_publicacion DESC, n.id DESC"
);

render_inicio('Noticias');
?>
<div class="pagina" style="max-width:780px;">
    <h2>Noticias</h2>
    <p class="subtitulo">Resultados, novedades y avisos de la escuela deportiva.</p>

    <?php if ($rol === 'admin'): ?>
        <p><a class="btn" href="admin_noticias.php">Publicar o editar noticias</a></p>
    <?php endif; ?>

    <?php if ($noticias->num_rows > 0): ?>
        <?php while ($n = $noticias->fetch_assoc()): ?>
            <article class="entrada">
                <?php if (!empty($n['imagen'])): ?>
                    <img src="<?php echo h($n['imagen']); ?>" alt="">
                <?php endif; ?>
                <div class="cuerpo">
                    <h3><?php echo h($n['titulo']); ?></h3>
                    <div class="meta">
                        <?php echo h(date('d/m/Y', strtotime($n['fecha_publicacion']))); ?>
                        <?php if (!empty($n['autor'])): ?> · Por <?php echo h($n['autor']); ?><?php endif; ?>
                        <?php if (!empty($n['fecha_actualizacion'])): ?> · Actualizada el <?php echo h(date('d/m/Y', strtotime($n['fecha_actualizacion']))); ?><?php endif; ?>
                    </div>
                    <div class="texto"><?php echo h($n['contenido']); ?></div>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="vacio">Aún no hay noticias publicadas.</p>
    <?php endif; ?>
</div>
<?php render_fin(); ?>
