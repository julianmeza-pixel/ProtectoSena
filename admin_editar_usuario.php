<?php
// admin_editar_usuario.php - el administrador edita el perfil de un deportista y sus documentos
require 'encabezado.php';
require 'conexion.php';
require 'categorias.php';

requerir_rol(['admin']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

function cargar_usuario($conexion, $id) {
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$u = cargar_usuario($conexion, $id);
if (!$u) {
    $_SESSION['flash'] = ['tipo' => 'error', 'texto' => 'El deportista no existe.'];
    header('Location: admin_usuarios.php');
    exit;
}

$carpeta_subidas = 'uploads/';
$extensiones_permitidas = ['jpg', 'jpeg', 'png', 'pdf'];
$tipos_documento = ['Identidad', 'Certificado de Residencia', 'Certificado Médico', 'Certificado EPS',
                    'Foto Carnet', 'Carné Año Anterior', 'Documento del Acudiente'];

// Guarda un archivo subido con nombre único; devuelve la ruta o null si no es válido
function guardar_archivo_admin($archivo, $carpeta, $permitidas) {
    if (!isset($archivo['tmp_name']) || $archivo['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $permitidas, true)) return null;
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
    $ruta = $carpeta . uniqid('doc_', true) . '.' . $ext;
    return move_uploaded_file($archivo['tmp_name'], $ruta) ? $ruta : null;
}

// Borra del disco un archivo, solo si está dentro de uploads/
function borrar_archivo_disco($ruta) {
    if (strpos($ruta, 'uploads/') === 0 && strpos($ruta, '..') === false && is_file($ruta)) {
        @unlink($ruta);
    }
}

function volver($tipo, $texto, $id) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
    header('Location: admin_editar_usuario.php?id=' . $id);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar_usuario') {
        $docs = $conexion->query("SELECT ruta_archivo FROM documentos WHERE usuario_id = $id")->fetch_all(MYSQLI_ASSOC);
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        foreach ($docs as $d) borrar_archivo_disco($d['ruta_archivo']);
        $_SESSION['flash'] = ['tipo' => 'ok', 'texto' => 'Deportista eliminado.'];
        header('Location: admin_usuarios.php');
        exit;
    }

    if ($accion === 'doc_eliminar' || $accion === 'doc_reemplazar') {
        $doc_id = (int) ($_POST['doc_id'] ?? 0);
        $stmt = $conexion->prepare("SELECT ruta_archivo FROM documentos WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param('ii', $doc_id, $id);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        if (!$doc) volver('error', 'El documento no existe.', $id);

        if ($accion === 'doc_eliminar') {
            $stmt = $conexion->prepare("DELETE FROM documentos WHERE id = ?");
            $stmt->bind_param('i', $doc_id);
            $stmt->execute();
            borrar_archivo_disco($doc['ruta_archivo']);
            volver('ok', 'Documento eliminado.', $id);
        }

        $ruta = guardar_archivo_admin($_FILES['archivo'] ?? [], $carpeta_subidas, $extensiones_permitidas);
        if (!$ruta) volver('error', 'Archivo no válido: usa JPG, PNG o PDF.', $id);
        $stmt = $conexion->prepare("UPDATE documentos SET ruta_archivo = ?, fecha_subida = NOW() WHERE id = ?");
        $stmt->bind_param('si', $ruta, $doc_id);
        $stmt->execute();
        borrar_archivo_disco($doc['ruta_archivo']);
        volver('ok', 'Documento reemplazado.', $id);
    }

    if ($accion === 'doc_agregar') {
        $tipo = $_POST['tipo'] ?? '';
        if (!in_array($tipo, $tipos_documento, true)) volver('error', 'Tipo de documento no válido.', $id);
        $ruta = guardar_archivo_admin($_FILES['archivo'] ?? [], $carpeta_subidas, $extensiones_permitidas);
        if (!$ruta) volver('error', 'Archivo no válido: usa JPG, PNG o PDF.', $id);
        $stmt = $conexion->prepare("INSERT INTO documentos (usuario_id, tipo, ruta_archivo) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $id, $tipo, $ruta);
        $stmt->execute();
        volver('ok', 'Documento agregado.', $id);
    }

    // ---------- Guardar datos del perfil ----------
    $nombre       = trim($_POST['nombre'] ?? '');
    $apellido     = trim($_POST['apellido'] ?? '');
    $correo       = trim($_POST['correo'] ?? '');
    $telefono     = trim($_POST['telefono'] ?? '');
    $fecha_nac    = $_POST['fecha_nacimiento'] ?? '';
    $genero       = $_POST['genero'] ?? '';
    $departamento = trim($_POST['departamento'] ?? '');
    $direccion    = trim($_POST['direccion'] ?? '');
    $deporte_id   = (int) ($_POST['deporte_id'] ?? 0);
    $alumno_antiguo = ($_POST['alumno_antiguo'] ?? 'No') === 'Si' ? 'Si' : 'No';
    $acu_nombre   = trim($_POST['acudiente_nombre'] ?? '');
    $acu_tel      = trim($_POST['acudiente_telefono'] ?? '');
    $estado       = $_POST['estado'] ?? '';
    $password     = $_POST['password'] ?? '';

    $edad = calcular_edad($fecha_nac);
    $categoria_id = ($edad !== null && $edad >= 0 && $edad <= 120) ? categoria_id_por_edad($conexion, $edad) : null;

    if ($nombre === '' || $apellido === '' || $telefono === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Nombre, apellido, teléfono y un correo válido son obligatorios.';
    } elseif ($edad === null || $edad < 0 || $edad > 120) {
        $error = 'La fecha de nacimiento no es válida.';
    } elseif ($categoria_id === null) {
        $error = 'No existe una categoría para esa edad.';
    } elseif (!in_array($estado, ['pendiente', 'aprobado', 'rechazado'], true)) {
        $error = 'Estado no válido.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        $stmt = $conexion->prepare("SELECT id FROM deportes WHERE id = ?");
        $stmt->bind_param('i', $deporte_id);
        $stmt->execute();
        $deporte_ok = $stmt->get_result()->num_rows > 0;

        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ? AND id <> ?");
        $stmt->bind_param('si', $correo, $id);
        $stmt->execute();
        $correo_repetido = $stmt->get_result()->num_rows > 0;

        $menor_edad = $edad < 18 ? 'Si' : 'No';
        if (!$deporte_ok) {
            $error = 'El deporte seleccionado no existe.';
        } elseif ($correo_repetido) {
            $error = 'Ya existe otro usuario con ese correo.';
        } elseif ($menor_edad === 'Si' && ($acu_nombre === '' || $acu_tel === '')) {
            $error = 'Para menores de edad el acudiente es obligatorio.';
        } else {
            if ($menor_edad === 'No') $acu_nombre = $acu_tel = null;

            $stmt = $conexion->prepare(
                "UPDATE usuarios SET nombre=?, apellido=?, correo=?, telefono=?, fecha_nacimiento=?, edad=?, genero=?,
                    departamento=?, direccion=?, deporte_id=?, categoria_id=?, alumno_antiguo=?, menor_edad=?,
                    acudiente_nombre=?, acudiente_telefono=?, estado=? WHERE id=?"
            );
            $stmt->bind_param(
                'sssssisssiisssssi',
                $nombre, $apellido, $correo, $telefono, $fecha_nac, $edad, $genero,
                $departamento, $direccion, $deporte_id, $categoria_id, $alumno_antiguo, $menor_edad,
                $acu_nombre, $acu_tel, $estado, $id
            );
            $stmt->execute();

            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $stmt->bind_param('si', $hash, $id);
                $stmt->execute();
            }
            volver('ok', 'Perfil actualizado.', $id);
        }
    }
    // Si hubo error, se vuelve a mostrar el formulario con lo que escribió el admin
    $u = array_merge($u, [
        'nombre' => $nombre, 'apellido' => $apellido, 'correo' => $correo, 'telefono' => $telefono,
        'fecha_nacimiento' => $fecha_nac, 'genero' => $genero, 'departamento' => $departamento,
        'direccion' => $direccion, 'deporte_id' => $deporte_id, 'alumno_antiguo' => $alumno_antiguo,
        'acudiente_nombre' => $acu_nombre, 'acudiente_telefono' => $acu_tel, 'estado' => $estado,
    ]);
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$deportes  = $conexion->query("SELECT id, nombre FROM deportes ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$documentos = $conexion->query("SELECT * FROM documentos WHERE usuario_id = $id ORDER BY id")->fetch_all(MYSQLI_ASSOC);

render_inicio('Editar deportista');
?>
<div class="pagina" style="max-width:800px;">
    <h2>Editar deportista</h2>
    <p class="subtitulo"><?php echo h($u['nombre'] . ' ' . $u['apellido']); ?></p>
    <?php if ($flash): ?>
        <div class="aviso <?php echo $flash['tipo'] === 'ok' ? 'ok' : 'error'; ?>"><?php echo h($flash['texto']); ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="aviso error"><?php echo h($error); ?></div><?php endif; ?>

    <form method="POST" class="card">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <h3>Datos del perfil</h3>
        <div class="grid">
            <div><label>Nombre</label><input type="text" name="nombre" value="<?php echo h($u['nombre']); ?>" required></div>
            <div><label>Apellido</label><input type="text" name="apellido" value="<?php echo h($u['apellido']); ?>" required></div>
            <div><label>Correo</label><input type="email" name="correo" value="<?php echo h($u['correo']); ?>" required></div>
            <div><label>Teléfono</label><input type="text" name="telefono" value="<?php echo h($u['telefono']); ?>" required></div>
            <div><label>Fecha de nacimiento (la edad y la categoría se recalculan)</label>
                <input type="date" name="fecha_nacimiento" value="<?php echo h($u['fecha_nacimiento']); ?>" required></div>
            <div><label>Género</label>
                <select name="genero">
                    <?php foreach (['Femenino', 'Masculino', 'Otro'] as $g): ?>
                        <option <?php echo $u['genero'] === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Departamento</label><input type="text" name="departamento" value="<?php echo h($u['departamento']); ?>"></div>
            <div><label>Dirección</label><input type="text" name="direccion" value="<?php echo h($u['direccion']); ?>"></div>
            <div><label>Deporte</label>
                <select name="deporte_id">
                    <?php foreach ($deportes as $d): ?>
                        <option value="<?php echo (int) $d['id']; ?>" <?php echo (int) $u['deporte_id'] === (int) $d['id'] ? 'selected' : ''; ?>><?php echo h($d['nombre']); ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Alumno antiguo</label>
                <select name="alumno_antiguo">
                    <option value="No" <?php echo $u['alumno_antiguo'] === 'No' ? 'selected' : ''; ?>>No</option>
                    <option value="Si" <?php echo $u['alumno_antiguo'] === 'Si' ? 'selected' : ''; ?>>Sí</option>
                </select></div>
            <div><label>Acudiente (solo menores de edad)</label><input type="text" name="acudiente_nombre" value="<?php echo h($u['acudiente_nombre']); ?>"></div>
            <div><label>Teléfono del acudiente</label><input type="text" name="acudiente_telefono" value="<?php echo h($u['acudiente_telefono']); ?>"></div>
            <div><label>Estado</label>
                <select name="estado">
                    <?php foreach (['pendiente', 'aprobado', 'rechazado'] as $e): ?>
                        <option value="<?php echo $e; ?>" <?php echo $u['estado'] === $e ? 'selected' : ''; ?>><?php echo ucfirst($e); ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Nueva contraseña (vacío = no cambiar)</label>
                <input type="password" name="password" minlength="6" autocomplete="new-password"></div>
        </div>
        <div style="margin-top:20px;">
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secundario" href="admin_usuarios.php">Volver</a>
        </div>
    </form>

    <div class="card">
        <h3>Documentos</h3>
        <?php foreach ($documentos as $d): ?>
            <div class="doc">
                <span class="tipo"><?php echo h($d['tipo']); ?></span>
                <a href="<?php echo h($d['ruta_archivo']); ?>" target="_blank">📄 Ver archivo</a>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="doc_id" value="<?php echo (int) $d['id']; ?>">
                    <input type="hidden" name="accion" value="doc_reemplazar">
                    <input type="file" name="archivo" accept=".jpg,.jpeg,.png,.pdf" required>
                    <button class="btn" type="submit">Reemplazar</button>
                </form>
                <form method="POST" onsubmit="return confirm('¿Eliminar este documento?');">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="doc_id" value="<?php echo (int) $d['id']; ?>">
                    <input type="hidden" name="accion" value="doc_eliminar">
                    <button class="btn btn-rojo" type="submit">Eliminar</button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if (!$documentos): ?><p><em>Sin documentos adjuntos.</em></p><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="doc" style="border-bottom:none; margin-top:10px;">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="accion" value="doc_agregar">
            <select name="tipo" style="width:auto;">
                <?php foreach ($tipos_documento as $t): ?><option><?php echo h($t); ?></option><?php endforeach; ?>
            </select>
            <input type="file" name="archivo" accept=".jpg,.jpeg,.png,.pdf" required>
            <button class="btn" type="submit">Agregar documento</button>
        </form>
    </div>

    <form method="POST" onsubmit="return confirm('¿Eliminar este deportista y sus documentos? Esta acción no se puede deshacer.');">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="accion" value="eliminar_usuario">
        <button class="btn btn-rojo" type="submit">Eliminar deportista</button>
    </form>
</div>
<?php render_fin(); ?>
