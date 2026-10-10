<?php
require 'conexion.php';
require 'categorias.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro_sport2.php');
    exit;
}

function volver_con_error($codigo) {
    header('Location: registro_sport2.php?error=' . $codigo);
    exit;
}

// ---------- Archivos: solo JPG, PNG o PDF ----------
$carpeta_subidas = 'uploads/';
if (!is_dir($carpeta_subidas)) {
    mkdir($carpeta_subidas, 0755, true);
}
$extensiones_permitidas = ['jpg', 'jpeg', 'png', 'pdf'];

function extension_valida($nombre, $permitidas) {
    return in_array(strtolower(pathinfo($nombre, PATHINFO_EXTENSION)), $permitidas, true);
}

// Guarda un archivo con nombre único y devuelve la ruta, o null si falla
function guardar_archivo($archivo, $carpeta) {
    if (!isset($archivo['tmp_name']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $ruta_destino = $carpeta . uniqid('doc_', true) . '.' . $extension;
    return move_uploaded_file($archivo['tmp_name'], $ruta_destino) ? $ruta_destino : null;
}

// ---------- Datos del formulario ----------
$nombre       = trim($_POST['nombre'] ?? '');
$apellido     = trim($_POST['apellido'] ?? '');
$correo       = trim($_POST['correo'] ?? '');
$telefono     = trim($_POST['telefono'] ?? '');
$genero       = $_POST['genero'] ?? '';
$departamento = trim($_POST['departamento'] ?? '');
$direccion    = trim($_POST['direccion'] ?? '');
$deporte_id   = (int) ($_POST['deporte_id'] ?? 0);
$fecha_nac    = $_POST['fecha_nacimiento'] ?? '';
$alumno_antiguo = ($_POST['alumno_antiguo'] ?? 'No') === 'Si' ? 'Si' : 'No';
$acudiente_nombre   = trim($_POST['acudiente_nombre'] ?? '');
$acudiente_telefono = trim($_POST['acudiente_telefono'] ?? '');
$password  = $_POST['password'] ?? '';
$confirmar = $_POST['confirmar_password'] ?? '';

if ($nombre === '' || $apellido === '' || $telefono === '' || $departamento === ''
    || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    volver_con_error('campos');
}
if (strlen($password) < 6 || $password !== $confirmar) {
    volver_con_error('password');
}

// La edad y la categoría las calcula el servidor a partir de la fecha de nacimiento
$edad = calcular_edad($fecha_nac);
if ($edad === null || $edad < 0 || $edad > 120) {
    volver_con_error('edad');
}
$categoria_id = categoria_id_por_edad($conexion, $edad);
$menor_edad   = $edad < 18 ? 'Si' : 'No';

if ($menor_edad === 'Si' && ($acudiente_nombre === '' || $acudiente_telefono === '')) {
    volver_con_error('campos');
}
if ($menor_edad === 'No') {
    $acudiente_nombre = $acudiente_telefono = null;
}

// El deporte debe existir en la tabla `deportes`
$stmt = $conexion->prepare("SELECT id FROM deportes WHERE id = ?");
$stmt->bind_param('i', $deporte_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    volver_con_error('deporte');
}
if ($categoria_id === null) {
    volver_con_error('categoria');
}

// Correo único
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->bind_param('s', $correo);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    volver_con_error('correo');
}

// Todos los archivos subidos deben tener una extensión permitida
foreach ($_FILES as $campo => $info) {
    $nombres = is_array($info['name']) ? $info['name'] : [$info['name']];
    $errores = is_array($info['error']) ? $info['error'] : [$info['error']];
    foreach ($nombres as $i => $n) {
        if ($errores[$i] === UPLOAD_ERR_OK && !extension_valida($n, $extensiones_permitidas)) {
            volver_con_error('archivo');
        }
    }
}

// ---------- Guardar usuario: queda "pendiente" hasta que el admin lo apruebe ----------
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conexion->prepare(
    "INSERT INTO usuarios
        (nombre, apellido, correo, password, telefono, fecha_nacimiento, edad, genero, departamento, direccion,
         deporte_id, categoria_id, alumno_antiguo, menor_edad, acudiente_nombre, acudiente_telefono, estado)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'pendiente')"
);
$stmt->bind_param(
    'ssssssisssiissss',
    $nombre, $apellido, $correo, $hash, $telefono, $fecha_nac, $edad, $genero, $departamento, $direccion,
    $deporte_id, $categoria_id, $alumno_antiguo, $menor_edad, $acudiente_nombre, $acudiente_telefono
);
if (!$stmt->execute()) {
    volver_con_error('guardar');
}
$usuario_id = $conexion->insert_id;

// ---------- Documentos ----------
$insertar_doc = $conexion->prepare("INSERT INTO documentos (usuario_id, tipo, ruta_archivo) VALUES (?, ?, ?)");

$documentos_simples = [
    'doc_identidad'       => 'Identidad',
    'cert_residencia'     => 'Certificado de Residencia',
    'cert_medico'         => 'Certificado Médico',
    'cert_eps'            => 'Certificado EPS',
    'carne_anterior'      => 'Carné Año Anterior',
    'acudiente_documento' => 'Documento del Acudiente',
];
foreach ($documentos_simples as $campo => $tipo) {
    if (isset($_FILES[$campo])) {
        $ruta = guardar_archivo($_FILES[$campo], $carpeta_subidas);
        if ($ruta) {
            $insertar_doc->bind_param('iss', $usuario_id, $tipo, $ruta);
            $insertar_doc->execute();
        }
    }
}

// Fotos carnet: campo múltiple (fotos_carnet[])
if (isset($_FILES['fotos_carnet'])) {
    $tipo = 'Foto Carnet';
    foreach ($_FILES['fotos_carnet']['name'] as $i => $n) {
        $individual = [
            'name'     => $n,
            'tmp_name' => $_FILES['fotos_carnet']['tmp_name'][$i],
            'error'    => $_FILES['fotos_carnet']['error'][$i],
        ];
        $ruta = guardar_archivo($individual, $carpeta_subidas);
        if ($ruta) {
            $insertar_doc->bind_param('iss', $usuario_id, $tipo, $ruta);
            $insertar_doc->execute();
        }
    }
}

header('Location: inicio_sesion.html?registro=exitoso');
exit;
