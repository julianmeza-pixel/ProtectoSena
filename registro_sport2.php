<?php
require 'conexion.php';

// Los deportes salen de la tabla `deportes` (los mismos que administra el admin)
$deportes = $conexion->query("SELECT id, nombre FROM deportes ORDER BY orden, nombre");

$errores = [
    'campos'    => 'Completa todos los campos obligatorios.',
    'password'  => 'La contraseña debe tener al menos 6 caracteres y coincidir con la confirmación.',
    'correo'    => 'Ya existe una inscripción con ese correo electrónico.',
    'deporte'   => 'El deporte seleccionado no es válido.',
    'categoria' => 'Tu edad no corresponde a ninguna categoría (la edad mínima de inscripción es 3 años).',
    'edad'      =>'La fecha de nacimiento no es válida.',
    'archivo'   => 'Solo se permiten archivos JPG, PNG o PDF.',
    'guardar'   => 'No se pudo guardar la inscripción. Intenta de nuevo.',
];
$mensaje_error = $errores[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción - Match Sport Integration</title>
    <link rel="stylesheet" href="home_proyecto.css">
</head>
<body class="auth">

<div class="card ancha">
    <img src="uploads/logo%20plataforma.PNG" alt="Match Sport Integration" class="logo-auth">
    <div class="brand-title">Match Sport Integration</div>
    <div class="brand-subtitle">Conecta, juega y gana</div>
    
    <div class="section-title">Inscripción de deportista</div>
    <div class="section-desc">Completa tus datos para acceder al sistema.</div>

    <form action="procesar_registro.php" method="POST" enctype="multipart/form-data" class="form-grid" id="formRegistro">

        <div class="error-msg<?php echo $mensaje_error ? ' show' : ''; ?>" id="errorServidor"><?php echo htmlspecialchars($mensaje_error); ?></div>
        <div class="error-msg" id="errorPassword">Las contraseñas no coinciden.</div>

        <!-- DATOS PERSONALES -->
        <div class="form-section-title">Datos Personales</div>

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" placeholder="Ej. Juan" required>
        </div>

        <div class="form-group">
            <label>Apellido</label>
            <input type="text" name="apellido" placeholder="Ej. Pérez" required>
        </div>

        <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="correo" placeholder="correo@ejemplo.com" required>
        </div>

        <div class="form-group">
            <label>Teléfono</label>
            <input type="tel" name="telefono" placeholder="Ej. 3101234567" required>
        </div>

        <div class="form-group">
            <label>Fecha de Nacimiento</label>
            <input type="date" name="fecha_nacimiento" id="fechaNacimiento" required>
        </div>

        <div class="form-group">
            <label>Edad</label>
            <input type="number" id="edad" placeholder="Se calcula sola" readonly>
            <small>Se calcula con la fecha de nacimiento; de ahí sale tu categoría.</small>
        </div>

        <div class="form-group">
            <label>Género</label>
            <select name="genero">
                <option value="" disabled selected>Seleccione</option>
                <option value="Masculino">Masculino</option>
                <option value="Femenino">Femenino</option>
                <option value="Otro">Otro</option>
            </select>
        </div>

        <div class="form-group">
            <label>Departamento</label>
            <select name="departamento" required>
                <option value="" disabled selected>Seleccione</option>
                <option value="Cundinamarca">Cundinamarca</option>
                <option value="Bogotá D.C.">Bogotá D.C.</option>
                <option value="Antioquia">Antioquia</option>
                <option value="Valle del Cauca">Valle del Cauca</option>
                <option value="Atlántico">Atlántico</option>
                <option value="Santander">Santander</option>
                <option value="Otro">Otro</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label>Dirección</label>
            <input type="text" name="direccion" placeholder="Ej. Calle 10 # 20-30">
        </div>

        <div class="form-group">
            <label>Deporte a Aplicar</label>
            <select name="deporte_id" required>
                <option value="" disabled selected>Seleccione</option>
                <?php while ($d = $deportes->fetch_assoc()): ?>
                    <option value="<?php echo (int) $d['id']; ?>"><?php echo htmlspecialchars($d['nombre']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- ALUMNO ANTIGUO -->
        <div class="form-group">
            <label>¿Es alumno antiguo?</label>
            <select name="alumno_antiguo" id="alumnoAntiguo" required>
                <option value="No" selected>No</option>
                <option value="Si">Sí</option>
            </select>
        </div>

        <div class="form-group" id="bloqueCarneAnterior" style="display: none;">
            <label>Carné del Año Anterior</label>
            <input type="file" name="carne_anterior" id="carneAnterior" accept="image/*,.pdf">
        </div>

        <!-- CONTRASEÑA DE LA CUENTA -->
        <div class="form-section-title">Contraseña de tu cuenta</div>

        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" id="password" minlength="6" placeholder="Mínimo 6 caracteres" required>
        </div>

        <div class="form-group">
            <label>Confirmar contraseña</label>
            <input type="password" name="confirmar_password" id="confirmarPassword" minlength="6" required>
        </div>

        <div class="form-group full-width">
            <small>Tu cuenta se activará cuando el área administrativa apruebe la inscripción. Entonces podrás ingresar con este correo y esta contraseña.</small>
        </div>

        <!-- DOCUMENTOS REQUERIDOS -->
        <div class="form-section-title">Documentos Requeridos</div>

        <div class="form-group full-width">
            <label>Fotocopia Número de Identidad</label>
            <input type="file" name="doc_identidad" accept="image/*,.pdf" required>
        </div>

        <div class="form-group">
            <label>Certificado de Residencia Actualizado</label>
            <input type="file" name="cert_residencia" accept="image/*,.pdf" required>
        </div>

        <div class="form-group">
            <label>Certificado Médico Actualizado</label>
            <input type="file" name="cert_medico" accept="image/*,.pdf" required>
        </div>

        <div class="form-group full-width">
            <label>Certificado de Afiliación EPS</label>
            <input type="file" name="cert_eps" accept="image/*,.pdf" required>
        </div>

        <div class="form-group full-width">
            <label>2 Fotos Tamaño Carnet</label>
            <input type="file" name="fotos_carnet[]" accept="image/*" multiple required>
            <small>Selecciona ambas fotos al mismo tiempo (mantén Ctrl o Cmd presionado).</small>
        </div>

        <!-- MENOR DE EDAD -->
        <div class="form-section-title">Información Adicional</div>

        <div class="form-group full-width">
            <label>¿Es menor de edad?</label>
            <select name="menor_edad" id="menorEdad" required>
                <option value="No" selected>No</option>
                <option value="Si">Sí</option>
            </select>
        </div>

        <div class="subsection" id="datosAcudiente">
            <div class="subsection-title">Datos del Acudiente</div>

            <div class="form-group">
                <label>Nombre del Acudiente</label>
                <input type="text" name="acudiente_nombre" placeholder="Nombre completo">
            </div>

            <div class="form-group">
                <label>Teléfono del Acudiente</label>
                <input type="tel" name="acudiente_telefono" placeholder="Ej. 3101234567">
            </div>

            <div class="form-group full-width">
                <label>Documento de Identificación del Acudiente</label>
                <input type="file" name="acudiente_documento" accept="image/*,.pdf">
            </div>
        </div>
        <p class="section-desc" style="grid-column: span 2; text-align:center;">
            Si ya tienes todos los archivos adjuntos, puedes hacer clic en Enviar. 
            <strong>La respuesta por parte del área administrativa será enviada al correo electrónico registrado.</strong>
        </p>
        <div class="full-width">
            <button type="submit" class="btn-submit">Enviar inscripción</button>
        </div>
    </form>

    <div class="back-link">
        ¿Ya tienes una cuenta? <a href="inicio_sesion.html">Iniciar Sesión</a>
    </div>
</div>

<script>
    // Mostrar/ocultar campos del acudiente según si es menor de edad
    const selectMenor = document.getElementById('menorEdad');
    const bloqueAcudiente = document.getElementById('datosAcudiente');
    const camposAcudiente = bloqueAcudiente.querySelectorAll('input, select');

    function actualizarAcudiente() {
        const esMenor = selectMenor.value === 'Si';
        bloqueAcudiente.classList.toggle('show', esMenor);
        camposAcudiente.forEach(function (campo) {
            campo.required = esMenor;
        });
    }

    selectMenor.addEventListener('change', actualizarAcudiente);
    actualizarAcudiente();

    // Mostrar/ocultar el campo de carné del año anterior según si es alumno antiguo
    const selectAlumnoAntiguo = document.getElementById('alumnoAntiguo');
    const bloqueCarne = document.getElementById('bloqueCarneAnterior');
    const inputCarne = document.getElementById('carneAnterior');

    function actualizarCarne() {
        const esAntiguo = selectAlumnoAntiguo.value === 'Si';
        bloqueCarne.style.display = esAntiguo ? 'block' : 'none';
        inputCarne.required = esAntiguo;
    }

    selectAlumnoAntiguo.addEventListener('change', actualizarCarne);
    actualizarCarne();

    // Calcular edad automáticamente a partir de la fecha de nacimiento
    const inputFecha = document.getElementById('fechaNacimiento');
    const inputEdad = document.getElementById('edad');
    inputFecha.max = new Date().toISOString().split('T')[0];

    inputFecha.addEventListener('change', function () {
        if (!inputFecha.value) { inputEdad.value = ''; return; }
        const nac = new Date(inputFecha.value);
        const hoy = new Date();
        let edad = hoy.getFullYear() - nac.getFullYear();
        const m = hoy.getMonth() - nac.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) edad--;
        inputEdad.value = edad;
        // Un menor de 18 años debe registrar acudiente
        if (edad < 18) { selectMenor.value = 'Si'; actualizarAcudiente(); }
    });

    // Las contraseñas deben coincidir antes de enviar
    document.getElementById('formRegistro').addEventListener('submit', function (e) {
        const igual = document.getElementById('password').value === document.getElementById('confirmarPassword').value;
        document.getElementById('errorPassword').classList.toggle('show', !igual);
        if (!igual) e.preventDefault();
    });
</script>

</body>
</html>