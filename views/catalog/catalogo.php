<?php
include __DIR__ . '/../partials/header.php';

$terminoBusquedaRaw = $terminoBusqueda ?? '';
$terminoBusqueda = htmlspecialchars($terminoBusquedaRaw, ENT_QUOTES, 'UTF-8');
$filtroTipoActivo = in_array($filtroTipoActivo ?? 'todos', ['todos', 'pelicula', 'serie'], true) ? ($filtroTipoActivo ?? 'todos') : 'todos';
$filtroGeneroActivo = (int)($filtroGeneroActivo ?? 0);

$generosDisponibles = $generosDisponibles ?? [];
$contenidos = $contenidos ?? [];
$recomendados = $recomendados ?? [];
$ultimasVistas = $ultimasVistas ?? [];
$mostrarTodo = !empty($mostrarTodo);
$primerNombre = htmlspecialchars(explode(' ', trim($nombreUsuario ?: 'Usuario'))[0] ?: 'Usuario', ENT_QUOTES, 'UTF-8');

$heroes = array_slice(!empty($recomendados) ? $recomendados : $contenidos, 0, 5);
$recomendacionesVista = !empty($recomendados) ? $recomendados : array_slice($contenidos, 0, 5);
if (count($recomendacionesVista) < 5) {
    $idsActuales = array_map(static fn ($item): int => (int)$item['id'], $recomendacionesVista);
    foreach ($contenidos as $item) {
        if (count($recomendacionesVista) >= 5) {
            break;
        }
        if (!in_array((int)$item['id'], $idsActuales, true)) {
            $recomendacionesVista[] = $item;
            $idsActuales[] = (int)$item['id'];
        }
    }
}
$filaAfinidad = array_slice($contenidos, 0, 5);
if (!empty($recomendacionesVista)) {
    $idsRecomendados = array_map(static fn ($item): int => (int)$item['id'], $recomendacionesVista);
    $filaAfinidad = array_values(array_filter($contenidos, static fn ($item): bool => !in_array((int)$item['id'], $idsRecomendados, true)));
    $filaAfinidad = array_slice($filaAfinidad, 0, 5);
}

$generoActivoNombre = '';
foreach ($generosDisponibles as $genero) {
    if ((int)$genero['id'] === $filtroGeneroActivo) {
        $generoActivoNombre = (string)$genero['nombre'];
        break;
    }
}
if ($generoActivoNombre === '' && !empty($recomendacionesVista[0]['generos'])) {
    $generoActivoNombre = trim(explode(',', (string)$recomendacionesVista[0]['generos'])[0]);
}
$generoActivoNombre = $generoActivoNombre !== '' ? $generoActivoNombre : 'tus gustos';

