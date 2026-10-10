<?php
require 'home_contenido.php';

// Inicio del profesor
requerir_rol(['profesor']);

render_inicio('Inicio', head_home());
render_home_contenido($conexion, 'profesor');
render_fin();
