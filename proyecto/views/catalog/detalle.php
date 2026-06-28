<?php
/**
 * views/catalog/detalle.php
 * Detalle de una película o serie.
 *
 * Variables opcionales del controlador CatalogoController:
 *   $contenido  (array) - ['id','titulo','tipo','anio','duracion','calificacion','sinopsis','imagen_url','generos']
 *   $similares  (array) - Otros títulos del mismo género
 */

require_once __DIR__ . '/../../app/bootstrap.php';

$contenidoModelo = new ContenidoModelo();
$idContenido = Seguridad::entero($_GET['id'] ?? 0, 0);

try {
    $contenido = $idContenido > 0 ? $contenidoModelo->obtenerPorId($idContenido) : null;
    if ($contenido && Seguridad::usuarioId()) {
        $contenidoModelo->registrarHistorial(Seguridad::usuarioId(), (int)$contenido['id']);
    }
    $similares = $contenido ? $contenidoModelo->obtenerSimilares((int)$contenido['id'], 3) : [];
} catch (Throwable $e) {
    error_log('Error detalle: ' . $e->getMessage());
    $contenido = null;
    $similares = [];
}

$tituloPagina = 'Detalle';
$paginaActiva  = 'catalogo';
include __DIR__ . '/../partials/header.php';

if (empty($contenido)): ?>
<main class="pagina-contenido contenedor">
    <a href="catalogo.php"
       class="btn btn--secundario btn--pequeno"
       style="margin-bottom: 1.5rem; display: inline-flex;">
        ← Volver al catálogo
    </a>
    <div class="tarjeta texto-centro" style="padding:3rem;">
        <p style="font-size:2.5rem; margin-bottom:0.75rem">🎬</p>
        <p class="tarjeta__titulo">Contenido no encontrado</p>
        <p class="tarjeta__subtitulo">El título solicitado no existe o no está disponible.</p>
    </div>
</main>
<?php include __DIR__ . '/../partials/footer.php'; exit; endif;

$similares = $similares ?? [];

$titulo       = htmlspecialchars($contenido['titulo'],   ENT_QUOTES, 'UTF-8');
$tipo         = $contenido['tipo'] === 'serie' ? 'Serie' : 'Película';
$sinopsis     = htmlspecialchars($contenido['sinopsis'] ?? '', ENT_QUOTES, 'UTF-8');
$generos      = htmlspecialchars($contenido['generos']  ?? '', ENT_QUOTES, 'UTF-8');
$calificacion = number_format((float)$contenido['calificacion'], 1);
?>

<main class="pagina-contenido contenedor">

    <!-- Botón volver -->
    <a href="catalogo.php"
       class="btn btn--secundario btn--pequeno"
       style="margin-bottom: 1.5rem; display: inline-flex;">
        ← Volver al catálogo
    </a>

    <!-- Detalle principal -->
    <div style="display: grid; grid-template-columns: 240px 1fr; gap: 2rem; align-items: start; margin-bottom: 3rem;">

        <!-- Póster -->
        <div>
            <?php if (!empty($contenido['imagen_url'])): ?>
                <img src="<?= htmlspecialchars($contenido['imagen_url'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="Póster de <?= $titulo ?>"
                     style="width:100%; border-radius: var(--radio-grande); box-shadow: var(--sombra);">
            <?php else: ?>
                <div style="width:100%; aspect-ratio:2/3; background: var(--color-fondo-tarjeta); border: 1px solid var(--color-borde); border-radius: var(--radio-grande); display:flex; align-items:center; justify-content:center; font-size:4rem; color:var(--color-borde);">
                    🎬
                </div>
            <?php endif; ?>
        </div>

        <!-- Información -->
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                <span class="badge badge--activo"><?= $tipo ?></span>
                <span class="texto-suave"><?= (int)$contenido['anio'] ?></span>
                <?php if (!empty($contenido['duracion'])): ?>
                    <span class="texto-suave">· <?= (int)$contenido['duracion'] ?> min</span>
                <?php endif; ?>
            </div>

            <h1 style="font-family: var(--fuente-display); font-size: 2rem; margin-bottom: 0.75rem;">
                <?= $titulo ?>
            </h1>

            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <span style="font-size: 1.5rem; color: var(--color-acento);">⭐</span>
                <span style="font-size: 1.3rem; font-weight: 700; color: var(--color-acento);"><?= $calificacion ?></span>
                <span class="texto-suave">/ 10</span>
            </div>

            <?php if (!empty($generos)): ?>
            <div style="margin-bottom: 1.25rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <?php foreach (explode(',', $generos) as $genero): ?>
                    <span class="filtro-chip activo" style="cursor:default; font-size:0.8rem;">
                        <?= htmlspecialchars(trim($genero), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="separador"></div>

            <p style="color: var(--color-texto-suave); line-height: 1.8; margin-bottom: 1.5rem;">
                <?= $sinopsis ?>
            </p>

            <!-- Acciones del usuario -->
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button class="btn btn--primario">
                    ▶ Ver ahora
                </button>
                <button class="btn btn--secundario">
                    + Mi lista
                </button>
            </div>
        </div>
    </div>

    <!-- Títulos similares -->
    <?php if (!empty($similares)): ?>
    <section>
        <h2 class="seccion-titulo">También te puede gustar</h2>
        <div class="grid-peliculas">
            <?php foreach ($similares as $item):
                $sId     = (int) $item['id'];
                $sTitulo = htmlspecialchars($item['titulo'],      ENT_QUOTES, 'UTF-8');
                $sTipo   = $item['tipo'] === 'serie' ? 'Serie' : 'Película';
                $sCal    = number_format((float)$item['calificacion'], 1);
                $sGeneros = htmlspecialchars($item['generos'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <article class="tarjeta-pelicula" data-generos="<?= $sGeneros ?>">
                <a href="detalle.php?id=<?= $sId ?>">
                    <div class="tarjeta-pelicula__imagen"
                         style="display:flex; align-items:center; justify-content:center; font-size:3rem; color:var(--color-borde);">
                        🎬
                    </div>
                </a>
                <span class="tarjeta-pelicula__badge"><?= $sTipo ?></span>
                <div class="tarjeta-pelicula__info">
                    <h3 class="tarjeta-pelicula__titulo">
                        <a href="detalle.php?id=<?= $sId ?>"><?= $sTitulo ?></a>
                    </h3>
                    <div class="tarjeta-pelicula__meta">
                        <span><?= (int)$item['anio'] ?></span>
                        <span class="tarjeta-pelicula__calificacion">⭐ <?= $sCal ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
