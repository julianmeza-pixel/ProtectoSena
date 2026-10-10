<?php
require 'encabezado.php';
require 'conexion.php';

requerir_rol(['admin']);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$filtro = $_GET['estado'] ?? 'pendiente'; // pendiente | aprobado | rechazado | todos
if (!in_array($filtro, ['pendiente', 'aprobado', 'rechazado', 'todos'], true)) {
    $filtro = 'pendiente';
}
$where = $filtro === 'todos' ? '' : "WHERE u.estado = '$filtro'";

$usuarios = $conexion->query(
    "SELECT u.*, d.nombre AS deporte, c.nombre AS categoria
     FROM usuarios u
     JOIN deportes d ON d.id = u.deporte_id
     JOIN categorias c ON c.id = u.categoria_id
     $where ORDER BY u.fecha_registro DESC"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="home_proyecto.css">
</head>
<body>
    <?php render_barra_menu(); ?>

    <div class="contenido">
        <h2>Aprobación de alumnos</h2>
        <p class="subtitulo">Revisa las inscripciones y apruébalas o recházalas.</p>
        <?php if ($flash): ?>
            <div class="aviso <?php echo $flash['tipo'] === 'ok' ? 'ok' : 'info'; ?>">
                <?php echo h($flash['texto']); ?>
            </div>
        <?php endif; ?>
        <div class="filtros">
            <a href="?estado=pendiente" class="<?php echo $filtro==='pendiente'?'activo':''; ?>">Pendientes</a>
            <a href="?estado=aprobado" class="<?php echo $filtro==='aprobado'?'activo':''; ?>">Aprobados</a>
            <a href="?estado=rechazado" class="<?php echo $filtro==='rechazado'?'activo':''; ?>">Rechazados</a>
            <a href="?estado=todos" class="<?php echo $filtro==='todos'?'activo':''; ?>">Todos</a>
        </div>

        <?php if ($usuarios && $usuarios->num_rows > 0): ?>
            <?php while ($u = $usuarios->fetch_assoc()): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?>
                        <span class="estado <?php echo $u['estado']; ?>"><?php echo strtoupper($u['estado']); ?></span>
                    </h3>
                    <div class="datos">
                         <?php echo htmlspecialchars($u['correo']); ?> &nbsp;|&nbsp;
                         <?php echo htmlspecialchars($u['telefono']); ?> &nbsp;|&nbsp;
                         <?php echo htmlspecialchars($u['deporte']); ?> &nbsp;|&nbsp;
                         Categoría: <?php echo htmlspecialchars($u['categoria']); ?> (<?php echo (int) $u['edad']; ?> años)<br>
                         Registrado: <?php echo $u['fecha_registro']; ?>
                    </div>

                    <div class="documentos">
                        <?php
                        $docs = $conexion->query("SELECT * FROM documentos WHERE usuario_id = " . (int)$u['id']);
                        if ($docs && $docs->num_rows > 0):
                            while ($d = $docs->fetch_assoc()):
                        ?>
                            <a href="<?php echo htmlspecialchars($d['ruta_archivo']); ?>" target="_blank">
                                📄 <?php echo htmlspecialchars($d['tipo']); ?>
                            </a>
                        <?php
                            endwhile;
                        else:
                            echo '<small>Sin documentos adjuntos</small>';
                        endif;
                        ?>
                    </div>

                    <?php if ($u['estado'] === 'pendiente'): ?>
                        <div class="acciones">
                            <form action="procesar_admin.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <input type="hidden" name="accion" value="aprobar">
                                <button type="submit" class="btn btn-aprobar">✔ Aprobar</button>
                            </form>
                            <form action="procesar_admin.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <input type="hidden" name="accion" value="rechazar">
                                <button type="submit" class="btn btn-rechazar" onclick="return confirm('¿Rechazar esta inscripción? Se enviará el correo al aspirante.');">✘ Rechazar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No hay solicitudes en este filtro.</p>
        <?php endif; ?>
    </div>
</body>
</html>