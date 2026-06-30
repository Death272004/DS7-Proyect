<?php
/**
 * views/admin/panel.php
 * Panel de administración — sin sesiones.
 *
 * Variables opcionales del controlador AdminController:
 *   $contenidos          (array)  - [['id','titulo','tipo','anio','calificacion','activo'], ...]
 *   $usuarios            (array)  - [['id','nombre','email','rol','activo','creado_en'], ...]
 *   $generosPopulares    (array)  - [['nombre','visitas','porcentaje'], ...]
 *   $totalUsuarios       (int)
 *   $totalContenidos     (int)
 *   $totalCalificaciones (int)
 *   $generoMasVisto      (string)
 *   $seccionActiva       (string) - 'contenido'|'usuarios'|'estadisticas'
 *   $erroresFormulario   (array)
 *   $mensajeExito        (string)
 */

require_once __DIR__ . '/../../app/bootstrap.php';
Seguridad::requiereAdmin('../inicio/inicio.php');

$contenidoModelo = new ContenidoModelo();
$usuarioModelo = new UsuarioModelo();
$erroresFormulario = [];
$mensajeExito = Seguridad::flash('admin_exito') ?? '';
$seccionActiva = in_array($_GET['seccion'] ?? 'contenido', ['contenido', 'usuarios', 'estadisticas'], true)
    ? (string)$_GET['seccion']
    : 'contenido';

if (Seguridad::metodoPost()) {
    $accion = $_POST['accion'] ?? '';

    if (!Seguridad::validarCsrf()) {
        $erroresFormulario['general'] = 'La solicitud no es valida. Recarga la pagina e intenta nuevamente.';
    } elseif ($accion === 'guardar_contenido') {
        $titulo = Seguridad::limpiarTexto($_POST['titulo'] ?? '', 200);
        $tipo = Seguridad::limpiarTexto($_POST['tipo'] ?? '', 20);
        $anio = trim((string)($_POST['anio'] ?? ''));
        $duracion = trim((string)($_POST['duracion'] ?? ''));
        $calificacion = trim((string)($_POST['calificacion'] ?? ''));
        $generoId = Seguridad::entero($_POST['genero_id'] ?? 0, 0);
        $sinopsis = Seguridad::limpiarTexto($_POST['sinopsis'] ?? '', 1000);
        $imagenUrl = trim((string)($_POST['imagen_url'] ?? ''));
        $anioActual = (int)date('Y');

        if ($titulo === '') {
            $erroresFormulario['titulo'] = 'El titulo es obligatorio.';
        }
        if (!in_array($tipo, ['pelicula', 'serie'], true)) {
            $erroresFormulario['tipo'] = 'Selecciona el tipo de contenido.';
        }
        if ($anio !== '' && (!ctype_digit($anio) || (int)$anio < 1888 || (int)$anio > $anioActual + 2)) {
            $erroresFormulario['anio'] = 'El anio debe estar entre 1888 y ' . ($anioActual + 2) . '.';
        }
        if ($duracion !== '' && (!ctype_digit($duracion) || (int)$duracion < 1 || (int)$duracion > 999)) {
            $erroresFormulario['duracion'] = 'La duracion debe estar entre 1 y 999 minutos.';
        }
        if ($calificacion !== '' && (!is_numeric($calificacion) || (float)$calificacion < 0 || (float)$calificacion > 10)) {
            $erroresFormulario['calificacion'] = 'La calificacion debe estar entre 0 y 10.';
        }
        if ($generoId <= 0) {
            $erroresFormulario['genero'] = 'Selecciona un genero.';
        }
        if (mb_strlen($sinopsis) < 10) {
            $erroresFormulario['sinopsis'] = 'La sinopsis debe tener al menos 10 caracteres.';
        }
        if ($imagenUrl !== '' && filter_var($imagenUrl, FILTER_VALIDATE_URL) === false) {
            $erroresFormulario['imagen_url'] = 'Ingresa una URL valida.';
        }

        if (empty($erroresFormulario)) {
            try {
                $contenidoModelo->crearContenido([
                    'titulo' => $titulo,
                    'tipo' => $tipo,
                    'anio' => $anio === '' ? null : (int)$anio,
                    'duracion' => $duracion === '' ? null : (int)$duracion,
                    'calificacion' => $calificacion === '' ? 0.0 : (float)$calificacion,
                    'genero_id' => $generoId,
                    'sinopsis' => $sinopsis,
                    'imagen_url' => $imagenUrl === '' ? null : $imagenUrl,
                ]);
                Seguridad::flash('admin_exito', 'Contenido agregado correctamente.');
                Seguridad::redirigir('panel.php?seccion=contenido');
            } catch (Throwable $e) {
                error_log('Error guardando contenido: ' . $e->getMessage());
                $erroresFormulario['general'] = 'No se pudo guardar el contenido.';
            }
        }
    } elseif ($accion === 'eliminar_contenido') {
        try {
            $contenidoModelo->eliminarContenido(Seguridad::entero($_POST['id'] ?? 0, 0));
            Seguridad::flash('admin_exito', 'Contenido eliminado correctamente.');
        } catch (Throwable $e) {
            error_log('Error eliminando contenido: ' . $e->getMessage());
            Seguridad::flash('admin_exito', 'No se pudo eliminar el contenido.');
        }
        Seguridad::redirigir('panel.php?seccion=contenido');
    } elseif ($accion === 'toggle_usuario') {
        try {
            $usuarioModelo->toggleEstado(Seguridad::entero($_POST['id'] ?? 0, 0));
            Seguridad::flash('admin_exito', 'Estado del usuario actualizado.');
        } catch (Throwable $e) {
            error_log('Error cambiando usuario: ' . $e->getMessage());
            Seguridad::flash('admin_exito', 'No se pudo actualizar el usuario.');
        }
        Seguridad::redirigir('panel.php?seccion=usuarios');
    }
}

