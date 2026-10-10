<?php
require 'conexion.php';
require 'encabezado.php';

$rol = requerir_rol(['admin', 'profesor', 'deportista']);

// Datos del perfil según el rol (etiqueta => valor)
$datos = [];
if ($rol === 'deportista') {
    $id = (int) $_SESSION['usuario_id'];
    $stmt = $conexion->prepare(
        "SELECT u.*, d.nombre AS deporte, c.nombre AS categoria
         FROM usuarios u
         JOIN deportes d ON d.id = u.deporte_id
         JOIN categorias c ON c.id = u.categoria_id
         WHERE u.id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $datos = [
        'Nombre'           => $u['nombre'] . ' ' . $u['apellido'],
        'Correo'           => $u['correo'],
        'Teléfono'         => $u['telefono'],
        'Fecha de nacimiento' => $u['fecha_nacimiento'],
        'Edad'             => $u['edad'] . ' años',
        'Género'           => $u['genero'],
        'Departamento'     => $u['departamento'],
        'Dirección'        => $u['direccion'],
        'Deporte'          => $u['deporte'],
        'Categoría'        => $u['categoria'],
    ];
} elseif ($rol === 'profesor') {
    $stmt = $conexion->prepare("SELECT nombre, correo FROM profesores WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['profesor_id']);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $datos = ['Nombre' => $p['nombre'], 'Correo' => $p['correo'], 'Rol' => 'Profesor'];
} else {
    $stmt = $conexion->prepare("SELECT nombre, correo FROM admins WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['admin_id']);
    $stmt->execute();
    $a = $stmt->get_result()->fetch_assoc();
    $datos = ['Nombre' => $a['nombre'], 'Correo' => $a['correo'], 'Rol' => 'Administrador'];
}

render_inicio('Perfil');
?>
<div class="pagina perfil">
    <h2>Mi perfil</h2>
    <p class="subtitulo">Tus datos registrados en la plataforma.</p>
    <div class="card perfil-tabla">
        <?php foreach ($datos as $etiqueta => $valor): ?>
            <div class="perfil-fila">
                <div class="etiqueta-dato"><?php echo h($etiqueta); ?></div>
                <div><?php echo h($valor); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php render_fin(); ?>
