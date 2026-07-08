<?php
include __DIR__ . '/../partials/header.php';

$erroresLogin    = $erroresLogin    ?? [];
$erroresRegistro = $erroresRegistro ?? [];
$tabActiva       = ($tabActiva ?? 'login') === 'registro' ? 'registro' : 'login';
$valoresRegistro = $valoresRegistro ?? [];

$emailLoginPrev  = htmlspecialchars($valoresRegistro['email_login'] ?? '', ENT_QUOTES, 'UTF-8');
$nombreRegPrev   = htmlspecialchars($valoresRegistro['nombre']      ?? '', ENT_QUOTES, 'UTF-8');
$emailRegPrev    = htmlspecialchars($valoresRegistro['email']       ?? '', ENT_QUOTES, 'UTF-8');
$loginBloqueado  = !empty($erroresLogin['bloqueado']);
$bloqueoSegundos = max(0, (int)($erroresLogin['bloqueo_segundos'] ?? 0));
?>

<main class="pagina-auth">
    <section class="auth-shell auth-shell--<?= $tabActiva ?>" aria-label="Acceso a Framefy">
        <div class="auth-panel">
            <div class="auth-logo">
                <img class="auth-logo__img auth-logo__img--oscuro" src="<?= htmlspecialchars($asset('img/logo-framefy.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Framefy">
                <img class="auth-logo__img auth-logo__img--claro" src="<?= htmlspecialchars($asset('img/logo-framefy-negro.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Framefy">
            </div>
            <p class="auth-subtitulo">Tu próximo maratón comienza aquí.</p>

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

            <div class="auth-formulario <?= $tabActiva === 'login' ? 'activo' : '' ?>" id="tab-login" role="tabpanel">

                <p class="auth-formulario__texto">Bienvenido de vuelta. Inicia sesión para continuar.</p>

                <?php if (!empty($erroresLogin['general'])): ?>
                <div class="alerta alerta--error" role="alert">
                    <span class="alerta__icono"><?= app_icon('shield') ?></span>
                    <span><?= htmlspecialchars($erroresLogin['general'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>

                <form id="formulario-login"
                      method="POST"
                      action="<?= htmlspecialchars($url('auth'), ENT_QUOTES, 'UTF-8') ?>"
                      class="<?= $loginBloqueado ? 'auth-login-bloqueado' : '' ?>"
                      data-bloqueo-segundos="<?= $loginBloqueado ? $bloqueoSegundos : 0 ?>"
                      novalidate>
                    <?= csrf_input() ?>
                    <input type="hidden" name="accion" value="login">

                    <div class="grupo-campo">
                        <label for="login-email">Correo electrónico</label>
                        <div class="campo-icono">
                            <span aria-hidden="true">✉</span>
                            <input type="email"
                                   id="login-email"
                                   name="email"
                                   class="campo-input <?= !empty($erroresLogin['email']) ? 'campo--error' : '' ?>"
                                   value="<?= $emailLoginPrev ?>"
                                   placeholder="tucorreo@ejemplo.com"
                                   maxlength="254"
                                   autocomplete="email"
                                   <?= $loginBloqueado ? 'disabled' : '' ?>>
                        </div>
                        <?php if (!empty($erroresLogin['email'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresLogin['email'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grupo-campo">
                        <label for="login-contrasena">Contraseña</label>
                        <div class="campo-contrasena-wrapper">
                            <span class="campo-icono__icono" aria-hidden="true">▣</span>
                            <input type="password"
                                   id="login-contrasena"
                                   name="contrasena"
                                   class="campo-input <?= !empty($erroresLogin['contrasena']) ? 'campo--error' : '' ?>"
                                   placeholder="Tu contraseña"
                                   maxlength="128"
                                   autocomplete="current-password"
                                   <?= $loginBloqueado ? 'disabled' : '' ?>>
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña" <?= $loginBloqueado ? 'disabled' : '' ?>><?= app_icon('eye') ?></button>
                        </div>
                        <?php if (!empty($erroresLogin['contrasena'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresLogin['contrasena'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="auth-opciones">
                        <label>
                            <input type="checkbox" name="recordarme" value="1" <?= $loginBloqueado ? 'disabled' : '' ?>>
                            <span>Recordarme</span>
                        </label>
                        <a href="#recuperar">¿Olvidaste tu contraseña?</a>
                    </div>

                    <?php if ($loginBloqueado): ?>
                        <p class="auth-bloqueo-countdown" role="status" aria-live="polite">
                            Panel bloqueado. Disponible en <strong><?= max(1, (int)ceil($bloqueoSegundos / 60)) ?> minuto</strong>.
                        </p>
                    <?php endif; ?>

                    <button type="submit" class="btn btn--primario btn--bloque auth-submit" <?= $loginBloqueado ? 'disabled' : '' ?>>
                        <?= $loginBloqueado ? 'Panel bloqueado' : 'Iniciar sesión' ?>
                    </button>
                </form>

            </div>

            <div class="auth-formulario <?= $tabActiva === 'registro' ? 'activo' : '' ?>" id="tab-registro" role="tabpanel">

                <p class="auth-formulario__texto">Crea tu cuenta y empieza a descubrir contenido para ti.</p>

                <?php if (!empty($erroresRegistro['general'])): ?>
                <div class="alerta alerta--error" role="alert">
                    <span class="alerta__icono"><?= app_icon('shield') ?></span>
                    <span><?= htmlspecialchars($erroresRegistro['general'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>

                <form id="formulario-registro" method="POST" action="<?= htmlspecialchars($url('auth'), ENT_QUOTES, 'UTF-8') ?>" novalidate autocomplete="on">
                    <?= csrf_input() ?>
                    <input type="hidden" name="accion" value="registro">

                    <div class="grupo-campo">
                        <label for="registro-nombre">Nombre completo</label>
                        <div class="campo-icono">
                            <span aria-hidden="true">●</span>
                            <input type="text"
                                   id="registro-nombre"
                                   name="nombre"
                                   class="campo-input <?= !empty($erroresRegistro['nombre']) ? 'campo--error' : '' ?>"
                                   value="<?= $nombreRegPrev ?>"
                                   placeholder="Ana López"
                                   maxlength="50"
                                   autocomplete="name">
                        </div>
                        <?php if (!empty($erroresRegistro['nombre'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresRegistro['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grupo-campo">
                        <label for="registro-email">Correo electrónico</label>
                        <div class="campo-icono">
                            <span aria-hidden="true">✉</span>
                            <input type="email"
                                   id="registro-email"
                                   name="email"
                                   class="campo-input <?= !empty($erroresRegistro['email']) ? 'campo--error' : '' ?>"
                                   value="<?= $emailRegPrev ?>"
                                   placeholder="tucorreo@ejemplo.com"
                                   maxlength="254"
                                   autocomplete="email">
                        </div>
                        <?php if (!empty($erroresRegistro['email'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresRegistro['email'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grupo-campo">
                        <label for="registro-contrasena">Contraseña</label>
                        <div class="campo-contrasena-wrapper">
                            <span class="campo-icono__icono" aria-hidden="true">▣</span>
                            <input type="password"
                                   id="registro-contrasena"
                                   name="contrasena"
                                   class="campo-input <?= !empty($erroresRegistro['contrasena']) ? 'campo--error' : '' ?>"
                                   placeholder="Mínimo 8 caracteres"
                                   maxlength="128"
                                   autocomplete="new-password">
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña"><?= app_icon('eye') ?></button>
                        </div>
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

                    <div class="grupo-campo">
                        <label for="registro-confirmar">Confirmar contraseña</label>
                        <div class="campo-contrasena-wrapper">
                            <span class="campo-icono__icono" aria-hidden="true">▣</span>
                            <input type="password"
                                   id="registro-confirmar"
                                   name="confirmar_contrasena"
                                   class="campo-input <?= !empty($erroresRegistro['confirmar']) ? 'campo--error' : '' ?>"
                                   placeholder="Repite tu contraseña"
                                   maxlength="128"
                                   autocomplete="new-password">
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña"><?= app_icon('eye') ?></button>
                        </div>
                        <?php if (!empty($erroresRegistro['confirmar'])): ?>
                            <span class="error-campo" role="alert">
                                <?= htmlspecialchars($erroresRegistro['confirmar'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn--primario btn--bloque auth-submit">Crear cuenta</button>
                </form>

            </div>

        </div>

        <p class="auth-cambio auth-cambio--login">¿No tienes cuenta?
            <button type="button" class="auth-cambio__btn" data-auth-switch="registro">Regístrate <span aria-hidden="true">→</span></button>
        </p>
        <p class="auth-cambio auth-cambio--registro">¿Ya tienes cuenta?
            <button type="button" class="auth-cambio__btn" data-auth-switch="login">Inicia sesión <span aria-hidden="true">→</span></button>
        </p>
    </section>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
