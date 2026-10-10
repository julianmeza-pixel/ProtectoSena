<?php
session_start();
require 'conexion.php';

$rol      = $_POST['rol'] ?? '';
$correo   = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

// Busca una fila por correo en la tabla indicada (solo tablas fijas del código)
function buscar_por_correo($conexion, $tabla, $correo) {
    $stmt = $conexion->prepare("SELECT * FROM $tabla WHERE correo = ? LIMIT 1");
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function volver($error) {
    header('Location: inicio_sesion.html?error=' . $error);
    exit;
}

// Empieza siempre con una sesión limpia (evita mezclar roles)
function nueva_sesion() {
    session_regenerate_id(true);
    $_SESSION = [];
}

switch ($rol) {

    case 'admin':
        $admin = buscar_por_correo($conexion, 'admins', $correo);
        if (!$admin) volver('no_existe');
        if (!password_verify($password, $admin['password'])) volver('credenciales');
        nueva_sesion();
        $_SESSION['admin_id']     = $admin['id'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        $_SESSION['admin_correo'] = $admin['correo'];
        header('Location: home_admin.php');
        exit;

    case 'profesor':
        $profe = buscar_por_correo($conexion, 'profesores', $correo);
        if (!$profe) volver('no_existe');
        if (!password_verify($password, $profe['password'])) volver('credenciales');
        nueva_sesion();
        $_SESSION['profesor_id']     = $profe['id'];
        $_SESSION['profesor_nombre'] = $profe['nombre'] !== '' ? $profe['nombre'] : $profe['correo'];
        $_SESSION['profesor_correo'] = $profe['correo'];
        header('Location: home_profesor.php');
        exit;

    case 'deportista':
        $usuario = buscar_por_correo($conexion, 'usuarios', $correo);
        if (!$usuario) volver('no_existe');

        // La cuenta solo se activa cuando el administrador aprueba la inscripción
        if ($usuario['estado'] === 'pendiente')  volver('pendiente');
        if ($usuario['estado'] === 'rechazado')  volver('rechazado');

        if (!password_verify($password, $usuario['password'])) volver('credenciales');

        nueva_sesion();
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre']     = $usuario['nombre'];
        $_SESSION['correo']     = $usuario['correo'];
        header('Location: home_proyecto.php');
        exit;

    default:
        volver('credenciales');
}