$renderCard = static function (array $item) use ($url): void {
    $itemId = (int)$item['id'];
    $itemTitulo = htmlspecialchars($item['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
    $itemTipo = ($item['tipo'] ?? '') === 'serie' ? 'Serie' : 'Película';
    $itemImagen = htmlspecialchars($item['imagen_url'] ?? '', ENT_QUOTES, 'UTF-8');
    $itemGeneros = htmlspecialchars($item['generos'] ?? '', ENT_QUOTES, 'UTF-8');
    $coincidencia = (int)($item['coincidencia'] ?? 65);
    ?>
    <article class="catalogo-card" data-generos="<?= $itemGeneros ?>" aria-label="<?= $itemTitulo ?>">
        <a class="catalogo-card__poster" href="<?= htmlspecialchars($url('detalle', ['id' => $itemId]), ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($itemImagen !== ''): ?>
                <img src="<?= $itemImagen ?>" alt="Portada de <?= $itemTitulo ?>" loading="lazy">
            <?php else: ?>
                <span class="catalogo-card__placeholder"><?= app_icon('film', 'icono--grande') ?></span>
            <?php endif; ?>
        </a>
        <button type="button"
                class="catalogo-card__guardar js-guardar-contenido"
                data-id="<?= $itemId ?>"
                data-titulo="<?= $itemTitulo ?>"
                aria-label="Guardar <?= $itemTitulo ?>">
            <?= app_icon('bookmark') ?>
        </button>
        <div class="catalogo-card__info">
            <h3><a href="<?= htmlspecialchars($url('detalle', ['id' => $itemId]), ENT_QUOTES, 'UTF-8') ?>"><?= $itemTitulo ?></a></h3>
            <span class="catalogo-card__genero"><?= htmlspecialchars(trim(explode(',', (string)($item['generos'] ?? $itemTipo))[0]), ENT_QUOTES, 'UTF-8') ?></span>
            <p><?= $coincidencia ?>% de coincidencia</p>
        </div>
    </article>
    <?php
};
?>

<main class="catalogo-experiencia">
    <section class="catalogo-shell contenedor">
        <div class="catalogo-hero-top">
            <div>
                <p class="catalogo-eyebrow">Tus recomendaciones</p>
                <h1>Hola, <?= $primerNombre ?></h1>
                <p>Contenido seleccionado segun tus gustos, preferencias e historial reciente.</p>
            </div>

            <form method="GET" action="<?= htmlspecialchars(app_base_url() . '/index.php', ENT_QUOTES, 'UTF-8') ?>" role="search" class="catalogo-search">
                <input type="hidden" name="ruta" value="catalogo">
                <?php if ($filtroTipoActivo !== 'todos'): ?>
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($filtroTipoActivo, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <?php if ($filtroGeneroActivo > 0): ?>
                    <input type="hidden" name="genero" value="<?= $filtroGeneroActivo ?>">
                <?php endif; ?>
                <?= app_icon('search') ?>
                <input type="search"
                       id="busqueda-catalogo"
                       name="buscar"
                       value="<?= $terminoBusqueda ?>"
                       placeholder="Buscar peliculas, series, generos y mas..."
                       maxlength="100"
                       autocomplete="off"
                       aria-label="Buscar por titulo">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <nav class="catalogo-chips" aria-label="Filtros del catalogo">
            <?php
                $baseSinTipo = [];
                if ($terminoBusquedaRaw !== '') { $baseSinTipo['buscar'] = $terminoBusquedaRaw; }
                if ($filtroGeneroActivo > 0) { $baseSinTipo['genero'] = $filtroGeneroActivo; }
            ?>
            <a href="<?= htmlspecialchars($url('catalogo', $baseSinTipo), ENT_QUOTES, 'UTF-8') ?>" class="<?= $filtroTipoActivo === 'todos' ? 'activo' : '' ?>">
                <?= app_icon('sparkles') ?> Todos
            </a>
            <a href="<?= htmlspecialchars($url('catalogo', array_merge($baseSinTipo, ['tipo' => 'pelicula'])), ENT_QUOTES, 'UTF-8') ?>" class="<?= $filtroTipoActivo === 'pelicula' ? 'activo' : '' ?>">
                <?= app_icon('film') ?> Películas
            </a>
            <a href="<?= htmlspecialchars($url('catalogo', array_merge($baseSinTipo, ['tipo' => 'serie'])), ENT_QUOTES, 'UTF-8') ?>" class="<?= $filtroTipoActivo === 'serie' ? 'activo' : '' ?>">
                <?= app_icon('play') ?> Series
            </a>
            <?php foreach (array_slice($generosDisponibles, 0, 6) as $g): ?>
                <?php
                    $queryGenero = [];
                    if ($terminoBusquedaRaw !== '') { $queryGenero['buscar'] = $terminoBusquedaRaw; }
                    if ($filtroTipoActivo !== 'todos') { $queryGenero['tipo'] = $filtroTipoActivo; }
                    $queryGenero['genero'] = (int)$g['id'];
                ?>
                <a href="<?= htmlspecialchars($url('catalogo', $queryGenero), ENT_QUOTES, 'UTF-8') ?>" class="<?= $filtroGeneroActivo === (int)$g['id'] ? 'activo' : '' ?>">
                    <?= app_genero_icon((string)$g['nombre'], 'chip-genero__icono') ?>
                    <?= htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (!empty($terminoBusquedaRaw) || $filtroTipoActivo !== 'todos' || $filtroGeneroActivo > 0): ?>
            <div class="catalogo-filtros-activos">
                <span>Mostrando <?= count($contenidos) ?> resultado<?= count($contenidos) !== 1 ? 's' : '' ?></span>
                <a href="<?= htmlspecialchars($url('catalogo'), ENT_QUOTES, 'UTF-8') ?>">Limpiar filtros</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($heroes)): ?>
            <div class="catalogo-destacados-rotador" data-intervalo="3000">
                <?php foreach ($heroes as $indiceHero => $hero): ?>
                    <?php
                        $heroId = (int)$hero['id'];
                        $heroTitulo = htmlspecialchars($hero['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
                        $heroSinopsis = htmlspecialchars($hero['sinopsis'] ?? 'Una seleccion destacada para tu proxima noche de peliculas y series.', ENT_QUOTES, 'UTF-8');
                        $heroImagen = htmlspecialchars($hero['imagen_url'] ?? '', ENT_QUOTES, 'UTF-8');
                        $heroGenero = htmlspecialchars(trim(explode(',', (string)($hero['generos'] ?? 'Recomendacion'))[0]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <section class="catalogo-destacado <?= $indiceHero === 0 ? 'activo' : '' ?>" data-hero-index="<?= $indiceHero ?>">
                        <div class="catalogo-destacado__texto">
                            <span class="catalogo-destacado__badge"><?= app_icon('star') ?> Recomendación destacada <?= $indiceHero + 1 ?>/<?= count($heroes) ?></span>
                            <h2><?= $heroTitulo ?></h2>
                            <p><?= mb_strlen($heroSinopsis) > 160 ? htmlspecialchars(mb_substr(htmlspecialchars_decode($heroSinopsis, ENT_QUOTES), 0, 157) . '...', ENT_QUOTES, 'UTF-8') : $heroSinopsis ?></p>
                            <div class="catalogo-destacado__acciones">
                                <a class="btn btn--primario" href="<?= htmlspecialchars($url('detalle', ['id' => $heroId]), ENT_QUOTES, 'UTF-8') ?>"><?= app_icon('play') ?> Ver detalles</a>
                                <button class="btn btn--cristal js-guardar-contenido"
                                        type="button"
                                        data-id="<?= $heroId ?>"
                                        data-titulo="<?= $heroTitulo ?>">
                                    <?= app_icon('bookmark') ?> Guardar
                                </button>
                            </div>
                        </div>
                        <a class="catalogo-destacado__poster" href="<?= htmlspecialchars($url('detalle', ['id' => $heroId]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Ver <?= $heroTitulo ?>">
                            <?php if ($heroImagen !== ''): ?>
                                <img src="<?= $heroImagen ?>" alt="Portada de <?= $heroTitulo ?>">
                            <?php else: ?>
                                <?= app_icon('film', 'icono--grande') ?>
                            <?php endif; ?>
                            <span><?= $heroGenero ?></span>
                        </a>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($recomendacionesVista)): ?>
            <section class="catalogo-seccion">
                <div class="catalogo-seccion__encabezado">
                    <h2>Recomendado para ti</h2>
                    <a href="<?= htmlspecialchars($url('catalogo', ['ver' => 'todos']) . '#todos', ENT_QUOTES, 'UTF-8') ?>">Ver más <?= app_icon('chevron-right') ?></a>
                </div>
                <div class="catalogo-grid-horizontal">
                    <?php foreach (array_slice($recomendacionesVista, 0, 5) as $item) { $renderCard($item); } ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($ultimasVistas)): ?>
            <section class="catalogo-seccion">
                <div class="catalogo-seccion__encabezado">
                    <h2>Continúa explorando</h2>
                </div>
                <div class="catalogo-grid-horizontal catalogo-grid-horizontal--compacto">
                    <?php foreach (array_slice($ultimasVistas, 0, 5) as $item) { $renderCard($item); } ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($filaAfinidad)): ?>
            <section class="catalogo-seccion">
                <div class="catalogo-seccion__encabezado">
                    <h2>Porque te gusta <?= htmlspecialchars(mb_strtolower($generoActivoNombre), ENT_QUOTES, 'UTF-8') ?></h2>
                    <a href="<?= htmlspecialchars($url('perfil'), ENT_QUOTES, 'UTF-8') ?>">Editar gustos <?= app_icon('chevron-right') ?></a>
                </div>
                <div class="catalogo-grid-horizontal">
                    <?php foreach ($filaAfinidad as $item) { $renderCard($item); } ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($mostrarTodo && !empty($contenidos)): ?>
            <section class="catalogo-seccion" id="todos">
                <div class="catalogo-seccion__encabezado">
                    <h2>Todos los títulos</h2>
                    <span class="texto-suave"><?= count($contenidos) ?> disponibles</span>
                </div>
                <div class="catalogo-grid-todos">
                    <?php foreach ($contenidos as $item) { $renderCard($item); } ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (empty($contenidos)): ?>
            <section class="catalogo-vacio">
                <?= app_icon('film', 'icono--grande') ?>
                <h2>Sin resultados</h2>
                <p>No encontramos contenido con esos filtros.</p>
                <a class="btn btn--primario" href="<?= htmlspecialchars($url('catalogo'), ENT_QUOTES, 'UTF-8') ?>">Ver todo</a>
            </section>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
