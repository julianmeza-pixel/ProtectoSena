<?php
// admin_usuarios.php - el administrador ve todos los deportistas y profesores y entra a editarlos
require 'encabezado.php';
require 'conexion.php';

requerir_rol(['admin']);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$tab = ($_GET['tab'] ?? 'deportistas') === 'profesores' ? 'profesores' : 'deportistas';
$q   = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

if ($tab === 'deportistas') {
    $stmt = $conexion->prepare(
        "SELECT u.id, u.nombre, u.apellido, u.correo, u.telefono, u.edad, u.estado, d.nombre AS deporte, c.nombre AS categoria
         FROM usuarios u
         LEFT JOIN deportes d ON d.id = u.deporte_id
         LEFT JOIN categorias c ON c.id = u.categoria_id
         WHERE CONCAT(u.nombre, ' ', u.apellido) LIKE ? OR u.correo LIKE ?
         ORDER BY u.nombre, u.apellido"
    );
    $stmt->bind_param('ss', $like, $like);
} else {
    $stmt = $conexion->prepare(
        "SELECT id, nombre, correo FROM profesores
         WHERE nombre LIKE ? OR correo LIKE ? ORDER BY nombre, correo"
    );
    $stmt->bind_param('ss', $like, $like);
}
$stmt->execute();
$filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

render_inicio('Gestionar usuarios');
?>
<div class="pagina" style="max-width:1000px;">
    <h2>Gestionar usuarios</h2>
    <p class="subtitulo">Edita los perfiles de deportistas y profesores.</p>
    <?php if ($flash): ?>
        <div class="aviso <?php echo $flash['tipo'] === 'ok' ? 'ok' : 'error'; ?>"><?php echo h($flash['texto']); ?></div>
    <?php endif; ?>

    <div class="tabs">
        <a href="?tab=deportistas" class="<?php echo $tab === 'deportistas' ? 'activo' : ''; ?>">Deportistas</a>
        <a href="?tab=profesores" class="<?php echo $tab === 'profesores' ? 'activo' : ''; ?>">Profesores</a>
    </div>

    <form class="busca" method="GET">
        <input type="hidden" name="tab" value="<?php echo h($tab); ?>">
        <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Buscar por nombre o correo">
        <button class="btn" type="submit">Buscar</button>
    </form>

    <table>
        <?php if ($tab === 'deportistas'): ?>
            <tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Edad</th><th>Deporte</th><th>Estado</th><th></th></tr>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td><?php echo h($f['nombre'] . ' ' . $f['apellido']); ?></td>
                    <td><?php echo h($f['correo']); ?></td>
                    <td><?php echo h($f['telefono']); ?></td>
                    <td><?php echo h($f['edad']); ?></td>
                    <td><?php echo h($f['deporte']); ?> <small>(<?php echo h($f['categoria']); ?>)</small></td>
                    <td><span class="estado <?php echo h($f['estado']); ?>"><?php echo h(strtoupper($f['estado'])); ?></span></td>
                    <td><a class="btn btn-sm" href="admin_editar_usuario.php?id=<?php echo (int) $f['id']; ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><th>Nombre</th><th>Correo</th><th></th></tr>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td><?php echo h($f['nombre'] !== '' ? $f['nombre'] : '(sin nombre)'); ?></td>
                    <td><?php echo h($f['correo']); ?></td>
                    <td><a class="btn btn-sm" href="admin_editar_profesor.php?id=<?php echo (int) $f['id']; ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!$filas): ?>
            <tr><td colspan="7" class="vacio"><em>No se encontraron resultados.</em></td></tr>
        <?php endif; ?>
    </table>
</div>
<?php render_fin(); ?>
