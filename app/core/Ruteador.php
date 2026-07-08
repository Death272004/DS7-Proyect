<?php
declare(strict_types=1);

final class Ruteador
{
    private array $rutas = [
        'inicio' => [InicioController::class, 'index'],
        'auth' => [AuthController::class, 'index'],
        'logout' => [AuthController::class, 'logout'],
        'catalogo' => [CatalogoController::class, 'index'],
        'detalle' => [CatalogoController::class, 'detalle'],
        'perfil' => [PerfilController::class, 'index'],
        'preferencias' => [PreferenciasController::class, 'index'],
        'admin' => [AdminController::class, 'index'],
        'api_catalogo' => [ApiController::class, 'catalogo'],
        'api_intercambio' => [ApiController::class, 'intercambio'],
    ];

    public function despachar(): void
    {
        $ruta = Seguridad::limpiarTexto($_GET['ruta'] ?? 'inicio', 50);
        [$controladorClase, $metodo] = $this->rutas[$ruta] ?? $this->rutas['inicio'];

        $controlador = new $controladorClase();
        $controlador->$metodo();
    }
}
