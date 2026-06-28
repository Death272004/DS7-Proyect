<?php
/**
 * views/catalog/catalogo.php
 * Catálogo de películas y series — sin sesiones.
 *
 * Variables opcionales del controlador CatalogoController:
 *   $contenidos          (array)  - [['id','titulo','tipo','anio','calificacion','imagen_url','generos'], ...]
 *   $generosDisponibles  (array)  - [['id','nombre'], ...]
 *   $terminoBusqueda     (string) - Término GET ?buscar=
 *   $filtroTipoActivo    (string) - 'todos'|'pelicula'|'serie'
 *   $filtroGeneroActivo  (int)    - ID género GET ?genero=
 */

require_once __DIR__ . '/../../app/bootstrap.php';

$contenidoModelo = new ContenidoModelo();
$terminoBusqueda = Seguridad::limpiarTexto($_GET['buscar'] ?? '', 100);
$tipoSolicitado = Seguridad::limpiarTexto($_GET['tipo'] ?? 'todos', 20);
$filtroTipoActivo = in_array($tipoSolicitado, ['todos', 'pelicula', 'serie'], true) ? $tipoSolicitado : 'todos';
$filtroGeneroActivo = Seguridad::entero($_GET['genero'] ?? 0, 0);

try {
    $generosDisponibles = $contenidoModelo->obtenerGeneros();
    $contenidos = $contenidoModelo->listarCatalogo($terminoBusqueda, $filtroTipoActivo, $filtroGeneroActivo);
} catch (Throwable $e) {
    error_log('Error catalogo: ' . $e->getMessage());
}

$tituloPagina = 'Catálogo';
$paginaActiva  = 'catalogo';
include __DIR__ . '/../partials/header.php';

// Defaults seguros
$terminoBusquedaRaw = $terminoBusqueda ?? '';
$terminoBusqueda   = htmlspecialchars($terminoBusquedaRaw, ENT_QUOTES, 'UTF-8');
$filtroTipoActivo  = in_array($filtroTipoActivo  ?? 'todos', ['todos','pelicula','serie']) ? ($filtroTipoActivo ?? 'todos') : 'todos';
$filtroGeneroActivo = (int)($filtroGeneroActivo ?? 0);

$generosDisponibles = $generosDisponibles ?? [
    ['id'=>1,'nombre'=>'Acción'],   ['id'=>2,'nombre'=>'Comedia'],
    ['id'=>3,'nombre'=>'Drama'],    ['id'=>4,'nombre'=>'Terror'],
    ['id'=>5,'nombre'=>'Sci-Fi'],   ['id'=>6,'nombre'=>'Romance'],
    ['id'=>7,'nombre'=>'Thriller'], ['id'=>8,'nombre'=>'Animación'],
    ['id'=>9,'nombre'=>'Documental'],['id'=>10,'nombre'=>'Fantasía'],
];

$contenidos = $contenidos ?? [
    ['id'=>1,'titulo'=>'Interstellar',    'tipo'=>'pelicula','anio'=>2014,'calificacion'=>8.7,'imagen_url'=>'','generos'=>'Sci-Fi,Drama'],
    ['id'=>2,'titulo'=>'Breaking Bad',    'tipo'=>'serie',   'anio'=>2008,'calificacion'=>9.5,'imagen_url'=>'','generos'=>'Drama,Thriller'],
    ['id'=>3,'titulo'=>'El Señor de los Anillos','tipo'=>'pelicula','anio'=>2001,'calificacion'=>8.9,'imagen_url'=>'','generos'=>'Fantasía,Aventura'],
    ['id'=>4,'titulo'=>'Stranger Things', 'tipo'=>'serie',   'anio'=>2016,'calificacion'=>8.7,'imagen_url'=>'','generos'=>'Sci-Fi,Terror'],
    ['id'=>5,'titulo'=>'The Dark Knight', 'tipo'=>'pelicula','anio'=>2008,'calificacion'=>9.0,'imagen_url'=>'','generos'=>'Acción,Thriller'],
    ['id'=>6,'titulo'=>'Friends',         'tipo'=>'serie',   'anio'=>1994,'calificacion'=>8.9,'imagen_url'=>'','generos'=>'Comedia,Romance'],
];
?>

