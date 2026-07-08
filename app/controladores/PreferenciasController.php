<?php
declare(strict_types=1);

final class PreferenciasController extends Controlador
{
    private UsuarioModelo $usuarioModelo;
    private ContenidoModelo $contenidoModelo;

    public function __construct()
    {
        $this->usuarioModelo = new UsuarioModelo();
        $this->contenidoModelo = new ContenidoModelo();
    }

    public function index(): void
    {
        Seguridad::requiereLogin(app_url('auth'));

        $errores = [];
        $generosSeleccionados = [];
        $tipoPreferido = Seguridad::limpiarTexto($_COOKIE['framefy_tipo_preferido'] ?? 'ambos', 20);
        $duracionPreferida = Seguridad::limpiarTexto($_COOKIE['framefy_duracion_preferida'] ?? 'media', 20);
        $temasSeleccionados = json_decode((string)($_COOKIE['framefy_temas_preferidos'] ?? '[]'), true);
        $temasSeleccionados = is_array($temasSeleccionados) ? array_map('strval', $temasSeleccionados) : [];

        if (Seguridad::metodoPost()) {
            $accion = Seguridad::limpiarTexto($_POST['accion'] ?? '', 30);

            if (!Seguridad::validarCsrf()) {
                $errores['general'] = 'La solicitud no es valida. Recarga la pagina e intenta nuevamente.';
            } elseif ($accion === 'guardar_preferencias') {
                $errores = $this->guardarPreferencias();
            } elseif ($accion === 'omitir_preferencias') {
                Seguridad::guardarCookie('framefy_preferencias_omitidas', '1', 2592000);
                $this->redirigir('catalogo');
            }
        }

        try {
            $generosDisponibles = $this->contenidoModelo->obtenerGeneros();
            $generosSeleccionados = $this->usuarioModelo->obtenerPreferencias(Seguridad::usuarioId());
        } catch (Throwable $e) {
            error_log('Error pantalla preferencias: ' . $e->getMessage());
            $generosDisponibles = [];
        }

        $this->vista('user/preferencias', [
            'tituloPagina' => 'Tus gustos',
            'paginaActiva' => 'preferencias',
            'generosDisponibles' => $generosDisponibles,
            'generosSeleccionados' => $generosSeleccionados,
            'tipoPreferido' => $tipoPreferido,
            'duracionPreferida' => $duracionPreferida,
            'temasSeleccionados' => $temasSeleccionados,
            'erroresPreferencias' => $errores,
        ]);
    }

    private function guardarPreferencias(): array
    {
        $errores = [];
        $generos = [];
        foreach ($_POST['generos'] ?? [] as $valor) {
            $idGenero = filter_var($valor, FILTER_VALIDATE_INT);
            if ($idGenero !== false && (int)$idGenero > 0) {
                $generos[] = (int)$idGenero;
            }
        }
        $generos = array_values(array_unique($generos));

        if (count($generos) === 0) {
            $errores['general'] = 'Selecciona al menos un genero favorito para personalizar tus recomendaciones.';
        } elseif (count($generos) > 10) {
            $errores['general'] = 'Puedes seleccionar maximo 10 generos.';
        }

        if (!empty($errores)) {
            return $errores;
        }

        $tipo = Seguridad::limpiarTexto($_POST['tipo_preferido'] ?? 'ambos', 20);
        $tipo = in_array($tipo, ['pelicula', 'serie', 'ambos'], true) ? $tipo : 'ambos';

        $duracion = Seguridad::limpiarTexto($_POST['duracion_preferida'] ?? 'media', 20);
        $duracion = in_array($duracion, ['corta', 'media', 'larga'], true) ? $duracion : 'media';

        $temasPermitidos = ['aventura', 'misterio', 'inspirador', 'futurista', 'familiar'];
        $temas = [];
        foreach ($_POST['temas'] ?? [] as $tema) {
            $temaLimpio = Seguridad::limpiarTexto($tema, 20);
            if (in_array($temaLimpio, $temasPermitidos, true)) {
                $temas[] = $temaLimpio;
            }
        }
        $temas = array_values(array_unique($temas));

        try {
            $this->usuarioModelo->guardarPreferencias(Seguridad::usuarioId(), $generos);
            Seguridad::guardarCookie('framefy_tipo_preferido', $tipo, 2592000);
            Seguridad::guardarCookie('framefy_duracion_preferida', $duracion, 2592000);
            Seguridad::guardarCookie('framefy_temas_preferidos', json_encode($temas), 2592000);
            Seguridad::borrarCookie('framefy_preferencias_omitidas');
            $this->redirigir('catalogo');
        } catch (Throwable $e) {
            error_log('Error guardando onboarding preferencias: ' . $e->getMessage());
            $errores['general'] = 'No se pudieron guardar tus preferencias.';
        }

        return $errores;
    }
}
