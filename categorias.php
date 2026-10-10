<?php
// categorias.php - la categoría de un deportista sale de su edad (tabla `categorias`)

function calcular_edad($fecha_nacimiento) {
    try {
        return (new DateTime($fecha_nacimiento))->diff(new DateTime('today'))->y;
    } catch (Exception $e) {
        return null;
    }
}

// Devuelve el id de la categoría que corresponde a la edad, o null si no hay
function categoria_id_por_edad($conexion, $edad) {
    $edad = (int) $edad;
    $stmt = $conexion->prepare(
        "SELECT id FROM categorias
         WHERE edad_min <= ? AND (edad_max IS NULL OR edad_max >= ?)
         ORDER BY edad_min DESC LIMIT 1"
    );
    $stmt->bind_param('ii', $edad, $edad);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    return $fila ? (int) $fila['id'] : null;
}
