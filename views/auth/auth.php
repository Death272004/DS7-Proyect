<?php
/**
 * views/auth/auth.php
 * Login y Registro — sin sesiones.
 *
 * Variables opcionales del controlador AuthController:
 *   $erroresLogin    (array)  - Errores del servidor para login    ['email','contrasena','general']
 *   $erroresRegistro (array)  - Errores del servidor para registro ['nombre','email','contrasena','confirmar','terminos','general']
 *   $tabActiva       (string) - 'login' | 'registro'
 *   $valoresRegistro (array)  - Valores previos para repoblar el form ['nombre','email']
 */

require_once __DIR__ . '/../../app/bootstrap.php';

$erroresLogin    = [];
$erroresRegistro = [];
$tabActiva       = 'login';
$valoresRegistro = [];
$usuarioModelo   = new UsuarioModelo();

if (($_GET['accion'] ?? '') === 'logout') {
    Seguridad::cerrarSesion();
    Seguridad::redirigir('../inicio/inicio.php');
}

if (Seguridad::metodoPost()) {
    $accion = $_POST['accion'] ?? '';

    if (!Seguridad::validarCsrf()) {
        $mensaje = 'La solicitud no es valida. Recarga la pagina e intenta nuevamente.';
        if ($accion === 'registro') {
            $erroresRegistro['general'] = $mensaje;
            $tabActiva = 'registro';
        } else {
            $erroresLogin['general'] = $mensaje;
        }
    } elseif ($accion === 'login') {
        $email = mb_strtolower(Seguridad::limpiarTexto($_POST['email'] ?? '', 254));
        $contrasena = (string)($_POST['contrasena'] ?? '');
        $valoresRegistro['email_login'] = $email;

        if (!Seguridad::validarEmail($email)) {
            $erroresLogin['email'] = 'Ingresa un correo electronico valido.';
        }
        if ($contrasena === '') {
            $erroresLogin['contrasena'] = 'La contrasena es obligatoria.';
        }

        if (empty($erroresLogin)) {
            try {
                $usuario = $usuarioModelo->buscarPorEmail($email);
                if (
                    !$usuario
                    || !(bool)$usuario['activo']
                    || !password_verify($contrasena, (string)$usuario['contrasena_hash'])
                ) {
                    $erroresLogin['general'] = 'Correo o contrasena incorrectos.';
                } else {
                    Seguridad::regenerarSesionLogin();
                    $_SESSION['usuario_id'] = (int)$usuario['id_usuario'];
                    $_SESSION['usuario_nombre'] = (string)$usuario['nombre'];
                    $_SESSION['usuario_rol'] = (string)$usuario['rol'];
                    $usuarioModelo->actualizarUltimoAcceso((int)$usuario['id_usuario']);
                    Seguridad::redirigir('../inicio/inicio.php');
                }
            } catch (Throwable $e) {
                error_log('Error login: ' . $e->getMessage());
                $erroresLogin['general'] = 'No se pudo iniciar sesion en este momento.';
            }
        }
    } elseif ($accion === 'registro') {
        $tabActiva = 'registro';
        $nombre = Seguridad::limpiarTexto($_POST['nombre'] ?? '', 80);
        $email = mb_strtolower(Seguridad::limpiarTexto($_POST['email'] ?? '', 254));
        $contrasena = (string)($_POST['contrasena'] ?? '');
        $confirmar = (string)($_POST['confirmar_contrasena'] ?? '');
        $aceptaTerminos = ($_POST['acepta_terminos'] ?? '') === '1';

        $valoresRegistro['nombre'] = $nombre;
        $valoresRegistro['email'] = $email;

        if (!Seguridad::validarNombre($nombre)) {
            $erroresRegistro['nombre'] = 'Solo letras y espacios, entre 2 y 80 caracteres.';
        }
        if (!Seguridad::validarEmail($email)) {
            $erroresRegistro['email'] = 'Ingresa un correo electronico valido.';
        } elseif ($usuarioModelo->emailExiste($email)) {
            $erroresRegistro['email'] = 'Ese correo ya esta registrado.';
        }
        if (!Seguridad::validarContrasena($contrasena)) {
            $erroresRegistro['contrasena'] = 'Minimo 8 caracteres, con mayuscula, minuscula, numero y caracter especial.';
        }
        if ($confirmar !== $contrasena) {
            $erroresRegistro['confirmar'] = 'Las contrasenas no coinciden.';
        }
        if (!$aceptaTerminos) {
            $erroresRegistro['terminos'] = 'Debes aceptar los terminos y condiciones.';
        }

        if (empty($erroresRegistro)) {
            try {
                $idUsuario = $usuarioModelo->crear($nombre, $email, $contrasena);
                Seguridad::regenerarSesionLogin();
                $_SESSION['usuario_id'] = $idUsuario;
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_rol'] = 'estandar';
                Seguridad::redirigir('../user/perfil.php');
            } catch (Throwable $e) {
                error_log('Error registro: ' . $e->getMessage());
                $erroresRegistro['general'] = 'No se pudo crear la cuenta. Intenta nuevamente.';
            }
        }
    }
}

