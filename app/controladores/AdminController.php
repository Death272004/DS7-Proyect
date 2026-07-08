<?php
declare(strict_types=1);

final class AdminController extends Controlador
{
    private ContenidoModelo $contenidoModelo;
    private UsuarioModelo $usuarioModelo;

    public function __construct()
    {
        $this->contenidoModelo = new ContenidoModelo();
        $this->usuarioModelo = new UsuarioModelo();
    }

    public function index(): void
    {
        Seguridad::requiereAdmin(app_url('inicio'));

        $erroresFormulario = [];
        $mensajeExito = Seguridad::flash('admin_exito') ?? '';
        $seccionSolicitada = Seguridad::limpiarTexto($_GET['seccion'] ?? 'contenido', 30);
        $seccionActiva = in_array($seccionSolicitada, ['contenido', 'usuarios', 'estadisticas'], true)
            ? $seccionSolicitada
            : 'contenido';

        if (Seguridad::metodoPost()) {
            $accion = $_POST['accion'] ?? '';

            if (!Seguridad::validarCsrf()) {
                $erroresFormulario['general'] = 'La solicitud no es valida. Recarga la pagina e intenta nuevamente.';
            } elseif ($accion === 'guardar_contenido') {
                $erroresFormulario = $this->guardarContenido();
            } elseif ($accion === 'eliminar_contenido') {
                $this->eliminarContenido();
            } elseif ($accion === 'toggle_usuario') {
                $this->toggleUsuario();
            }
        }

        try {
            $contenidos = $this->contenidoModelo->listarAdmin();
            $usuarios = $this->usuarioModelo->listarUsuarios();
            $generosPopulares = $this->contenidoModelo->generosPopulares();
            $generosOpciones = $this->contenidoModelo->obtenerGeneros();
            $estadisticas = $this->contenidoModelo->estadisticas();
        } catch (Throwable $e) {
            error_log('Error panel admin: ' . $e->getMessage());
            $contenidos = [];
            $usuarios = [];
            $generosPopulares = [];
            $generosOpciones = [];
            $estadisticas = [
                'totalUsuarios' => 0,
                'totalContenidos' => 0,
                'totalCalificaciones' => 0,
                'generoMasVisto' => 'Sin visitas',
            ];
        }

        $this->vista('admin/panel', [
            'tituloPagina' => 'Panel de administracion',
            'paginaActiva' => 'admin',
            'seccionActiva' => $seccionActiva,
            'mensajeExito' => $mensajeExito,
            'erroresFormulario' => $erroresFormulario,
            'contenidos' => $contenidos,
            'usuarios' => $usuarios,
            'generosPopulares' => $generosPopulares,
            'generosOpciones' => $generosOpciones,
            'totalUsuarios' => $estadisticas['totalUsuarios'],
            'totalContenidos' => $estadisticas['totalContenidos'],
            'totalCalificaciones' => $estadisticas['totalCalificaciones'],
            'generoMasVisto' => $estadisticas['generoMasVisto'],
        ]);
    }

