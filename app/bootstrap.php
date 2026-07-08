<?php
declare(strict_types=1);

require_once __DIR__ . '/core/Seguridad.php';
require_once __DIR__ . '/core/Controlador.php';
require_once __DIR__ . '/core/Ruteador.php';
require_once __DIR__ . '/core/WebserviceLocal.php';
require_once __DIR__ . '/modelos/UsuarioModelo.php';
require_once __DIR__ . '/modelos/ContenidoModelo.php';
require_once __DIR__ . '/controladores/InicioController.php';
require_once __DIR__ . '/controladores/AuthController.php';
require_once __DIR__ . '/controladores/CatalogoController.php';
require_once __DIR__ . '/controladores/PerfilController.php';
require_once __DIR__ . '/controladores/PreferenciasController.php';
require_once __DIR__ . '/controladores/AdminController.php';
require_once __DIR__ . '/controladores/ApiController.php';

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

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $proyecto = basename(APP_BASE_PATH);
        $posicion = strpos($scriptDir, '/' . $proyecto);

        if ($posicion !== false) {
            return substr($scriptDir, 0, $posicion + strlen('/' . $proyecto));
        }

        return rtrim($scriptDir, '/');
    }
}

if (!function_exists('app_url')) {
    function app_url(string $ruta = 'inicio', array $parametros = []): string
    {
        $query = http_build_query(array_merge(['ruta' => $ruta], $parametros));
        return app_base_url() . '/index.php' . ($query !== '' ? '?' . $query : '');
    }
}

if (!function_exists('app_asset')) {
    function app_asset(string $ruta): string
    {
        $rutaLimpia = ltrim($ruta, '/');
        $url = app_base_url() . '/public/' . $rutaLimpia;
        $archivo = APP_BASE_PATH . '/public/' . $rutaLimpia;

        if (is_file($archivo)) {
            $url .= '?v=' . filemtime($archivo);
        }

        return $url;
    }
}

if (!function_exists('app_icon')) {
    function app_icon(string $nombre, string $clase = ''): string
    {
        $iconos = [
            'bar-chart' => '<path d="M3 3v18h18"/><path d="M8 17V9"/><path d="M13 17V5"/><path d="M18 17v-6"/>',
            'ban' => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
            'bookmark' => '<path d="M19 21 12 17 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>',
            'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
            'check' => '<path d="M20 6 9 17l-5-5"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
            'clapperboard' => '<path d="M20.2 6 3 11l-.9-2.8a2 2 0 0 1 1.3-2.5L17.6 1.4a2 2 0 0 1 2.5 1.3Z"/><path d="m6.2 5.3 3.1 3.9"/><path d="m12.4 3.4 3.1 4"/><path d="M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
            'compass' => '<circle cx="12" cy="12" r="10"/><path d="m16.2 7.8-2.1 6.3-6.3 2.1 2.1-6.3Z"/>',
            'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off' => '<path d="m2 2 20 20"/><path d="M6.7 6.7C3.8 8.7 2 12 2 12s3.5 7 10 7c1.6 0 3-.4 4.2-1"/><path d="M19.3 17.3C22 15.2 22 12 22 12s-3.5-7-10-7c-1.1 0-2.1.2-3 .5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
            'film' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M17 3v18"/><path d="M3 7h4"/><path d="M3 12h18"/><path d="M3 17h4"/><path d="M17 7h4"/><path d="M17 17h4"/>',
            'flame' => '<path d="M8.5 14.5A4.5 4.5 0 0 0 13 19a4 4 0 0 0 4-4c0-2.5-1.5-4-3-5.5.2 1.7-.8 2.7-2 3.5.2-2.2-1-4.4-3-6.5.5 3-2.5 4.5-2.5 8Z"/><path d="M12 22a7 7 0 0 1-7-7c0-3.8 2.7-6 4-10 4 2 6 5 6 8 .8-.8 1.5-2 1.5-3.5A7 7 0 0 1 19 15a7 7 0 0 1-7 7Z"/>',
            'heart' => '<path d="M19.5 12.6 12 20l-7.5-7.4A5 5 0 1 1 12 6a5 5 0 1 1 7.5 6.6Z"/>',
            'list-plus' => '<path d="M11 12H3"/><path d="M16 6H3"/><path d="M16 18H3"/><path d="M18 9v6"/><path d="M21 12h-6"/>',
            'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-10 6L2 7"/>',
            'moon' => '<path d="M12 3a6 6 0 0 0 9 7.4A9 9 0 1 1 12 3Z"/>',
            'play' => '<circle cx="12" cy="12" r="10"/><path d="m10 8 6 4-6 4Z"/>',
            'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
            'rocket' => '<path d="M4.5 16.5c-1.5 1.3-2 3.8-2 3.8s2.5-.5 3.8-2"/><path d="M9 15 4.5 10.5a12 12 0 0 1 7-7L19 2l-1.5 7.5a12 12 0 0 1-7 7Z"/><path d="M9 15 8 22l4-4"/><path d="M9 15l-7-1 4-4"/><circle cx="14.5" cy="7.5" r="1.5"/>',
            'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
            'shield' => '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3Z"/>',
            'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01"/><path d="M15 9h.01"/>',
            'sparkles' => '<path d="M9.9 4.2 9 2 8.1 4.2 6 5l2.1.8L9 8l.9-2.2L12 5Z"/><path d="M19 8.5 17.8 6 16.5 8.5 14 9.8l2.5 1.2 1.3 2.5L19 11l2.5-1.2Z"/><path d="M14 16.5 12.5 13 11 16.5 7.5 18l3.5 1.5 1.5 3.5 1.5-3.5 3.5-1.5Z"/>',
            'star' => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.3L5.8 21 7 14.2 2 9.3l6.9-1Z"/>',
            'theater' => '<path d="M2 10s2-3 6-3 6 3 6 3-2 6-6 6-6-6-6-6Z"/><path d="M10 10h.01"/><path d="M6 10h.01"/><path d="M6.5 13s1.5 1 3 0"/><path d="M14 7c.7-.2 1.4-.3 2-.3 4 0 6 3 6 3s-2 6-6 6c-.7 0-1.3-.2-1.9-.5"/>',
            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/>',
            'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>',
            'user' => '<path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
            'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        ];

        $contenido = $iconos[$nombre] ?? $iconos['sparkles'];
        $claseSeguro = trim('icono ' . $clase);
        return '<svg class="' . Seguridad::escapar($claseSeguro) . '" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $contenido . '</svg>';
    }
}

if (!function_exists('app_genero_icon')) {
    function app_genero_icon(string $genero, string $clase = ''): string
    {
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT', mb_strtolower($genero));
        $normalizado = preg_replace('/[^a-z0-9]+/', '-', (string)$normalizado) ?? '';
        $normalizado = trim($normalizado, '-');

        $mapa = [
            'accion' => 'flame',
            'animacion' => 'sparkles',
            'aventura' => 'compass',
            'ciencia-ficcion' => 'rocket',
            'sci-fi' => 'rocket',
            'comedia' => 'smile',
            'documental' => 'film',
            'drama' => 'theater',
            'fantasia' => 'sparkles',
            'misterio' => 'search',
            'romance' => 'heart',
            'terror' => 'eye',
            'thriller' => 'shield',
            'suspenso' => 'shield',
        ];

        return app_icon($mapa[$normalizado] ?? 'sparkles', $clase);
    }
}

if (!function_exists('app_youtube_embed_url')) {
    function app_youtube_embed_url(?string $valor): ?string
    {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9_-]{8,20}$/', $valor) === 1) {
            return 'https://www.youtube.com/embed/' . $valor;
        }

        $partes = parse_url($valor);
        $host = mb_strtolower((string)($partes['host'] ?? ''));
        $path = trim((string)($partes['path'] ?? ''), '/');

        if (str_contains($host, 'youtu.be') && $path !== '') {
            $id = explode('/', $path)[0];
            return preg_match('/^[A-Za-z0-9_-]{8,20}$/', $id) === 1
                ? 'https://www.youtube.com/embed/' . $id
                : null;
        }

        if (str_contains($host, 'youtube.com')) {
            if (str_starts_with($path, 'embed/')) {
                $id = explode('/', substr($path, 6))[0];
                return preg_match('/^[A-Za-z0-9_-]{8,20}$/', $id) === 1
                    ? 'https://www.youtube.com/embed/' . $id
                    : null;
            }

            parse_str((string)($partes['query'] ?? ''), $query);
            $id = (string)($query['v'] ?? '');
            return preg_match('/^[A-Za-z0-9_-]{8,20}$/', $id) === 1
                ? 'https://www.youtube.com/embed/' . $id
                : null;
        }

        return null;
    }
}

