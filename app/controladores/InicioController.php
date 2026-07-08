<?php
declare(strict_types=1);

final class InicioController extends Controlador
{
    public function index(): void
    {
        if (Seguridad::estaAutenticado()) {
            $this->redirigir('catalogo');
        }

        $this->vista('inicio/inicio', [
            'tituloPagina' => 'Inicio',
            'paginaActiva' => 'inicio',
        ]);
    }
}
