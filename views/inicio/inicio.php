<?php
/**
 * views/inicio/inicio.php
 * Página de inicio de CineMatch.
 * Muestra contenido destacado y recomendaciones.
 *
 * Variables opcionales del controlador InicioController:
 *   $destacados       (array)  - Contenido destacado [['id','titulo','tipo','anio','calificacion','imagen_url','generos'], ...]
 *   $recomendados     (array)  - Recomendados para el usuario
 *   $nombreUsuario    (string) - Nombre del usuario logueado
 */

require_once __DIR__ . '/../../app/bootstrap.php';

$contenidoModelo = new ContenidoModelo();
$omdb            = new OmdbApi();
try {
    $destacados   = $contenidoModelo->obtenerDestacados(6);
    $recomendados = Seguridad::usuarioId()
        ? $contenidoModelo->obtenerRecomendados(Seguridad::usuarioId(), 4)
        : [];
    // Enriquecer con pósters de OMDb
    $destacados   = $omdb->enriquecerContenidos($destacados);
    $recomendados = $omdb->enriquecerContenidos($recomendados);
} catch (Throwable $e) {
    error_log('Error inicio: ' . $e->getMessage());
}

$tituloPagina  = 'Inicio';
$paginaActiva  = 'inicio';
include __DIR__ . '/../partials/header.php';

$destacados = $destacados ?? [
    ['id'=>1,'titulo'=>'Interstellar',    'tipo'=>'pelicula','anio'=>2014,'calificacion'=>8.7,'imagen_url'=>'','generos'=>'Sci-Fi,Drama'],
    ['id'=>2,'titulo'=>'Breaking Bad',    'tipo'=>'serie',   'anio'=>2008,'calificacion'=>9.5,'imagen_url'=>'','generos'=>'Drama,Thriller'],
    ['id'=>3,'titulo'=>'The Dark Knight', 'tipo'=>'pelicula','anio'=>2008,'calificacion'=>9.0,'imagen_url'=>'','generos'=>'Acción,Thriller'],
    ['id'=>4,'titulo'=>'Stranger Things', 'tipo'=>'serie',   'anio'=>2016,'calificacion'=>8.7,'imagen_url'=>'','generos'=>'Sci-Fi,Terror'],
    ['id'=>5,'titulo'=>'El Señor de los Anillos','tipo'=>'pelicula','anio'=>2001,'calificacion'=>8.9,'imagen_url'=>'','generos'=>'Fantasía,Aventura'],
    ['id'=>6,'titulo'=>'Friends',         'tipo'=>'serie',   'anio'=>1994,'calificacion'=>8.9,'imagen_url'=>'','generos'=>'Comedia,Romance'],
];

$recomendados = $recomendados ?? array_slice($destacados, 0, 4);
?>

<main class="pagina-contenido contenedor">

    <!-- Hero de bienvenida -->
    <section style="text-align:center; padding: 3rem 0 2.5rem; border-bottom: 1px solid var(--color-borde); margin-bottom: 2.5rem;">
        <p style="font-size: 3.5rem; margin-bottom: 0.5rem;">🎬</p>
        <h1 style="font-family: var(--fuente-display); font-size: 2.2rem; color: var(--color-acento); margin-bottom: 0.75rem;">
            Bienvenido a CineMatch
        </h1>
        <p style="color: var(--color-texto-suave); max-width: 480px; margin: 0 auto 1.5rem;">
            Descubre películas y series según tus gustos. Configura tus preferencias y recibe recomendaciones personalizadas.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="../catalog/catalogo.php" class="btn btn--primario">Explorar catálogo</a>
            <a href="../user/perfil.php"      class="btn btn--secundario">Mis preferencias</a>
        </div>
    </section>

    <!-- Recomendados para ti -->
    <?php if (!empty($nombreUsuario)): ?>
    <section style="margin-bottom: 2.5rem;">
        <h2 class="seccion-titulo">Recomendados para ti</h2>
        <div class="grid-peliculas">
            <?php foreach ($recomendados as $item):
                $iId    = (int) $item['id'];
                $iTitulo = htmlspecialchars($item['titulo'],     ENT_QUOTES, 'UTF-8');
                $iTipo   = $item['tipo'] === 'serie' ? 'Serie' : 'Película';
                $iCal    = number_format((float) $item['calificacion'], 1);
                $iGeneros = htmlspecialchars($item['generos'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <article class="tarjeta-pelicula" data-generos="<?= $iGeneros ?>">
                <a href="../catalog/detalle.php?id=<?= $iId ?>">
                    <div class="tarjeta-pelicula__imagen"
                         style="display:flex; align-items:center; justify-content:center; font-size:3rem; color:var(--color-borde);">
                        🎬
                    </div>
                </a>
                <span class="tarjeta-pelicula__badge"><?= $iTipo ?></span>
                <div class="tarjeta-pelicula__info">
                    <h3 class="tarjeta-pelicula__titulo">
                        <a href="../catalog/detalle.php?id=<?= $iId ?>"><?= $iTitulo ?></a>
                    </h3>
                    <div class="tarjeta-pelicula__meta">
                        <span><?= (int)$item['anio'] ?></span>
                        <span class="tarjeta-pelicula__calificacion">⭐ <?= $iCal ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Destacados -->
    <section>
        <h2 class="seccion-titulo">Destacados</h2>
        <div class="grid-peliculas">
            <?php foreach ($destacados as $item):
                $iId     = (int) $item['id'];
                $iTitulo  = htmlspecialchars($item['titulo'],      ENT_QUOTES, 'UTF-8');
                $iTipo    = $item['tipo'] === 'serie' ? 'Serie' : 'Película';
                $iCal     = number_format((float) $item['calificacion'], 1);
                $iGeneros = htmlspecialchars($item['generos'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <article class="tarjeta-pelicula" data-generos="<?= $iGeneros ?>">
                <a href="../catalog/detalle.php?id=<?= $iId ?>">
                    <div class="tarjeta-pelicula__imagen"
                         style="display:flex; align-items:center; justify-content:center; font-size:3rem; color:var(--color-borde);">
                        🎬
                    </div>
                </a>
                <span class="tarjeta-pelicula__badge"><?= $iTipo ?></span>
                <div class="tarjeta-pelicula__info">
                    <h3 class="tarjeta-pelicula__titulo">
                        <a href="../catalog/detalle.php?id=<?= $iId ?>"><?= $iTitulo ?></a>
                    </h3>
                    <div class="tarjeta-pelicula__meta">
                        <span><?= (int)$item['anio'] ?></span>
                        <span class="tarjeta-pelicula__calificacion">⭐ <?= $iCal ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center; margin-top: 2rem;">
            <a href="../catalog/catalogo.php" class="btn btn--secundario">Ver catálogo completo →</a>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
