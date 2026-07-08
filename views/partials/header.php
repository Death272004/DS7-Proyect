<?php
if (!function_exists('app_url')) {
    require_once __DIR__ . '/../../app/bootstrap.php';
}

if (!defined('APP_RENDERING_VIEW')) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $mapaVistas = [
        '/views/inicio/inicio.php' => 'inicio',
        '/views/auth/auth.php' => 'auth',
        '/views/catalog/catalogo.php' => 'catalogo',
        '/views/catalog/detalle.php' => 'detalle',
        '/views/user/perfil.php' => 'perfil',
        '/views/user/preferencias.php' => 'preferencias',
        '/views/admin/panel.php' => 'admin',
    ];

    foreach ($mapaVistas as $sufijo => $rutaMvc) {
        if (str_ends_with($script, $sufijo)) {
            $parametros = array_intersect_key($_GET, array_flip(['tab', 'buscar', 'tipo', 'genero', 'id', 'seccion']));
            Seguridad::redirigir(app_url($rutaMvc, $parametros));
        }
    }
}

$url = $url ?? static fn (string $ruta = 'inicio', array $parametros = []): string => app_url($ruta, $parametros);
$asset = $asset ?? static fn (string $ruta): string => app_asset($ruta);

$tituloPagina  = htmlspecialchars($tituloPagina  ?? 'Framefy', ENT_QUOTES, 'UTF-8');
$paginaActiva  = $paginaActiva  ?? '';
$nombreUsuario = htmlspecialchars($nombreUsuario ?? '', ENT_QUOTES, 'UTF-8');
$rolUsuario    = $rolUsuario    ?? '';

$temaUsuario = 'oscuro';
if (isset($_COOKIE['tema']) && $_COOKIE['tema'] === 'claro') {
    $temaUsuario = 'claro';
}

$rutas = [
    'inicio'    => $url('inicio'),
    'catalogo'  => $url('catalogo'),
    'perfil'    => $url('perfil'),
    'admin'     => $url('admin'),
    'auth'      => $url('auth'),
    'registro'  => $url('auth', ['tab' => 'registro']),
    'logout'    => $url('logout'),
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Framefy - Plataforma de recomendación de películas y series">
    <title><?= $tituloPagina ?> - Framefy</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('css/styles.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="<?= $temaUsuario === 'claro' ? 'tema-claro' : '' ?>">

<nav class="navbar <?= $paginaActiva === 'inicio' ? 'navbar--landing' : '' ?>" role="navigation" aria-label="Navegación principal">
    <a href="<?= $rutas['inicio'] ?>" class="navbar__logo" aria-label="Framefy inicio">
        <img src="<?= htmlspecialchars($asset('img/logo-framefy.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Framefy" class="navbar__logo-img navbar__logo-img--oscuro">
        <img src="<?= htmlspecialchars($asset('img/logo-framefy-negro.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Framefy" class="navbar__logo-img navbar__logo-img--claro">
    </a>

    <ul class="navbar__links" role="list">
        <?php if (empty($nombreUsuario)): ?>
            <li>
                <a href="<?= $rutas['inicio'] ?>"
                   class="<?= $paginaActiva === 'inicio' ? 'activo' : '' ?>">
                   Inicio
                </a>
            </li>
        <?php endif; ?>
        <?php if (!empty($nombreUsuario)): ?>
            <li>
                <a href="<?= $rutas['catalogo'] ?>"
                   class="<?= $paginaActiva === 'catalogo' ? 'activo' : '' ?>">
                   Catálogo
                </a>
            </li>
        <?php endif; ?>
        <?php if (!empty($nombreUsuario)): ?>
            <li>
                <a href="<?= $rutas['perfil'] ?>"
               class="<?= $paginaActiva === 'perfil' ? 'activo' : '' ?>">
               Perfil
            </a>
        </li>
        <?php endif; ?>
        <?php if ($rolUsuario === 'admin'): ?>
        <li>
            <a href="<?= $rutas['admin'] ?>"
               class="<?= $paginaActiva === 'admin' ? 'activo' : '' ?>">
               Panel de Control
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="navbar__acciones">
        <button id="btn-toggle-tema" class="btn-tema" aria-label="Cambiar tema visual">
            <?= app_icon($temaUsuario === 'claro' ? 'moon' : 'sun') ?>
        </button>
        <?php if (!empty($nombreUsuario)): ?>
            <a href="<?= $rutas['perfil'] ?>"  class="btn btn--secundario btn--pequeno btn-usuario-navbar">
                <span class="avatar-mini"><?= htmlspecialchars(mb_strtoupper(mb_substr($nombreUsuario, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                <?= $nombreUsuario ?>
                <?= app_icon('chevron-down') ?>
            </a>
            <a href="<?= $rutas['logout'] ?>"  class="btn btn--secundario btn--pequeno">Salir</a>
        <?php else: ?>
            <a href="<?= $rutas['auth'] ?>"    class="btn btn--pequeno btn-nav btn-nav--login">Iniciar sesión</a>
            <a href="<?= $rutas['registro'] ?>" class="btn btn--pequeno btn-nav btn-nav--registro">Regístrate</a>
        <?php endif; ?>
    </div>
</nav>
