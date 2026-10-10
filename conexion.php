<?php
// conexion.php
// Ajusta estos 4 datos según tu servidor (XAMPP/Hostinger/etc.)
$host     = 'localhost';
$bd_nombre = 'match_sport';
$bd_usuario = 'root';
$bd_password = '';

// Los errores de SQL lanzan excepción (mismo comportamiento en PHP 8.0 y 8.1+)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = new mysqli($host, $bd_usuario, $bd_password, $bd_nombre);

if ($conexion->connect_error) {
    die('Error de conexión a la base de datos: ' . $conexion->connect_error);
}

$conexion->set_charset('utf8mb4');
