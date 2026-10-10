<?php
require 'home_contenido.php';

// Inicio del administrador
requerir_rol(['admin']);

render_inicio('Inicio', head_home());
render_home_contenido($conexion, 'admin');
render_fin();
