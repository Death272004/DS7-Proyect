<?php
/**
 * views/catalog/detalle.php
 * Detalle de película/serie con datos enriquecidos de OMDb.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

$contenidoModelo = new ContenidoModelo();
$omdb            = new OmdbApi();
$idContenido     = Seguridad::entero($_GET['id'] ?? 0, 0);

try {
    $contenido = $idContenido > 0 ? $contenidoModelo->obtenerPorId($idContenido) : null;
    if ($contenido && Seguridad::usuarioId()) {
        $contenidoModelo->registrarHistorial(Seguridad::usuarioId(), (int)$contenido['id']);
    }
    $similares = $contenido ? $contenidoModelo->obtenerSimilares((int)$contenido['id'], 3) : [];

    // Enriquecer contenido principal con OMDb
    if ($contenido) {
        $omdbTipo = $contenido['tipo'] === 'serie' ? 'series' : 'movie';
        $omdbData = $omdb->buscarPorTituloYTipo($contenido['titulo'], (int)($contenido['anio'] ?? 0), $omdbTipo);
        if ($omdbData) {
            if (empty($contenido['imagen_url']) && !empty($omdbData['Poster']) && $omdbData['Poster'] !== 'N/A') {
                $contenido['imagen_url'] = $omdbData['Poster'];
            }
            $contenido['imdb_id']      = $omdbData['imdbID']     ?? null;
            $contenido['imdb_rating']  = ($omdbData['imdbRating'] ?? 'N/A') !== 'N/A' ? $omdbData['imdbRating'] : null;
            $contenido['imdb_votes']   = ($omdbData['imdbVotes']  ?? 'N/A') !== 'N/A' ? $omdbData['imdbVotes']  : null;
            $contenido['director']     = ($omdbData['Director']   ?? 'N/A') !== 'N/A' ? $omdbData['Director']   : null;
            $contenido['actores']      = ($omdbData['Actors']     ?? 'N/A') !== 'N/A' ? $omdbData['Actors']     : null;
            $contenido['pais']         = ($omdbData['Country']    ?? 'N/A') !== 'N/A' ? $omdbData['Country']    : null;
            $contenido['idioma']       = ($omdbData['Language']   ?? 'N/A') !== 'N/A' ? $omdbData['Language']   : null;
            $contenido['awards']       = ($omdbData['Awards']     ?? 'N/A') !== 'N/A' ? $omdbData['Awards']     : null;
            $contenido['rated']        = ($omdbData['Rated']      ?? 'N/A') !== 'N/A' ? $omdbData['Rated']      : null;
            $contenido['sinopsis_omdb']= ($omdbData['Plot']       ?? 'N/A') !== 'N/A' ? $omdbData['Plot']       : null;
            // Ratings externos (Rotten Tomatoes, Metacritic…)
            $contenido['ratings_ext']  = $omdbData['Ratings'] ?? [];
        }
    }

    // Enriquecer similares con pósters
    $similares = $omdb->enriquecerContenidos($similares);

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
    <a href="catalogo.php" class="btn btn--secundario btn--pequeno" style="margin-bottom:1.5rem;display:inline-flex;">← Volver al catálogo</a>
    <div class="tarjeta texto-centro" style="padding:3rem;">
        <p style="font-size:2.5rem;margin-bottom:0.75rem">🎬</p>
        <p class="tarjeta__titulo">Contenido no encontrado</p>
        <p class="tarjeta__subtitulo">El título solicitado no existe o no está disponible.</p>
    </div>
</main>
<?php include __DIR__ . '/../partials/footer.php'; exit; endif;

$similares = $similares ?? [];

$titulo       = htmlspecialchars($contenido['titulo'],   ENT_QUOTES, 'UTF-8');
$tipo         = $contenido['tipo'] === 'serie' ? 'Serie' : 'Película';
$sinopsis     = htmlspecialchars($contenido['sinopsis_omdb'] ?? $contenido['sinopsis'] ?? '', ENT_QUOTES, 'UTF-8');
$generos      = htmlspecialchars($contenido['generos']  ?? '', ENT_QUOTES, 'UTF-8');
$calificacion = number_format((float)$contenido['calificacion'], 1);
?>

<main class="pagina-contenido contenedor">

    <a href="catalogo.php" class="btn btn--secundario btn--pequeno" style="margin-bottom:1.5rem;display:inline-flex;">
        ← Volver al catálogo
    </a>

    <!-- Detalle principal -->
    <div style="display:grid;grid-template-columns:260px 1fr;gap:2.5rem;align-items:start;margin-bottom:3rem;">

        <!-- Póster OMDb -->
        <div>
            <?php if (!empty($contenido['imagen_url'])): ?>
                <img src="<?= htmlspecialchars($contenido['imagen_url'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="Póster de <?= $titulo ?>"
                     style="width:100%;border-radius:var(--radio-grande);box-shadow:var(--sombra);">
            <?php else: ?>
                <div style="width:100%;aspect-ratio:2/3;background:var(--color-fondo-tarjeta);border:1px solid var(--color-borde);border-radius:var(--radio-grande);display:flex;align-items:center;justify-content:center;font-size:4rem;color:var(--color-borde);">
                    🎬
                </div>
            <?php endif; ?>

            <!-- Calificaciones externas (OMDb) -->
            <?php if (!empty($contenido['ratings_ext'])): ?>
            <div style="margin-top:1rem;display:flex;flex-direction:column;gap:0.5rem;">
                <?php foreach ($contenido['ratings_ext'] as $rating): ?>
                <div style="background:var(--color-fondo-tarjeta);border:1px solid var(--color-borde);border-radius:var(--radio);padding:0.5rem 0.75rem;display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:0.78rem;color:var(--color-texto-suave);">
                        <?= htmlspecialchars($rating['Source'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <strong style="color:var(--color-acento);font-size:0.9rem;">
                        <?= htmlspecialchars($rating['Value'], ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Información -->
        <div>
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                <span class="badge badge--activo"><?= $tipo ?></span>
                <?php if (!empty($contenido['rated'])): ?>
                    <span class="badge" style="background:var(--color-fondo-tarjeta);border:1px solid var(--color-borde);">
                        <?= htmlspecialchars($contenido['rated'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
                <span class="texto-suave"><?= (int)$contenido['anio'] ?></span>
                <?php if (!empty($contenido['duracion'])): ?>
                    <span class="texto-suave">· <?= (int)$contenido['duracion'] ?> min</span>
                <?php endif; ?>
                <?php if (!empty($contenido['pais'])): ?>
                    <span class="texto-suave">· 🌍 <?= htmlspecialchars($contenido['pais'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <h1 style="font-family:var(--fuente-display);font-size:2rem;margin-bottom:0.75rem;">
                <?= $titulo ?>
            </h1>

            <!-- Calificaciones -->
            <div style="display:flex;align-items:center;gap:1.25rem;margin-bottom:1rem;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:0.4rem;">
                    <span style="font-size:1.5rem;color:var(--color-acento);">⭐</span>
                    <span style="font-size:1.3rem;font-weight:700;color:var(--color-acento);"><?= $calificacion ?></span>
                    <span class="texto-suave">/ 10</span>
                </div>
                <?php if (!empty($contenido['imdb_rating'])): ?>
                <div style="display:flex;align-items:center;gap:0.4rem;background:#f5c518;color:#000;padding:0.25rem 0.6rem;border-radius:var(--radio);font-weight:700;font-size:0.88rem;">
                    IMDb <?= htmlspecialchars($contenido['imdb_rating'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($contenido['imdb_votes'])): ?>
                        <span style="font-weight:400;font-size:0.78rem;">(<?= htmlspecialchars($contenido['imdb_votes'], ENT_QUOTES, 'UTF-8') ?> votos)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($contenido['imdb_id'])): ?>
                <a href="https://www.imdb.com/title/<?= htmlspecialchars($contenido['imdb_id'], ENT_QUOTES, 'UTF-8') ?>/"
                   target="_blank" rel="noopener noreferrer"
                   class="btn btn--secundario btn--pequeno">Ver en IMDb ↗</a>
                <?php endif; ?>
            </div>

            <!-- Géneros -->
            <?php if (!empty($generos)): ?>
            <div style="margin-bottom:1.25rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
                <?php foreach (explode(',', $generos) as $genero): ?>
                    <span class="filtro-chip activo" style="cursor:default;font-size:0.8rem;">
                        <?= htmlspecialchars(trim($genero), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="separador"></div>

            <!-- Sinopsis -->
            <p style="color:var(--color-texto-suave);line-height:1.8;margin-bottom:1.5rem;">
                <?= $sinopsis ?>
            </p>

            <!-- Ficha técnica OMDb -->
            <?php if (!empty($contenido['director']) || !empty($contenido['actores']) || !empty($contenido['idioma'])): ?>
            <div style="background:var(--color-fondo-tarjeta);border:1px solid var(--color-borde);border-radius:var(--radio-grande);padding:1.25rem;margin-bottom:1.5rem;">
                <h3 style="font-size:0.95rem;margin-bottom:0.85rem;color:var(--color-texto);">📋 Ficha técnica</h3>
                <dl style="display:grid;grid-template-columns:auto 1fr;gap:0.4rem 1rem;font-size:0.875rem;">
                    <?php if (!empty($contenido['director'])): ?>
                    <dt style="color:var(--color-texto-suave);white-space:nowrap;">Director</dt>
                    <dd style="margin:0;"><?= htmlspecialchars($contenido['director'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($contenido['actores'])): ?>
                    <dt style="color:var(--color-texto-suave);white-space:nowrap;">Reparto</dt>
                    <dd style="margin:0;"><?= htmlspecialchars($contenido['actores'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($contenido['idioma'])): ?>
                    <dt style="color:var(--color-texto-suave);white-space:nowrap;">Idioma</dt>
                    <dd style="margin:0;"><?= htmlspecialchars($contenido['idioma'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($contenido['awards'])): ?>
                    <dt style="color:var(--color-texto-suave);white-space:nowrap;">Premios</dt>
                    <dd style="margin:0;"><?= htmlspecialchars($contenido['awards'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <button class="btn btn--primario">▶ Ver ahora</button>
                <button class="btn btn--secundario">+ Mi lista</button>
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
                $sImagen = htmlspecialchars($item['imagen_url'] ?? '', ENT_QUOTES, 'UTF-8');
                $sGeneros= htmlspecialchars($item['generos'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <article class="tarjeta-pelicula" data-generos="<?= $sGeneros ?>">
                <a href="detalle.php?id=<?= $sId ?>">
                    <?php if (!empty($sImagen)): ?>
                        <img class="tarjeta-pelicula__imagen omdb-poster"
                             src="<?= $sImagen ?>"
                             alt="Portada de <?= $sTitulo ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="tarjeta-pelicula__imagen"
                             style="display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--color-borde);">
                            🎬
                        </div>
                    <?php endif; ?>
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
