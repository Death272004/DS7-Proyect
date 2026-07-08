<?php
declare(strict_types=1);

final class AuthController extends Controlador
{
    private UsuarioModelo $usuarioModelo;

    public function __construct()
    {
        $this->usuarioModelo = new UsuarioModelo();
    }

    public function index(): void
    {
        $erroresLogin = [];
        $erroresRegistro = [];
        $tabActiva = ($_GET['tab'] ?? '') === 'registro' ? 'registro' : 'login';
        $valoresRegistro = [];

        if (Seguridad::metodoPost()) {
            $accion = $_POST['accion'] ?? '';

            if (!Seguridad::validarCsrf()) {
                $mensaje = 'La solicitud no es valida. Recarga la pagina e intenta nuevamente.';
                if ($accion === 'registro') {
                    $erroresRegistro['general'] = $mensaje;
                    $tabActiva = 'registro';
                } else {
                    $erroresLogin['general'] = $mensaje;
                }
            } elseif ($accion === 'login') {
                [$erroresLogin, $valoresRegistro] = $this->procesarLogin();
            } elseif ($accion === 'registro') {
                $tabActiva = 'registro';
                [$erroresRegistro, $valoresRegistro] = $this->procesarRegistro();
            }
        }

        $this->vista('auth/auth', [
            'tituloPagina' => 'Acceso',
            'paginaActiva' => '',
            'erroresLogin' => $erroresLogin,
            'erroresRegistro' => $erroresRegistro,
            'tabActiva' => $tabActiva,
            'valoresRegistro' => $valoresRegistro,
        ]);
    }

    public function logout(): void
    {
        Seguridad::cerrarSesion();
        Seguridad::borrarCookie('framefy_nombre');
        Seguridad::borrarCookie('framefy_ultimas_vistas');
        $this->redirigir('inicio');
    }

    private function procesarLogin(): array
    {
        $errores = [];
        $valores = [];
        $email = mb_strtolower(Seguridad::limpiarTexto($_POST['email'] ?? '', 254));
        $contrasena = (string)($_POST['contrasena'] ?? '');
        $valores['email_login'] = $email;

        if (!Seguridad::validarEmail($email)) {
            $errores['email'] = 'Ingresa un correo electronico valido.';
        }
        if ($contrasena === '') {
            $errores['contrasena'] = 'La contrasena es obligatoria.';
        }

        if (!empty($errores)) {
            return [$errores, $valores];
        }

        $segundosBloqueo = Seguridad::segundosBloqueoLogin($email);
        if ($segundosBloqueo > 0) {
            $errores['general'] = 'Sesion bloqueada por demasiados intentos fallidos. Espera 1 minuto e intenta nuevamente.';
            $errores['bloqueado'] = true;
            $errores['bloqueo_segundos'] = $segundosBloqueo;
            return [$errores, $valores];
        }

        try {
            $usuario = $this->usuarioModelo->buscarPorEmail($email);
            if (
                !$usuario
                || !(bool)$usuario['activo']
                || !password_verify($contrasena, (string)$usuario['contrasena_hash'])
            ) {
                $segundosBloqueo = Seguridad::registrarLoginFallido($email);
                if ($segundosBloqueo > 0) {
                    $errores['general'] = 'Sesion bloqueada por demasiados intentos fallidos. Espera 1 minuto e intenta nuevamente.';
                    $errores['bloqueado'] = true;
                    $errores['bloqueo_segundos'] = $segundosBloqueo;
                    return [$errores, $valores];
                }

                $errores['general'] = 'Correo o contrasena incorrectos.';
                return [$errores, $valores];
            }

            Seguridad::limpiarIntentosLogin($email);
            Seguridad::regenerarSesionLogin();
            $_SESSION['usuario_id'] = (int)$usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = (string)$usuario['nombre'];
            $_SESSION['usuario_rol'] = (string)$usuario['rol'];
            Seguridad::guardarCookie('framefy_nombre', (string)$usuario['nombre']);
            $this->usuarioModelo->actualizarUltimoAcceso((int)$usuario['id_usuario']);
            if (
                (string)$usuario['rol'] !== 'admin'
                && empty($_COOKIE['framefy_preferencias_omitidas'])
                && !$this->usuarioModelo->tienePreferencias((int)$usuario['id_usuario'])
            ) {
                $this->redirigir('preferencias');
            }
            $this->redirigir('catalogo');
        } catch (Throwable $e) {
            error_log('Error login: ' . $e->getMessage());
            $errores['general'] = 'No se pudo iniciar sesion en este momento.';
        }

        return [$errores, $valores];
    }

    private function procesarRegistro(): array
    {
        $errores = [];
        $nombre = Seguridad::limpiarTexto($_POST['nombre'] ?? '', 80);
        $email = mb_strtolower(Seguridad::limpiarTexto($_POST['email'] ?? '', 254));
        $contrasena = (string)($_POST['contrasena'] ?? '');
        $confirmar = (string)($_POST['confirmar_contrasena'] ?? '');
        $valores = ['nombre' => $nombre, 'email' => $email];

        if (!Seguridad::validarNombre($nombre)) {
            $errores['nombre'] = 'Solo letras y espacios, entre 2 y 80 caracteres.';
        }
        if (!Seguridad::validarEmail($email)) {
            $errores['email'] = 'Ingresa un correo electronico valido.';
        } elseif ($this->usuarioModelo->emailExiste($email)) {
            $errores['email'] = 'Ese correo ya esta registrado.';
        }
        if (!Seguridad::validarContrasena($contrasena)) {
            $errores['contrasena'] = 'Minimo 8 caracteres, con mayuscula, minuscula, numero y caracter especial.';
        }
        if ($confirmar !== $contrasena) {
            $errores['confirmar'] = 'Las contrasenas no coinciden.';
        }
        if (!empty($errores)) {
            return [$errores, $valores];
        }

        try {
            $idUsuario = $this->usuarioModelo->crear($nombre, $email, $contrasena);
            Seguridad::regenerarSesionLogin();
            $_SESSION['usuario_id'] = $idUsuario;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_rol'] = 'estandar';
            Seguridad::guardarCookie('framefy_nombre', $nombre);
            $this->redirigir('preferencias');
        } catch (Throwable $e) {
            error_log('Error registro: ' . $e->getMessage());
            $errores['general'] = 'No se pudo crear la cuenta. Intenta nuevamente.';
        }

        return [$errores, $valores];
    }
}
