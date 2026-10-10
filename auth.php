<?php
// auth.php - control de sesión y roles (admin / profesor / deportista)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Devuelve el rol de la sesión actual o null si no hay sesión
function rol_actual() {
    if (isset($_SESSION['superadmin_id'])) return 'superadmin';
    if (isset($_SESSION['admin_id']))    return 'admin';
    if (isset($_SESSION['profesor_id'])) return 'profesor';
    if (isset($_SESSION['usuario_id']))  return 'deportista';
    return null;
}

// Deja pasar solo a los roles indicados; si no, manda al inicio de sesión
function requerir_rol(array $roles) {
    $rol = rol_actual();
    if ($rol === null || !in_array($rol, $roles, true)) {
        header('Location: inicio_sesion.html');
        exit;
    }
    return $rol;
}

// Escapa texto para imprimirlo en HTML
function h($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
