<?php
require 'conexion.php';
require 'encabezado.php';

// Solo el rol administrativo crea, edita o elimina noticias
requerir_rol(['admin']);
$admin_id = (int) $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    if (($_POST['accion'] ?? '') === 'eliminar') {
        // Eliminar solo por POST (evita borrados por un simple enlace)
        $stmt = $conexion->prepare("DELETE FROM noticias WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    } else {
        $titulo    = trim($_POST['titulo'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $imagen    = trim($_POST['imagen'] ?? '');

        if ($titulo !== '' && $contenido !== '') {
            if ($id > 0) {
                $stmt = $conexion->prepare("UPDATE noticias SET titulo=?, contenido=?, imagen=? WHERE id=?");
                $stmt->bind_param('sssi', $titulo, $contenido, $imagen, $id);
            } else {
                $stmt = $conexion->prepare("INSERT INTO noticias (titulo, contenido, imagen, admin_id) VALUES (?,?,?,?)");
                $stmt->bind_param('sssi', $titulo, $contenido, $imagen, $admin_id);
            }
            $stmt->execute();
        }
    }
    header('Location: admin_noticias.php');
    exit;
}

$editando = ['id' => 0, 'titulo' => '', 'contenido' => '', 'imagen' => ''];
if (isset($_GET['editar'])) {
    $editar_id = (int) $_GET['editar'];
    $stmt = $conexion->prepare("SELECT * FROM noticias WHERE id=?");
    $stmt->bind_param('i', $editar_id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    if ($fila) $editando = $fila;
}

$noticias = $conexion->query("SELECT id, titulo, fecha_publicacion FROM noticias ORDER BY fecha_publicacion DESC, id DESC");

render_inicio('Administrar noticias');
?>
<div class="pagina">
    <h2>Administrar noticias</h2>
    <p class="subtitulo">Publica y edita las novedades de la escuela.</p>

    <div class="card">
        <h3><?php echo $editando['id'] > 0 ? 'Editar noticia' : 'Nueva noticia'; ?></h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo (int) $editando['id']; ?>">

            <label>Título</label>
            <input type="text" name="titulo" value="<?php echo h($editando['titulo']); ?>" placeholder="Ej. Conoce los ganadores del partido de fútbol categoría Juvenil" required maxlength="200">

            <label>Contenido</label>
            <textarea name="contenido" required><?php echo h($editando['contenido']); ?></textarea>

            <label>Imagen (ruta o URL, opcional)</label>
            <input type="text" name="imagen" value="<?php echo h($editando['imagen']); ?>" placeholder="https://...">

            <button type="submit" class="btn"><?php echo $editando['id'] > 0 ? 'Guardar cambios' : 'Publicar'; ?></button>
            <?php if ($editando['id'] > 0): ?>
                <a href="admin_noticias.php" class="btn btn-secundario">Cancelar edición</a>
            <?php endif; ?>
        </form>
    </div>

    <table>
        <tr><th>Título</th><th>Publicada</th><th>Acciones</th></tr>
        <?php while ($n = $noticias->fetch_assoc()): ?>
        <tr>
            <td><?php echo h($n['titulo']); ?></td>
            <td><?php echo h(date('d/m/Y', strtotime($n['fecha_publicacion']))); ?></td>
            <td class="acciones">
                <a href="admin_noticias.php?editar=<?php echo $n['id']; ?>">Editar</a>
                <form method="POST" style="display:inline; margin-left:12px;" onsubmit="return confirm('¿Eliminar esta noticia?');">
                    <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                    <input type="hidden" name="accion" value="eliminar">
                    <button type="submit" class="btn btn-rojo btn-sm">Eliminar</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php render_fin(); ?>
