<?php
// encabezado.php - barra superior + menú lateral según el rol de la sesión.
// Uso:  require 'encabezado.php';  render_inicio('Título');  ...contenido...  render_fin();
require_once __DIR__ . '/auth.php';

function menu_por_rol($rol) {
    switch ($rol) {
        case 'admin':
            return [
                'home_admin.php'      => 'Inicio',
                'noticias.php'        => 'Noticias',
                'admin_noticias.php'  => 'Administrar noticias',
                'admin_deportes.php'  => 'Deportes',
                'admin_horarios.php'  => 'Horarios',
                'admin_calendario.php'=> 'Calendario (editar)',
                'calendario.php'      => 'Calendario (vista)',
                'admin.php'           => 'Aprobación de alumnos',
                'crear_profesor.php'  => 'Crear profesor',
                'admin_usuarios.php'  => 'Gestionar usuarios',
                'perfil_usuario.php'  => 'Perfil',
            ];
        case 'profesor':
            return [
                'home_profesor.php'   => 'Inicio',
                'noticias.php'        => 'Noticias',
                'admin_horarios.php'  => 'Deportes y horarios',
                'admin_calendario.php'=> 'Calendario (editar)',
                'calendario.php'      => 'Calendario (vista)',
                'profesor_avance.php' => 'Avance deportivo',
                'perfil_usuario.php'  => 'Perfil',
            ];
        default: // deportista
            return [
                'home_proyecto.php'       => 'Inicio',
                'noticias.php'            => 'Noticias',
                'deportes.php'            => 'Mi deporte',
                'horarios_categoria.php'  => 'Mis horarios',
                'calendario.php'          => 'Calendario',
                'perfil_usuario.php'      => 'Perfil',
            ];
    }
}

function nombre_sesion($rol) {
    if ($rol === 'admin')    return $_SESSION['admin_nombre'] ?? 'Administrador';
    if ($rol === 'profesor') return $_SESSION['profesor_nombre'] ?? 'Profesor';
    return $_SESSION['nombre'] ?? 'Deportista';
}

function render_inicio($titulo, $head_extra = '') {
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($titulo); ?> - Match Sport Integration</title>
    <link rel="stylesheet" href="home_proyecto.css">
    <?php echo $head_extra; ?>
</head>
<body>
<?php
    render_barra_menu();
}

// Barra superior + menú hamburguesa según el rol (también la usan las páginas de administración)
function render_barra_menu() {
    $rol = rol_actual();
    $menu = menu_por_rol($rol);
    ?>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMenu()"></div>

<div class="barra-superior">
    <div class="bloque-izquierdo">
        <button class="btn-abrir" onclick="toggleMenu()">☰</button>
        <img src="uploads/logo%20plataforma.PNG" alt="Logo" class="logo-img">
        <span class="logo-texto">Match Sport Integration</span>
    </div>
    <div class="bloque-derecho">
        <span class="nombre-usuario"><?php echo h(nombre_sesion($rol)); ?></span>
        <a href="logout.php" class="btn-sesion">Cerrar sesión</a>
    </div>
</div>

<aside id="sidebar" class="sidebar">
    <button class="btn-cerrar" onclick="toggleMenu()">&times;</button>
    <nav class="menu-lateral">
        <ul>
            <?php foreach ($menu as $url => $texto): ?>
                <li><a href="<?php echo h($url); ?>"<?php echo $url === basename($_SERVER['SCRIPT_NAME']) ? ' class="actual"' : ''; ?>><?php echo h($texto); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>

<script>
    function toggleMenu() {
        document.getElementById("sidebar").classList.toggle("activo");
        document.getElementById("sidebarOverlay").classList.toggle("activo");
    }
</script>
<?php
}

function render_fin() {
    echo "\n</body>\n</html>\n";
}
