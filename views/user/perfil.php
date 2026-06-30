<?php
/**
 * views/user/perfil.php
 * Perfil del usuario — sin sesiones.
 *
 * Variables opcionales del controlador PerfilController:
 *   $usuario           (array)  - ['nombre','email','rol','creado_en']
 *   $generosDisponibles (array) - [['id','nombre','emoji'], ...]
 *   $generosUsuario    (array)  - IDs seleccionados [1, 3, 5]
 *   $historialReciente (array)  - [['titulo','imagen_url','visto_en'], ...]
 *   $erroresPerfil     (array)  - Errores del servidor
 *   $mensajeExito      (string) - Mensaje tras guardar
 */

require_once __DIR__ . '/../../app/bootstrap.php';
Seguridad::requiereLogin('../auth/auth.php');

$usuarioModelo = new UsuarioModelo();
$contenidoModelo = new ContenidoModelo();
$erroresPerfil = [];
$mensajeExito = Seguridad::flash('perfil_exito') ?? '';

if (Seguridad::metodoPost()) {
    $accion = $_POST['accion'] ?? '';

    if (!Seguridad::validarCsrf()) {
        $erroresPerfil['general'] = 'La solicitud no es valida. Recarga la pagina e intenta nuevamente.';
    } elseif ($accion === 'actualizar_perfil') {
        $nombre = Seguridad::limpiarTexto($_POST['nombre'] ?? '', 80);
        $email = mb_strtolower(Seguridad::limpiarTexto($_POST['email'] ?? '', 254));
        $contrasenaNueva = (string)($_POST['contrasena_nueva'] ?? '');
        $confirmar = (string)($_POST['confirmar_contrasena'] ?? '');

        if (!Seguridad::validarNombre($nombre)) {
            $erroresPerfil['nombre'] = 'Solo letras y espacios, entre 2 y 80 caracteres.';
        }
        if (!Seguridad::validarEmail($email)) {
            $erroresPerfil['email'] = 'Ingresa un correo electronico valido.';
        } elseif ($usuarioModelo->emailExiste($email, Seguridad::usuarioId())) {
            $erroresPerfil['email'] = 'Ese correo ya esta registrado por otro usuario.';
        }
        if ($contrasenaNueva !== '') {
            if (!Seguridad::validarContrasena($contrasenaNueva)) {
                $erroresPerfil['contrasena_nueva'] = 'Minimo 8 caracteres, con mayuscula, minuscula, numero y caracter especial.';
            }
            if ($confirmar !== $contrasenaNueva) {
                $erroresPerfil['confirmar'] = 'Las contrasenas no coinciden.';
            }
        }

        if (empty($erroresPerfil)) {
            try {
                $usuarioModelo->actualizarPerfil(Seguridad::usuarioId(), $nombre, $email, $contrasenaNueva ?: null);
                $_SESSION['usuario_nombre'] = $nombre;
                Seguridad::flash('perfil_exito', 'Datos personales actualizados correctamente.');
                Seguridad::redirigir('perfil.php');
            } catch (Throwable $e) {
                error_log('Error actualizando perfil: ' . $e->getMessage());
                $erroresPerfil['general'] = 'No se pudieron guardar los cambios.';
            }
        } else {
            $usuario = [
                'nombre' => $nombre,
                'email' => $email,
                'rol' => $_SESSION['usuario_rol'] ?? 'estandar',
                'creado_en' => date('Y-m-d'),
            ];
        }
    } elseif ($accion === 'guardar_preferencias') {
        $generos = [];
        foreach ($_POST['generos'] ?? [] as $valor) {
            $idGenero = filter_var($valor, FILTER_VALIDATE_INT);
            if ($idGenero !== false && (int)$idGenero > 0) {
                $generos[] = (int)$idGenero;
            }
        }
        $generos = array_values(array_unique($generos));

        if (count($generos) === 0) {
            $erroresPerfil['general'] = 'Selecciona al menos un genero favorito.';
        } elseif (count($generos) > 10) {
            $erroresPerfil['general'] = 'Puedes seleccionar maximo 10 generos.';
        } else {
            try {
                $usuarioModelo->guardarPreferencias(Seguridad::usuarioId(), $generos);
                Seguridad::flash('perfil_exito', 'Preferencias guardadas correctamente.');
                Seguridad::redirigir('perfil.php');
            } catch (Throwable $e) {
                error_log('Error guardando preferencias: ' . $e->getMessage());
                $erroresPerfil['general'] = 'No se pudieron guardar las preferencias.';
            }
        }
    }
}