try {
    $contenidos = $contenidoModelo->listarAdmin();
    $usuarios = $usuarioModelo->listarUsuarios();
    $generosPopulares = $contenidoModelo->generosPopulares();
    $generosOpciones = $contenidoModelo->obtenerGeneros();
    $estadisticas = $contenidoModelo->estadisticas();
    $totalUsuarios = $estadisticas['totalUsuarios'];
    $totalContenidos = $estadisticas['totalContenidos'];
    $totalCalificaciones = $estadisticas['totalCalificaciones'];
    $generoMasVisto = $estadisticas['generoMasVisto'];
} catch (Throwable $e) {
    error_log('Error panel admin: ' . $e->getMessage());
}

$tituloPagina = 'Panel de administración';
$paginaActiva  = 'admin';
include __DIR__ . '/../partials/header.php';

// Defaults seguros
$seccionActiva       = in_array($seccionActiva ?? 'contenido', ['contenido','usuarios','estadisticas']) ? ($seccionActiva ?? 'contenido') : 'contenido';
$totalUsuarios       = $totalUsuarios       ?? 128;
$totalContenidos     = $totalContenidos     ?? 340;
$totalCalificaciones = $totalCalificaciones ?? 1204;
$generoMasVisto      = htmlspecialchars($generoMasVisto ?? 'Acción', ENT_QUOTES, 'UTF-8');
$mensajeExito        = $mensajeExito        ?? '';
$erroresFormulario   = $erroresFormulario   ?? [];

$generosPopulares = $generosPopulares ?? [
    ['nombre'=>'Acción',   'visitas'=>520,'porcentaje'=>85],
    ['nombre'=>'Drama',    'visitas'=>410,'porcentaje'=>67],
    ['nombre'=>'Sci-Fi',   'visitas'=>380,'porcentaje'=>62],
    ['nombre'=>'Comedia',  'visitas'=>290,'porcentaje'=>47],
    ['nombre'=>'Terror',   'visitas'=>215,'porcentaje'=>35],
];

$contenidos = $contenidos ?? [
    ['id'=>1,'titulo'=>'Interstellar', 'tipo'=>'pelicula','anio'=>2014,'calificacion'=>8.7,'activo'=>true],
    ['id'=>2,'titulo'=>'Breaking Bad', 'tipo'=>'serie',   'anio'=>2008,'calificacion'=>9.5,'activo'=>true],
    ['id'=>3,'titulo'=>'Joker',        'tipo'=>'pelicula','anio'=>2019,'calificacion'=>8.4,'activo'=>false],
];

$usuarios = $usuarios ?? [
    ['id'=>1,'nombre'=>'Ana López',   'email'=>'ana@ejemplo.com',    'rol'=>'admin',    'activo'=>true, 'creado_en'=>'2024-01-15'],
    ['id'=>2,'nombre'=>'Carlos Ruiz', 'email'=>'carlos@ejemplo.com', 'rol'=>'estandar', 'activo'=>true, 'creado_en'=>'2024-03-10'],
    ['id'=>3,'nombre'=>'María Gómez', 'email'=>'maria@ejemplo.com',  'rol'=>'estandar', 'activo'=>false,'creado_en'=>'2024-05-22'],
];

