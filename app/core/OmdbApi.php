<?php
declare(strict_types=1);

class OmdbApi
{
    private const BASE_URL   = 'https://www.omdbapi.com/';
    private const TIMEOUT    = 5;   // segundos
    private const CACHE_TTL  = 86400; // 24 horas en segundos

    private string $apiKey;
    private string $cacheDir;

    public function __construct(string $apiKey = '', string $cacheDir = '')
    {
        $this->apiKey   = $apiKey ?: (defined('OMDB_API_KEY') ? OMDB_API_KEY : '');
        if ($this->apiKey === 'TU_API_KEY_AQUI') {
            $this->apiKey = '';
        }
        $this->cacheDir = $cacheDir ?: (defined('APP_BASE_PATH') ? APP_BASE_PATH . '/cache/omdb' : sys_get_temp_dir() . '/omdb_cache');

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
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
                'header'  => "User-Agent: Framefy/1.0\r\n",
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