try {
    $usuario = $usuario ?? $usuarioModelo->obtenerPorId(Seguridad::usuarioId());
    $generosDisponibles = $contenidoModelo->obtenerGeneros();
    $generosUsuario = $usuarioModelo->obtenerPreferencias(Seguridad::usuarioId());
    $historialReciente = $contenidoModelo->obtenerHistorialUsuario(Seguridad::usuarioId(), 5);
} catch (Throwable $e) {
    error_log('Error perfil: ' . $e->getMessage());
}

$tituloPagina = 'Mi perfil';
$paginaActiva  = 'perfil';
include __DIR__ . '/../partials/header.php';

// Defaults seguros
$usuario           = $usuario           ?? ['nombre' => 'Usuario', 'email' => '', 'rol' => 'estandar', 'creado_en' => date('Y-m-d')];
$generosDisponibles = $generosDisponibles ?? [
    ['id'=>1,  'nombre'=>'Acción',     'emoji'=>'💥'],
    ['id'=>2,  'nombre'=>'Comedia',    'emoji'=>'😂'],
    ['id'=>3,  'nombre'=>'Drama',      'emoji'=>'🎭'],
    ['id'=>4,  'nombre'=>'Terror',     'emoji'=>'👻'],
    ['id'=>5,  'nombre'=>'Sci-Fi',     'emoji'=>'🚀'],
    ['id'=>6,  'nombre'=>'Romance',    'emoji'=>'💕'],
    ['id'=>7,  'nombre'=>'Thriller',   'emoji'=>'🔪'],
    ['id'=>8,  'nombre'=>'Animación',  'emoji'=>'🎨'],
    ['id'=>9,  'nombre'=>'Documental', 'emoji'=>'🎥'],
    ['id'=>10, 'nombre'=>'Fantasía',   'emoji'=>'🧙'],
    ['id'=>11, 'nombre'=>'Aventura',   'emoji'=>'🗺️'],
    ['id'=>12, 'nombre'=>'Misterio',   'emoji'=>'🔍'],
];
$generosUsuario    = $generosUsuario    ?? [];
$historialReciente = $historialReciente ?? [];
$erroresPerfil     = $erroresPerfil     ?? [];
$mensajeExito      = $mensajeExito      ?? '';

$nombreSeguro = htmlspecialchars($usuario['nombre'] ?? '',          ENT_QUOTES, 'UTF-8');
$emailSeguro  = htmlspecialchars($usuario['email']  ?? '',          ENT_QUOTES, 'UTF-8');
$rolLabel     = ($usuario['rol'] ?? '') === 'admin' ? 'Administrador' : 'Usuario estándar';
$iniciales    = strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1));
?>