$generosOpciones = [
    ['id'=>1,'nombre'=>'Acción'],   ['id'=>2,'nombre'=>'Comedia'],
    ['id'=>3,'nombre'=>'Drama'],    ['id'=>4,'nombre'=>'Terror'],
    ['id'=>5,'nombre'=>'Sci-Fi'],   ['id'=>6,'nombre'=>'Romance'],
    ['id'=>7,'nombre'=>'Thriller'], ['id'=>8,'nombre'=>'Animación'],
    ['id'=>9,'nombre'=>'Documental'],['id'=>10,'nombre'=>'Fantasía'],
];
?>

<div class="panel-admin">

    <!-- Sidebar -->
    <nav class="panel-admin__sidebar" aria-label="Menú del panel">
        <p class="panel-admin__sidebar-titulo">Gestión</p>

        <a href="panel.php?seccion=contenido"
           class="panel-admin__nav-item <?= $seccionActiva === 'contenido'    ? 'activo' : '' ?>">
            <span class="panel-admin__nav-icono">🎬</span> Películas y Series
        </a>
        <a href="panel.php?seccion=usuarios"
           class="panel-admin__nav-item <?= $seccionActiva === 'usuarios'     ? 'activo' : '' ?>">
            <span class="panel-admin__nav-icono">👥</span> Usuarios
        </a>
        <a href="panel.php?seccion=estadisticas"
           class="panel-admin__nav-item <?= $seccionActiva === 'estadisticas' ? 'activo' : '' ?>">
            <span class="panel-admin__nav-icono">📊</span> Estadísticas
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
                <span class="tarjeta-stat__etiqueta">👥 Usuarios registrados</span>
            </div>
            <div class="tarjeta-stat">
                <span class="tarjeta-stat__numero"><?= number_format($totalContenidos) ?></span>
                <span class="tarjeta-stat__etiqueta">🎬 Títulos en catálogo</span>
            </div>
            <div class="tarjeta-stat">
                <span class="tarjeta-stat__numero"><?= number_format($totalCalificaciones) ?></span>
                <span class="tarjeta-stat__etiqueta">⭐ Calificaciones</span>
            </div>
            <div class="tarjeta-stat">
                <span class="tarjeta-stat__numero" style="font-size:1.2rem"><?= $generoMasVisto ?></span>
                <span class="tarjeta-stat__etiqueta">🔥 Género más visto</span>
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
                        onclick="abrirModal('modal-contenido')"
                        aria-haspopup="dialog">
                    + Agregar título
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
                            ?>
                            <tr>
                                <td><?= $iTitulo ?></td>
                                <td><?= $iTipo ?></td>
                                <td><?= (int)$item['anio'] ?></td>
                                <td>⭐ <?= number_format((float)$item['calificacion'], 1) ?></td>
                                <td>
                                    <span class="badge <?= $iActivo ? 'badge--activo' : 'badge--inactivo' ?>">
                                        <?= $iActivo ? 'Activo' : 'Oculto' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="tabla-acciones">
                                        <form method="POST" action="panel.php?seccion=contenido" style="display:inline;">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="accion" value="eliminar_contenido">
                                            <input type="hidden" name="id" value="<?= $iId ?>">
                                            <button type="submit"
                                                    class="btn btn--peligro btn--pequeno btn-eliminar"
                                                    data-nombre="<?= $iTitulo ?>">
                                                🗑️ Eliminar
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
                                        <form method="POST" action="panel.php?seccion=usuarios" style="display:inline;">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="accion" value="toggle_usuario">
                                            <input type="hidden" name="id" value="<?= $uId ?>">
                                            <button type="submit" class="btn btn--secundario btn--pequeno">
                                                <?= $uActivo ? '🚫 Suspender' : '✅ Activar' ?>
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
            <span class="alerta__icono">⚠</span>
            <span><?= htmlspecialchars($erroresFormulario['general'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php endif; ?>

        <form id="formulario-contenido" method="POST" action="panel.php?seccion=contenido" novalidate>
            <?= csrf_input() ?>
            <input type="hidden" name="accion" value="guardar_contenido">

            <input type="hidden" name="id" value="">

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

            <div style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:0.5rem;">
                <button type="button" class="btn btn--secundario" onclick="cerrarModal('modal-contenido')">Cancelar</button>
                <button type="submit" class="btn btn--primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
