<?php
// home_contenido.php - cuerpo común de las páginas de inicio (deportista / profesor / admin)
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/encabezado.php';

// Se mantiene por compatibilidad: los estilos del inicio ya están en home_proyecto.css
function head_home() {
    return '';
}

function render_home_contenido($conexion, $rol) {
    // Las 3 noticias más recientes (las publica el administrador)
    $noticias = $conexion->query("SELECT id, titulo, fecha_publicacion FROM noticias ORDER BY fecha_publicacion DESC, id DESC LIMIT 3")
                         ->fetch_all(MYSQLI_ASSOC);
    $imagenes = [
        ['https://images.unsplash.com/photo-1766136809046-efa58a4ec469?fm=jpg&q=60&w=1600&auto=format&fit=crop', 'Baloncesto'],
        ['https://images.unsplash.com/flagged/photo-1568105631375-d992b82a905b?fm=jpg&q=60&w=1600&auto=format&fit=crop', 'Fútbol'],
        ['https://images.unsplash.com/photo-1590701722430-6a9751ff65d1?fm=jpg&q=60&w=1600&auto=format&fit=crop', 'Equipo deportivo'],
    ];
    ?>
<main>
    <div class="hero-seccion">
        <h1>Match Sport Integration</h1>
        <p class="eslogan">Conecta · Juega · Gana</p>
    </div>

    <div class="carrusel" id="carrusel">
        <?php foreach ($imagenes as $i => $img): ?>
            <img src="<?php echo h($img[0]); ?>" alt="<?php echo h($img[1]); ?>" class="<?php echo $i === 0 ? 'activo' : ''; ?>">
        <?php endforeach; ?>
        <div class="carrusel-puntos">
            <?php foreach ($imagenes as $i => $img): ?>
                <button type="button" aria-label="Imagen <?php echo $i + 1; ?>" class="<?php echo $i === 0 ? 'activo' : ''; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="rejilla-info">
        <div class="card-info">
            <h2>Últimas noticias</h2>
            <?php if ($noticias): ?>
                <ul class="lista-noticias">
                    <?php foreach ($noticias as $n): ?>
                        <li>
                            <a href="noticias.php"><strong><?php echo h($n['titulo']); ?></strong></a>
                            <small><?php echo h(date('d/m/Y', strtotime($n['fecha_publicacion']))); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="noticias.php">Ver todas las noticias →</a>
            <?php else: ?>
                <p>Aún no hay noticias publicadas.</p>
            <?php endif; ?>
        </div>
        <div class="card-info">
            <h2>Calendario y horarios</h2>
            <p>Consulta las clases y entrenamientos programados.</p>
            <a href="calendario.php">Ver calendario →</a>
        </div>
    </div>
</main>

<footer class="pie">
    <p>Match Sport Integration · Conecta · Juega · Gana</p>
    <p>&copy; <?php echo date('Y'); ?></p>
</footer>
<script>
    (function () {
        var imgs = document.querySelectorAll('#carrusel img');
        var puntos = document.querySelectorAll('#carrusel .carrusel-puntos button');
        var actual = 0, timer;
        function mostrar(n) {
            imgs[actual].classList.remove('activo'); puntos[actual].classList.remove('activo');
            actual = n % imgs.length;
            imgs[actual].classList.add('activo'); puntos[actual].classList.add('activo');
        }
        function iniciar() { timer = setInterval(function () { mostrar(actual + 1); }, 5000); }
        puntos.forEach(function (p, i) { p.addEventListener('click', function () { clearInterval(timer); mostrar(i); iniciar(); }); });
        iniciar();
    })();
</script>
<?php
}
