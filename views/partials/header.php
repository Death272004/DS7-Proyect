<?php
/**
 * views/partials/header.php
 * Incluir al inicio de cada vista.
 *
 * Variables que el controlador puede pasar (todas opcionales):
 *   $tituloPagina  (string) - Título de la pestaña
 *   $paginaActiva  (string) - 'inicio' | 'catalogo' | 'perfil' | 'admin'
 *   $nombreUsuario (string) - Nombre del usuario logueado
 *   $rolUsuario    (string) - 'admin' | 'estandar'
 *   $temaUsuario   (string) - 'oscuro' | 'claro'
 */

require_once __DIR__ . '/../../app/bootstrap.php';

// Valores seguros por defecto (no necesita controlador ni sesión)
$tituloPagina  = htmlspecialchars($tituloPagina  ?? 'CineMatch', ENT_QUOTES, 'UTF-8');
$paginaActiva  = $paginaActiva  ?? '';
$nombreUsuario = htmlspecialchars($nombreUsuario ?? '', ENT_QUOTES, 'UTF-8');
$rolUsuario    = $rolUsuario    ?? '';

// Leer tema desde cookie directamente (sin sesión)
$temaUsuario = 'oscuro';
if (isset($_COOKIE['tema']) && $_COOKIE['tema'] === 'claro') {
    $temaUsuario = 'claro';
}

// Ruta base hacia /public  (views/subcarpeta/archivo.php -> ../../public)
$rutaPublic = '../../public';

// Rutas de navegación relativas entre vistas
// Todas las vistas están en views/<subcarpeta>/archivo.php
// Para ir de una subcarpeta a otra: ../otra_subcarpeta/archivo.php
$rutas = [
    'inicio'    => '../inicio/inicio.php',
    'catalogo'  => '../catalog/catalogo.php',
    'perfil'    => '../user/perfil.php',
    'admin'     => '../admin/panel.php',
    'auth'      => '../auth/auth.php',
    'logout'    => '../auth/auth.php?accion=logout',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CineMatch – Plataforma de recomendación de películas y series">
    <title><?= $tituloPagina ?> – CineMatch</title>
    <link rel="stylesheet" href="<?= $rutaPublic ?>/css/styles.css">
</head>
<body class="<?= $temaUsuario === 'claro' ? 'tema-claro' : '' ?>">

<nav class="navbar" role="navigation" aria-label="Navegación principal">
    <a href="<?= $rutas['inicio'] ?>" class="navbar__logo">🎬 Cine<span>Match</span></a>

    <ul class="navbar__links" role="list">
        <li>
            <a href="<?= $rutas['inicio'] ?>"
               class="<?= $paginaActiva === 'inicio' ? 'activo' : '' ?>">
               Inicio
            </a>
        </li>
        <li>
            <a href="<?= $rutas['catalogo'] ?>"
               class="<?= $paginaActiva === 'catalogo' ? 'activo' : '' ?>">
               Catálogo
            </a>
        </li>
        <li>
            <a href="<?= $rutas['perfil'] ?>"
               class="<?= $paginaActiva === 'perfil' ? 'activo' : '' ?>">
               Perfil
            </a>
        </li>
        <?php if ($rolUsuario === 'admin'): ?>
        <li>
            <a href="<?= $rutas['admin'] ?>"
               class="<?= $paginaActiva === 'admin' ? 'activo' : '' ?>">
               Admin
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="navbar__acciones">
        <button id="btn-toggle-tema" class="btn-tema" aria-label="Cambiar tema visual">
            <?= $temaUsuario === 'claro' ? '🌙' : '☀️' ?>
        </button>
        <?php if (!empty($nombreUsuario)): ?>
            <a href="<?= $rutas['perfil'] ?>"  class="btn btn--secundario btn--pequeno">👤 <?= $nombreUsuario ?></a>
            <a href="<?= $rutas['logout'] ?>"  class="btn btn--secundario btn--pequeno">Salir</a>
        <?php else: ?>
            <a href="<?= $rutas['auth'] ?>"    class="btn btn--primario btn--pequeno">Entrar</a>
        <?php endif; ?>
    </div>
</nav>
