<?php
include __DIR__ . '/../partials/header.php';

$seccionActiva       = in_array($seccionActiva ?? 'contenido', ['contenido','usuarios','estadisticas']) ? ($seccionActiva ?? 'contenido') : 'contenido';
$totalUsuarios       = $totalUsuarios       ?? 0;
$totalContenidos     = $totalContenidos     ?? 0;
$totalCalificaciones = $totalCalificaciones ?? 0;
$generoMasVisto      = htmlspecialchars($generoMasVisto ?? 'Sin visitas', ENT_QUOTES, 'UTF-8');
$mensajeExito        = $mensajeExito        ?? '';
$erroresFormulario   = $erroresFormulario   ?? [];

$generosPopulares = $generosPopulares ?? [];
$contenidos = $contenidos ?? [];
$usuarios = $usuarios ?? [];
$generosOpciones = $generosOpciones ?? [];
?>

<div class="panel-admin">

    <!-- Sidebar -->
    <nav class="panel-admin__sidebar" aria-label="Menú del panel">
        <p class="panel-admin__sidebar-titulo">Gestión</p>

        <a href="<?= htmlspecialchars($url('admin', ['seccion' => 'contenido']), ENT_QUOTES, 'UTF-8') ?>"
           class="panel-admin__nav-item <?= $seccionActiva === 'contenido'    ? 'activo' : '' ?>">
            <?= app_icon('clapperboard', 'panel-admin__nav-icono') ?> Películas y Series
        </a>
        <a href="<?= htmlspecialchars($url('admin', ['seccion' => 'usuarios']), ENT_QUOTES, 'UTF-8') ?>"
           class="panel-admin__nav-item <?= $seccionActiva === 'usuarios'     ? 'activo' : '' ?>">
            <?= app_icon('users', 'panel-admin__nav-icono') ?> Usuarios
        </a>
        <a href="<?= htmlspecialchars($url('admin', ['seccion' => 'estadisticas']), ENT_QUOTES, 'UTF-8') ?>"
           class="panel-admin__nav-item <?= $seccionActiva === 'estadisticas' ? 'activo' : '' ?>">
            <?= app_icon('bar-chart', 'panel-admin__nav-icono') ?> Estadísticas
        </a>    </nav>

    <!-- Contenido principal -->
    <main class="panel-admin__contenido">

        <?php if (!empty($mensajeExito)): ?>
        <div class="alerta alerta--exito" role="alert">
            <span class="alerta__icono">✓</span>
            <span><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="grid-estadisticas">
            <div class="tarjeta-stat">
                <span class="tarjeta-stat__numero"><?= number_format($totalUsuarios) ?></span>
                <span class="tarjeta-stat__etiqueta"><?= app_icon('users') ?> Usuarios registrados</span>
            </div>
            <div class="tarjeta-stat">
                <span class="tarjeta-stat__numero"><?= number_format($totalContenidos) ?></span>
                <span class="tarjeta-stat__etiqueta"><?= app_icon('clapperboard') ?> Títulos en catálogo</span>
            </div>
            <div class="tarjeta-stat">
                <span class="tarjeta-stat__numero"><?= number_format($totalCalificaciones) ?></span>
                <span class="tarjeta-stat__etiqueta"><?= app_icon('star') ?> Calificaciones</span>
            </div>
            <div class="tarjeta-stat">
                <span class="tarjeta-stat__numero" style="font-size:1.2rem"><?= $generoMasVisto ?></span>
                <span class="tarjeta-stat__etiqueta"><?= app_icon('flame') ?> Género más visto</span>
            </div>
        </div>

        <!-- ============================
             SECCIÓN: CONTENIDO
             ============================ -->
        <?php if ($seccionActiva === 'contenido'): ?>
        <section>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:0.75rem;">
                <h2 class="seccion-titulo" style="margin:0;">Películas y Series</h2>
                <button class="btn btn--primario btn--pequeno"
                        id="btn-agregar-contenido"
                        onclick="abrirModal('modal-contenido')"
                        aria-haspopup="dialog">
                    <?= app_icon('plus') ?> Agregar título
                </button>
            </div>

            <div class="tarjeta" style="padding:0; overflow:hidden;">
                <div style="overflow-x:auto;">
                    <table class="tabla-admin">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Año</th>
                                <th>Calificación</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contenidos as $item):
                                $iId     = (int) $item['id'];
                                $iTitulo = htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8');
                                $iTipo   = $item['tipo'] === 'serie' ? 'Serie' : 'Película';
                                $iActivo = (bool) $item['activo'];
                                $iSinopsis = htmlspecialchars($item['sinopsis'] ?? '', ENT_QUOTES, 'UTF-8');
                                $iImagen = htmlspecialchars($item['imagen_url'] ?? '', ENT_QUOTES, 'UTF-8');
                                $iTrailer = htmlspecialchars($item['trailer_url'] ?? '', ENT_QUOTES, 'UTF-8');
                                $iGeneroId = (int)($item['genero_id'] ?? 0);
                            ?>
                            <tr>
                                <td><?= $iTitulo ?></td>
                                <td><?= $iTipo ?></td>
                                <td><?= (int)$item['anio'] ?></td>
                                <td><span class="valor-rating"><?= app_icon('star') ?> <?= number_format((float)$item['calificacion'], 1) ?></span></td>
                                <td>
                                    <span class="badge <?= $iActivo ? 'badge--activo' : 'badge--inactivo' ?>">
                                        <?= $iActivo ? 'Activo' : 'Oculto' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="tabla-acciones">
                                        <button type="button"
                                                class="btn btn--secundario btn--pequeno btn-editar-contenido"
                                                data-id="<?= $iId ?>"
                                                data-titulo="<?= $iTitulo ?>"
                                                data-tipo="<?= htmlspecialchars($item['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-anio="<?= htmlspecialchars((string)($item['anio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-duracion="<?= htmlspecialchars((string)($item['duracion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-calificacion="<?= htmlspecialchars((string)$item['calificacion'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-genero="<?= $iGeneroId ?>"
                                                data-sinopsis="<?= $iSinopsis ?>"
                                                data-imagen="<?= $iImagen ?>"
                                                data-trailer="<?= $iTrailer ?>">
                                            Editar
                                        </button>
                                        <form method="POST" action="<?= htmlspecialchars($url('admin', ['seccion' => 'contenido']), ENT_QUOTES, 'UTF-8') ?>" style="display:inline;">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="accion" value="eliminar_contenido">
                                            <input type="hidden" name="id" value="<?= $iId ?>">
                                            <button type="submit"
                                                    class="btn btn--peligro btn--pequeno btn-eliminar"
                                                data-nombre="<?= $iTitulo ?>">
                                                <?= app_icon('trash') ?> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ============================
             SECCIÓN: USUARIOS
             ============================ -->
        <?php if ($seccionActiva === 'usuarios'): ?>
        <section>
            <h2 class="seccion-titulo">Usuarios registrados</h2>

            <div class="tarjeta" style="padding:0; overflow:hidden;">
                <div style="overflow-x:auto;">
                    <table class="tabla-admin">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u):
                                $uId     = (int) $u['id'];
                                $uNombre = htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8');
                                $uEmail  = htmlspecialchars($u['email'],  ENT_QUOTES, 'UTF-8');
                                $uRol    = $u['rol'] === 'admin' ? 'Admin' : 'Estándar';
                                $uActivo = (bool) $u['activo'];
                            ?>
                            <tr>
                                <td><?= $uNombre ?></td>
                                <td><?= $uEmail ?></td>
                                <td>
                                    <span class="badge <?= $u['rol'] === 'admin' ? 'badge--admin' : '' ?>">
                                        <?= $uRol ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $uActivo ? 'badge--activo' : 'badge--inactivo' ?>">
                                        <?= $uActivo ? 'Activo' : 'Suspendido' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($u['creado_en'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="tabla-acciones">
                                        <form method="POST" action="<?= htmlspecialchars($url('admin', ['seccion' => 'usuarios']), ENT_QUOTES, 'UTF-8') ?>" style="display:inline;">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="accion" value="toggle_usuario">
                                            <input type="hidden" name="id" value="<?= $uId ?>">
                                            <button type="submit" class="btn btn--secundario btn--pequeno">
                                                <?= app_icon($uActivo ? 'ban' : 'check') ?> <?= $uActivo ? 'Suspender' : 'Activar' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ============================
             SECCIÓN: ESTADÍSTICAS
             ============================ -->
        <?php if ($seccionActiva === 'estadisticas'): ?>
        <section>
            <h2 class="seccion-titulo">Comportamiento de usuarios</h2>

            <div class="tarjeta">
                <h3 style="font-size:0.85rem; color:var(--color-texto-suave); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:1.25rem; font-weight:700;">
                    Géneros más visitados
                </h3>
                <?php foreach ($generosPopulares as $g):
                    $gNombre     = htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8');
                    $gVisitas    = number_format((int)$g['visitas']);
                    $gPorcentaje = min(100, max(0, (int)$g['porcentaje']));
                ?>
                <div class="barra-genero">
                    <div class="barra-genero__nombre">
                        <span><?= $gNombre ?></span>
                        <span><?= $gVisitas ?> visitas</span>
                    </div>
                    <div class="barra-genero__pista"
                         role="progressbar"
                         aria-valuenow="<?= $gPorcentaje ?>"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        <div class="barra-genero__relleno" style="width:<?= $gPorcentaje ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </main>
</div>

<!-- Modal: Agregar título -->
<div class="modal-fondo <?= !empty($erroresFormulario) ? 'visible' : '' ?>" id="modal-contenido" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
    <div class="modal">
        <div class="modal__encabezado">
            <h2 class="modal__titulo" id="modal-titulo">Agregar título</h2>
            <button class="modal__cerrar" onclick="cerrarModal('modal-contenido')" aria-label="Cerrar">✕</button>
        </div>

        <?php if (!empty($erroresFormulario['general'])): ?>
        <div class="alerta alerta--error" role="alert">
            <span class="alerta__icono"><?= app_icon('shield') ?></span>
            <span><?= htmlspecialchars($erroresFormulario['general'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php endif; ?>

        <form id="formulario-contenido" method="POST" action="<?= htmlspecialchars($url('admin', ['seccion' => 'contenido']), ENT_QUOTES, 'UTF-8') ?>" novalidate>
            <?= csrf_input() ?>
            <input type="hidden" name="accion" value="guardar_contenido">
            <input type="hidden" name="id_contenido" id="contenido-id" value="">

            <!-- Título -->
            <div class="grupo-campo">
                <label for="contenido-titulo">Título *</label>
                <input type="text"
                       id="contenido-titulo"
                       name="titulo"
                       class="campo-input <?= !empty($erroresFormulario['titulo']) ? 'campo--error' : '' ?>"
                       placeholder="Nombre de la película o serie"
                       maxlength="200">
                <?php if (!empty($erroresFormulario['titulo'])): ?>
                    <span class="error-campo" role="alert"><?= htmlspecialchars($erroresFormulario['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <!-- Tipo y Año -->
            <div class="dos-columnas">
                <div class="grupo-campo">
                    <label for="contenido-tipo">Tipo *</label>
                    <select id="contenido-tipo" name="tipo" class="campo-select <?= !empty($erroresFormulario['tipo']) ? 'campo--error' : '' ?>">
                        <option value="" disabled selected>Seleccionar…</option>
                        <option value="pelicula">Película</option>
                        <option value="serie">Serie</option>
                    </select>
                    <?php if (!empty($erroresFormulario['tipo'])): ?>
                        <span class="error-campo" role="alert"><?= htmlspecialchars($erroresFormulario['tipo'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>

                <div class="grupo-campo">
                    <label for="contenido-anio">Año</label>
                    <input type="number"
                           id="contenido-anio"
                           name="anio"
                           class="campo-input <?= !empty($erroresFormulario['anio']) ? 'campo--error' : '' ?>"
                           placeholder="<?= date('Y') ?>"
                           min="1888"
                           max="<?= date('Y') + 2 ?>">
                    <?php if (!empty($erroresFormulario['anio'])): ?>
                        <span class="error-campo" role="alert"><?= htmlspecialchars($erroresFormulario['anio'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Duración y Calificación -->
            <div class="dos-columnas">
                <div class="grupo-campo">
                    <label for="contenido-duracion">Duración (min)</label>
                    <input type="number"
                           id="contenido-duracion"
                           name="duracion"
                           class="campo-input"
                           placeholder="120"
                           min="1"
                           max="999">
                </div>
                <div class="grupo-campo">
                    <label for="contenido-calificacion">Calificación (0-10)</label>
                    <input type="number"
                           id="contenido-calificacion"
                           name="calificacion"
                           class="campo-input"
                           placeholder="7.5"
                           min="0"
                           max="10"
                           step="0.1">
                </div>
            </div>

            <!-- Género -->
            <div class="grupo-campo">
                <label for="contenido-genero">Género principal *</label>
                <select id="contenido-genero" name="genero_id" class="campo-select <?= !empty($erroresFormulario['genero']) ? 'campo--error' : '' ?>">
                    <option value="" disabled selected>Seleccionar género…</option>
                    <?php foreach ($generosOpciones as $g): ?>
                        <option value="<?= (int)$g['id'] ?>">
                            <?= htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($erroresFormulario['genero'])): ?>
                    <span class="error-campo" role="alert"><?= htmlspecialchars($erroresFormulario['genero'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <!-- Sinopsis -->
            <div class="grupo-campo">
                <label for="contenido-sinopsis">Sinopsis *</label>
                <textarea id="contenido-sinopsis"
                          name="sinopsis"
                          class="campo-textarea <?= !empty($erroresFormulario['sinopsis']) ? 'campo--error' : '' ?>"
                          rows="3"
                          maxlength="1000"
                          placeholder="Breve descripción de la trama…"
                          style="resize:vertical;"></textarea>
                <?php if (!empty($erroresFormulario['sinopsis'])): ?>
                    <span class="error-campo" role="alert"><?= htmlspecialchars($erroresFormulario['sinopsis'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <!-- URL Póster -->
            <div class="grupo-campo">
                <label for="contenido-imagen">URL del póster</label>
                <input type="url"
                       id="contenido-imagen"
                       name="imagen_url"
                       class="campo-input"
                       placeholder="https://ejemplo.com/poster.jpg"
                       maxlength="500">
            </div>

            <!-- URL Trailer -->
            <div class="grupo-campo">
                <label for="contenido-trailer">URL del trailer de YouTube</label>
                <input type="text"
                       id="contenido-trailer"
                       name="trailer_url"
                       class="campo-input <?= !empty($erroresFormulario['trailer_url']) ? 'campo--error' : '' ?>"
                       placeholder="https://www.youtube.com/watch?v=..."
                       maxlength="500">
                <?php if (!empty($erroresFormulario['trailer_url'])): ?>
                    <span class="error-campo" role="alert"><?= htmlspecialchars($erroresFormulario['trailer_url'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="modal__acciones">
                <button type="button" class="btn btn--secundario" onclick="cerrarModal('modal-contenido')">Cancelar</button>
                <button type="submit" class="btn btn--primario" id="contenido-submit">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
