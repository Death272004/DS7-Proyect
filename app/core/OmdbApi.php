<?php
declare(strict_types=1);

/**
 * OmdbApi.php
 * Servicio para consumir la API de OMDb (Open Movie Database).
 * Documentación: https://www.omdbapi.com/
 *
 * Uso:
 *   $omdb   = new OmdbApi('TU_API_KEY');
 *   $datos  = $omdb->buscarPorTitulo('Interstellar', 2014);
 *   $poster = $omdb->obtenerPoster('Interstellar');
 */
class OmdbApi
{
    private const BASE_URL   = 'https://www.omdbapi.com/';
    private const IMG_URL    = 'https://img.omdbapi.com/';
    private const TIMEOUT    = 5;   // segundos
    private const CACHE_TTL  = 86400; // 24 horas en segundos

    private string $apiKey;
    private string $cacheDir;

    public function __construct(string $apiKey = '', string $cacheDir = '')
    {
        $this->apiKey   = $apiKey ?: (defined('OMDB_API_KEY') ? OMDB_API_KEY : '');
        $this->cacheDir = $cacheDir ?: (defined('APP_BASE_PATH') ? APP_BASE_PATH . '/cache/omdb' : sys_get_temp_dir() . '/omdb_cache');

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Métodos públicos                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Busca una película/serie por título y año opcional.
     * Retorna el array completo de OMDb o null si no se encuentra.
     */
    public function buscarPorTitulo(string $titulo, ?int $anio = null): ?array
    {
        $params = [
            'apikey' => $this->apiKey,
            't'      => $titulo,
            'plot'   => 'short',
        ];
        if ($anio) {
            $params['y'] = $anio;
        }

        return $this->request($params);
    }

    /**
     * Busca por IMDb ID (ej. 'tt1375666').
     */
    public function buscarPorImdbId(string $imdbId): ?array
    {
        $params = [
            'apikey' => $this->apiKey,
            'i'      => $imdbId,
            'plot'   => 'short',
        ];

        return $this->request($params);
    }

    /**
     * Búsqueda de listado por término (retorna hasta 10 resultados).
     */
    public function buscarListado(string $termino, string $tipo = '', int $pagina = 1): array
    {
        $params = [
            'apikey' => $this->apiKey,
            's'      => $termino,
            'page'   => $pagina,
        ];
        if (in_array($tipo, ['movie', 'series', 'episode'], true)) {
            $params['type'] = $tipo;
        }

        $data = $this->request($params);

        if ($data && isset($data['Search']) && is_array($data['Search'])) {
            return $data['Search'];
        }
        return [];
    }

    /**
     * Devuelve la URL del póster de una película dado su título.
     * Retorna null si no se encuentra.
     */
    public function obtenerPoster(string $titulo, ?int $anio = null): ?string
    {
        $datos = $this->buscarPorTitulo($titulo, $anio);
        if ($datos && !empty($datos['Poster']) && $datos['Poster'] !== 'N/A') {
            return $datos['Poster'];
        }
        return null;
    }

    /**
     * Enriquece un array de contenidos del proyecto con datos de OMDb:
     * - imagen_url (póster real)
     * - imdb_id
     * - imdb_rating
     * Útil para mostrar en catálogo/detalle.
     */
    public function enriquecerContenidos(array $contenidos): array
    {
        foreach ($contenidos as &$item) {
            if (!empty($item['imagen_url'])) {
                // Ya tiene imagen, no consultar API
                continue;
            }

            $omdbTipo = $item['tipo'] === 'serie' ? 'series' : 'movie';
            $datos    = $this->buscarPorTituloYTipo($item['titulo'], (int)($item['anio'] ?? 0), $omdbTipo);

            if ($datos) {
                if (!empty($datos['Poster']) && $datos['Poster'] !== 'N/A') {
                    $item['imagen_url'] = $datos['Poster'];
                }
                if (!empty($datos['imdbID'])) {
                    $item['imdb_id'] = $datos['imdbID'];
                }
                if (!empty($datos['imdbRating']) && $datos['imdbRating'] !== 'N/A') {
                    $item['imdb_rating'] = $datos['imdbRating'];
                }
                if (!empty($datos['Genre']) && $datos['Genre'] !== 'N/A') {
                    $item['generos_omdb'] = $datos['Genre'];
                }
                if (!empty($datos['Director']) && $datos['Director'] !== 'N/A') {
                    $item['director'] = $datos['Director'];
                }
                if (!empty($datos['Actors']) && $datos['Actors'] !== 'N/A') {
                    $item['actores'] = $datos['Actors'];
                }
                if (!empty($datos['Country']) && $datos['Country'] !== 'N/A') {
                    $item['pais'] = $datos['Country'];
                }
                if (!empty($datos['Language']) && $datos['Language'] !== 'N/A') {
                    $item['idioma'] = $datos['Language'];
                }
            }
        }
        unset($item);

        return $contenidos;
    }

    /**
     * Busca por título filtrando además por tipo (movie|series).
     */
    public function buscarPorTituloYTipo(string $titulo, int $anio = 0, string $tipo = ''): ?array
    {
        $params = [
            'apikey' => $this->apiKey,
            't'      => $titulo,
            'plot'   => 'short',
        ];
        if ($anio > 0) {
            $params['y'] = $anio;
        }
        if (in_array($tipo, ['movie', 'series'], true)) {
            $params['type'] = $tipo;
        }

        return $this->request($params);
    }

    /* ------------------------------------------------------------------ */
    /*  Métodos privados                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Ejecuta una petición HTTP a OMDb con caché en disco.
     */
    private function request(array $params): ?array
    {
        if (empty($this->apiKey)) {
            return null; // Sin API key no hay consulta
        }

        $url       = self::BASE_URL . '?' . http_build_query($params);
        $cacheFile = $this->cacheDir . '/' . md5($url) . '.json';

        // Devolver desde caché si está vigente
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return $cached;
            }
        }

        // Llamada HTTP
        $ctx = stream_context_create([
            'http' => [
                'timeout' => self::TIMEOUT,
                'header'  => "User-Agent: CineMatch/1.0\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || ($data['Response'] ?? '') === 'False') {
            return null;
        }

        // Guardar en caché
        @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE));

        return $data;
    }
}
