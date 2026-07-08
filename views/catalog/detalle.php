<?php
include __DIR__ . '/../partials/header.php';

if (empty($contenido)): ?>
<main class="pagina-contenido contenedor">
    <a href="<?= htmlspecialchars($url('catalogo'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn--secundario btn--pequeno" style="margin-bottom:1.5rem;display:inline-flex;">Volver al catálogo</a>
    <div class="tarjeta texto-centro" style="padding:3rem;">
        <p class="estado-vacio__icono"><?= app_icon('film', 'icono--grande') ?></p>
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
$trailerEmbed = $trailerEmbed ?? null;
$trailerBusqueda = 'https://www.youtube.com/results?search_query=' . rawurlencode($contenido['titulo'] . ' trailer oficial');
?>

<main class="pagina-contenido contenedor">

    <a href="<?= htmlspecialchars($url('catalogo'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn--secundario btn--pequeno" style="margin-bottom:1.5rem;display:inline-flex;">
        Volver al catálogo
    </a>

    <!-- Detalle principal -->
    <div style="display:grid;grid-template-columns:260px 1fr;gap:2.5rem;align-items:start;margin-bottom:3rem;">

        <!-- Póster -->
        <div>
            <?php if (!empty($contenido['imagen_url'])): ?>
                <img src="<?= htmlspecialchars($contenido['imagen_url'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="Póster de <?= $titulo ?>"
                     style="width:100%;border-radius:var(--radio-grande);box-shadow:var(--sombra);">
            <?php else: ?>
                <div style="width:100%;aspect-ratio:2/3;background:var(--color-fondo-tarjeta);border:1px solid var(--color-borde);border-radius:var(--radio-grande);display:flex;align-items:center;justify-content:center;font-size:4rem;color:var(--color-borde);">
                    <?= app_icon('film', 'icono--grande') ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Información -->
        <div>
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                <span class="badge badge--activo"><?= $tipo ?></span>
                <span class="texto-suave"><?= (int)$contenido['anio'] ?></span>
                <?php if (!empty($contenido['duracion'])): ?>
                    <span class="texto-suave">· <?= (int)$contenido['duracion'] ?> min</span>
                <?php endif; ?>
            </div>

            <h1 style="font-family:var(--fuente-display);font-size:2rem;margin-bottom:0.75rem;">
                <?= $titulo ?>
            </h1>

            <!-- Calificaciones -->
            <div style="display:flex;align-items:center;gap:1.25rem;margin-bottom:1rem;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:0.4rem;">
                    <?= app_icon('star', 'icono--estrella') ?>
                    <span style="font-size:1.3rem;font-weight:700;color:var(--color-acento);"><?= $calificacion ?></span>
                    <span class="texto-suave">/ 10</span>
                </div>
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

            <section class="detalle-trailer" id="trailer">
                <h2><?= app_icon('play') ?> Trailer oficial</h2>
                <?php if ($trailerEmbed): ?>
                    <div class="detalle-trailer__video">
                        <iframe src="<?= htmlspecialchars($trailerEmbed, ENT_QUOTES, 'UTF-8') ?>"
                                title="Trailer oficial de <?= $titulo ?>"
                                loading="lazy"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <p class="texto-suave" style="margin-bottom:1rem;">No tenemos un trailer enlazado todavía para este título.</p>
                    <a class="btn btn--secundario btn--pequeno"
                       href="<?= htmlspecialchars($trailerBusqueda, ENT_QUOTES, 'UTF-8') ?>"
                       target="_blank"
                       rel="noopener noreferrer">Buscar trailer</a>
                <?php endif; ?>
            </section>

            <!-- Acciones -->
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <a class="btn btn--primario" href="#trailer"><?= app_icon('play') ?> Ver trailer</a>
                <button class="btn btn--secundario js-guardar-contenido"
                        type="button"
                        data-id="<?= (int)$contenido['id'] ?>"
                        data-titulo="<?= $titulo ?>">
                    <?= app_icon('bookmark') ?> Guardar
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
                $sImagen = htmlspecialchars($item['imagen_url'] ?? '', ENT_QUOTES, 'UTF-8');
                $sGeneros= htmlspecialchars($item['generos'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <article class="tarjeta-pelicula" data-generos="<?= $sGeneros ?>">
                <a href="<?= htmlspecialchars($url('detalle', ['id' => $sId]), ENT_QUOTES, 'UTF-8') ?>">
                    <?php if (!empty($sImagen)): ?>
                        <img class="tarjeta-pelicula__imagen"
                             src="<?= $sImagen ?>"
                             alt="Portada de <?= $sTitulo ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="tarjeta-pelicula__imagen"
                             style="display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--color-borde);">
                            <?= app_icon('film', 'icono--grande') ?>
                        </div>
                    <?php endif; ?>
                </a>
                <span class="tarjeta-pelicula__badge"><?= $sTipo ?></span>
                <div class="tarjeta-pelicula__info">
                    <h3 class="tarjeta-pelicula__titulo">
                        <a href="<?= htmlspecialchars($url('detalle', ['id' => $sId]), ENT_QUOTES, 'UTF-8') ?>"><?= $sTitulo ?></a>
                    </h3>
                    <div class="tarjeta-pelicula__meta">
                        <span><?= (int)$item['anio'] ?></span>
                        <span class="tarjeta-pelicula__calificacion"><?= app_icon('star') ?> <?= $sCal ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