<main class="pagina-contenido contenedor">

    <h1 class="seccion-titulo">Catálogo</h1>

    <!-- Buscador -->
    <form method="GET" action="catalogo.php" role="search" class="barra-busqueda">
        <input type="search"
               id="busqueda-catalogo"
               name="buscar"
               class="campo-input"
               value="<?= $terminoBusqueda ?>"
               placeholder="Busca por título..."
               maxlength="100"
               autocomplete="off"
               aria-label="Buscar por título">
        <?php if ($filtroGeneroActivo > 0): ?>
            <input type="hidden" name="genero" value="<?= $filtroGeneroActivo ?>">
        <?php endif; ?>

        <select name="tipo" class="campo-select" style="width:auto; min-width:130px;" aria-label="Tipo">
            <option value="todos"    <?= $filtroTipoActivo === 'todos'    ? 'selected' : '' ?>>Todo</option>
            <option value="pelicula" <?= $filtroTipoActivo === 'pelicula' ? 'selected' : '' ?>>Películas</option>
            <option value="serie"    <?= $filtroTipoActivo === 'serie'    ? 'selected' : '' ?>>Series</option>
        </select>

        <button type="submit" class="btn btn--primario">🔍 Buscar</button>

        <?php if (!empty($terminoBusqueda) || $filtroGeneroActivo): ?>
            <a href="catalogo.php" class="btn btn--secundario">Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Chips de género -->
    <nav aria-label="Filtrar por género">
        <div class="filtros-catalogo">
            <?php
                $queryBase = [];
                if ($terminoBusquedaRaw !== '') {
                    $queryBase['buscar'] = $terminoBusquedaRaw;
                }
                if ($filtroTipoActivo !== 'todos') {
                    $queryBase['tipo'] = $filtroTipoActivo;
                }
                $urlTodos = 'catalogo.php' . (!empty($queryBase) ? '?' . http_build_query($queryBase) : '');
            ?>
            <a href="<?= htmlspecialchars($urlTodos, ENT_QUOTES, 'UTF-8') ?>"
               class="filtro-chip <?= $filtroGeneroActivo === 0 ? 'activo' : '' ?>"
               data-genero="todos">Todos</a>
            <?php foreach ($generosDisponibles as $g): ?>
                <?php
                    $queryGenero = $queryBase;
                    $queryGenero['genero'] = (int)$g['id'];
                    $urlGenero = 'catalogo.php?' . http_build_query($queryGenero);
                ?>
                <a href="<?= htmlspecialchars($urlGenero, ENT_QUOTES, 'UTF-8') ?>"
                      class="filtro-chip <?= $filtroGeneroActivo === (int)$g['id'] ? 'activo' : '' ?>"
                      data-genero="<?= htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <!-- Grid de contenido -->
    <?php if (empty($contenidos)): ?>
        <div class="tarjeta texto-centro" style="padding:3rem; margin-top:1rem">
            <p style="font-size:2.5rem; margin-bottom:0.75rem">🎬</p>
            <p class="tarjeta__titulo">Sin resultados</p>
            <p class="tarjeta__subtitulo">No encontramos contenido con esos filtros.</p>
            <a href="catalogo.php" class="btn btn--primario margen-arriba">Ver todo</a>
        </div>
    <?php else: ?>
        <p class="texto-suave" style="margin-bottom:1rem">
            Mostrando <?= count($contenidos) ?> resultado<?= count($contenidos) !== 1 ? 's' : '' ?>
            <?php if (!empty($terminoBusqueda)): ?>
                para "<strong><?= $terminoBusqueda ?></strong>"
            <?php endif; ?>
        </p>

        <div class="grid-peliculas">
            <?php foreach ($contenidos as $item):
                $itemId           = (int) $item['id'];
                $itemTitulo       = htmlspecialchars($item['titulo'],      ENT_QUOTES, 'UTF-8');
                $itemTipo         = $item['tipo'] === 'serie' ? 'Serie' : 'Película';
                $itemAnio         = (int) $item['anio'];
                $itemCalificacion = number_format((float) $item['calificacion'], 1);
                $itemImagen       = htmlspecialchars($item['imagen_url']   ?? '', ENT_QUOTES, 'UTF-8');
                $itemGeneros      = htmlspecialchars($item['generos']      ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <article class="tarjeta-pelicula"
                     data-generos="<?= $itemGeneros ?>"
                     aria-label="<?= $itemTitulo ?>">
                <a href="detalle.php?id=<?= $itemId ?>">
                    <?php if (!empty($itemImagen)): ?>
                        <img class="tarjeta-pelicula__imagen"
                             src="<?= $itemImagen ?>"
                             alt="Portada de <?= $itemTitulo ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="tarjeta-pelicula__imagen"
                             style="display:flex; align-items:center; justify-content:center; font-size:3rem; color:var(--color-borde);">
                            🎬
                        </div>
                    <?php endif; ?>
                </a>
                <span class="tarjeta-pelicula__badge"><?= $itemTipo ?></span>
                <div class="tarjeta-pelicula__info">
                    <h2 class="tarjeta-pelicula__titulo">
                        <a href="detalle.php?id=<?= $itemId ?>"><?= $itemTitulo ?></a>
                    </h2>
                    <div class="tarjeta-pelicula__meta">
                        <span><?= $itemAnio ?></span>
                        <span class="tarjeta-pelicula__calificacion">⭐ <?= $itemCalificacion ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
