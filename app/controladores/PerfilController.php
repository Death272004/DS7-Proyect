<?php
declare(strict_types=1);

final class PerfilController extends Controlador
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

        $erroresPerfil = [];
        $mensajeExito = Seguridad::flash('perfil_exito') ?? '';
        $usuario = null;

        if (Seguridad::metodoPost()) {
            $accion = $_POST['accion'] ?? '';

            if (!Seguridad::validarCsrf()) {
                $erroresPerfil['general'] = 'La solicitud no es valida. Recarga la pagina e intenta nuevamente.';
            } elseif ($accion === 'actualizar_perfil') {
                [$erroresPerfil, $usuario] = $this->actualizarPerfil();
            } elseif ($accion === 'guardar_preferencias') {
                $erroresPerfil = $this->guardarPreferencias();
            }
        }

        try {
            $usuario = $usuario ?? $this->usuarioModelo->obtenerPorId(Seguridad::usuarioId());
            $generosDisponibles = $this->contenidoModelo->obtenerGeneros();
            $generosUsuario = $this->usuarioModelo->obtenerPreferencias(Seguridad::usuarioId());
            $historialReciente = $this->contenidoModelo->obtenerHistorialUsuario(Seguridad::usuarioId(), 5);
        } catch (Throwable $e) {
            error_log('Error perfil: ' . $e->getMessage());
            $generosDisponibles = [];
            $generosUsuario = [];
            $historialReciente = [];
        }

        $this->vista('user/perfil', [
            'tituloPagina' => 'Mi perfil',
            'paginaActiva' => 'perfil',
            'usuario' => $usuario,
            'generosDisponibles' => $generosDisponibles,
            'generosUsuario' => $generosUsuario,
            'historialReciente' => $historialReciente,
            'erroresPerfil' => $erroresPerfil,
            'mensajeExito' => $mensajeExito,
        ]);
    }

    private function actualizarPerfil(): array
    {
        $errores = [];
        $nombre = Seguridad::limpiarTexto($_POST['nombre'] ?? '', 80);
        $email = mb_strtolower(Seguridad::limpiarTexto($_POST['email'] ?? '', 254));
        $contrasenaNueva = (string)($_POST['contrasena_nueva'] ?? '');
        $confirmar = (string)($_POST['confirmar_contrasena'] ?? '');

        if (!Seguridad::validarNombre($nombre)) {
            $errores['nombre'] = 'Solo letras y espacios, entre 2 y 80 caracteres.';
        }
        if (!Seguridad::validarEmail($email)) {
            $errores['email'] = 'Ingresa un correo electronico valido.';
        } elseif ($this->usuarioModelo->emailExiste($email, Seguridad::usuarioId())) {
            $errores['email'] = 'Ese correo ya esta registrado por otro usuario.';
        }
        if ($contrasenaNueva !== '') {
            if (!Seguridad::validarContrasena($contrasenaNueva)) {
                $errores['contrasena_nueva'] = 'Minimo 8 caracteres, con mayuscula, minuscula, numero y caracter especial.';
            }
            if ($confirmar !== $contrasenaNueva) {
                $errores['confirmar'] = 'Las contrasenas no coinciden.';
            }
        }

        $usuario = [
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $_SESSION['usuario_rol'] ?? 'estandar',
            'creado_en' => date('Y-m-d'),
        ];

        if (!empty($errores)) {
            return [$errores, $usuario];
        }

        try {
            $this->usuarioModelo->actualizarPerfil(Seguridad::usuarioId(), $nombre, $email, $contrasenaNueva ?: null);
            $_SESSION['usuario_nombre'] = $nombre;
            Seguridad::guardarCookie('framefy_nombre', $nombre);
            Seguridad::flash('perfil_exito', 'Datos personales actualizados correctamente.');
            $this->redirigir('perfil');
        } catch (Throwable $e) {
            error_log('Error actualizando perfil: ' . $e->getMessage());
            $errores['general'] = 'No se pudieron guardar los cambios.';
        }

        return [$errores, $usuario];
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
            $errores['general'] = 'Selecciona al menos un genero favorito.';
        } elseif (count($generos) > 10) {
            $errores['general'] = 'Puedes seleccionar maximo 10 generos.';
        }

        if (!empty($errores)) {
            return $errores;
        }

        try {
            $this->usuarioModelo->guardarPreferencias(Seguridad::usuarioId(), $generos);
            Seguridad::flash('perfil_exito', 'Preferencias guardadas correctamente.');
            $this->redirigir('perfil');
        } catch (Throwable $e) {
            error_log('Error guardando preferencias: ' . $e->getMessage());
            $errores['general'] = 'No se pudieron guardar las preferencias.';
        }

        return $errores;
    }
}
