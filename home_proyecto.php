<?php
require 'home_contenido.php';

// Inicio del deportista
requerir_rol(['deportista']);

render_inicio('Inicio', head_home());
render_home_contenido($conexion, 'deportista');
render_fin();