$tituloPagina    = 'Acceso';
$paginaActiva    = '';
include __DIR__ . '/../partials/header.php';

// Defaults seguros
$erroresLogin    = $erroresLogin    ?? [];
$erroresRegistro = $erroresRegistro ?? [];
$tabActiva       = ($tabActiva ?? 'login') === 'registro' ? 'registro' : 'login';
$valoresRegistro = $valoresRegistro ?? [];

$emailLoginPrev  = htmlspecialchars($valoresRegistro['email_login'] ?? '', ENT_QUOTES, 'UTF-8');
$nombreRegPrev   = htmlspecialchars($valoresRegistro['nombre']      ?? '', ENT_QUOTES, 'UTF-8');
$emailRegPrev    = htmlspecialchars($valoresRegistro['email']       ?? '', ENT_QUOTES, 'UTF-8');
?>

<main class="pagina-auth">
    <div class="contenedor--estrecho" style="width:100%">

        <div class="auth-logo">🎬 Cine<span>Match</span></div>

        <div class="tarjeta">

            <!-- Tabs -->
            <div class="auth-tabs" role="tablist">
                <button class="auth-tab <?= $tabActiva === 'login'    ? 'activo' : '' ?>"
                        role="tab"
                        aria-selected="<?= $tabActiva === 'login' ? 'true' : 'false' ?>"
                        data-tab="login">
                    Iniciar sesión
                </button>
                <button class="auth-tab <?= $tabActiva === 'registro' ? 'activo' : '' ?>"
                        role="tab"
                        aria-selected="<?= $tabActiva === 'registro' ? 'true' : 'false' ?>"
                        data-tab="registro">
                    Crear cuenta
                </button>
            </div>

            <!-- ============================
                 LOGIN
                 ============================ -->
            <div class="auth-formulario <?= $tabActiva === 'login' ? 'activo' : '' ?>" id="tab-login" role="tabpanel">

                <p class="tarjeta__subtitulo">Bienvenido de vuelta. Ingresa tus datos para continuar.</p>

                <?php if (!empty($erroresLogin['general'])): ?>
                <div class="alerta alerta--error" role="alert">
                    <span class="alerta__icono">⚠</span>
                    <span><?= htmlspecialchars($erroresLogin['general'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>

                <!-- Al integrar con el backend, action apunta al controlador PHP que procese el login -->
                <form id="formulario-login" method="POST" action="auth.php" novalidate>
                    <?= csrf_input() ?>
                    <input type="hidden" name="accion" value="login">

                    <!-- Email -->
                    <div class="grupo-campo">
                        <label for="login-email">Correo electrónico</label>
                        <input type="email"
                               id="login-email"
                               name="email"
                               class="campo-input <?= !empty($erroresLogin['email']) ? 'campo--error' : '' ?>"
                               value="<?= $emailLoginPrev ?>"
                               placeholder="tucorreo@ejemplo.com"
                               maxlength="254"
                               autocomplete="email">
                        <?php if (!empty($erroresLogin['email'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresLogin['email'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Contraseña -->
                    <div class="grupo-campo">
                        <label for="login-contrasena">Contraseña</label>
                        <div class="campo-contrasena-wrapper">
                            <input type="password"
                                   id="login-contrasena"
                                   name="contrasena"
                                   class="campo-input <?= !empty($erroresLogin['contrasena']) ? 'campo--error' : '' ?>"
                                   placeholder="Tu contraseña"
                                   maxlength="128"
                                   autocomplete="current-password">
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña">👁️</button>
                        </div>
                        <?php if (!empty($erroresLogin['contrasena'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresLogin['contrasena'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn--primario btn--bloque">Iniciar sesión</button>
                </form>
            </div>

            <!-- ============================
                 REGISTRO
                 ============================ -->
            <div class="auth-formulario <?= $tabActiva === 'registro' ? 'activo' : '' ?>" id="tab-registro" role="tabpanel">

                <p class="tarjeta__subtitulo">Crea tu cuenta y empieza a descubrir contenido para ti.</p>

                <?php if (!empty($erroresRegistro['general'])): ?>
                <div class="alerta alerta--error" role="alert">
                    <span class="alerta__icono">⚠</span>
                    <span><?= htmlspecialchars($erroresRegistro['general'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>

                <!-- Al integrar con el backend, action apunta al controlador PHP que procese el registro -->
                <form id="formulario-registro" method="POST" action="auth.php" novalidate autocomplete="on">
                    <?= csrf_input() ?>
                    <input type="hidden" name="accion" value="registro">

                    <!-- Nombre -->
                    <div class="grupo-campo">
                        <label for="registro-nombre">Nombre completo</label>
                        <input type="text"
                               id="registro-nombre"
                               name="nombre"
                               class="campo-input <?= !empty($erroresRegistro['nombre']) ? 'campo--error' : '' ?>"
                               value="<?= $nombreRegPrev ?>"
                               placeholder="Ana López"
                               maxlength="50"
                               autocomplete="name">
                        <?php if (!empty($erroresRegistro['nombre'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresRegistro['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="grupo-campo">
                        <label for="registro-email">Correo electrónico</label>
                        <input type="email"
                               id="registro-email"
                               name="email"
                               class="campo-input <?= !empty($erroresRegistro['email']) ? 'campo--error' : '' ?>"
                               value="<?= $emailRegPrev ?>"
                               placeholder="tucorreo@ejemplo.com"
                               maxlength="254"
                               autocomplete="email">
                        <?php if (!empty($erroresRegistro['email'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresRegistro['email'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Contraseña -->
                    <div class="grupo-campo">
                        <label for="registro-contrasena">Contraseña</label>
                        <div class="campo-contrasena-wrapper">
                            <input type="password"
                                   id="registro-contrasena"
                                   name="contrasena"
                                   class="campo-input <?= !empty($erroresRegistro['contrasena']) ? 'campo--error' : '' ?>"
                                   placeholder="Mínimo 8 caracteres"
                                   maxlength="128"
                                   autocomplete="new-password">
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña">👁️</button>
                        </div>
                        <!-- Indicador de fuerza -->
                        <div class="bloque-fuerza">
                            <div class="barra-contrasena">
                                <div class="barra-contrasena__relleno"></div>
                            </div>
                            <p class="texto-fuerza" aria-live="polite"></p>
                        </div>
                        <p class="texto-suave" style="font-size:0.75rem; margin-top:0.3rem;">
                            Debe tener mayúscula, minúscula, número y carácter especial ($&nbsp;#&nbsp;€&nbsp;%&nbsp;-&nbsp;_)
                        </p>
                        <?php if (!empty($erroresRegistro['contrasena'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresRegistro['contrasena'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div class="grupo-campo">
                        <label for="registro-confirmar">Confirmar contraseña</label>
                        <div class="campo-contrasena-wrapper">
                            <input type="password"
                                   id="registro-confirmar"
                                   name="confirmar_contrasena"
                                   class="campo-input <?= !empty($erroresRegistro['confirmar']) ? 'campo--error' : '' ?>"
                                   placeholder="Repite tu contraseña"
                                   maxlength="128"
                                   autocomplete="new-password">
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña">👁️</button>
                        </div>
                        <?php if (!empty($erroresRegistro['confirmar'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresRegistro['confirmar'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Términos -->
                    <div class="grupo-campo">
                        <label style="display:flex; align-items:center; gap:0.6rem; text-transform:none; letter-spacing:0; font-weight:400; font-size:0.88rem; cursor:pointer;">
                            <input type="checkbox" id="registro-terminos" name="acepta_terminos" value="1">
                            Acepto los <a href="#terminos">términos y condiciones</a>
                        </label>
                        <?php if (!empty($erroresRegistro['terminos'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresRegistro['terminos'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn--primario btn--bloque">Crear cuenta</button>
                </form>
            </div>

        </div><!-- /tarjeta -->
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
