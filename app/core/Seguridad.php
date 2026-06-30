<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

final class Seguridad
{
    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $usaHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string)(APP_SESSION_MINUTOS * 60));

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $usaHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        if (!isset($_SESSION['_creada_en'])) {
            session_regenerate_id(true);
            $_SESSION['_creada_en'] = time();
        }

        if (
            isset($_SESSION['_ultimo_movimiento'])
            && (time() - (int)$_SESSION['_ultimo_movimiento']) > (APP_SESSION_MINUTOS * 60)
        ) {
            self::cerrarSesion();
            session_start();
            $_SESSION['_creada_en'] = time();
        }

        $_SESSION['_ultimo_movimiento'] = time();

        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function cerrarSesion(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, [
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool)($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_destroy();
    }

    public static function regenerarSesionLogin(): void
    {
        session_regenerate_id(true);
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['_ultimo_movimiento'] = time();
    }

    public static function csrfToken(): string
    {
        self::iniciarSesion();
        return (string)$_SESSION['_csrf'];
    }

    public static function csrfInput(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::escapar(self::csrfToken()) . '">';
    }

    public static function validarCsrf(): bool
    {
        self::iniciarSesion();
        $token = $_POST['csrf_token'] ?? '';
        return is_string($token) && hash_equals((string)($_SESSION['_csrf'] ?? ''), $token);
    }

    public static function escapar(mixed $valor): string
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }

    public static function limpiarTexto(mixed $valor, int $maximo = 255): string
    {
        $texto = trim((string)$valor);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';
        return mb_substr($texto, 0, $maximo);
    }

    public static function validarEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 254;
    }

    public static function validarNombre(string $nombre): bool
    {
        return (bool)preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]{2,80}$/u', $nombre);
    }

    public static function validarContrasena(string $contrasena): bool
    {
        return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$#€%\-_]).{8,128}$/u', $contrasena);
    }

    public static function entero(mixed $valor, int $minimo = 0, int $maximo = PHP_INT_MAX): int
    {
        $numero = filter_var($valor, FILTER_VALIDATE_INT);
        if ($numero === false) {
            return $minimo;
        }
        return min($maximo, max($minimo, (int)$numero));
    }

    public static function metodoPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function usuarioId(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
    }

    public static function rolUsuario(): string
    {
        return (string)($_SESSION['usuario_rol'] ?? '');
    }

    public static function estaAutenticado(): bool
    {
        return self::usuarioId() !== null;
    }

    public static function requiereLogin(string $rutaLogin = '../auth/auth.php'): void
    {
        if (!self::estaAutenticado()) {
            self::redirigir($rutaLogin);
        }
    }

    public static function requiereAdmin(string $rutaInicio = '../inicio/inicio.php'): void
    {
        self::requiereLogin('../auth/auth.php');
        if (self::rolUsuario() !== 'admin') {
            self::redirigir($rutaInicio);
        }
    }

    public static function flash(string $clave, ?string $mensaje = null): ?string
    {
        if ($mensaje !== null) {
            $_SESSION['_flash'][$clave] = $mensaje;
            return null;
        }

        $valor = $_SESSION['_flash'][$clave] ?? null;
        unset($_SESSION['_flash'][$clave]);
        return $valor;
    }

    public static function redirigir(string $ruta): void
    {
        header('Location: ' . $ruta);
        exit;
    }
}
