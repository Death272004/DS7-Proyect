<?php
declare(strict_types=1);

abstract class Controlador
{
    protected function vista(string $vista, array $datos = []): void
    {
        $datos = array_merge([
            'nombreUsuario' => $_SESSION['usuario_nombre'] ?? '',
            'rolUsuario' => $_SESSION['usuario_rol'] ?? '',
        ], $datos);

        $url = static fn (string $ruta = 'inicio', array $parametros = []): string => app_url($ruta, $parametros);
        $asset = static fn (string $ruta): string => app_asset($ruta);

        extract($datos, EXTR_SKIP);

        $archivoVista = APP_BASE_PATH . '/views/' . $vista . '.php';
        if (!is_file($archivoVista)) {
            http_response_code(404);
            echo 'Vista no encontrada.';
            return;
        }

        if (!defined('APP_RENDERING_VIEW')) {
            define('APP_RENDERING_VIEW', true);
        }

        include $archivoVista;
    }

    protected function redirigir(string $ruta, array $parametros = []): void
    {
        Seguridad::redirigir(app_url($ruta, $parametros));
    }
}
