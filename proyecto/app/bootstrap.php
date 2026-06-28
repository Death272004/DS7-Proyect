<?php
declare(strict_types=1);

require_once __DIR__ . '/core/Seguridad.php';
require_once __DIR__ . '/modelos/UsuarioModelo.php';
require_once __DIR__ . '/modelos/ContenidoModelo.php';

Seguridad::iniciarSesion();

if (!function_exists('e')) {
    function e(mixed $valor): string
    {
        return Seguridad::escapar($valor);
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string
    {
        return Seguridad::csrfInput();
    }
}

$nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
$rolUsuario = $_SESSION['usuario_rol'] ?? '';