if (!function_exists('app_trailer_embed')) {
    function app_trailer_embed(string $titulo, ?string $trailerUrl = null): ?string
    {
        $embedPersonalizado = app_youtube_embed_url($trailerUrl);
        if ($embedPersonalizado !== null) {
            return $embedPersonalizado;
        }

        $clave = mb_strtolower(trim($titulo));
        $trailers = [
            'interstellar' => 'zSWdZVtXT7E',
            'breaking bad' => 'HhesaQXLuRY',
            'el senor de los anillos' => 'V75dMMIW2B4',
            'stranger things' => 'b9EkMc79ZSU',
            'the dark knight' => 'EXeTwQWrcwY',
            'friends' => 'hDNNmeeJs1Q',
            'coco' => 'Ga6RYejo6Hk',
            'the social dilemma' => 'uaaC57tcci0',
            'inception' => 'YoHD9XEInc0',
            'the matrix' => 'vKQi3bBA1y8',
            'titanic' => 'kVrqfYjkTdQ',
            'avatar' => '5PSNL1qE6VY',
            'avengers: endgame' => 'TcMBFSGVi1c',
            'jurassic park' => 'lc0UehYemQA',
            'gladiator' => 'P5ieIbInFpg',
            'pulp fiction' => 's7EdQ4FqbhY',
            'forrest gump' => 'bLvqoHBptjg',
            'the godfather' => 'sY1S34973zA',
            'spider-man: into the spider-verse' => 'g4Hbz2jLxvQ',
            'toy story' => 'v-PjgYDrg70',
            'finding nemo' => 'wZdpNglLbt8',
            'parasite' => '5xH0HfJHsaY',
            'la la land' => '0pdqf4P9MB8',
            'joker' => 'zAGVQLHvwOY',
            'game of thrones' => 'KPLWWIOCOOQ',
            'the last of us' => 'uLtkt8BonwM',
            'the crown' => 'JWtnJjn6ng0',
            'the mandalorian' => 'aOC8E8z_ifw',
            'black mirror' => 'jDiYGjp5E6E',
            'the office' => 'LHOtME2DL4g',
            'dark' => 'rrwycJ08PSA',
            'the boys' => 'M1bhOaLV4FU',
            'the witcher' => 'ndl1W4ltcmg',
            'house of the dragon' => 'DotnJ7tTA34',
            'wednesday' => 'Di310WS8zLk',
            "the queen's gambit" => 'CDrieqwSdgI',
        ];

        if (!isset($trailers[$clave])) {
            return null;
        }

        return 'https://www.youtube.com/embed/' . $trailers[$clave];
    }
}

$nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
$rolUsuario = $_SESSION['usuario_rol'] ?? '';