    private function guardarContenido(): array
    {
        $errores = [];
        $titulo = Seguridad::limpiarTexto($_POST['titulo'] ?? '', 200);
        $tipo = Seguridad::limpiarTexto($_POST['tipo'] ?? '', 20);
        $anio = trim((string)($_POST['anio'] ?? ''));
        $duracion = trim((string)($_POST['duracion'] ?? ''));
        $calificacion = trim((string)($_POST['calificacion'] ?? ''));
        $generoId = Seguridad::entero($_POST['genero_id'] ?? 0, 0);
        $sinopsis = Seguridad::limpiarTexto($_POST['sinopsis'] ?? '', 1000);
        $imagenUrl = trim((string)($_POST['imagen_url'] ?? ''));
        $trailerUrl = trim((string)($_POST['trailer_url'] ?? ''));
        $idContenido = Seguridad::entero($_POST['id_contenido'] ?? 0, 0);
        $anioActual = (int)date('Y');

        if ($titulo === '') {
            $errores['titulo'] = 'El titulo es obligatorio.';
        }
        if (!in_array($tipo, ['pelicula', 'serie'], true)) {
            $errores['tipo'] = 'Selecciona el tipo de contenido.';
        }
        if ($anio !== '' && (!ctype_digit($anio) || (int)$anio < 1888 || (int)$anio > $anioActual + 2)) {
            $errores['anio'] = 'El anio debe estar entre 1888 y ' . ($anioActual + 2) . '.';
        }
        if ($duracion !== '' && (!ctype_digit($duracion) || (int)$duracion < 1 || (int)$duracion > 999)) {
            $errores['duracion'] = 'La duracion debe estar entre 1 y 999 minutos.';
        }
        if ($calificacion !== '' && (!is_numeric($calificacion) || (float)$calificacion < 0 || (float)$calificacion > 10)) {
            $errores['calificacion'] = 'La calificacion debe estar entre 0 y 10.';
        }
        if ($generoId <= 0) {
            $errores['genero'] = 'Selecciona un genero.';
        }
        if (mb_strlen($sinopsis) < 10) {
            $errores['sinopsis'] = 'La sinopsis debe tener al menos 10 caracteres.';
        }
        if ($imagenUrl !== '' && filter_var($imagenUrl, FILTER_VALIDATE_URL) === false) {
            $errores['imagen_url'] = 'Ingresa una URL valida.';
        }
        if ($trailerUrl !== '' && app_youtube_embed_url($trailerUrl) === null) {
            $errores['trailer_url'] = 'Ingresa una URL valida de YouTube o el ID del video.';
        }

        if (!empty($errores)) {
            return $errores;
        }

        try {
            $datos = [
                'titulo' => $titulo,
                'tipo' => $tipo,
                'anio' => $anio === '' ? null : (int)$anio,
                'duracion' => $duracion === '' ? null : (int)$duracion,
                'calificacion' => $calificacion === '' ? 0.0 : (float)$calificacion,
                'genero_id' => $generoId,
                'sinopsis' => $sinopsis,
                'imagen_url' => $imagenUrl === '' ? null : $imagenUrl,
                'trailer_url' => $trailerUrl === '' ? null : $trailerUrl,
            ];

            if ($idContenido > 0) {
                $this->contenidoModelo->actualizarContenido($idContenido, $datos);
                Seguridad::flash('admin_exito', 'Contenido actualizado correctamente.');
            } else {
                $this->contenidoModelo->crearContenido($datos);
                Seguridad::flash('admin_exito', 'Contenido agregado correctamente.');
            }
            $this->redirigir('admin', ['seccion' => 'contenido']);
        } catch (Throwable $e) {
            error_log('Error guardando contenido: ' . $e->getMessage());
            $errores['general'] = 'No se pudo guardar el contenido.';
        }

        return $errores;
    }

    private function eliminarContenido(): void
    {
        try {
            $this->contenidoModelo->eliminarContenido(Seguridad::entero($_POST['id'] ?? 0, 0));
            Seguridad::flash('admin_exito', 'Contenido eliminado correctamente.');
        } catch (Throwable $e) {
            error_log('Error eliminando contenido: ' . $e->getMessage());
            Seguridad::flash('admin_exito', 'No se pudo eliminar el contenido.');
        }
        $this->redirigir('admin', ['seccion' => 'contenido']);
    }

    private function toggleUsuario(): void
    {
        try {
            $this->usuarioModelo->toggleEstado(Seguridad::entero($_POST['id'] ?? 0, 0));
            Seguridad::flash('admin_exito', 'Estado del usuario actualizado.');
        } catch (Throwable $e) {
            error_log('Error cambiando usuario: ' . $e->getMessage());
            Seguridad::flash('admin_exito', 'No se pudo actualizar el usuario.');
        }
        $this->redirigir('admin', ['seccion' => 'usuarios']);
    }
}
