<?php
include __DIR__ . '/../partials/header.php';

$usuario           = $usuario           ?? ['nombre' => 'Usuario', 'email' => '', 'rol' => 'estandar', 'creado_en' => date('Y-m-d')];
$generosDisponibles = $generosDisponibles ?? [];
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
        <span class="alerta__icono"><?= app_icon('shield') ?></span>
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

                <form id="formulario-perfil" method="POST" action="<?= htmlspecialchars($url('perfil'), ENT_QUOTES, 'UTF-8') ?>" novalidate autocomplete="on">
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
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña"><?= app_icon('eye') ?></button>
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
                            <button type="button" class="btn-ver-contrasena" aria-label="Ver contraseña"><?= app_icon('eye') ?></button>
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

                <form id="formulario-preferencias" method="POST" action="<?= htmlspecialchars($url('perfil'), ENT_QUOTES, 'UTF-8') ?>" novalidate>
                    <?= csrf_input() ?>
                    <input type="hidden" name="accion" value="guardar_preferencias">

                    <div class="grupo-campo">
                        <div class="grupo-checkboxes" role="group" aria-label="Géneros">
                            <?php foreach ($generosDisponibles as $genero):
                                $gId     = (int) $genero['id'];
                                $gNombre = htmlspecialchars($genero['nombre'], ENT_QUOTES, 'UTF-8');
                                $checked = in_array($gId, $generosUsuario, true);
                            ?>
                                <input class="checkbox-genero"
                                       type="checkbox"
                                       id="genero-<?= $gId ?>"
                                       name="generos[]"
                                       value="<?= $gId ?>"
                                       <?= $checked ? 'checked' : '' ?>>
                                <label for="genero-<?= $gId ?>"><?= app_genero_icon($genero['nombre'], 'chip-genero__icono') ?><?= $gNombre ?></label>
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
