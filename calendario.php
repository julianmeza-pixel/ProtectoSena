<?php
require 'conexion.php';
require 'encabezado.php';

$rol = requerir_rol(['admin', 'profesor', 'deportista']);

$dias_validos  = ['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'];
$dias_columnas = ['Lunes'=>'Lunes','Martes'=>'Martes','Miercoles'=>'Miércoles','Jueves'=>'Jueves','Viernes'=>'Viernes','Sabado'=>'Sábado'];

$sql = "SELECT cc.*, d.nombre AS deporte, c.nombre AS categoria, IF(p.nombre = '', p.correo, p.nombre) AS profesor
        FROM calendario_clases cc
        LEFT JOIN deportes d ON d.id = cc.deporte_id
        LEFT JOIN categorias c ON c.id = cc.categoria_id
        LEFT JOIN profesores p ON p.id = cc.profesor_id";

// El calendario es el mismo para todos los roles (no se filtra por deporte ni categoría)
$resultado = $conexion->query("$sql ORDER BY cc.hora");

// $tabla[hora][dia] = lista de clases (puede haber varias a la misma hora)
$horas = [];
$tabla = [];
while ($c = $resultado->fetch_assoc()) {
    $hora = $c['hora'];
    if (!isset($tabla[$hora])) {
        $tabla[$hora] = [];
        $horas[] = $hora;
    }
    $tabla[$hora][$c['dia']][] = $c;
}

render_inicio('Calendario');
?>
<div class="pagina" style="max-width:1100px;">
    <h2>Calendario de clases</h2>
    <p class="subtitulo">Clases y entrenamientos de la semana.</p>

    <div style="overflow-x:auto;">
        <table class="calendario-tabla">
            <thead>
                <tr>
                    <th>Hora</th>
                    <?php foreach ($dias_validos as $dia): ?>
                        <th><?php echo $dias_columnas[$dia]; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($horas)): ?>
                <tr><td colspan="7" class="vacio">Aún no hay clases registradas en el calendario.</td></tr>
                <?php endif; ?>

                <?php foreach ($horas as $hora): ?>
                <tr>
                    <td class="hora"><?php echo h(date('h:i a', strtotime($hora))); ?></td>
                    <?php foreach ($dias_validos as $dia): ?>
                        <?php if (!empty($tabla[$hora][$dia])): ?>
                        <td>
                            <?php foreach ($tabla[$hora][$dia] as $c): ?>
                                <span class="clase <?php echo h($c['color']); ?>">
                                    <?php echo h($c['materia']); ?>
                                    <small><?php echo h(($c['deporte'] ?? 'Todos los deportes') . ' · ' . ($c['categoria'] ?? 'Todas las categorías')); ?></small>
                                    <?php if (!empty($c['profesor'])): ?><small>Prof. <?php echo h($c['profesor']); ?></small><?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                        <?php else: ?>
                        <td class="libre"></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($rol !== 'deportista'): ?>
        <p style="margin-top:20px;"><a class="btn" href="admin_calendario.php">Editar calendario</a></p>
    <?php endif; ?>
</div>
<?php render_fin(); ?>