<main class="pagina-contenido contenedor">

    <!-- Encabezado del perfil -->
    <div class="perfil-encabezado">
        <div class="perfil-avatar"><?= $iniciales ?></div>
        <div>
            <h1 class="perfil-info__nombre"><?= $nombreSeguro ?></h1>
            <p class="perfil-info__rol">
                <?= $rolLabel ?> &middot;
                <span>Miembro desde <?= htmlspecialchars(date('M Y', strtotime($usuario['creado_en'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></span>
            </p>
        </div>
    </div>

    <?php if (!empty($mensajeExito)): ?>
    <div class="alerta alerta--exito" role="alert">
        <span class="alerta__icono">✓</span>
        <span><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($erroresPerfil['general'])): ?>
    <div class="alerta alerta--error" role="alert">
        <span class="alerta__icono">⚠</span>
        <span><?= htmlspecialchars($erroresPerfil['general'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 300px; gap:1.5rem; align-items:start;">

        <!-- Columna principal -->
        <div>

            <!-- Datos personales -->
            <section class="tarjeta" style="margin-bottom:1.5rem">
                <h2 class="tarjeta__titulo">Datos personales</h2>
                <p class="tarjeta__subtitulo">Actualiza tu nombre, correo o contraseña.</p>

                <form id="formulario-perfil" method="POST" action="perfil.php" novalidate autocomplete="on">
                    <?= csrf_input() ?>
                    <input type="hidden" name="accion" value="actualizar_perfil">

                    <!-- Nombre -->
                    <div class="grupo-campo">
                        <label for="perfil-nombre">Nombre completo</label>
                        <input type="text"
                               id="perfil-nombre"
                               name="nombre"
                               class="campo-input <?= !empty($erroresPerfil['nombre']) ? 'campo--error' : '' ?>"
                               value="<?= $nombreSeguro ?>"
                               placeholder="Tu nombre"
                               maxlength="50"
                               autocomplete="name">
                        <?php if (!empty($erroresPerfil['nombre'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresPerfil['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="grupo-campo">
                        <label for="perfil-email">Correo electrónico</label>
                        <input type="email"
                               id="perfil-email"
                               name="email"
                               class="campo-input <?= !empty($erroresPerfil['email']) ? 'campo--error' : '' ?>"
                               value="<?= $emailSeguro ?>"
                               placeholder="tucorreo@ejemplo.com"
                               maxlength="254"
                               autocomplete="email">
                        <?php if (!empty($erroresPerfil['email'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresPerfil['email'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="separador"></div>
                    <p class="texto-suave" style="margin-bottom:1rem">Deja vacíos los campos de contraseña si no deseas cambiarla.</p>

                    <!-- Nueva contraseña -->
                    <div class="grupo-campo">
                        <label for="perfil-contrasena-nueva">Nueva contraseña</label>
                        <div class="campo-contrasena-wrapper">
                            <input type="password"
                                   id="perfil-contrasena-nueva"
                                   name="contrasena_nueva"
                                   class="campo-input <?= !empty($erroresPerfil['contrasena_nueva']) ? 'campo--error' : '' ?>"
                                   placeholder="Mínimo 8 caracteres"
                                   maxlength="128"
                                   autocomplete="new-password">
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña">👁️</button>
                        </div>
                        <div class="bloque-fuerza">
                            <div class="barra-contrasena">
                                <div class="barra-contrasena__relleno"></div>
                            </div>
                            <p class="texto-fuerza" aria-live="polite"></p>
                        </div>
                        <?php if (!empty($erroresPerfil['contrasena_nueva'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresPerfil['contrasena_nueva'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div class="grupo-campo">
                        <label for="perfil-confirmar-contrasena">Confirmar nueva contraseña</label>
                        <div class="campo-contrasena-wrapper">
                            <input type="password"
                                   id="perfil-confirmar-contrasena"
                                   name="confirmar_contrasena"
                                   class="campo-input <?= !empty($erroresPerfil['confirmar']) ? 'campo--error' : '' ?>"
                                   placeholder="Repite la nueva contraseña"
                                   maxlength="128"
                                   autocomplete="new-password">
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña">👁️</button>
                        </div>
                        <?php if (!empty($erroresPerfil['confirmar'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresPerfil['confirmar'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn--primario">Guardar cambios</button>
                </form>
            </section>

            <!-- Géneros favoritos -->
            <section class="tarjeta">
                <h2 class="tarjeta__titulo">Géneros favoritos</h2>
                <p class="tarjeta__subtitulo">Selecciona los géneros que más te gustan para recibir recomendaciones.</p>

                <form id="formulario-preferencias" method="POST" action="perfil.php" novalidate>
                    <?= csrf_input() ?>
                    <input type="hidden" name="accion" value="guardar_preferencias">

                    <div class="grupo-campo">
                        <div class="grupo-checkboxes" role="group" aria-label="Géneros">
                            <?php foreach ($generosDisponibles as $genero):
                                $gId     = (int) $genero['id'];
                                $gNombre = htmlspecialchars($genero['nombre'], ENT_QUOTES, 'UTF-8');
                                $gEmoji  = htmlspecialchars($genero['emoji'],  ENT_QUOTES, 'UTF-8');
                                $checked = in_array($gId, $generosUsuario, true);
                            ?>
                                <input class="checkbox-genero"
                                       type="checkbox"
                                       id="genero-<?= $gId ?>"
                                       name="generos[]"
                                       value="<?= $gId ?>"
                                       <?= $checked ? 'checked' : '' ?>>
                                <label for="genero-<?= $gId ?>"><?= $gEmoji ?> <?= $gNombre ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="margen-arriba">
                        <button type="submit" class="btn btn--primario">Guardar preferencias</button>
                    </div>
                </form>
            </section>
        </div>

        <!-- Historial reciente -->
        <aside>
            <div class="tarjeta">
                <h2 class="tarjeta__titulo" style="font-size:1.1rem;">Visto recientemente</h2>
                <p class="tarjeta__subtitulo">Tu actividad de las últimas sesiones.</p>

                <?php if (empty($historialReciente)): ?>
                    <p class="texto-suave texto-centro" style="padding:1rem 0">
                        Aún no has explorado nada. ¡Ve al catálogo!
                    </p>
                <?php else: ?>
                    <ul class="lista-historial" role="list">
                        <?php foreach ($historialReciente as $item): ?>
                        <li class="item-historial">
                            <img class="item-historial__miniatura"
                                 src="<?= htmlspecialchars($item['imagen_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                                 loading="lazy">
                            <div>
                                <p class="item-historial__titulo"><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="item-historial__fecha"><?= htmlspecialchars(date('d/m/Y', strtotime($item['visto_en'])), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>

    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
